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

use editor_Controllers_Plugins_LoadCurrentTask;
use editor_Models_Loaders_Taskuserassoc;
use editor_Models_Task;
use editor_Models_TaskUserAssoc;
use MittagQI\Translate5\Task\Current\NoAccessException;
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
     * @throws \ZfExtended_Exception
     */
    protected function _restrictUsage()
    {
        if (! is_a($this, \Zend_Controller_Action::class)) {
            throw new \ZfExtended_Exception('Using TaskContextTrait outside a controller in ' . get_class($this));
        }
    }

    /**
     * Loads the current context task by it's id
     * @param bool $loadJob true to load the current users opened job
     * @throws NoJobFoundException
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \ReflectionException
     * @throws \ZfExtended_Exception
     */
    protected function initCurrentTaskById(int $taskId, bool $loadJob = false): void
    {
        $this->_restrictUsage();
        if (empty($taskId)) {
            $this->initCurrentTask();

            return;
        }
        $this->_currentTask = ZfExtended_Factory::get(editor_Models_Task::class);
        $this->_currentTask->load($taskId);
        if ($loadJob) {
            $this->loadCurrentJob();
        }
    }

    /**
     * Loads the current context task either by the given taskGuid, or if empty by the default way.
     * @param bool $loadJob false to not load the current users opened job
     * @throws Current\Exception
     * @throws NoAccessException
     * @throws NoJobFoundException
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \ReflectionException
     * @throws \ZfExtended_Exception
     */
    protected function initCurrentTaskByGuid(?string $taskGuid, bool $loadJob = true): void
    {
        $this->_restrictUsage();
        if (empty($taskGuid)) {
            $this->initCurrentTask();

            return;
        }
        $this->_currentTask = ZfExtended_Factory::get(editor_Models_Task::class);
        $this->_currentTask->loadByTaskGuid($taskGuid);
        if ($loadJob) {
            $this->loadCurrentJob();
        }
    }

    public function isTaskProvided(): bool
    {
        return ! is_null(editor_Controllers_Plugins_LoadCurrentTask::getTaskId());
    }

    /**
     * Loads the current context task from task ID given via URL - to be used in either controller init or preDispatch function
     * If this function is used in task editing context, loadJob must always be set to true.
     * @throws Current\Exception
     * @throws NoJobFoundException
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \ReflectionException
     * @throws \ZfExtended_Exception
     */
    protected function initCurrentTask(bool $loadJob = true): void
    {
        $this->_restrictUsage();
        $this->_currentTask = ZfExtended_Factory::get(editor_Models_Task::class);

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
            $this->loadCurrentJob();
        }
    }

    /**
     * Loads the current job of the current user to the current task
     * @throws NoJobFoundException
     */
    protected function loadCurrentJob(): void
    {
        //load job, if there is no job in usage, throw 403
        $userGuid = ZfExtended_Authentication::getInstance()->getUserGuid();
        $debug = true;
        $this->_currentJob = editor_Models_Loaders_Taskuserassoc::loadFirstInUse($userGuid, $this->_currentTask, $debug);

        if (is_null($this->_currentJob)) {
            $exception = new NoJobFoundException('E1600');
            $this->log ??= ZfExtended_Factory::get('editor_Logger_Workflow', [$this->_currentTask, $debug]);
            $this->log->exception($exception);

            throw $exception;
        }
    }

    /**
     * Returns the currently USED job, null if none used (though jobs may exist for that user and task!)
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
     * @throws NoAccessException
     */
    public function validateTaskAccess(string $taskGuid)
    {
        if ($this->_currentTask->getTaskGuid() !== $taskGuid) {
            //if the given to be validated taskGuid is not valid (so the current one), we prevent access
            throw new NoAccessException();
        }
    }

    /**
     * Recalculate task progress and assign results into view
     *
     * @throws Current\Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \ReflectionException
     * @throws \Zend_Db_Statement_Exception
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function appendTaskProgress(?editor_Models_Task $task = null): void
    {
        // Get taskProgress model instance
        $progressM = ZfExtended_Factory::get(\editor_Models_TaskProgress::class);

        // If $task arg is not given - pick from getCurrentTask() call
        $task ??= $this->getCurrentTask();

        // Refresh progress and assign into view
        $this->view->assign(
            $progressM->refreshProgress($task, ZfExtended_Authentication::getInstance()->getUserGuid())
        );
    }
}
