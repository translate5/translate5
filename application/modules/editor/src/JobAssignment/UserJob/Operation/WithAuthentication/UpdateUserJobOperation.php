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
use editor_Workflow_Default as DefaultWorkflow;
use MittagQI\Translate5\Acl\Rights;
use MittagQI\Translate5\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinator;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Exception\NotFoundCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\Exception\JobNotFinishableException;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Permission\UserJobActionPermissionAssert;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\UserJobAction;
use MittagQI\Translate5\JobAssignment\UserJob\Contract\UpdateUserJobOperationInterface;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidWorkflowStepProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DTO\UpdateUserJobDto;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\SegmentRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Segment\QualityService;
use MittagQI\Translate5\Task\Exception\TaskHasCriticalQualityErrorsException;
use MittagQI\Translate5\User\Exception\InexistentUserException;
use Zend_Exception;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_ErrorCodeException;

class UpdateUserJobOperation implements UpdateUserJobOperationInterface
{
    public function __construct(
        private readonly ActionPermissionAssertInterface $permissionAssert,
        private readonly UpdateUserJobOperationInterface $operation,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly UserRepository $userRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
        private readonly QualityService $qualityService,
        private readonly SegmentRepository $segmentRepository,
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
            CoordinatorGroupJobRepository::create(),
            new QualityService(),
            SegmentRepository::create(),
        );
    }

    /**
     * @throws ZfExtended_ErrorCodeException
     * @throws PermissionExceptionInterface
     * @throws Zend_Exception
     * @throws InexistentUserException
     * @throws TaskHasCriticalQualityErrorsException
     * @throws FeasibilityExceptionInterface
     */
    public function update(UserJob $job, UpdateUserJobDto $dto): void
    {
        $authUser = $this->userRepository->get($this->authentication->getUserId());
        $context = new PermissionAssertContext($authUser);

        $this->permissionAssert->assertGranted(UserJobAction::Update, $job, $context);

        $this->checkAdditionalAsserts($dto, $job);

        $coordinator = $this->coordinatorRepository->findByUserGuid($this->authentication->getUserGuid());

        if ($coordinator !== null) {
            $this->coordinatorAsserts($coordinator, $job, $dto, $context);
        }

        $this->operation->update($job, $dto);
    }

    /**
     * @throws PermissionExceptionInterface
     */
    private function coordinatorAsserts(
        JobCoordinator $coordinator,
        UserJob $job,
        UpdateUserJobDto $dto,
        PermissionAssertContext $context,
    ): void {
        try {
            $this->coordinatorGroupJobRepository->getByCoordinatorGroupIdTaskGuidAndWorkflow(
                (int) $coordinator->group->getId(),
                $job->getTaskGuid(),
                $dto->workflow->workflow,
                $dto->workflow->workflowStepName,
            );
        } catch (NotFoundCoordinatorGroupJobException) {
            throw new InvalidWorkflowStepProvidedException();
        }

        if (null === $dto->deadlineDate) {
            return;
        }

        $this->permissionAssert->assertGranted(UserJobAction::UpdateDeadline, $job, $context);
    }

    /**
     * @throws JobNotFinishableException
     * TODO convert to proper feasibility assert
     */
    private function checkAdditionalAsserts(UpdateUserJobDto $dto, UserJob $job): void
    {
        $maySkipAutoQa = $this->authentication->isUserAllowed(Rights::ID, Rights::TASK_FINISH_SKIP_AUTOQA);
        $doAutoQaCheck = ($dto->state === DefaultWorkflow::STATE_FINISH) && ! ($dto->skipAutoQaCheck && $maySkipAutoQa);
        if ($doAutoQaCheck && $this->qualityService->taskHasCriticalErrors($job->getTaskGuid(), $job)) {
            throw new JobNotFinishableException('E1750');
        }

        if ($dto->state === DefaultWorkflow::STATE_FINISH
            && $this->segmentRepository->hasDraftsInTask($job->getTaskGuid())) {
            throw new JobNotFinishableException('E1751');
        }
    }
}
