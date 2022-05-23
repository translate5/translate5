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
use editor_Models_Segment;
use editor_Models_Segment_AutoStates;
use editor_Models_Segment_Exception;
use ZfExtended_Factory;

/**
 * Controller Helper for lock and unlock segments
 */
class Locking
{
    /**
     * sets the segment lock, checks if possible, sets history and so on accordingly
     * @param editor_Models_Segment $segment
     * @param bool $lock
     * @throws editor_Models_Segment_Exception
     */
    public function toggleLock(editor_Models_Segment $segment, bool $lock) {
        $history = $segment->getNewHistoryEntity();
        $task = editor_ModelInstances::taskByGuid($segment->getTaskGuid());

        /* @var editor_Models_Segment_AutoStates $autoState */
        $autoState = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');

        //if a segment is locked and lockLocked is set, the editable flag may not be changed
        $isBlocked = $autoState->isBlocked($segment->getAutoStateId());
        $isLockLocked = $segment->meta()->getLocked() && (bool)$task->getLockLocked();
        $isAlreadyOnValue = $lock === !$segment->getEditable();
        // same if the value is already as expected
        if($isBlocked || $isLockLocked || $isAlreadyOnValue) {
            return;
        }
        $segment->setEditable(! $lock);

        if($lock) {
            //since we check for the BLOCKED state above, we can savely set the new autoStateId to LOCKED here (BLOCKED is immutable!)
            $autoStateId = $autoState::LOCKED;
        }
        else {
            $autoStateId = $autoState->recalculateUnLockedState($segment);
        }

        $segment->setAutoStateId($autoStateId);

        $history->save();
        $segment->save();
    }
}