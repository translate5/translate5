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

namespace MittagQI\Translate5\Test\Unit\LSP\Validation;

use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\Validation\LspCustomerAssociationValidator;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LspCustomerAssociationValidatorTest extends TestCase
{
    private LspRepositoryInterface|MockObject $lspRepository;

    private LspCustomerAssociationValidator $validator;

    public function setUp(): void
    {
        $this->lspRepository = $this->createMock(LspRepositoryInterface::class);

        $this->validator = new LspCustomerAssociationValidator(
            $this->lspRepository,
        );
    }

    public function customerIdsProvider(): iterable
    {
        yield 'empty customer ids' => [
            'customers' => [],
            'lspCustomers' => [1, 2, 3],
            'valid' => true,
        ];

        yield 'customer ids are subset for lsp' => [
            'customers' => [1],
            'lspCustomers' => [1, 2, 3],
            'valid' => true,
        ];

        yield 'customer ids are not subset for lsp' => [
            'customers' => [2, 3],
            'lspCustomers' => [2, 4],
            'valid' => false,
        ];
    }

    /**
     * @dataProvider customerIdsProvider
     */
    public function testAssertCustomersAreSubsetForLSP(array $customers, array $lspCustomers, bool $valid): void
    {
        $this->lspRepository->method('getCustomerIds')->willReturn($lspCustomers);

        if ($valid) {
            self::assertTrue(true);
        } else {
            $this->expectException(CustomerDoesNotBelongToLspException::class);
        }

        $this->validator->assertCustomersAreSubsetForLSP(17, ...$customers);
    }
}
