<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

namespace MittagQI\Translate5\Segment;

use editor_Models_LanguageResources_LanguageResource;
use editor_Models_Segment;
use editor_Models_SegmentField;
use editor_Models_Workflow_Step;
use editor_Workflow_Default;
use MittagQI\Translate5\Repository\{SegmentHistoryDataRepository,
    SegmentHistoryRepository
};
use MittagQI\Translate5\Statistics\Helpers\LoggedJobsData;
use Zend_Registry;

class UpdateSegmentStatistics
{
    private bool $enabled;

    public function __construct(
        private readonly SegmentHistoryRepository $history,
        private readonly SegmentHistoryDataRepository $historyData,
        private readonly SegmentHistoryAggregation $aggregator,
    ) {
        $this->enabled = (bool) Zend_Registry::get('config')->resources->db->statistics?->enabled;
    }

    public static function create(): self
    {
        return new self(
            new SegmentHistoryRepository(),
            new SegmentHistoryDataRepository(),
            SegmentHistoryAggregation::create()
        );
    }

    public function updateFor(
        editor_Models_Segment $segment,
        string $workflowName,
        int $workflowStepNo,
        bool $isInteractive = false,
    ): void {
        if (! $this->enabled) {
            return;
        }

        $segmentOriginalValue = $segment->getTarget();
        $workflowStepName = $segment->getWorkflowStep();
        if ($workflowStepName === editor_Workflow_Default::STEP_PM_CHECK) {
            $workflowStepName = $segment->getTask()->getWorkflowStepName();
        }

        $segment->setEditedInStep($workflowStepName);

        if ($segmentOriginalValue === '' && $isInteractive) {
            $segmentOriginalValue = $this->lookupPretranslatedValue(
                (int) $segment->getId(),
                $workflowName,
                $workflowStepName
            );
            if ($segmentOriginalValue === null) {
                return;
            }
        }

        if ($isInteractive) {
            $this->updateLevenshtein($segment, $workflowStepNo, $segmentOriginalValue);

            $filterBy = self::stepWithinWorkflow($workflowStepName) ? 'workflowStep' : 'editedInStep';
            $duration = $segment->getDuration(
                editor_Models_SegmentField::TYPE_TARGET
            ) + $this->historyData->getDurationSumByHistoryIds(
                $this->history->getHistoryIdsForSegment(
                    (int) $segment->getId(),
                    $segment->getUserGuid(),
                    [
                        $filterBy . ' = ?' => $workflowStepName,
                    ]
                )
            );

            error_log((string) $duration);
        } else {
            $duration = 0;
        }

        $this->aggregator->upsert(
            $segment->getTaskGuid(),
            $segment->getUserGuid(),
            $workflowName,
            ! empty($workflowStepName) ? $workflowStepName : '',
            (int) $segment->getId(),
            $duration,
            (int) $segment->getLevenshteinOriginal(),
            (int) $segment->getLevenshteinPrevious(),
            (int) $segment->getMatchRate(),
            $segment->getMatchRateType(),
            self::getLangResId($segment->meta()->getPreTransLangResUuid()),
            (int) $segment->getEditable()
        );
    }

    private function updateLevenshtein(
        editor_Models_Segment $segment,
        int $workflowStepNr,
        string $segmentOriginalValue,
    ): void {
        $segmentCurrentValue = $segment->getTargetEdit();
        $segmentPrevStepValue = null;
        // get last value from prev. workflow step if available

        $filter = [];
        if ($workflowStepNr === 1) {
            if ($segment->getTask()->getWorkflowStepName() === editor_Workflow_Default::STEP_NO_WORKFLOW) {
                $segmentPrevStepValue = $segmentOriginalValue;
            } else {
                $filter = LoggedJobsData::getBeforeWorkflowStartFilter($segment->getTaskGuid(), $segment);
            }
        }

        if ($segmentPrevStepValue === null) {
            if (empty($filter)) {
                $filter = [
                    'workflowStepNr < ?' => $workflowStepNr,
                ];
            }
            $segmentPrevStepValue = $this->getLastHistoryEntry((int) $segment->getId(), $filter);
        }

        if ($segmentPrevStepValue === null) {
            $segmentPrevStepValue = $segmentOriginalValue;
        }

        $segment->setLevenshteinOriginal(
            LevenshteinUTF8::calcDistance($segmentOriginalValue, $segmentCurrentValue)
        );
        $segment->setLevenshteinPrevious(
            LevenshteinUTF8::calcDistance($segmentPrevStepValue, $segmentCurrentValue)
        );
    }

    private static function stepWithinWorkflow(string $step): bool
    {
        return ! in_array(
            $step,
            [editor_Workflow_Default::STEP_NO_WORKFLOW, editor_Workflow_Default::STEP_WORKFLOW_ENDED]
        );
    }

    private function lookupPretranslatedValue(int $segmentId, string $workflowName, string $workflowStepName): ?string
    {
        if (empty($workflowStepName)) { // no aggregation for empty workflow step w/o pretranslation
            return null;
        }

        $stepModel = new editor_Models_Workflow_Step();

        $currentStep = $stepModel->loadFirstByFilter($workflowName, [
            'name' => $workflowStepName,
        ]);

        // no aggregation for translation step w/o pretranslation
        if (isset($currentStep['role']) && $currentStep['role'] === editor_Workflow_Default::ROLE_TRANSLATOR) {
            return null;
        }

        $translatorStep = $stepModel->loadFirstByFilter($workflowName, [
            'position' => 1,
            'role' => editor_Workflow_Default::ROLE_TRANSLATOR,
        ]);
        if (empty($translatorStep)) {
            // error_log('No first translation step for "'.$workflowName.'" workflow');
            return null;
        }

        return $this->getLastHistoryEntry($segmentId, [
            'workflowStep = ?' => $translatorStep['name'],
        ]);
        // error_log('No history entry for segment #' . $segment->getId().' within "'.$translatorStep['name'].'" step ['.$currentStep['name'].']');
    }

    private function getLastHistoryEntry(int $segmentId, array $filter): ?string
    {
        $lastInHistory = $this->history->loadLatestForSegment(
            $segmentId,
            $filter
        );

        if (empty($lastInHistory)) {
            return null;
        }
        $historyDataEntry = $this->historyData->loadByHistoryId((int) $lastInHistory['id'], ['edited']);

        return ! empty($historyDataEntry) ? $historyDataEntry['edited'] : null;
    }

    private static function getLangResId(?string $langResUuid): int
    {
        $langResId = 0;
        if (! empty($langResUuid)) {
            $languageResource = new editor_Models_LanguageResources_LanguageResource();
            $langResInfo = $languageResource->loadByUuid($langResUuid);
            if (! empty($langResInfo)) {
                $langResId = (int) $langResInfo->toArray()['id'];
            }
        }

        return $langResId;
    }
}
