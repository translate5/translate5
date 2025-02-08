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

namespace MittagQI\Translate5\Test\Unit\CoordinatorGroup\Operations;

use editor_Models_Customer_Customer;
use MittagQI\Translate5\CoordinatorGroup\Exception\CustomerDoesNotBelongToCoordinatorGroupException;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroupCustomer;
use MittagQI\Translate5\CoordinatorGroup\Operations\CoordinatorGroupAssignCustomerOperation;
use MittagQI\Translate5\CoordinatorGroup\Validation\CoordinatorGroupCustomerAssociationValidator;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CoordinatorGroupJobAssignCustomerOperationTest extends TestCase
{
    private CoordinatorGroupRepositoryInterface|MockObject $coordinatorGroupJobRepository;

    private CoordinatorGroupCustomerAssociationValidator|MockObject $coordinatorGroupCustomerAssociationValidator;

    private CoordinatorGroupAssignCustomerOperation $operation;

    public function setUp(): void
    {
        $this->coordinatorGroupJobRepository = $this->createMock(CoordinatorGroupRepositoryInterface::class);
        $this->coordinatorGroupCustomerAssociationValidator = $this->createMock(CoordinatorGroupCustomerAssociationValidator::class);

        $this->operation = new CoordinatorGroupAssignCustomerOperation(
            $this->coordinatorGroupJobRepository,
            $this->coordinatorGroupCustomerAssociationValidator,
        );
    }

    public function testThrowsCustomerDoesNotBelongToGroupException(): void
    {
        $this->expectException(CustomerDoesNotBelongToCoordinatorGroupException::class);

        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('isTopRankGroup')->willReturn(false);

        $parentGroup = $this->createMock(CoordinatorGroup::class);
        $parentGroup->method('__call')->willReturnMap([
            ['getId', [], '10'],
        ]);

        $this->coordinatorGroupJobRepository->method('get')->with((int) $group->getParentId())->willReturn($parentGroup);

        $customer = $this->createMock(editor_Models_Customer_Customer::class);
        $customer->method('__call')->willReturnMap([
            ['getId', [], '12'],
        ]);

        $this->coordinatorGroupCustomerAssociationValidator
            ->expects(self::once())
            ->method('assertCustomersAreSubsetForCoordinatorGroup')
            ->with((int) $parentGroup->getId(), (int) $customer->getId())
            ->willThrowException($this->createMock(CustomerDoesNotBelongToCoordinatorGroupException::class));

        $this->coordinatorGroupJobRepository->expects(self::never())->method('saveCustomerAssignment');

        $this->operation->assignCustomer($group, $customer);
    }

    public function testAssignCustomerToNotDirectCoordinatorGroupJob(): void
    {
        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('isTopRankGroup')->willReturn(false);
        $group->method('__call')->willReturnMap([
            ['getId', [], '11'],
        ]);

        $parentGroup = $this->createMock(CoordinatorGroup::class);
        $parentGroup->method('__call')->willReturnMap([
            ['getId', [], '10'],
        ]);

        $this->coordinatorGroupJobRepository->method('get')->with((int) $group->getParentId())->willReturn($parentGroup);

        $customer = $this->createMock(editor_Models_Customer_Customer::class);
        $customer->method('__call')->willReturnMap([
            ['getId', [], '12'],
        ]);

        $this->coordinatorGroupCustomerAssociationValidator
            ->expects(self::once())
            ->method('assertCustomersAreSubsetForCoordinatorGroup')
            ->with((int) $parentGroup->getId(), (int) $customer->getId());

        $groupCustomer = $this->createMock(CoordinatorGroupCustomer::class);
        $calledSetter = null;

        $groupCustomer->expects(self::exactly(2))
            ->method('__call')
            ->with(
                self::callback(function ($value) use (&$calledSetter) {
                    $calledSetter = $value;

                    return in_array($value, ['setGroupId', 'setCustomerId']);
                }),
                self::callback(function ($value) use (&$calledSetter) {
                    if ('setGroupId' === $calledSetter) {
                        self::assertSame([11], $value);

                        return [11] === $value;
                    }

                    return [12] === $value;
                })
            );

        $this->coordinatorGroupJobRepository
            ->method('getEmptyCoordinatorGroupCustomerModel')
            ->willReturn($groupCustomer);

        $this->coordinatorGroupJobRepository->expects(self::once())->method('saveCustomerAssignment');

        $this->operation->assignCustomer($group, $customer);
    }

    public function testAssignCustomerToDirectGroup(): void
    {
        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('isTopRankGroup')->willReturn(true);
        $group->method('__call')->willReturnMap([
            ['getId', [], '11'],
        ]);

        $parentGroup = $this->createMock(CoordinatorGroup::class);

        $this->coordinatorGroupJobRepository->method('get')->with((int) $group->getParentId())->willReturn($parentGroup);

        $customer = $this->createMock(editor_Models_Customer_Customer::class);
        $customer->method('__call')->willReturnMap([
            ['getId', [], '12'],
        ]);

        $this->coordinatorGroupCustomerAssociationValidator
            ->expects(self::never())
            ->method('assertCustomersAreSubsetForCoordinatorGroup');

        $groupCustomer = $this->createMock(CoordinatorGroupCustomer::class);
        $calledSetter = null;

        $groupCustomer->expects(self::exactly(2))
            ->method('__call')
            ->with(
                self::callback(function ($value) use (&$calledSetter) {
                    $calledSetter = $value;

                    return in_array($value, ['setGroupId', 'setCustomerId']);
                }),
                self::callback(function ($value) use (&$calledSetter) {
                    if ('setGroupId' === $calledSetter) {
                        self::assertSame([11], $value);

                        return [11] === $value;
                    }

                    return [12] === $value;
                })
            );

        $this->coordinatorGroupJobRepository
            ->method('getEmptyCoordinatorGroupCustomerModel')
            ->willReturn($groupCustomer);

        $this->coordinatorGroupJobRepository->expects(self::once())->method('saveCustomerAssignment');

        $this->operation->assignCustomer($group, $customer);
    }
}
