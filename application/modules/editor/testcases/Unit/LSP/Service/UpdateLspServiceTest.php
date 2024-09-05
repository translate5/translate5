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

namespace MittagQI\Translate5\Test\Unit\LSP\Service;

use MittagQI\Translate5\LSP\LspService;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Service\LspCustomerAssociationUpdateService;
use MittagQI\Translate5\LSP\Validation\LspCustomerAssociationValidator;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\User\Validation\UserCustomerAssociationValidator;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class UpdateLspServiceTest extends TestCase
{
    public function testUpdateInfoFields(): void
    {
        $lspRepository = $this->createMock(LspRepository::class);
        $lspService = $this->createMock(LspService::class);
        $userCustomerAssociationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $lsp = $this->createMock(LanguageServiceProvider::class);

        $lspRepository->expects(self::once())
            ->method('save')
            ->with(
                $this->callback(
                    fn (LanguageServiceProvider $lspToSave) => $lsp === $lspToSave
                )
            );

        $service = new LspCustomerAssociationUpdateService(
            $lspRepository,
            $userCustomerAssociationValidator,
            $lspCustomerAssociationValidator,
            $customerRepository,
            $eventDispatcher,
        );

        $service->updateCustomers($lsp, [1, 2, 3]);
    }
}
