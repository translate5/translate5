<?php

namespace MittagQI\Translate5\UserJob\Operation\Factory;

use editor_Models_Task as Task;
use editor_Utils;
use editor_Workflow_Default as Workflow;
use editor_Workflow_Manager;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Exception\InexistentUserException;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Task\ActionAssert\Permission\TaskActionPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use MittagQI\Translate5\UserJob\Exception\InvalidTypeProvidedException;
use MittagQI\Translate5\UserJob\Exception\TaskGuidNotProvidedException;
use MittagQI\Translate5\UserJob\Exception\UserGuidNotProvidedException;
use MittagQI\Translate5\UserJob\Exception\WorkflowStepNotProvidedException;
use MittagQI\Translate5\UserJob\Operation\DTO\NewUserJobDto;
use MittagQI\Translate5\UserJob\TypeEnum;
use REST_Controller_Request_Http as Request;
use UnexpectedValueException;
use Zend_Registry;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Factory;
use ZfExtended_Logger;
use ZfExtended_NotAuthenticatedException;

class NewUserJobDtoFactory
{
    public function __construct(
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly UserRepository $userRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly TaskRepository $taskRepository,
        private readonly ActionPermissionAssertInterface $userPermissionAssert,
        private readonly ActionPermissionAssertInterface $taskPermissionAssert,
        private readonly ZfExtended_Logger $logger,
        private readonly editor_Workflow_Manager $workflowManager,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            ZfExtended_Authentication::getInstance(),
            new UserRepository(),
            JobCoordinatorRepository::create(),
            new TaskRepository(),
            UserActionPermissionAssert::create(),
            TaskActionPermissionAssert::create(),
            Zend_Registry::get('logger')->cloneMe('userJob.create'),
            ZfExtended_Factory::get(editor_Workflow_Manager::class)
        );
    }

    public function fromRequest(Request $request): NewUserJobDto
    {
        $data = $request->getParam('data');
        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        if (! isset($data['taskGuid'])) {
            throw new TaskGuidNotProvidedException();
        }

        if (! isset($data['userGuid'])) {
            throw new UserGuidNotProvidedException();
        }

        try {
            $authUser = $this->userRepository->get($this->authentication->getUserId());
        } catch (InexistentUserException) {
            throw new ZfExtended_NotAuthenticatedException();
        }

        $context = new PermissionAssertContext($authUser);
        $task = $this->taskRepository->getByGuid($data['taskGuid']);
        $user = $this->userRepository->getByGuid($data['userGuid']);

        $this->taskPermissionAssert->assertGranted(Action::READ, $task, $context);
        $this->userPermissionAssert->assertGranted(Action::READ, $user, $context);

        $workflow = $this->getWorkflow($data['workflow'], $task);

        [$workflowStepName, $role] = $this->getWorkflowStepNameAndRole($data, $workflow);

        $state = $data['state'] ?? $workflow::STATE_WAITING;

        try {
            $type = isset($data['type']) ? TypeEnum::from((int) $data['type']) : TypeEnum::Editor;
        } catch (UnexpectedValueException) {
            throw new InvalidTypeProvidedException();
        }

        $coordinator = $this->coordinatorRepository->findByUser($authUser);

        if (null === $coordinator && TypeEnum::Coordinator === $type) {
            throw new InvalidTypeProvidedException();
        }

        $deadlineDate = $this->getDefaultDeadlineDate($data['deadlineDate'], $workflowStepName, $task);

        return new NewUserJobDto(
            $data['taskGuid'],
            $data['userGuid'],
            $state,
            $role,
            $workflow->getName(),
            $workflowStepName,
            $type,
            $data['segmentRange'] ?? null,
            $data['assignmentDate'] ?? NOW_ISO,
            $deadlineDate,
        );
    }

    private function getWorkflow(?string $workflowName, Task $task): Workflow
    {
        if (! empty($workflowName)) {
            return $this->workflowManager->getCached($workflowName);
        }

        return $this->workflowManager->getActiveByTask($task);
    }

    /**
     * @return array{string, string}
     */
    private function getWorkflowStepNameAndRole(array $data, Workflow $workflow): array
    {
        $role = $data['role'] ?? null;
        $workflowStepName = $data['workflowStepName'] ?? null;

        if (null === $workflowStepName && null === $role) {
            throw new WorkflowStepNotProvidedException();
        }

        if (null !== $workflowStepName) {
            return [$workflowStepName, $workflow->getRoleOfStep($workflowStepName)];
        }

        if ($role === 'lector') {
            $role = Workflow::ROLE_REVIEWER;

            $this->logger->warn('E1232', 'Job creation: role "lector" is deprecated, use "reviewer" instead!');
        }

        //we have to get the step from the role (the first found step to the role)
        $steps = $workflow->getSteps2Roles();
        $roles = array_flip(array_reverse($steps));
        $workflowStepName = $roles[$data['role']] ?? null;

        $this->logger->warn(
            'E1232',
            'Job creation: using role as parameter on job creation is deprecated, use workflowStepName instead'
        );

        return [$workflowStepName, $role];
    }

    /**
     * Get the default deadline date from the config.
     * How many work days the deadline date will be from the task order date can be defined in the system configuration.
     * To use the defaultDeadline date, the deadlineDate field should be set to "default"
     */
    protected function getDefaultDeadlineDate(?string $deadlineDate, string $workflowStepName, Task $task): ?string
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
}
