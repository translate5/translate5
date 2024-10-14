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

namespace MittagQI\Translate5\UserJob\Operation\Factory;

use editor_Models_Task as Task;
use editor_Workflow_Default as Workflow;
use editor_Workflow_Manager;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\Exception;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\UserJob\Exception\InvalidStateProvidedException;
use MittagQI\Translate5\UserJob\Exception\InvalidTypeProvidedException;
use MittagQI\Translate5\UserJob\Exception\WorkflowStepNotProvidedException;
use MittagQI\Translate5\UserJob\Operation\DTO\UpdateUserJobDto;
use MittagQI\Translate5\UserJob\Validation\SegmentRangeValidator;
use REST_Controller_Request_Http as Request;
use Zend_Registry;
use ZfExtended_Logger;
use ZfExtended_NotAuthenticatedException;

class UpdateUserJobDtoFactory extends UserJobDtoFactory
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly editor_Workflow_Manager $workflowManager,
        ZfExtended_Logger $logger,
        SegmentRangeValidator $segmentRangeValidator,
    ) {
        parent::__construct($logger, $workflowManager, $segmentRangeValidator);
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            new TaskRepository(),
            UserJobRepository::create(),
            new editor_Workflow_Manager(),
            Zend_Registry::get('logger')->cloneMe('userJob.update'),
            SegmentRangeValidator::create(),
        );
    }

    /**
     * @param Request $request
     *
     * @throws Exception\InexistentTaskException
     * @throws Exception\InexistentUserException
     * @throws InvalidStateProvidedException
     * @throws InvalidTypeProvidedException
     * @throws ZfExtended_NotAuthenticatedException
     * @throws PermissionExceptionInterface
     */
    public function fromRequest(Request $request): UpdateUserJobDto
    {
        $data = $request->getParam('data');
        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        $job = $this->userJobRepository->get((int) $request->getParam('id'));
        $task = $this->taskRepository->getByGuid($job->getTaskGuid());

        try {
            $workflowDto = $this->getWorkflowDto($data, $task);
        } catch (WorkflowStepNotProvidedException) {
            $workflowDto = null;
        }

        $state = $data['state'] ?? null;

        $deadlineDate = $data['deadlineDate'] ?? null;

        $segmentRanges = $this->getSegmentRanges(
            $data['segmentrange'] ?? null,
            $job->getTaskGuid(),
            $job->getUserGuid(),
            $workflowDto->workflowStepName
        );

        return new UpdateUserJobDto(
            $state,
            $workflowDto,
            $segmentRanges,
            $deadlineDate,
            $data['trackchangesShow'] ?? false,
            $data['trackchangesShowAll'] ?? false,
            $data['trackchangesAcceptReject'] ?? false,
        );
    }

    protected function getWorkflow(Task $task, ?string $workflowName = null): Workflow
    {
        return $this->workflowManager->getActiveByTask($task);
    }
}