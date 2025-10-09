<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\BatchOperations\BatchSetTaskGuidsProvider;
use MittagQI\Translate5\Task\BatchOperations\DTO\TaskGuidsQueryDto;
use MittagQI\Translate5\Task\BatchOperations\Exception\InvalidDeadlineDateStringProvidedException;
use MittagQI\Translate5\Task\BatchOperations\Exception\InvalidValueProvidedException;
use MittagQI\Translate5\Task\BatchOperations\Exception\InvalidWorkflowProvidedException;
use MittagQI\Translate5\Task\BatchOperations\Exception\InvalidWorkflowStepProvidedException;
use MittagQI\Translate5\Task\BatchOperations\Exception\MaintenanceScheduledException;
use MittagQI\Translate5\Task\BatchOperations\Handler\TaskBatchExport;
use MittagQI\Translate5\Task\BatchOperations\Handler\TaskBatchSetDeadlineDate;
use MittagQI\Translate5\Task\BatchOperations\TaskBatchExportInterface;

/**
 * Controller for Batch Operations
 */
class Editor_TaskbatchController extends ZfExtended_RestController
{
    protected $entityClass = editor_Models_Task::class;

    /**
     * @var editor_Models_Task
     */
    protected $entity;

    /**
     * ignoring ID field for POST Requests
     * @var array
     */
    protected $postBlacklist = ['id'];

    private const TASKS_LIMIT_DEFAULT = 30;

    public function init(): void
    {
        parent::init();

        ZfExtended_UnprocessableEntity::addCodes([
            'E1678' => 'Invalid param value provided',
        ], 'editor.task.batch-set');
    }

    public function indexAction(): void
    {
        $invalidValueProvidedMessage = 'Ungültiger Wert bereitgestellt';

        if ($this->getParam('countTasks')) {
            $request = $this->getRequest();

            foreach (['batchWorkflow', 'batchWorkflowStep'] as $param) {
                if ($this->getParam($param) === null) {
                    throw ZfExtended_UnprocessableEntity::createResponse(
                        'E1678',
                        [
                            $param => [
                                $invalidValueProvidedMessage,
                            ],
                        ],
                    );
                }
            }

            $taskGuids = BatchSetTaskGuidsProvider::create()->getAllowedTaskGuids(
                TaskGuidsQueryDto::fromRequest($request)
            );
            $this->view->total = ! empty($taskGuids) ? UserJobRepository::create()->getJobsCountWithinWorkflowStep(
                $taskGuids,
                $request->getParam('batchWorkflow'),
                $request->getParam('batchWorkflowStep')
            ) : 0;

            return;
        }

        if ($this->getParam('previewTasks')) {
            $this->previewTasks((int) $this->getParam('tasksLimit', self::TASKS_LIMIT_DEFAULT));

            return;
        }

        try {
            $batchType = $this->getParam('batchType');
            switch ($batchType) {
                case 'export':
                    $batchHandler = TaskBatchExport::create();

                    break;
                case 'deadlineDate':
                    $batchHandler = TaskBatchSetDeadlineDate::create();

                    break;
                default:
                    return;
            }

            $batchHandler->process($this->getRequest());

            if ($batchHandler instanceof TaskBatchExportInterface) {
                $this->view->nextUrl = $batchHandler->getUrl();
            }
            $this->view->success = true;
        } catch (MaintenanceScheduledException $e) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E1401',
                [
                    'export' => 'Maintenance is scheduled, exports are not possible at the moment',
                ],
            );
        } catch (InvalidValueProvidedException $e) {
            $param = match ($e::class) {
                InvalidDeadlineDateStringProvidedException::class => 'deadlineDate',
                InvalidWorkflowProvidedException::class => 'batchWorkflow',
                InvalidWorkflowStepProvidedException::class => 'batchWorkflowStep',
                default => null,
            };

            throw ZfExtended_UnprocessableEntity::createResponse(
                'E1678',
                [
                    $param => [
                        $invalidValueProvidedMessage,
                    ],
                ],
            );
        }
    }

    private function previewTasks(int $tasksMax): void
    {
        $this->view->rows = [];
        $this->view->success = true;
        $taskIds = BatchSetTaskGuidsProvider::create()->getAllowedTaskIds(
            TaskGuidsQueryDto::fromRequest($this->getRequest())
        );

        if ($tasksMax < 1) {
            $tasksMax = self::TASKS_LIMIT_DEFAULT;
        }
        if (count($taskIds) > $tasksMax) {
            $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
            $this->view->error = str_replace('{0}', (string) $tasksMax, $translate->_('Es können nur maximal {0} Aufgaben als Batch verarbeitet werden'));
            $this->view->success = false;
        } elseif (count($taskIds) > 0) {
            $taskRepository = TaskRepository::create();
            sort($taskIds, SORT_NUMERIC);
            $tasks = $taskRepository->getTaskListByIds($taskIds);
            foreach ($tasks as $task) {
                $row = new stdClass();
                $row->taskId = $task->getId();
                $row->taskName = $task->getTaskName();
                $row->busy = $task->isLocked($task->getTaskGuid()) || $task->isOperating() || $task->isErroneous();
                $row->checked = ! $row->busy;
                $this->view->rows[] = $row;
            }
        }
        $this->view->total = count($this->view->rows);
    }
}
