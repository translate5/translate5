<?php
/*
 START LICENSE AND COPYRIGHT

  This file is part of translate5

  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Segment;

use editor_ModelInstances;
use editor_Models_Segment as Segment;
use editor_Models_Segment_AutoStates;
use editor_Models_Segment_Exception;
use editor_Models_SegmentUserAssoc;
use MittagQI\Translate5\Statistics\UpdateSegmentService;
use ReflectionException;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use ZfExtended_Authentication;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_ValidateException;

/**
 * Controller Helper for segment controller (batch) operations, like bookmarking, lock and unlock segments
 */
readonly class Operations
{
    public function __construct(
        private UpdateSegmentService $updateSegmentStatisticsService,
    ) {
    }

    /**
     * @throws Zend_Exception
     */
    public static function create(): self
    {
        return new self(UpdateSegmentService::create());
    }

    /**
     * sets the segment lock, checks if possible, sets history and so on accordingly
     * @throws ReflectionException
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_Segment_Exception
     */
    public function toggleLockOperation(string $taskGuid, Segment $segment, bool $lock): void
    {
        $this->internalToggleLockOperation($taskGuid, $segment, $lock);
    }

    /**
     * @throws editor_Models_Segment_Exception
     * @throws ReflectionException
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    protected function internalToggleLockOperation(string $taskGuid, Segment $segment, bool $lock): void
    {
        $history = $segment->getNewHistoryEntity();
        $task = editor_ModelInstances::taskByGuid($taskGuid);

        /* @var editor_Models_Segment_AutoStates $autoState */
        $autoState = ZfExtended_Factory::get(editor_Models_Segment_AutoStates::class);

        //if a segment is locked and lockLocked is set, the editable flag may not be changed
        $isBlocked = $autoState->isBlocked((int) $segment->getAutoStateId());
        $isLockLocked = $segment->meta()->getLocked() && (bool) $task->getLockLocked();
        $isAlreadyOnValue = $lock === ! $segment->getEditable();
        // same if the value is already as expected
        if ($isBlocked || $isLockLocked || $isAlreadyOnValue) {
            return;
        }
        $segment->setEditable(! $lock);

        if ($lock) {
            //since we check for the BLOCKED state above, we can savely set the
            // new autoStateId to LOCKED here (BLOCKED is immutable!)
            $autoStateId = $autoState::LOCKED;
        } else {
            $autoStateId = $autoState->recalculateUnLockedState($segment);
        }

        $segment->setAutoStateId($autoStateId);

        $history->save();
        $segment->save();

        $this->updateSegmentStatisticsService->updateEditable($task, $segment);
    }

    /**
     * Toggles segment lock on the filtered segment operator
     * @throws ReflectionException
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function toggleLockBatch(string $taskGuid, Segment $segment, bool $lock): void
    {
        $this->iterateOverFilteredList($taskGuid, $segment, function (Segment $segment) use ($lock, $taskGuid) {
            try {
                $this->internalToggleLockOperation($taskGuid, $segment, $lock);
            } catch (editor_Models_Segment_Exception) {
                // we just ignore that exception on batch processing
            }
        });
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_ValidateException
     * @throws ReflectionException
     */
    public function toggleBookmarkBatch(string $taskGuid, Segment $segment, bool $bookmark): void
    {
        $assoc = ZfExtended_Factory::get(editor_Models_SegmentUserAssoc::class);

        $this->iterateOverFilteredList($taskGuid, $segment, function (Segment $segment) use ($assoc, $bookmark) {
            $userGuid = ZfExtended_Authentication::getInstance()->getUserGuid();
            if ($bookmark) {
                $assoc->createAndSave($segment->getTaskGuid(), (int) $segment->getId(), $userGuid);
            } else {
                $assoc->directDelete((int) $segment->getId(), $userGuid);
            }
        });
    }

    /**
     * calls the callback per each segment in the filtered iterator (applying the filters from outside)
     * @throws ReflectionException
     */
    protected function iterateOverFilteredList(string $taskGuid, Segment $segment, callable $callback): void
    {
        /* @var FilteredIterator $segments */
        $segments = ZfExtended_Factory::get(FilteredIterator::class, [
            $taskGuid, // implies the validTaskAccess check by loading only such segments
            $segment,
        ]);
        foreach ($segments as $oneSegment) {
            $callback($oneSegment);
        }
    }
}
