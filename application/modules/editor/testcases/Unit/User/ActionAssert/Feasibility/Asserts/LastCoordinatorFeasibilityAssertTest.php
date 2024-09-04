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

namespace User\Action\FeasibilityCheck\Checkers;

use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\LspUserService;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\User\Action\Action;
use MittagQI\Translate5\User\Action\FeasibilityCheck\Checkers\LastCoordinatorFeasibilityChecker;
use MittagQI\Translate5\User\Action\FeasibilityCheck\Exception\LastCoordinatorException;
use PHPUnit\Framework\TestCase;
use ZfExtended_Models_User;

class LastCoordinatorFeasibilityCheckerTest extends TestCase
{
    public function provideSupports(): iterable
    {
        yield [Action::DELETE, true];
        yield [Action::UPDATE, false];
        yield [Action::READ, false];
        yield [Action::CREATE, false];
    }

    /**
     * @dataProvider provideSupports
     */
    public function testSupports(Action $action, bool $expected): void
    {
        $lspPermissionAuditor = new LastCoordinatorFeasibilityChecker($this->createMock(LspUserService::class));
        $this->assertEquals($expected, $lspPermissionAuditor->supports($action));
    }

    public function testAssertAllowedNotCoordinator(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->expects($this->once())
            ->method('findCoordinatorBy')
            ->with($this->callback(fn (object $provided) => $provided === $user))
            ->willReturn(null);

        $lspPermissionAuditor = new LastCoordinatorFeasibilityChecker($lspUserService);
        $lspPermissionAuditor->assertAllowed($user);
    }

    public function testAssertAllowedLastCoordinator(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $lsp = $this->createMock(LanguageServiceProvider::class);

        $coordinator = $this->getMockBuilder(JobCoordinator::class)
            ->setConstructorArgs(['lsp', $user, $lsp])
            ->getMock();

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->expects($this->once())
            ->method('findCoordinatorBy')
            ->with($this->callback(fn (object $provided) => $provided === $user))
            ->willReturn($coordinator);
        $lspUserService->expects($this->once())
            ->method('getCoordinatorsCountFor')
            ->with($this->callback(fn (object $provided) => $provided === $lsp))
            ->willReturn(1);

        $lspPermissionAuditor = new LastCoordinatorFeasibilityChecker($lspUserService);
        $this->expectException(LastCoordinatorException::class);
        $lspPermissionAuditor->assertAllowed($user);
    }
}
