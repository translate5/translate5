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

namespace MittagQI\Translate5\Statistics;

use editor_Models_LanguageResources_LanguageResource;
use editor_Models_Segment as Segment;
use editor_Models_SegmentField;
use editor_Models_Task as Task;
use MittagQI\Translate5\Repository\{SegmentHistoryDataRepository,
    SegmentHistoryRepository
};
use MittagQI\Translate5\Segment\Exception\InvalidInputForLevenshtein;
use MittagQI\Translate5\Segment\LevenshteinCalculationService;
use MittagQI\Translate5\Segment\SegmentHistoryAggregation;
use MittagQI\Translate5\Statistics\Dto\SegmentLevenshteinDTO;
use MittagQI\Translate5\Statistics\Dto\StatisticSegmentDTO;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Models_Entity_NotFoundException;

class UpdateSegmentService
{
    /**
     * Internal caches to reduce DB load on instance wide re-aggregation
     */
    private static array $langResUuidtoIdMap = [];

    private bool $enabled;

    /**
     * @throws Zend_Exception
     */
    public function __construct(
        private readonly SegmentHistoryRepository $history,
        private readonly SegmentHistoryDataRepository $historyData,
        private readonly SegmentHistoryAggregation $aggregator,
        private readonly LevenshteinCalculationService $levenshtein,
        private readonly SegmentLevenshteinRepository $segmentLevenshteinRepository,
    ) {
        $this->enabled = (bool) Zend_Registry::get('config')->resources->db->statistics?->enabled;
    }

    /**
     * @throws Zend_Exception
     */
    public static function create(): self
    {
        return new self(
            SegmentHistoryRepository::create(),
            new SegmentHistoryDataRepository(),
            SegmentHistoryAggregation::create(),
            LevenshteinCalculationService::create(),
            SegmentLevenshteinRepository::create(),
        );
    }

    public function updateEditable(Task $task, Segment $segment): void
    {
        if (! $this->enabled) {
            return;
        }

        $qualityScore = $segment->getQualityScore();
        $segmentStatistics = $this->segmentLevenshteinRepository->getCurrentBySegmentId((int) $segment->getId());
        $levenshteinOriginal = $segmentStatistics?->levenshteinOriginal ?? 0;
        $levenshteinPrevious = $segmentStatistics?->levenshteinPrevious ?? 0;
        $segmentlengthPrevious = $segmentStatistics?->segmentlengthPrevious ?? 0;

        $this->aggregator->updateOrInsertEditable(new StatisticSegmentDTO(
            $segment->getTaskGuid(),
            // when just changing the editable flag still the last editor is the responsible person
            $segment->getUserGuid(),
            $task->getWorkflow(),
            //CRUCIAL: when locked/editable is changed, no other segment values are changed, so the editedInStep of
            // the segment is not changed and probably of a previous workflow step. But for the statistics we
            // are now in the step of the task - and for that step the editable must be reflected
            $task->getWorkflowStepName(),
            (int) $segment->getId(),
            $levenshteinOriginal,
            $levenshteinPrevious,
            (int) $segment->getMatchRate(),
            $segment->getMatchRateType(),
            $this->getLangResId($segment->meta()->getPreTransLangResUuid()),
            (int) $segment->getEditable(),
            ($qualityScore !== null && $qualityScore !== '') ? (int) $qualityScore : null,
            0,
            $segmentlengthPrevious,
        ));
    }

    /**
     * @throws InvalidInputForLevenshtein
     */
    public function updateFor(
        Segment $segment,
        string $workflowName,
    ): void {
        if (! $this->enabled) {
            return;
        }

        $levenshteinData = $this->updateLevenshtein($segment);
        $this->segmentLevenshteinRepository->upsert(new SegmentLevenshteinDTO(
            $segment->getTaskGuid(),
            (int) $segment->getId(),
            0,
            $levenshteinData['levenshteinOriginal'],
            $levenshteinData['levenshteinPrevious'],
            $levenshteinData['segmentlengthPrevious'],
        ));
        $this->aggregator->increaseOrInsertPosteditingDuration(
            $segment->getTaskGuid(),
            (int) $segment->getId(),
            $segment->getEditedInStep(),
            $segment->getUserGuid(),
            $segment->getDuration(editor_Models_SegmentField::TYPE_TARGET),
        );

        $qualityScore = $segment->getQualityScore();
        $this->aggregator->resetLastEdit($segment->getTaskGuid(), (int) $segment->getId());
        $this->aggregator->upsert(new StatisticSegmentDTO(
            $segment->getTaskGuid(),
            $segment->getUserGuid(),
            $workflowName,
            $segment->getEditedInStep(),
            (int) $segment->getId(),
            $levenshteinData['levenshteinOriginal'],
            $levenshteinData['levenshteinPrevious'],
            (int) $segment->getMatchRate(),
            $segment->getMatchRateType(),
            $this->getLangResId($segment->meta()->getPreTransLangResUuid()),
            (int) $segment->getEditable(),
            ($qualityScore !== null && $qualityScore !== '') ? (int) $qualityScore : null,
            1, //updated one is by definition the latestEntry
            $levenshteinData['segmentlengthPrevious'],
        ));
    }

    /**
     * @throws InvalidInputForLevenshtein
     * @return array{levenshteinOriginal: int, levenshteinPrevious: int, segmentlengthPrevious: int}
     */
    private function updateLevenshtein(
        Segment $segment,
    ): array {
        $previousStepTargetEdited = $this->getLastHistoryEntry(
            (int) $segment->getId(),
            [
                'editedInStep != ?' => $segment->getEditedInStep(),
            ]
        );

        //if no previous different step found, we calculated against targetOriginal
        if ($previousStepTargetEdited === null) {
            $previousStepTargetEdited = $segment->getTarget();
        }

        $levenshteinOriginal = $this->levenshtein->calcDistance($segment->getTarget(), $segment->getTargetEdit());
        $levenshteinPrevious = $this->levenshtein->calcDistance($previousStepTargetEdited, $segment->getTargetEdit());

        return [
            'levenshteinOriginal' => $levenshteinOriginal['distance'],
            'levenshteinPrevious' => $levenshteinPrevious['distance'],
            'segmentlengthPrevious' => $levenshteinPrevious['segmentlengthPrevious'],
        ];
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

        return empty($historyDataEntry) ? null : $historyDataEntry['edited'];
    }

    private function getLangResId(?string $langResUuid): int
    {
        $langResId = 0;
        if (! empty($langResUuid)) {
            if (array_key_exists($langResUuid, self::$langResUuidtoIdMap)) {
                return self::$langResUuidtoIdMap[$langResUuid];
            }
            $languageResource = new editor_Models_LanguageResources_LanguageResource();

            try {
                $langResInfo = $languageResource->loadByUuid($langResUuid);
                if (! empty($langResInfo)) {
                    $langResId = (int) $langResInfo->toArray()['id'];
                }
            } catch (ZfExtended_Models_Entity_NotFoundException) {
                // do nothing
            }
        }

        self::$langResUuidtoIdMap[$langResUuid] = $langResId;

        return $langResId;
    }
}
