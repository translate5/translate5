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

namespace MittagQI\Translate5\Test\Unit\DefaultJobAssignment\DefaultLspJob\Operation\WithAuthentication;

use editor_Models_Customer_Customer;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\DefaultJobAssignment\Contract\CreateDefaultLspJobOperationInterface;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation\DTO\NewDefaultLspJobDto;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation\WithAuthentication\CreateDefaultLspJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\DTO\TrackChangesRightsDto;
use MittagQI\Translate5\DefaultJobAssignment\DTO\WorkflowDto;
use MittagQI\Translate5\JobAssignment\LspJob\Exception\CoordinatorAttemptedToCreateLspJobForHisLspException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToLspJobException;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Exception\InexistentUserException;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_AuthenticationInterface;
use ZfExtended_NotAuthenticatedException;

class CreateDefaultLspJobOperationTest extends TestCase
{
    private MockObject|CustomerRepository $customerRepository;

    private MockObject|JobCoordinatorRepository $coordinatorRepository;

    private MockObject|UserRepository $userRepository;

    private MockObject|ZfExtended_AuthenticationInterface $authentication;

    private MockObject|ActionPermissionAssertInterface $userPermissionAssert;

    private MockObject|ActionPermissionAssertInterface $customerPermissionAssert;

    private MockObject|CreateDefaultLspJobOperationInterface $createOperation;

    private CreateDefaultLspJobOperation $operation;

    public function setUp(): void
    {
        $this->customerRepository = $this->createMock(CustomerRepository::class);
        $this->coordinatorRepository = $this->createMock(JobCoordinatorRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);
        $this->userPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->customerPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->createOperation = $this->createMock(CreateDefaultLspJobOperationInterface::class);

        $this->operation = new CreateDefaultLspJobOperation(
            $this->customerRepository,
            $this->coordinatorRepository,
            $this->userRepository,
            $this->authentication,
            $this->userPermissionAssert,
            $this->customerPermissionAssert,
            $this->createOperation,
        );
    }

    public function testThrowsNotAuthenticatedException(): void
    {
        $this->userRepository->method('get')->willThrowException(new InexistentUserException('1'));

        $this->expectException(ZfExtended_NotAuthenticatedException::class);

        $this->createOperation->expects(self::never())->method('assignJob');

        $dto = new NewDefaultLspJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            2,
            new WorkflowDto(
                'default',
                'translation',
            ),
            1.0,
            new TrackChangesRightsDto(
                true,
                false,
                true,
            ),
        );

        $this->operation->assignJob($dto);
    }

    public function testNotAllowsToCreateIfUserDontHavePermissionToCustomer(): void
    {
        $actor = $this->createMock(User::class);

        $this->userRepository->method('get')->willReturn($actor);

        $customer = $this->createMock(editor_Models_Customer_Customer::class);

        $this->customerRepository->method('get')->willReturn($customer);

        $this->customerPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->with(CustomerAction::DefaultJob, $customer)
            ->willThrowException(new class() extends \Exception implements PermissionExceptionInterface {
            });

        $this->expectException(PermissionExceptionInterface::class);

        $this->createOperation->expects(self::never())->method('assignJob');

        $dto = new NewDefaultLspJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            2,
            new WorkflowDto(
                'default',
                'translation',
            ),
            1.0,
            new TrackChangesRightsDto(
                true,
                false,
                true,
            ),
        );

        $this->operation->assignJob($dto);
    }

    public function testNotAllowsToUpdateIfActorNotAllowedToReadUser(): void
    {
        $actor = $this->createMock(User::class);

        $this->userRepository->method('get')->willReturn($actor);

        $userToSet = $this->createMock(User::class);

        $this->userRepository->method('getByGuid')->willReturn($userToSet);

        $this->userPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->with(UserAction::Read, $userToSet)
            ->willThrowException(new class() extends \Exception implements PermissionExceptionInterface {
            });

        $this->expectException(PermissionExceptionInterface::class);

        $this->createOperation->expects(self::never())->method('assignJob');

        $dto = new NewDefaultLspJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            2,
            new WorkflowDto(
                'default',
                'translation',
            ),
            1.0,
            new TrackChangesRightsDto(
                true,
                false,
                true,
            ),
        );

        $this->operation->assignJob($dto);
    }

    public function testThrowsExceptionIfProvidedUserIsNotCoordinator(): void
    {
        $actor = $this->createMock(User::class);

        $this->userRepository->method('get')->willReturn($actor);

        $this->coordinatorRepository->method('findByUser')->willReturn(null);

        $this->createOperation->expects(self::never())->method('assignJob');

        $dto = new NewDefaultLspJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            2,
            new WorkflowDto(
                'default',
                'translation',
            ),
            1.0,
            new TrackChangesRightsDto(
                true,
                false,
                true,
            ),
        );

        $this->expectException(OnlyCoordinatorCanBeAssignedToLspJobException::class);

        $this->operation->assignJob($dto);
    }

    public function testCoordinatorNotAllowedToCreateLspJobsForHisLsp(): void
    {
        $actor = $this->createMock(User::class);
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('same')->willReturn(true);
        $actorJc = new JobCoordinator('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $actor, $lsp);

        $this->userRepository->method('get')->willReturn($actor);

        $user = $this->createMock(User::class);
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $jc = new JobCoordinator('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $lsp);

        $this->userRepository->method('getByGuid')->willReturn($user);

        $this->coordinatorRepository->method('findByUser')->willReturnMap([
            [$user, $jc],
            [$actor, $actorJc],
        ]);

        $this->createOperation->expects(self::never())->method('assignJob');

        $dto = new NewDefaultLspJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            2,
            new WorkflowDto(
                'default',
                'translation',
            ),
            1.0,
            new TrackChangesRightsDto(
                true,
                false,
                true,
            ),
        );

        $this->expectException(CoordinatorAttemptedToCreateLspJobForHisLspException::class);

        $this->operation->assignJob($dto);
    }

    public function testAllowsToCreate(): void
    {
        $actor = $this->createMock(User::class);
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('same')->willReturn(false);
        $actorJc = new JobCoordinator('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $actor, $lsp);

        $this->userRepository->method('get')->willReturn($actor);

        $user = $this->createMock(User::class);
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $jc = new JobCoordinator('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $lsp);

        $this->userRepository->method('getByGuid')->willReturn($user);

        $this->coordinatorRepository->method('findByUser')->willReturnMap([
            [$user, $jc],
            [$actor, $actorJc],
        ]);

        $this->createOperation->expects(self::once())->method('assignJob');

        $dto = new NewDefaultLspJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            2,
            new WorkflowDto(
                'default',
                'translation',
            ),
            1.0,
            new TrackChangesRightsDto(
                true,
                false,
                true,
            ),
        );

        $this->operation->assignJob($dto);
    }
}
