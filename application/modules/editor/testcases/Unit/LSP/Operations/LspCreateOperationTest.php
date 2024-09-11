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

namespace MittagQI\Translate5\Test\Unit\LSP\Operations;

use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Operations\LspCreateOperation;
use MittagQI\Translate5\Repository\LspRepository;
use PHPUnit\Framework\TestCase;
use ZfExtended_Models_User;

class LspCreateOperationTest extends TestCase
{
    public function coordinatorProvider(): array
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('__call')->willReturn(1);
        $coordinator = new JobCoordinator('guid', $user, $lsp);

        return [
            [null],
            [$coordinator],
        ];
    }

    /**
     * @dataProvider coordinatorProvider
     */
    public function testCreateLsp(?JobCoordinator $coordinator): void
    {
        $lspRepository = $this->createMock(LspRepository::class);

        $mock = new class() extends LanguageServiceProvider {
            public function __construct()
            {
            }

            public function setDescription(string $description): void
            {
                TestCase::assertSame('description', $description);
            }

            public function setName(string $name): void
            {
                TestCase::assertSame('name', $name);
            }

            public function setParentId(int $parentId): void
            {
                TestCase::assertSame(1, $parentId);
            }
        };

        $lspRepository->method('getEmptyModel')->willReturn($mock);
        $lspRepository->expects(self::once())->method('save');

        $service = new LspCreateOperation(
            $lspRepository,
        );

        $lsp = $service->createLsp('name', 'description', $coordinator);

        self::assertInstanceOf(LanguageServiceProvider::class, $lsp);
    }
}
