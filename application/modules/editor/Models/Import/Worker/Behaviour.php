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

/**
 * Contains the Import Worker (the scheduling parts)
 * The import process itself is encapsulated in editor_Models_Import_Worker_Import
 */
class editor_Models_Import_Worker_Behaviour extends ZfExtended_Worker_Behaviour_Default
{
    /**
     * @var editor_Models_Task
     */
    protected $task;

    public function __construct()
    {
        //in import worker behaviour isMaintenanceScheduled is by default on and does not start anymore 60 minutes before maintenance
        $this->config['isMaintenanceScheduled'] = 60;
    }

    /**
     * set the taask instance internally
     */
    public function setTask(editor_Models_Task $task)
    {
        $this->task = $task;
    }

    /**
     * @see ZfExtended_Worker_Behaviour_Default::checkParentDefunc()
     */
    public function checkParentDefunc(): bool
    {
        $parentsOk = parent::checkParentDefunc();
        if (! $parentsOk) {
            $this->task->setErroneous();
            $this->defuncRemainingOfGroup();
        }

        return $parentsOk;
    }

    /**
     * defuncing the tasks import worker group
     * (no default behaviour, provided by this class)
     */
    public function defuncRemainingOfGroup()
    {
        //final step must run in any case, so we exclude it here
        $this->workerModel->defuncRemainingOfGroup(['editor_Models_Import_Worker_FinalStep']);
        $this->wakeUpAndStartNextWorkers($this->workerModel);
    }

    /**
     * basicly sets the task to be imported to state error when a fatal error happens after the work method
     */
    public function registerShutdown()
    {
        register_shutdown_function(function ($task, $worker) {
            $error = error_get_last();
            if (! is_null($error) && ($error['type'] & FATAL_ERRORS_TO_HANDLE)) {
                /* @var $task editor_Models_Task */
                if (! is_null($task) && $task->isImporting()) {
                    // should only set to error if task was really on import
                    $task->setErroneous();
                    if (Zend_Registry::isRegistered('logger')) {
                        $logger = Zend_Registry::get('logger');
                        /* @var $logger ZfExtended_Logger */
                        $logger->error('E1027', 'Fatal system error on task usage - check system log!', [
                            'task' => $task,
                        ]);
                    }
                }
                if (! is_null($worker)) {
                    /* @var $worker ZfExtended_Models_Worker */
                    $worker->defuncRemainingOfGroup(['editor_Models_Import_Worker_FinalStep']);
                    $worker->setState($worker::STATE_DEFUNCT);
                    $worker->save();
                }
            }
        }, $this->task, $this->workerModel);
    }
}
