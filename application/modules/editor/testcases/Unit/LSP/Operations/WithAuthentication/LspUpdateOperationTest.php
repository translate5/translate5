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

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\LSP\Contract\LspUpdateOperationInterface;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Operations\WithAuthentication\LspUpdateOperation;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_AuthenticationInterface;

class LspUpdateOperationTest extends TestCase
{
    private ActionPermissionAssertInterface|MockObject $lspPermissionAssert;

    private LspUpdateOperationInterface|MockObject $generalOperation;

    private ZfExtended_AuthenticationInterface|MockObject $authentication;

    private UserRepository|MockObject $userRepository;

    private LspUpdateOperation $operation;

    public function setUp(): void
    {
        $this->lspPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->generalOperation = $this->createMock(LspUpdateOperationInterface::class);
        $this->authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->operation = new LspUpdateOperation(
            $this->generalOperation,
            $this->lspPermissionAssert,
            $this->authentication,
            $this->userRepository,
        );
    }

    public function testThrowsPermissionExceptionOnDelete(): void
    {
        $this->expectException(PermissionExceptionInterface::class);

        $this->lspPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->with(Action::Update)
            ->willThrowException($this->createMock(PermissionExceptionInterface::class));

        $lsp = $this->createMock(LanguageServiceProvider::class);

        $this->operation->updateLsp($lsp, 'name', 'description');
    }

    public function testUpdateLsp(): void
    {
        $this->authentication->method('getUserId')->willReturn(1);

        $authUser = $this->createMock(User::class);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $this->lspPermissionAssert->expects(self::once())->method('assertGranted')->with(Action::Update);

        $lsp = $this->createMock(LanguageServiceProvider::class);

        $this->generalOperation->expects(self::once())->method('updateLsp')->with($lsp);

        $this->operation->updateLsp($lsp, 'name', 'description');
    }
}
