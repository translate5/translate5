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

namespace MittagQI\Translate5\Task\BatchOperations\Handler;

use DateTime;
use Exception;
use MittagQI\Translate5\JobAssignment\UserJob\BatchUpdate\UserJobDeadlineBatchUpdater;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\BatchOperations\BatchSetTaskGuidsProvider;
use MittagQI\Translate5\Task\BatchOperations\DTO\TaskGuidsQueryDto;
use MittagQI\Translate5\Task\BatchOperations\Exception\InvalidDeadlineDateStringProvidedException;
use MittagQI\Translate5\Task\BatchOperations\Exception\InvalidWorkflowProvidedException;
use MittagQI\Translate5\Task\BatchOperations\Exception\InvalidWorkflowStepProvidedException;
use MittagQI\Translate5\Task\BatchOperations\TaskBatchHandlerInterface;
use REST_Controller_Request_Http as Request;
use Zend_Registry;
use ZfExtended_Logger;

class TaskBatchSetDeadlineDate implements TaskBatchHandlerInterface
{
    public function __construct(
        private readonly ZfExtended_Logger $logger,
        private readonly UserJobRepository $userJobRepository,
        private readonly UserJobDeadlineBatchUpdater $userJobDeadlineBatchUpdater,
        private readonly BatchSetTaskGuidsProvider $taskGuidsProvider,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Registry::get('logger')->cloneMe('task.batchSet.deadlineDate'),
            UserJobRepository::create(),
            UserJobDeadlineBatchUpdater::create(),
            BatchSetTaskGuidsProvider::create(),
        );
    }

    public function supports(string $batchType): bool
    {
        return 'deadlineDate' === $batchType;
    }

    /**
     * @throws InvalidDeadlineDateStringProvidedException
     * @throws InvalidWorkflowProvidedException
     * @throws InvalidWorkflowStepProvidedException
     */
    public function process(Request $request): ?string
    {
        $deadlineDate = $request->getParam('deadlineDate');
        $workflow = $request->getParam('batchWorkflow');
        $workflowStep = $request->getParam('batchWorkflowStep');

        if (empty($deadlineDate)) {
            throw new InvalidDeadlineDateStringProvidedException();
        }

        if (empty($workflow)) {
            throw new InvalidWorkflowProvidedException();
        }

        if (empty($workflowStep)) {
            throw new InvalidWorkflowStepProvidedException();
        }

        try {
            $deadlineDate = (new DateTime($deadlineDate))->format('Y-m-d H:i:s');
        } catch (Exception) {
            throw new InvalidDeadlineDateStringProvidedException();
        }

        foreach ($this->taskGuidsProvider->getAllowedTaskGuids(TaskGuidsQueryDto::fromRequest($request)) as $taskGuid) {
            $jobIds = $this->userJobRepository->getAllJobIdsInTaskWithWorkflow($taskGuid, $workflow, $workflowStep);

            $this->userJobDeadlineBatchUpdater->updateDeadlines($jobIds, $deadlineDate);

            $this->logger->info(
                'E1012',
                'Deadline date for user jobs in task {task} updated to {deadlineDate}',
                [
                    'jobs' => $jobIds,
                    'task' => $taskGuid,
                    'deadlineDate' => $deadlineDate,
                ]
            );
        }

        return null;
    }
}
