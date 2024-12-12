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

namespace LSP\Operations\WithAuthentication;

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\LSP\ActionAssert\Permission\LspAction;
use MittagQI\Translate5\LSP\Contract\LspDeleteOperationInterface;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Operations\WithAuthentication\LspDeleteOperation;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Logger;

class LspDeleteOperationTest extends TestCase
{
    private ActionPermissionAssertInterface|MockObject $lspPermissionAssert;

    private LspDeleteOperationInterface|MockObject $generalOperation;

    private ZfExtended_AuthenticationInterface|MockObject $authentication;

    private UserRepository|MockObject $userRepository;

    private LspDeleteOperation $operation;

    private ZfExtended_Logger $logger;

    public function setUp(): void
    {
        $this->lspPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->generalOperation = $this->createMock(LspDeleteOperationInterface::class);
        $this->authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->logger = $this->createMock(ZfExtended_Logger::class);

        $this->operation = new LspDeleteOperation(
            $this->generalOperation,
            $this->lspPermissionAssert,
            $this->authentication,
            $this->userRepository,
            $this->logger,
        );
    }

    public function testThrowsPermissionExceptionOnDelete(): void
    {
        $this->expectException(PermissionExceptionInterface::class);

        $this->lspPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->with(LspAction::Delete)
            ->willThrowException($this->createMock(PermissionExceptionInterface::class));

        $lsp = $this->createMock(LanguageServiceProvider::class);

        $this->generalOperation->expects(self::never())->method('deleteLsp');

        $this->operation->deleteLsp($lsp);
    }

    public function testDeleteLsp(): void
    {
        $this->authentication->method('getUserId')->willReturn(1);

        $authUser = $this->createMock(User::class);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $this->lspPermissionAssert->expects(self::once())->method('assertGranted')->with(LspAction::Delete);

        $lsp = $this->createMock(LanguageServiceProvider::class);

        $this->generalOperation->expects(self::once())->method('deleteLsp')->with($lsp);

        $this->operation->deleteLsp($lsp);
    }
}
