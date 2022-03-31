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

namespace MittagQI\Translate5\Models\Task;

use editor_Controllers_Plugins_LoadCurrentTask;
use editor_Models_Loaders_Taskuserassoc;
use editor_Models_Task;
use editor_User;
use Exception;
use Zend_Db_Table_Row_Abstract;
use ZfExtended_Models_Entity_NotFoundException;

/**
 * The instance of the currently opened task by this request
 */
class CurrentTask extends editor_Models_Task {

    static protected CurrentTask $instance;

    /**
     * returns true if a current task is provided in the URI and if it is accessible
     * @return bool
     */
    public static function isProvided(): bool {
        return !is_null(editor_Controllers_Plugins_LoadCurrentTask::getTaskId());
    }

    public static function getProvidedId(): ?int {
        return editor_Controllers_Plugins_LoadCurrentTask::getTaskId();
    }

    /**
     * Gets the CurrentTask instance, if uses has no access (no opened job on that task) throw NoAccessException
     * @throws Current\Exception|Current\NoAccessException|ZfExtended_Models_Entity_NotFoundException
     */
    public static function getInstance(): self {
        if(empty($instance)) {
            $task = new self;
            $taskId = editor_Controllers_Plugins_LoadCurrentTask::getTaskId();
            if(is_null($taskId)) {
                // Access to CurrentTask was requested but no task ID was given in the URL.
                // if we get here this is a programming error, either the currentTask was used outside a current task context,
                // or the taskId was not provided in the URL
                throw new Current\Exception('E1381');
            }
            //NotFound Exception of the loader should bubble up, so no catch for that here
            $task->_loadInternal($taskId);

            //load job, if there is no job in usage, throw 403
            $userGuid = editor_User::instance()->getGuid();
            $job = editor_Models_Loaders_Taskuserassoc::loadFirstInUse($userGuid, $task);

            //TODO if it turns out, that the checking of the jobs to find out if the user is allowed to access the taskid is to error prone,
            // then change to a taskid list of opened tasks in the session
            if(is_null($job)) {
                throw new Current\NoAccessException();
            }

            $task->row->setReadOnly(true);
            self::$instance = $task;
        }
        return self::$instance;
    }

    /**
     * @throws Exception
     */
    public function load($id)
    {
        throw new Exception("Current Task can not be loaded manually, there may be only the one defined by the URL.");
    }

    /**
     * @param int $id
     * @return Zend_Db_Table_Row_Abstract|null
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    protected function _loadInternal(int $id): ?Zend_Db_Table_Row_Abstract
    {
        return parent::load($id);
    }
}
