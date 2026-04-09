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

namespace MittagQI\Translate5\Statistics\Helpers;

use editor_Models_Segment_AutoStates as SegmentAutoStates;
use editor_Models_SegmentField;
use editor_Workflow_Default;
use MittagQI\Translate5\Segment\Exception\InvalidInputForLevenshtein;
use MittagQI\Translate5\Segment\LevenshteinCalculationService;
use MittagQI\Translate5\Statistics\Dto\LevenshteinHistoryDTO;
use MittagQI\Translate5\Statistics\SegmentLevenshteinRepository;
use Throwable;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use Zend_Exception;
use Zend_Registry;

readonly class LevenshteinCalcTaskHistory
{
    protected function __construct(
        private LevenshteinCalculationService $levenshtein,
        private SegmentLevenshteinRepository $segmentLevenshteinRepository,
        private ?Zend_Db_Adapter_Abstract $db,
    ) {
    }

    public static function create(): self
    {
        return new self(
            LevenshteinCalculationService::create(),
            SegmentLevenshteinRepository::create(),
            Zend_Db_Table::getDefaultAdapter(),
        );
    }

    /**
     * @throws InvalidInputForLevenshtein
     * @throws Throwable
     */
    public function calculate(string $taskGuid): void
    {
        $this->segmentLevenshteinRepository->removeByTaskGuid($taskGuid);

        $segments = $this->db->fetchAll(
            'SELECT
                s.id,
                s.autoStateId,
                s.editedInStep,
                sd.original,
                sd.edited
            FROM LEK_segments s
            INNER JOIN LEK_segment_data sd
                ON sd.segmentId = s.id
                AND sd.name = :targetField
            WHERE s.taskGuid = :taskGuid
            ORDER BY s.id ASC',
            [
                'targetField' => editor_Models_SegmentField::TYPE_TARGET,
                'taskGuid' => $taskGuid,
            ]
        );

        $insertBuffer = [];
        foreach ($segments as $segment) {
            $orderdContent = $this->loadSegmentContent($segment);
            $this->calculateLevenshteinForSegment($orderdContent);
            $insertBuffer = array_merge($insertBuffer, $orderdContent);
            if (count($insertBuffer) > 100) {
                $this->flushInsertBuffer($taskGuid, $insertBuffer);
                $insertBuffer = [];
            }
        }
        $this->flushInsertBuffer($taskGuid, $insertBuffer);
    }

    /**
     * @param LevenshteinHistoryDTO[] $insertBuffer
     */
    private function flushInsertBuffer(string $taskGuid, array $insertBuffer): void
    {
        if (! empty($insertBuffer)) {
            $this->segmentLevenshteinRepository->insertBatch($taskGuid, $insertBuffer);
        }
    }

    /**
     * @param LevenshteinHistoryDTO[] $orderdContent
     * @throws Zend_Exception
     */
    private function calculateLevenshteinForSegment(array $orderdContent): void
    {
        $previousStep = '';
        $previousStepEdited = '';
        //since original is changing only in pretranslation that should be consistent
        // through later steps and can be used for calculation without search the first changed one
        $previousStepOriginal = '';
        $firstEntry = true;
        foreach ($orderdContent as $levenshteinHistoryDTO) {
            //on review tasks we get targetOriginal by import - so calculation against
            // initial empty string in $previousStepOriginal makes no sense
            $setFirstToZero = $firstEntry && strlen($levenshteinHistoryDTO->targetOriginal);

            //pre-translation re-sets the targetOriginal - therefore all levenshtein values are 0 here
            $isPreTranslation = $levenshteinHistoryDTO->autoStateId === SegmentAutoStates::PRETRANSLATED;

            if ($setFirstToZero || $isPreTranslation) {
                $levenshteinHistoryDTO->levenshteinOriginal = 0;
                $levenshteinHistoryDTO->levenshteinPrevious = 0;
                $initLength = $this->levenshtein->calcDistance(
                    $levenshteinHistoryDTO->targetOriginal,
                    $levenshteinHistoryDTO->targetOriginal,
                );
                $levenshteinHistoryDTO->segmentlengthPrevious = $initLength['segmentlengthPrevious'];
            } else {
                //while no real previousStep was set, current original contains the proper value to compare against
                if ($previousStep === '') {
                    $previousStepEdited = $previousStepOriginal = $levenshteinHistoryDTO->targetOriginal;
                }
                $this->calculateLevenshteinInDto($levenshteinHistoryDTO, $previousStepOriginal, $previousStepEdited);
            }

            if ($levenshteinHistoryDTO->editedInStep !== $previousStep) {
                $previousStep = $levenshteinHistoryDTO->editedInStep;
                $previousStepEdited = $levenshteinHistoryDTO->targetEdited;
                $previousStepOriginal = $levenshteinHistoryDTO->targetOriginal;
            }
            $firstEntry = false;
        }
    }

    /**
     * Returns the segment changes from oldest to newest.
     * Works on preloaded assoc-array segment data (no segment model instance needed).
     *
     * @param array{
     *     id:int|string,
     *     autoStateId:int|string|null,
     *     editedInStep:string|null,
     *     original:string|null,
     *     edited:string|null
     * } $segment
     * @return LevenshteinHistoryDTO[]
     */
    private function loadSegmentContent(array $segment): array
    {
        $segmentId = (int) $segment['id'];
        $orderdContent = [
            0 => new LevenshteinHistoryDTO(
                $segmentId,
                null,
                (int) ($segment['autoStateId'] ?? SegmentAutoStates::NOT_TRANSLATED),
                (string) ($segment['editedInStep'] ?? editor_Workflow_Default::STEP_NO_WORKFLOW),
                (string) ($segment['original'] ?? ''),
                (string) ($segment['edited'] ?? ''),
            ),
        ];

        $historyRows = $this->db->fetchAll(
            'SELECT
                h.id AS segmentHistoryId,
                h.autoStateId,
                h.editedInStep,
                hd.original,
                hd.edited
            FROM LEK_segment_history h
            INNER JOIN LEK_segment_history_data hd
                ON hd.segmentHistoryId = h.id
                AND hd.name = :targetField
            WHERE h.segmentId = :segmentId
            ORDER BY h.id DESC',
            [
                'targetField' => editor_Models_SegmentField::TYPE_TARGET,
                'segmentId' => $segmentId,
            ]
        );

        foreach ($historyRows as $historyRow) {
            $orderdContent[(int) $historyRow['segmentHistoryId']] = new LevenshteinHistoryDTO(
                $segmentId,
                (int) $historyRow['segmentHistoryId'],
                (int) ($historyRow['autoStateId'] ?? SegmentAutoStates::NOT_TRANSLATED),
                (string) ($historyRow['editedInStep'] ?? editor_Workflow_Default::STEP_NO_WORKFLOW),
                (string) ($historyRow['original'] ?? ''),
                (string) ($historyRow['edited'] ?? ''),
            );
        }

        return array_reverse($orderdContent);
    }

    /**
     * @throws Zend_Exception
     */
    private function calculateLevenshteinInDto(
        LevenshteinHistoryDTO $levenshteinHistoryDTO,
        string $previousStepOriginal,
        string $previousStepEdited
    ): void {
        try {
            $levenshteinOriginal = $this->levenshtein->calcDistance(
                $previousStepOriginal,
                $levenshteinHistoryDTO->targetEdited
            );
            $levenshteinPrevious = $this->levenshtein->calcDistance(
                $previousStepEdited,
                $levenshteinHistoryDTO->targetEdited
            );
            $levenshteinHistoryDTO->levenshteinOriginal = $levenshteinOriginal['distance'];
            $levenshteinHistoryDTO->levenshteinPrevious = $levenshteinPrevious['distance'];
            $levenshteinHistoryDTO->segmentlengthPrevious = $levenshteinPrevious['segmentlengthPrevious'];
        } catch (InvalidInputForLevenshtein $e) {
            Zend_Registry::get('logger')->exception($e);
            $levenshteinHistoryDTO->levenshteinOriginal = 0;
            $levenshteinHistoryDTO->levenshteinPrevious = 0;
            $levenshteinHistoryDTO->segmentlengthPrevious = 0;
        }
    }
}
