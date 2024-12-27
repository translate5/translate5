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

namespace MittagQI\Translate5\Test\Unit\DefaultJobAssignment\DefaultLspJob\ActionAssert\Permission\Asserts;

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\DefaultJobAssignment\DefaultJobAction;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\ActionAssert\Permission\Asserts\LspRestrictionAssert;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\ActionAssert\Permission\Exception\NoAccessToDefaultLspJobException;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Model\DefaultLspJob;
use MittagQI\Translate5\LSP\ActionAssert\Permission\LspAction;
use MittagQI\Translate5\LSP\Exception\LspNotFoundException;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\TestCase;

class LspRestrictionAssertTest extends TestCase
{
    private ActionPermissionAssertInterface $lspPermissionAssert;

    private LspRepositoryInterface $lspRepository;

    private LspRestrictionAssert $assert;

    public function setUp(): void
    {
        $this->lspPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->lspRepository = $this->createMock(LspRepositoryInterface::class);

        $this->assert = new LspRestrictionAssert(
            $this->lspPermissionAssert,
            $this->lspRepository,
        );
    }

    public function provideSupports(): iterable
    {
        yield [DefaultJobAction::Delete, true];
        yield [DefaultJobAction::Update, true];
        yield [DefaultJobAction::Read, true];
    }

    /**
     * @dataProvider provideSupports
     */
    public function testSupports(DefaultJobAction $action, bool $expected): void
    {
        $this->assertEquals($expected, $this->assert->supports($action));
    }

    public function testAssertNotGrantedIfLspNotFound(): void
    {
        $this->lspRepository->method('get')->willThrowException(new LspNotFoundException(1));
        $defaultLspJob = $this->createMock(DefaultLspJob::class);
        $defaultLspJob->method('__call')->willReturnMap([
            ['getCustomerId', [], 1],
        ]);
        $viewer = $this->createMock(User::class);
        $context = new PermissionAssertContext($viewer);

        $this->expectException(NoAccessToDefaultLspJobException::class);

        $this->assert->assertGranted(DefaultJobAction::Update, $defaultLspJob, $context);
    }

    public function testAssertNotGrantedIfNoAccessToLsp(): void
    {
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $this->lspRepository->method('get')->willReturn($lsp);

        $defaultLspJob = $this->createMock(DefaultLspJob::class);
        $defaultLspJob->method('__call')->willReturnMap([
            ['getLspId', [], 1],
        ]);

        $viewer = $this->createMock(User::class);
        $context = new PermissionAssertContext($viewer);

        $this->lspPermissionAssert
            ->expects($this->once())
            ->method('assertGranted')
            ->with(LspAction::Update, $lsp)
            ->willThrowException(new class() extends \Exception implements PermissionExceptionInterface {
            });

        $this->expectException(NoAccessToDefaultLspJobException::class);

        $this->assert->assertGranted(DefaultJobAction::Update, $defaultLspJob, $context);
    }

    public function testAssertGranted(): void
    {
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $this->lspRepository->method('get')->willReturn($lsp);

        $defaultLspJob = $this->createMock(DefaultLspJob::class);
        $defaultLspJob->method('__call')->willReturnMap([
            ['getLspId', [], 1],
        ]);

        $viewer = $this->createMock(User::class);
        $context = new PermissionAssertContext($viewer);

        $this->assert->assertGranted(DefaultJobAction::Update, $defaultLspJob, $context);

        self::assertTrue(true);
    }
}
