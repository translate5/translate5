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

namespace MittagQI\Translate5\Test\Unit\DefaultJobAssignment\DefaultCoordinatorGroupJob\Operation;

use Exception;
use MittagQI\Translate5\CoordinatorGroup\Exception\CustomerDoesNotBelongToCoordinatorGroupException;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinator;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\CoordinatorGroup\Validation\CoordinatorGroupCustomerAssociationValidator;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Exception\NotCoordinatorGroupCustomerException;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Operation\CreateDefaultCoordinatorGroupJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Operation\DTO\NewDefaultCoordinatorGroupJobDto;
use MittagQI\Translate5\DefaultJobAssignment\DTO\TrackChangesRightsDto;
use MittagQI\Translate5\DefaultJobAssignment\DTO\WorkflowDto;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException;
use MittagQI\Translate5\Repository\DefaultCoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateDefaultCoordinatorGroupJobOperationTest extends TestCase
{
    private MockObject|DefaultCoordinatorGroupJobRepository $defaultCoordinatorGroupJobRepository;

    private MockObject|DefaultUserJobRepository $defaultUserJobRepository;

    private MockObject|JobCoordinatorRepository $coordinatorRepository;

    private MockObject|CoordinatorGroupCustomerAssociationValidator $coordinatorGroupCustomerAssociationValidator;

    private CreateDefaultCoordinatorGroupJobOperation $operation;

    public function setUp(): void
    {
        $this->defaultCoordinatorGroupJobRepository = $this->createMock(DefaultCoordinatorGroupJobRepository::class);
        $this->defaultUserJobRepository = $this->createMock(DefaultUserJobRepository::class);
        $this->coordinatorRepository = $this->createMock(JobCoordinatorRepository::class);
        $this->coordinatorGroupCustomerAssociationValidator = $this->createMock(CoordinatorGroupCustomerAssociationValidator::class);

        $this->operation = new CreateDefaultCoordinatorGroupJobOperation(
            $this->defaultCoordinatorGroupJobRepository,
            $this->defaultUserJobRepository,
            $this->coordinatorRepository,
            $this->coordinatorGroupCustomerAssociationValidator,
        );
    }

    public function testThrowsExceptionIfUserIsNotCoordinator(): void
    {
        $this->coordinatorRepository->method('findByUserGuid')->willReturn(null);

        $this->expectException(OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException::class);

        $dto = new NewDefaultCoordinatorGroupJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            2,
            new WorkflowDto(
                'default',
                'translation',
            ),
            null,
            new TrackChangesRightsDto(
                false,
                false,
                false,
            ),
        );

        $this->operation->assignJob($dto);
    }

    public function testThrowsExceptionIfCustomerDoesNotBelongToCoordinatorGroup(): void
    {
        $user = $this->createMock(User::class);
        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('__call')->willReturnMap([
            ['getId', [], '1'],
        ]);
        $jc = new JobCoordinator('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $group);

        $dto = new NewDefaultCoordinatorGroupJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            2,
            new WorkflowDto(
                'default',
                'translation',
            ),
            null,
            new TrackChangesRightsDto(
                false,
                false,
                false,
            ),
        );

        $this->coordinatorRepository->method('findByUserGuid')->willReturn($jc);

        $this->coordinatorGroupCustomerAssociationValidator
            ->method('assertCustomersAreSubsetForCoordinatorGroup')
            ->willThrowException(new CustomerDoesNotBelongToCoordinatorGroupException($dto->customerId, (int) $group->getId()));

        $this->expectException(NotCoordinatorGroupCustomerException::class);

        $this->operation->assignJob($dto);
    }

    public function testDeletesUserJobOnCoordinatorGroupJobSaveError(): void
    {
        $user = $this->createMock(User::class);
        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('__call')->willReturnMap([
            ['getId', [], '1'],
        ]);
        $jc = new JobCoordinator('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $group);

        $dto = new NewDefaultCoordinatorGroupJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            2,
            new WorkflowDto(
                'default',
                'translation',
            ),
            null,
            new TrackChangesRightsDto(
                false,
                false,
                false,
            ),
        );

        $this->coordinatorRepository->method('findByUserGuid')->willReturn($jc);

        $exception = new Exception();

        $this->defaultCoordinatorGroupJobRepository->method('save')->willThrowException($exception);

        $this->defaultUserJobRepository->expects(self::once())->method('delete');

        $this->expectExceptionObject($exception);

        $this->operation->assignJob($dto);
    }

    public function testAssign(): void
    {
        $user = $this->createMock(User::class);
        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('__call')->willReturnMap([
            ['getId', [], '1'],
        ]);
        $jc = new JobCoordinator('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $group);

        $dto = new NewDefaultCoordinatorGroupJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            2,
            new WorkflowDto(
                'default',
                'translation',
            ),
            null,
            new TrackChangesRightsDto(
                false,
                false,
                false,
            ),
        );

        $this->coordinatorRepository->method('findByUserGuid')->willReturn($jc);

        $job = $this->operation->assignJob($dto);

        self::assertSame($dto->customerId, $job->getCustomerId());
        self::assertSame($dto->workflow->workflow, $job->getWorkflow());
        self::assertSame($dto->workflow->workflowStepName, $job->getWorkflowStepName());
        self::assertSame($dto->targetLanguageId, $job->getTargetLang());
        self::assertSame($dto->sourceLanguageId, $job->getSourceLang());
        self::assertSame((int) $group->getId(), (int) $job->getGroupId());
    }
}
