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

use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\BatchSet\BatchSetTaskGuidsProvider;
use MittagQI\Translate5\Task\BatchSet\DTO\TaskGuidsQueryDto;
use MittagQI\Translate5\Task\BatchSet\Exception\InvalidDeadlineDateStringProvidedException;
use MittagQI\Translate5\Task\BatchSet\Exception\InvalidValueProvidedException;
use MittagQI\Translate5\Task\BatchSet\Exception\InvalidWorkflowProvidedException;
use MittagQI\Translate5\Task\BatchSet\Exception\InvalidWorkflowStepProvidedException;
use MittagQI\Translate5\Task\BatchSet\TaskBatchSetter;

/**
 * Controller for Batch Updates
 */
class Editor_BatchsetController extends ZfExtended_RestController
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

        try {
            TaskBatchSetter::create()->process($this->getRequest());
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
}
