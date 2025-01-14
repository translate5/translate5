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

namespace MittagQI\Translate5\Test\Unit\Customer\ActionAssert\Permission;

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinator;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroupCustomer;
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\Customer\ActionAssert\Permission\CoordinatorAccessAssert;
use MittagQI\Translate5\Customer\Exception\NoAccessToCustomerException;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupRepositoryInterface;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CoordinatorAccessAssertTest extends TestCase
{
    private JobCoordinatorRepository|MockObject $coordinatorRepository;

    private CoordinatorGroupRepositoryInterface|MockObject $coordinatorGroupRepository;

    private CoordinatorAccessAssert $assert;

    public function setUp(): void
    {
        $this->coordinatorRepository = $this->createMock(JobCoordinatorRepository::class);
        $this->coordinatorGroupRepository = $this->createMock(CoordinatorGroupRepositoryInterface::class);

        $this->assert = new CoordinatorAccessAssert(
            $this->coordinatorRepository,
            $this->coordinatorGroupRepository,
        );
    }

    public function provideSupports(): iterable
    {
        yield [CustomerAction::Delete, true];
        yield [CustomerAction::Update, true];
        yield [CustomerAction::Read, true];
        yield [CustomerAction::DefaultJob, true];
    }

    /**
     * @dataProvider provideSupports
     */
    public function testSupports(CustomerAction $action, bool $expected): void
    {
        $this->assertEquals($expected, $this->assert->supports($action));
    }

    public function testAssertGrantedToNotCoordinator(): void
    {
        $customer = $this->createMock(Customer::class);
        $viewer = $this->createMock(User::class);
        $viewer->method('isCoordinator')->willReturn(false);
        $context = new PermissionAssertContext($viewer);

        $this->assert->assertGranted(CustomerAction::Update, $customer, $context);

        self::assertTrue(true);
    }

    public function allowedActionsProvider(): iterable
    {
        yield [CustomerAction::Read];
        yield [CustomerAction::DefaultJob];
    }

    /**
     * @dataProvider allowedActionsProvider
     */
    public function testThrowsExceptionOnCoordinatorNotFound(CustomerAction $action): void
    {
        $customer = $this->createMock(Customer::class);
        $viewer = $this->createMock(User::class);
        $viewer->method('isCoordinator')->willReturn(true);
        $context = new PermissionAssertContext($viewer);

        $this->coordinatorRepository->method('findByUser')->willReturn(null);

        $this->expectException(NoAccessToCustomerException::class);

        $this->assert->assertGranted($action, $customer, $context);
    }

    public function actionsProvider(): iterable
    {
        yield [CustomerAction::Delete, true];
        yield [CustomerAction::Delete, false];
        yield [CustomerAction::Update, true];
        yield [CustomerAction::Update, false];
        yield [CustomerAction::Read, true];
        yield [CustomerAction::Read, false];
        yield [CustomerAction::DefaultJob, true];
        yield [CustomerAction::DefaultJob, false];
    }

    /**
     * @dataProvider actionsProvider
     */
    public function testAssertGranted(CustomerAction $action, bool $granted): void
    {
        $customer = $this->createMock(Customer::class);
        $viewer = $this->createMock(User::class);
        $viewer->method('isCoordinator')->willReturn(true);
        $context = new PermissionAssertContext($viewer);

        $user = $this->createMock(User::class);
        $coordinatorGroup = $this->createMock(CoordinatorGroup::class);
        $coordinator = new JobCoordinator('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $coordinatorGroup);

        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $lrToCustomer = $this->createMock(CoordinatorGroupCustomer::class);

        $this->coordinatorGroupRepository->method('findCustomerConnection')->willReturn($granted ? $lrToCustomer : null);

        $forbiddenActions = [CustomerAction::Delete, CustomerAction::Update];

        if (! $granted || in_array($action, $forbiddenActions, true)) {
            $this->expectException(NoAccessToCustomerException::class);
        }

        $this->assert->assertGranted($action, $customer, $context);

        self::assertTrue(true);
    }
}
