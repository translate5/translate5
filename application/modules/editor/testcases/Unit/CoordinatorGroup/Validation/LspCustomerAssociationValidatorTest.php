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

namespace MittagQI\Translate5\Test\Unit\CoordinatorGroup\Validation;

use MittagQI\Translate5\CoordinatorGroup\Exception\CustomerDoesNotBelongToCoordinatorGroupException;
use MittagQI\Translate5\CoordinatorGroup\Validation\CoordinatorGroupCustomerAssociationValidator;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LspCustomerAssociationValidatorTest extends TestCase
{
    private CoordinatorGroupRepositoryInterface|MockObject $groupRepository;

    private CoordinatorGroupCustomerAssociationValidator $validator;

    public function setUp(): void
    {
        $this->groupRepository = $this->createMock(CoordinatorGroupRepositoryInterface::class);

        $this->validator = new CoordinatorGroupCustomerAssociationValidator(
            $this->groupRepository,
        );
    }

    public function customerIdsProvider(): iterable
    {
        yield 'empty customer ids' => [
            'customers' => [],
            'coordinatorGroupCustomers' => [1, 2, 3],
            'valid' => true,
        ];

        yield 'customer ids are subset for lsp' => [
            'customers' => [1],
            'coordinatorGroupCustomers' => [1, 2, 3],
            'valid' => true,
        ];

        yield 'customer ids are not subset for lsp' => [
            'customers' => [2, 3],
            'coordinatorGroupCustomers' => [2, 4],
            'valid' => false,
        ];
    }

    /**
     * @dataProvider customerIdsProvider
     */
    public function testAssertCustomersAreSubsetForLSP(array $customers, array $groupCustomers, bool $valid): void
    {
        $this->groupRepository->method('getCustomerIds')->willReturn($groupCustomers);

        if ($valid) {
            self::assertTrue(true);
        } else {
            $this->expectException(CustomerDoesNotBelongToCoordinatorGroupException::class);
        }

        $this->validator->assertCustomersAreSubsetForCoordinatorGroup(17, ...$customers);
    }
}
