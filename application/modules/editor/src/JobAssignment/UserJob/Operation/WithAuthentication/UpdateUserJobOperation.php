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

namespace MittagQI\Translate5\JobAssignment\UserJob\Operation\WithAuthentication;

use editor_Models_TaskUserAssoc as UserJob;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\JobAssignment\LspJob\Exception\NotFoundLspJobException;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Permission\UserJobActionPermissionAssert;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\UserJobAction;
use MittagQI\Translate5\JobAssignment\UserJob\Contract\UpdateUserJobOperationInterface;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidWorkflowStepProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DTO\UpdateUserJobDto;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\UserRepository;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;

class UpdateUserJobOperation implements UpdateUserJobOperationInterface
{
    public function __construct(
        private readonly ActionPermissionAssertInterface $permissionAssert,
        private readonly UpdateUserJobOperationInterface $operation,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly UserRepository $userRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly LspJobRepository $lspJobRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            UserJobActionPermissionAssert::create(),
            \MittagQI\Translate5\JobAssignment\UserJob\Operation\UpdateUserJobOperation::create(),
            ZfExtended_Authentication::getInstance(),
            new UserRepository(),
            JobCoordinatorRepository::create(),
            LspJobRepository::create(),
        );
    }

    public function update(UserJob $job, UpdateUserJobDto $dto): void
    {
        $authUser = $this->userRepository->get($this->authentication->getUserId());
        $context = new PermissionAssertContext($authUser);

        $this->permissionAssert->assertGranted(UserJobAction::Update, $job, $context);

        $coordinator = $this->coordinatorRepository->findByUserGuid($this->authentication->getUserGuid());

        if ($coordinator !== null) {
            $this->coordinatorAsserts($coordinator, $job, $dto, $context);
        }

        $this->operation->update($job, $dto);
    }

    private function coordinatorAsserts(
        JobCoordinator $coordinator,
        UserJob $job,
        UpdateUserJobDto $dto,
        PermissionAssertContext $context,
    ): void {
        try {
            $this->lspJobRepository->getByLspIdTaskGuidAndWorkflow(
                (int) $coordinator->lsp->getId(),
                $job->getTaskGuid(),
                $dto->workflow->workflow,
                $dto->workflow->workflowStepName,
            );
        } catch (NotFoundLspJobException) {
            throw new InvalidWorkflowStepProvidedException();
        }

        if (null === $dto->deadlineDate) {
            return;
        }

        $this->permissionAssert->assertGranted(UserJobAction::UpdateDeadline, $job, $context);
    }
}
