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

namespace MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation\WithAuthentication;

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Contract\CreateCoordinatorGroupJobOperationInterface;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Exception\CoordinatorAttemptedToCreateCoordinatorGroupJobForHisCoordinatorGroupException;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Model\CoordinatorGroupJob;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation\DTO\NewCoordinatorGroupJobDto;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Task\ActionAssert\Permission\TaskActionPermissionAssert;
use MittagQI\Translate5\Task\ActionAssert\TaskAction;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Exception\InexistentUserException;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;

class CreateCoordinatorGroupJobOperation implements CreateCoordinatorGroupJobOperationInterface
{
    public function __construct(
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
        private readonly TaskRepository $taskRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly UserRepository $userRepository,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly ActionPermissionAssertInterface $userPermissionAssert,
        private readonly ActionPermissionAssertInterface $taskPermissionAssert,
        private readonly CreateCoordinatorGroupJobOperationInterface $operation,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            CoordinatorGroupJobRepository::create(),
            TaskRepository::create(),
            JobCoordinatorRepository::create(),
            new UserRepository(),
            ZfExtended_Authentication::getInstance(),
            UserActionPermissionAssert::create(),
            TaskActionPermissionAssert::create(),
            \MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation\CreateCoordinatorGroupJobOperation::create(),
        );
    }

    public function assignJob(NewCoordinatorGroupJobDto $dto): CoordinatorGroupJob
    {
        try {
            $authUser = $this->userRepository->get($this->authentication->getUserId());
        } catch (InexistentUserException) {
            throw new \ZfExtended_NotAuthenticatedException();
        }

        $context = new PermissionAssertContext($authUser);
        $task = $this->taskRepository->getByGuid($dto->taskGuid);
        $user = $this->userRepository->getByGuid($dto->userGuid);

        $this->taskPermissionAssert->assertGranted(TaskAction::AssignJob, $task, $context);
        $this->userPermissionAssert->assertGranted(UserAction::Read, $user, $context);

        $coordinator = $this->coordinatorRepository->findByUser($user);

        if ($coordinator === null) {
            throw new OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException();
        }

        $authCoordinator = $this->coordinatorRepository->findByUser($authUser);

        if ($authCoordinator?->group->same($coordinator->group)) {
            throw new CoordinatorAttemptedToCreateCoordinatorGroupJobForHisCoordinatorGroupException();
        }

        // Coordinator can only assign sub jobs of Coordinator Group Job
        if (null !== $authCoordinator && ! $this->groupJobExists((int) $authCoordinator->group->getId(), $dto)) {
            throw new \ZfExtended_NotAuthenticatedException();
        }

        return $this->operation->assignJob($dto);
    }

    private function groupJobExists(int $groupId, NewCoordinatorGroupJobDto $dto): bool
    {
        return $this->coordinatorGroupJobRepository->hasJobInTaskGuidAndWorkflow(
            $groupId,
            $dto->taskGuid,
            $dto->workflow->workflow,
            $dto->workflow->workflowStepName,
        );
    }
}
