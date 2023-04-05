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

namespace MittagQI\Translate5\LanguageResource\Pretranslation;

use editor_Models_Task;
use editor_Task_Operation;
use MittagQI\Translate5\PauseWorker\PauseWorker;
use MittagQI\Translate5\LanguageResource\TaskPivotAssociation;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Authentication;
use ZfExtended_EventManager;
use ZfExtended_Factory;
use ZfExtended_Models_User;
use ZfExtended_Models_Worker;

/**
 * This will queue pivot pre-translation and batch(if needed) worker
 */
class PivotQueuer
{
    /**
     * @var ZfExtended_EventManager
     */
    protected ZfExtended_EventManager $events;

    public function __construct()
    {
        $this->events = ZfExtended_Factory::get(
            ZfExtended_EventManager::class,
            ['MittagQI\Translate5\LanguageResource\Pretranslation\PivotQueuerPivotQueuer']
        );
    }

    /***
     * Queue the match analysis worker
     *
     * @param string $taskGuid
     *
     * @throws Zend_Exception
     */
    public function queuePivotWorker(string $taskGuid): void
    {
        $pivotAssoc = ZfExtended_Factory::get(TaskPivotAssociation::class);
        $assoc = $pivotAssoc->loadTaskAssociated($taskGuid);

        if (empty($assoc)) {
            return;
        }

        $task = ZfExtended_Factory::get(editor_Models_Task::class);
        $task->loadByTaskGuid($taskGuid);

        if ($task->isImporting()) {
            //on import, we use the import worker as parentId
            $parentWorkerId = $this->fetchImportWorkerId($task->getTaskGuid());
        } else {
            // crucial: add a different behaviour for the workers when performing an operation
            $workerParameters['workerBehaviour'] = 'ZfExtended_Worker_Behaviour_Default';
            // this creates the operation start/finish workers
            $parentWorkerId = editor_Task_Operation::create(
                editor_Task_Operation::PIVOT_PRE_TRANSLATION,
                $task
            );
        }

        $user = ZfExtended_Authentication::getInstance()->getUser();

        $workerParameters['userGuid'] = $user?->getUserGuid() ?? ZfExtended_Models_User::SYSTEM_GUID;
        $workerParameters['userName'] = $user?->getUserName() ?? ZfExtended_Models_User::SYSTEM_LOGIN;


        // enable batch query via config
        $workerParameters['batchQuery'] = $this->batchQueryEnabled();
        if ($workerParameters['batchQuery']) {

            // trigger event before the pivot pre-translation worker is queued
            $this->events->trigger(
                'beforePivotPreTranslationQueue',
                'MittagQI\Translate5\LanguageResource\Pretranslation\PivotQueuerPivotQueuer',
                [
                    'task' => $task,
                    'pivotAssociations' => $assoc,
                    'parentWorkerId' => $parentWorkerId
                ]
            );
        }

        /** @var PivotWorker $pivotWorker */
        $pivotWorker = ZfExtended_Factory::get(PivotWorker::class);

        if (!$pivotWorker->init($taskGuid, $workerParameters)) {
            $this->addWarn($task, 'Pivot pre-translation Error on worker init(). Worker could not be initialized');

            return;
        }

        $worker = ZfExtended_Factory::get(PausePivotWorker::class);
        $worker->init($task->getTaskGuid(), [PauseWorker::PROCESSOR => PausePivotProcessor::class]);
        $worker->queue($parentWorkerId);

        $pivotWorker->queue($parentWorkerId, null, false);
    }

    /**
     * @param string $taskGuid
     *
     * @return null|int
     */
    private function fetchImportWorkerId(string $taskGuid): ?int
    {
        $parent = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $parent ZfExtended_Models_Worker */
        $result = $parent->loadByState(
            ZfExtended_Models_Worker::STATE_PREPARE,
            'editor_Models_Import_Worker',
            $taskGuid
        );

        if (count($result) > 0) {
            return (int)$result[0]['id'];
        }

        return 0;
    }

    /***
     * Log analysis warning
     *
     * @param editor_Models_Task $task
     * @param string $message
     * @param array $extra
     *
     * @throws Zend_Exception
     */
    protected function addWarn(editor_Models_Task $task, string $message, array $extra = []): void
    {
        $extra['task'] = $task;
        $logger = Zend_Registry::get('logger')->cloneMe('plugin.matchanalysis');
        $logger->warn('E1100', $message, $extra);
    }

    private function batchQueryEnabled(): bool
    {
        return (bool)Zend_Registry::get('config')->runtimeOptions->LanguageResources->Pretranslation->enableBatchQuery;
    }
}
