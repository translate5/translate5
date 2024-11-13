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

namespace MittagQI\Translate5\LspJob\Operation\WithAuthentication;

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LspJob\Contract\CreateLspJobAssignmentOperationInterface;
use MittagQI\Translate5\LspJob\Exception\CoordinatorAttemptedToCreateLspJobForHisLspException;
use MittagQI\Translate5\LspJob\Model\LspJobAssociation;
use MittagQI\Translate5\LspJob\Operation\DTO\NewLspJobDto;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Task\ActionAssert\Permission\TaskActionPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use MittagQI\Translate5\User\Exception\InexistentUserException;
use MittagQI\Translate5\UserJob\Exception\OnlyCoordinatorCanBeAssignedToLspJobException;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;

class CreateLspJobAssignmentOperation implements CreateLspJobAssignmentOperationInterface
{
    public function __construct(
        private readonly LspJobRepository $lspJobRepository,
        private readonly TaskRepository $taskRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly UserRepository $userRepository,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly ActionPermissionAssertInterface $userPermissionAssert,
        private readonly ActionPermissionAssertInterface $taskPermissionAssert,
        private readonly CreateLspJobAssignmentOperationInterface $operation,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            LspJobRepository::create(),
            new TaskRepository(),
            JobCoordinatorRepository::create(),
            new UserRepository(),
            ZfExtended_Authentication::getInstance(),
            UserActionPermissionAssert::create(),
            TaskActionPermissionAssert::create(),
            \MittagQI\Translate5\LspJob\Operation\CreateLspJobAssignmentOperation::create(),
        );
    }

    public function assignJob(NewLspJobDto $dto): LspJobAssociation
    {
        try {
            $authUser = $this->userRepository->get($this->authentication->getUserId());
        } catch (InexistentUserException) {
            throw new \ZfExtended_NotAuthenticatedException();
        }

        $context = new PermissionAssertContext($authUser);
        $task = $this->taskRepository->getByGuid($dto->taskGuid);
        $user = $this->userRepository->getByGuid($dto->userGuid);

        $this->taskPermissionAssert->assertGranted(Action::Update, $task, $context);
        $this->userPermissionAssert->assertGranted(Action::Read, $user, $context);

        $coordinator = $this->coordinatorRepository->findByUser($user);

        if ($coordinator === null) {
            throw new OnlyCoordinatorCanBeAssignedToLspJobException();
        }

        $authCoordinator = $this->coordinatorRepository->findByUser($authUser);

        if ($authCoordinator?->lsp->same($coordinator->lsp)) {
            throw new CoordinatorAttemptedToCreateLspJobForHisLspException();
        }

        // Coordinator can only assign sub jobs of LSP Job
        if (null !== $authCoordinator && ! $this->lspJobExists((int) $authCoordinator->lsp->getId(), $dto)) {
            throw new \ZfExtended_NotAuthenticatedException();
        }

        return $this->operation->assignJob($dto);
    }

    private function lspJobExists(int $lspId, NewLspJobDto $dto): bool
    {
        return $this->lspJobRepository->hasJobInTaskGuidAndWorkflow(
            $lspId,
            $dto->taskGuid,
            $dto->workflow->workflow,
            $dto->workflow->workflowStepName,
        );
    }
}
