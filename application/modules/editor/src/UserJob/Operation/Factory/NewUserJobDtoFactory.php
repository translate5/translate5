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
use editor_Utils;
use editor_Workflow_Default as Workflow;
use editor_Workflow_Manager;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\Exception;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\UserJob\Exception\InvalidStateProvidedException;
use MittagQI\Translate5\UserJob\Exception\InvalidTypeProvidedException;
use MittagQI\Translate5\UserJob\Exception\TaskIdentificatorNotProvidedException;
use MittagQI\Translate5\UserJob\Exception\UserGuidNotProvidedException;
use MittagQI\Translate5\UserJob\Exception\WorkflowStepNotProvidedException;
use MittagQI\Translate5\UserJob\Operation\DTO\NewUserJobDto;
use MittagQI\Translate5\UserJob\Operation\DTO\TrackChangesRightsDto;
use MittagQI\Translate5\UserJob\TypeEnum;
use MittagQI\Translate5\UserJob\Validation\SegmentRangeValidator;
use REST_Controller_Request_Http as Request;
use UnexpectedValueException;
use Zend_Registry;
use ZfExtended_Logger;
use ZfExtended_NotAuthenticatedException;

class NewUserJobDtoFactory extends UserJobDtoFactory
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly TaskRepository $taskRepository,
        ZfExtended_Logger $logger,
        editor_Workflow_Manager $workflowManager,
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
            new UserRepository(),
            JobCoordinatorRepository::create(),
            new TaskRepository(),
            Zend_Registry::get('logger')->cloneMe('userJob.create'),
            new editor_Workflow_Manager(),
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
     * @throws PermissionExceptionInterface
     * @throws WorkflowStepNotProvidedException
     * @throws ZfExtended_NotAuthenticatedException
     */
    public function fromRequest(Request $request): NewUserJobDto
    {
        $data = $request->getParam('data');
        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        $taskId = $request->getParam('taskId');

        if (! isset($data['taskGuid']) && null === $taskId) {
            throw new TaskIdentificatorNotProvidedException();
        }

        if (!isset($data['userGuid'])) {
            throw new UserGuidNotProvidedException();
        }

        $task = null !== $taskId
            ? $this->taskRepository->get((int) $taskId)
            : $this->taskRepository->getByGuid($data['taskGuid']);
        $user = $this->userRepository->getByGuid($data['userGuid']);

        $workflowDto = $this->getWorkflowDto($data, $task);

        $state = $data['state'] ?? Workflow::STATE_WAITING;

        try {
            $type = isset($data['type']) ? TypeEnum::from((int) $data['type']) : TypeEnum::Editor;
        } catch (UnexpectedValueException) {
            throw new InvalidTypeProvidedException();
        }

        $coordinator = $this->coordinatorRepository->findByUser($user);

        if (null === $coordinator && TypeEnum::Coordinator === $type) {
            throw new InvalidTypeProvidedException();
        }

        $deadlineDate = $this->getDeadlineDate($data['deadlineDate'], $workflowDto->workflowStepName, $task);

        $trackChangesRights = $this->getTrackChangesRightsDto($data);

        $segmentRanges = $this->getSegmentRanges(
            $data['segmentrange'],
            $task->getTaskGuid(),
            $user->getUserGuid(),
            $workflowDto->workflowStepName
        );

        return new NewUserJobDto(
            $task->getTaskGuid(),
            $user->getUserGuid(),
            $state,
            $workflowDto,
            $type,
            $segmentRanges,
            $data['assignmentDate'] ?? NOW_ISO,
            $deadlineDate,
            $trackChangesRights,
        );
    }

    /**
     * Get deadline date from the config.
     * How many work days the deadline date will be from the task order date can be defined in the system configuration.
     * To use the defaultDeadline date, the deadlineDate field should be set to "default"
     */
    private function getDeadlineDate(?string $deadlineDate, string $workflowStepName, Task $task): ?string
    {
        //check if default deadline date should be set
        //To set the defaultDeadline date via the api, the deadlineDate field should be set to "default"
        if (empty($deadlineDate) || $deadlineDate !== "default") {
            return $deadlineDate;
        }

        //check if the order date is set. With empty order data, no deadline date from config is possible
        if (empty($task->getOrderdate())) {
            return $deadlineDate;
        }

        //get the config for the task workflow and the user assoc role workflow step
        $configValue = $task->getConfig()
            ->runtimeOptions
            ->workflow
            ?->{$task->getWorkflow()}
            ?->{$workflowStepName}
            ?->defaultDeadlineDate ?? 0;

        if ($configValue <= 0) {
            return $deadlineDate;
        }

        return editor_Utils::addBusinessDays($task->getOrderdate(), $configValue);
    }

    private function getTrackChangesRightsDto(mixed $data): TrackChangesRightsDto
    {
        return new TrackChangesRightsDto(
            $data['trackchangesShow'] ?? false,
            $data['trackchangesShowAll'] ?? false,
            $data['trackchangesAcceptReject'] ?? false,
        );
    }
}