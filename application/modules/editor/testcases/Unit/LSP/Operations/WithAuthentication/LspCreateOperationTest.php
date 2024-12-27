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
use MittagQI\Translate5\LSP\Contract\LspCreateOperationInterface;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Operations\WithAuthentication\LspCreateOperation;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_AuthenticationInterface;

class LspCreateOperationTest extends TestCase
{
    private ActionPermissionAssertInterface|MockObject $coordinatorRepository;

    private LspCreateOperationInterface|MockObject $generalOperation;

    private ZfExtended_AuthenticationInterface|MockObject $authentication;

    private UserRepository|MockObject $userRepository;

    private LspCreateOperation $operation;

    public function setUp(): void
    {
        $this->coordinatorRepository = $this->createMock(JobCoordinatorRepository::class);
        $this->generalOperation = $this->createMock(LspCreateOperationInterface::class);
        $this->authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->operation = new LspCreateOperation(
            $this->generalOperation,
            $this->coordinatorRepository,
            $this->authentication,
            $this->userRepository,
        );
    }

    public function testCreateLspAuthUserIsNotCoordinator(): void
    {
        $this->authentication->method('getUserId')->willReturn(1);

        $authUser = $this->createMock(User::class);

        $lsp = $this->createMock(LanguageServiceProvider::class);

        $this->coordinatorRepository->method('findByUser')->with($authUser)->willReturn(null);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $this->generalOperation
            ->expects(self::once())
            ->method('createLsp')
            ->with('name', 'description', null)
            ->willReturn($lsp);

        $this->operation->createLsp('name', 'description');
    }

    public function testCreateLspAuthUserIsCoordinator(): void
    {
        $this->authentication->method('getUserId')->willReturn(1);

        $authUser = $this->createMock(User::class);

        $user = $this->createMock(User::class);
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('__call')->willReturnMap([
            ['getId', [], '12'],
        ]);

        $coordinator = new JobCoordinator('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $lsp);

        $this->coordinatorRepository->method('findByUser')->with($authUser)->willReturn($coordinator);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $this->generalOperation
            ->expects(self::once())
            ->method('createLsp')
            ->with('name', 'description', (int) $lsp->getId())
            ->willReturn($lsp);

        $this->operation->createLsp('name', 'description');
    }
}
