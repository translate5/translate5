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

namespace MittagQI\Translate5\Task;

use editor_Models_Task;
use Zend_Registry;

/**
 *
 */
class Lock
{

    /***
     * Locking task for given stateId.
     * @param editor_Models_Task $task
     * @return bool
     */
    public static function taskLock(editor_Models_Task $task, string $lockId) : bool {
        $log = Zend_Registry::get('logger')->cloneMe('editor.task');

        if(!$task->lock(NOW_ISO, $lockId)) {
            $log->debug('E0000', 'Task lock: task lock failed',[
                'task' => $task,
                'lockId' => $lockId
            ]);
            return false;
        }

        $task->setState($lockId);
        $task->save();
        $log->debug('E0000', 'Task lock: task lock success',[
            'task' => $task,
            'lockId' => $lockId
        ]);
        return true;
    }

    /***
     * Unlock the task if locked and set it to STATE_OPEN
     * @param editor_Models_Task $task
     * @return bool
     */
    public static function taskUnlock(editor_Models_Task $task) : bool {
        $log = Zend_Registry::get('logger')->cloneMe('editor.task');

        if (!$task->unlock()) {
            $log->debug('E0000', 'Task unlock: task unlock failed',[
                'task' => $task,
                'lockId' => $task->getState()
            ]);
            return false;
        }
        $task->setState(editor_Models_Task::STATE_OPEN);
        $task->save();
        $log->debug('E0000', 'Task unlock: task unlock success',[
            'task' => $task
        ]);
        return false;
    }

}