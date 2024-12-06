<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Segment\BatchOperations;

use editor_Models_Segment as Segment;
use editor_Models_Segment_AutoStates as AutoStates;
use editor_Models_Segment_Exception;
use editor_Models_Segment_InternalTag as InternalTag;
use editor_Models_Segment_Iterator as SegmentIterator;
use editor_Models_Segment_MatchRateType;
use editor_Models_Segment_Meta as SegmentMeta;
use editor_Models_Task as Task;
use editor_Models_TaskProgress as TaskProgress;
use Zend_Db_Statement_Exception;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint as IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey as IntegrityDuplicateKey;

/**
 * Applies the edit full match flag to the task segments
 */
class ApplyEditFullMatchOperation
{
    public function __construct(
        private readonly AutoStates $autoState,
        private readonly InternalTag $internalTag,
        private readonly SegmentMeta $segmentMeta,
        private readonly TaskProgress $taskProgress,
    ) {
    }

    /**
     * Update the $edit100PercentMatch flag for all segments in the task.
     * See https://confluence.translate5.net/x/BoC5DQ
     * for auto status change matrix
     * @throws editor_Models_Segment_Exception
     */
    public function updateSegmentsEdit100PercentMatch(
        Task $task,
        SegmentIterator $segments,
        bool $edit100PercentMatch
    ): void {
        foreach ($segments as $segment) {
            //we can ignore segments where the editable state is already as the desired $edit100PercentMatch state
            // or where the matchrate is lower as 100% since such segments should always be editable
            // and no locked change is needed
            if ($this->autoState->isBlocked((int) $segment->getAutoStateId())
                || $segment->getEditable() == $edit100PercentMatch
                || $segment->getMatchRate() < 100) {
                continue;
            }

            $history = $segment->getNewHistoryEntity();
            $autoStateId = $this->calculateAutoStateId($segment, $task, $edit100PercentMatch);

            if (! is_null($autoStateId)) {
                $segment->setAutoStateId($autoStateId);
                $segment->setEditable($autoStateId != $this->autoState::LOCKED);
                $history->save();
                $segment->save();
            }
        }

        try {
            $this->taskProgress->updateSegmentEditableCount($task);
        } catch (Zend_Db_Statement_Exception | IntegrityConstraint | IntegrityDuplicateKey) {
            // we just du nothing here, since update progress is called frequently
        }

        //update task word count when 100% matches editable is changed
        $task->setWordCount($this->segmentMeta->getWordCountSum($task));
    }

    private function calculateAutoStateId(Segment $segment, Task $task, bool $edit100PercentMatch): ?int
    {
        $autoStateId = null;

        //is locked config has precendence over all other calculations!
        $isLocked = $segment->meta()->getLocked() && $task->getLockLocked();

        //if we want editable 100% matches, the segment should be not editable before,
        // which is checked in the foreach head
        if ($edit100PercentMatch) {
            $hasText = $this->internalTag->hasText($segment->getSource());

            // calc and change new autoState only if it is not hard locked and hasText
            if (! $isLocked && $hasText) {
                $autoStateId = $this->autoState->recalculateUnLockedState($segment);
            }
        } else {
            //all other pretrans values mean that it was either modified (PRETRANS_TRANSLATED)
            // or it was not pre-translated at all so it could not be a 100% match
            $initialPretrans = $segment->getPretrans() == $segment::PRETRANS_INITIAL;

            $wasFromTMOrTermCollection = editor_Models_Segment_MatchRateType::isFromTM($segment->getMatchRateType())
                || editor_Models_Segment_MatchRateType::isFromTermCollection($segment->getMatchRateType());

            //if we do NOT want editable 100% matches, the segment should be editable before,
            // which is checked outside and not explicitly unlocked with autopropagation:
            $allowToBlock = (! $segment->meta()->getAutopropagated() || $isLocked);
            if ($allowToBlock && $initialPretrans && $wasFromTMOrTermCollection) {
                //if segment.pretrans = 1 and matchrate >= 100% (checked in head) and matchtype ^= import;tm
                // then
                // TRANSLATED → LOCKED
                // REVIEWED_UNTOUCHED → LOCKED
                // REVIEWED_UNCHANGED → LOCKED
                // REVIEWED_UNCHANGED_AUTO → LOCKED
                // REVIEWED_PM_UNCHANGED → LOCKED
                // REVIEWED_PM_UNCHANGED_AUTO → LOCKED
                // PRETRANSLATED → LOCKED
                $autoStateId = $this->autoState->recalculateLockedState($segment);
            }
        }

        return $autoStateId;
    }
}
