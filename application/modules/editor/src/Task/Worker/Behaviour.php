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

namespace MittagQI\Translate5\Task\Worker;

use editor_Models_Task;
use Zend_Db_Exception;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Logger;
use ZfExtended_Models_Db_Exceptions_DeadLockHandler;
use ZfExtended_Models_Worker;
use ZfExtended_Worker_Behaviour_Default;

/**
 * Contains the Import Worker (the scheduling parts)
 * The import process itself is encapsulated in editor_Models_Import_Worker_Import
 */
class Behaviour extends ZfExtended_Worker_Behaviour_Default
{
    protected editor_Models_Task $task;

    public function __construct()
    {
        //in import worker behaviour isMaintenanceScheduled is by default on
        // and does not start anymore 60 minutes before maintenance
        $this->config['isMaintenanceScheduled'] = 60;
    }

    /**
     * set the taask instance internally
     */
    public function setTask(editor_Models_Task $task): void
    {
        $this->task = $task;
    }

    /**
     * @throws Zend_Db_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Db_Exceptions_DeadLockHandler
     * @see ZfExtended_Worker_Behaviour_Default::checkParentDefunc()
     */
    public function checkParentDefunc(): bool
    {
        $parentsOk = parent::checkParentDefunc();
        if (! $parentsOk) {
            if ($this->task->isImporting()) {
                $this->task->setErroneous();
            }
            $this->defuncRemainingOfGroup();
        }

        return $parentsOk;
    }

    /**
     * defuncing the tasks import worker group
     * (no default behaviour, provided by this class)
     * @throws Zend_Db_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Db_Exceptions_DeadLockHandler
     */
    public function defuncRemainingOfGroup(): void
    {
        //final step must run in any case, so we exclude it here
        $this->workerModel->defuncRemainingOfGroup(['editor_Models_Import_Worker_FinalStep']);
        $this->wakeUpAndStartNextWorkers();
    }

    /**
     * basicly sets the task to be imported to state error when a fatal error happens after the work method
     * @throws Zend_Exception
     */
    public function registerShutdown(): void
    {
        register_shutdown_function(function (editor_Models_Task $task = null, ZfExtended_Models_Worker $worker = null) {
            $error = error_get_last();
            if (is_null($error) || ! ($error['type'] & FATAL_ERRORS_TO_HANDLE)) {
                return;
            }
            if (! is_null($task)) {
                // should only set to error if task was really on import
                if ($task->isImporting()) {
                    $task->setErroneous();
                }
                if (Zend_Registry::isRegistered('logger')) {
                    try {
                        $logger = Zend_Registry::get('logger');
                        /* @var ZfExtended_Logger $logger */
                        $logger->error('E1027', 'Fatal system error on task usage - check system log!', [
                            'task' => $task,
                        ]);
                    } catch (Zend_Exception $e) {
                        error_log((string) $e);
                    }
                }
            }
            if (! is_null($worker)) {
                $worker->defuncRemainingOfGroup(['editor_Models_Import_Worker_FinalStep']);
                $worker->setState($worker::STATE_DEFUNCT);
                $worker->save();
            }
        }, $this->task, $this->workerModel);
    }
}
