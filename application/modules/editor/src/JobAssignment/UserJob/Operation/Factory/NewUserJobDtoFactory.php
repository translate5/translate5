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

namespace MittagQI\Translate5\JobAssignment\UserJob\Operation\Factory;

use editor_Models_Task as Task;
use editor_Utils;
use editor_Workflow_Default as Workflow;
use editor_Workflow_Manager;
use MittagQI\Translate5\JobAssignment\DTO\TrackChangesRightsDto;
use MittagQI\Translate5\JobAssignment\Exception\InvalidTypeProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidStateProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\TaskIdentificatorNotProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\UserGuidNotProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\WorkflowStepNotProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DTO\NewUserJobDto;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\Translate5\JobAssignment\UserJob\Validation\SegmentRangeValidator;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Task\Exception\InexistentTaskException;
use MittagQI\Translate5\User\Exception\InexistentUserException;
use REST_Controller_Request_Http as Request;
use UnexpectedValueException;

class NewUserJobDtoFactory extends AbstractUserJobDtoFactory
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TaskRepository $taskRepository,
        editor_Workflow_Manager $workflowManager,
        SegmentRangeValidator $segmentRangeValidator,
    ) {
        parent::__construct($workflowManager, $segmentRangeValidator);
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            new UserRepository(),
            TaskRepository::create(),
            new editor_Workflow_Manager(),
            SegmentRangeValidator::create(),
        );
    }

    /**
     * @throws InexistentTaskException
     * @throws InexistentUserException
     * @throws InvalidStateProvidedException
     * @throws InvalidTypeProvidedException
     * @throws WorkflowStepNotProvidedException
     */
    public function fromRequest(Request $request): NewUserJobDto
    {
        $data = $request->getParam('data');
        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        if (! isset($data['userGuid'])) {
            throw new UserGuidNotProvidedException();
        }

        $task = $this->resolveTask($request);
        $user = $this->userRepository->getByGuid($data['userGuid']);

        $workflowDto = $this->getWorkflowDto($data, $task);

        $defaultState = Workflow::STATE_WAITING;

        if ($task->isCompetitive()) {
            $defaultState = Workflow::STATE_UNCONFIRMED;
        }

        $state = $data['state'] ?: $defaultState;

        try {
            $type = isset($data['type']) ? TypeEnum::from((int) $data['type']) : TypeEnum::Editor;
        } catch (UnexpectedValueException) {
            throw new InvalidTypeProvidedException();
        }

        $deadlineDate = $this->getDeadlineDate($data['deadlineDate'] ?? null, $workflowDto->workflowStepName, $task);

        $trackChangesRights = $this->getTrackChangesRightsDto($data);

        $segmentRanges = $this->getSegmentRanges(
            $data['segmentrange'] ?? null,
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

    private function getTrackChangesRightsDto(array $data): ?TrackChangesRightsDto
    {
        if (
            ! isset($data['trackchangesShow'])
            && ! isset($data['trackchangesShowAll'])
            && ! isset($data['trackchangesAcceptReject'])
        ) {
            return null;
        }

        return new TrackChangesRightsDto(
            (bool) ($data['trackchangesShow'] ?? false),
            (bool) ($data['trackchangesShowAll'] ?? false),
            (bool) ($data['trackchangesAcceptReject'] ?? false),
        );
    }

    private function resolveTask(Request $request): Task
    {
        $taskId = $request->getParam('taskId');
        $data = $request->getParam('data');
        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        if (! isset($data['taskGuid']) && null === $taskId) {
            throw new TaskIdentificatorNotProvidedException();
        }

        $taskIdentifier = $taskId ?: $data['taskGuid'];

        $task = $this->taskRepository->find((int) $taskIdentifier);

        if (null !== $task) {
            return $task;
        }

        return $this->taskRepository->getByGuid((string) $taskIdentifier);
    }
}
