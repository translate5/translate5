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

use MittagQI\Translate5\Task\Current\NoAccessException;
use editor_Controllers_Plugins_LoadCurrentTask;
use editor_Models_Loaders_Taskuserassoc;
use editor_Models_Task;
use editor_Models_TaskUserAssoc;
use ZfExtended_Authentication;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

/**
 * This trait defines a controller to be in task context (so a task is opened) and provides access to it
 * To be used for controllers only!
 */
trait TaskContextTrait
{

    private ?editor_Models_Task $_currentTask = null;
    private ?editor_Models_TaskUserAssoc $_currentJob = null;

    /**
     * This trait is only usable in controllers and in plugins
     * @throws Current\Exception
     */
    protected function _restrictUsage()
    {
        if (!is_a($this, '\Zend_Controller_Action')) {
            throw new Current\Exception('E1234');
        }
    }

    /**
     * Loads the current context task either by the given taskGuid, or if empty by the default way.
     * @param string|null $taskGuid
     * @param bool $loadJob false to not load the current users opened job
     * @throws Current\Exception
     * @throws NoAccessException
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    protected function initCurrentTaskByGuid(?string $taskGuid, bool $loadJob = true)
    {
        if (empty($taskGuid)) {
            $this->initCurrentTask();
            return;
        }
        $this->_restrictUsage();
        /** @var editor_Models_Task $task */
        $this->_currentTask = ZfExtended_Factory::get('editor_Models_Task');
        $this->_currentTask->loadByTaskGuid($taskGuid);
        if ($loadJob) {
            $this->_loadCurrentJob();
        }
    }

    public function isTaskProvided(): bool
    {
        return !is_null(editor_Controllers_Plugins_LoadCurrentTask::getTaskId());
    }

    /**
     * Loads the current context task from task ID given via URL - to be used in either controller init or preDispatch function
     * If this function is used in task editing context, loadJob must always be set to true.
     * @param bool $loadJob
     * @return void
     * @throws Current\Exception
     * @throws NoAccessException
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    protected function initCurrentTask(bool $loadJob = true): void
    {
        $this->_restrictUsage();
        /** @var editor_Models_Task $task */
        $this->_currentTask = ZfExtended_Factory::get('editor_Models_Task');

        $taskId = editor_Controllers_Plugins_LoadCurrentTask::getTaskId();
        if (is_null($taskId)) {
            // Access to CurrentTask was requested but no task ID was given in the URL.
            // if we get here this is a programming error, either the currentTask was used outside a current task context,
            // or the taskId was not provided in the URL
            throw new Current\Exception('E1381');
        }

        //NotFound Exception of the loader should bubble up, so no catch for that here
        $this->_currentTask->load($taskId);

        if ($loadJob) {
            $this->_loadCurrentJob();
        }
    }

    /**
     * Loads the current job of the current user to the current task
     * @throws NoAccessException
     */
    protected function _loadCurrentJob()
    {
        //load job, if there is no job in usage, throw 403
        $userGuid = ZfExtended_Authentication::getInstance()->getUserGuid();

        $this->_currentJob = editor_Models_Loaders_Taskuserassoc::loadFirstInUse($userGuid, $this->_currentTask);

        //TODO if it turns out, that the checking of the jobs to find out if the user is allowed to access the taskid is to error prone,
        // then change to a taskid list of opened tasks in the session
        if (is_null($this->_currentJob)) {
            //ensures that no segments for example can be loaded if task was not opened properly
            throw new NoAccessException(code: 423); //423 code to distinguish in UI and trigger logger there
        }
    }

    /**
     * Returns the currently USED job, null if none used (though jobs may exist for that user and task!)
     * @return editor_Models_TaskUserAssoc|null
     */
    public function getCurrentJob(): ?editor_Models_TaskUserAssoc
    {
        return $this->_currentJob;
    }

    public function getCurrentTask(): editor_Models_Task
    {
        if (is_null($this->_currentTask)) {
            //Access to CurrentTask was requested but it was NOT initialized yet.
            throw new Current\Exception('E1382');
        }
        return $this->_currentTask;
    }

    /**
     * Verifies the given taskGuid if it fits to the task context
     * @param string $taskGuid
     * @throws NoAccessException
     */
    public function validateTaskAccess(string $taskGuid)
    {
        if ($this->_currentTask->getTaskGuid() !== $taskGuid) {
            //if the given to be validated taskGuid is not valid (so the current one), we prevent access
            throw new NoAccessException();
        }
    }
}
