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

namespace MittagQI\Translate5\Test\Unit\LSP;

use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\LspUser;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\TestCase;

class JobCoordinatorTest extends TestCase
{
    public function lspDataProvider(): iterable
    {
        yield 'same lsp' => [
            'testUserFromSameLsp' => true,
            'isCoordinator' => false,
            'testUserFromSubLsp' => false,
            'expected' => true,
        ];

        yield 'different lsp, test user is not coordinator' => [
            'testUserFromSameLsp' => false,
            'isCoordinator' => false,
            'testUserFromSubLsp' => false,
            'expected' => false,
        ];

        yield 'different lsp, test user is coordinator, user not from sub lsp' => [
            'testUserFromSameLsp' => false,
            'isCoordinator' => true,
            'testUserFromSubLsp' => false,
            'expected' => false,
        ];

        yield 'different lsp, test user is coordinator, user from sub lsp' => [
            'testUserFromSameLsp' => false,
            'isCoordinator' => true,
            'testUserFromSubLsp' => true,
            'expected' => true,
        ];
    }

    /**
     * @dataProvider lspDataProvider
     */
    public function testIsSupervisorOf(
        bool $testUserFromSameLsp,
        bool $isCoordinator,
        bool $testUserFromSubLsp,
        bool $expected
    ): void {
        $coordinatorUser = $this->createMock(User::class);
        $coordinatorLsp = $this->createMock(LanguageServiceProvider::class);
        $coordinatorLsp->method('same')->willReturn($testUserFromSameLsp);

        $coordinator = new JobCoordinator('coordinator-guid', $coordinatorUser, $coordinatorLsp);

        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);
        $user->method('isCoordinator')->willReturn($isCoordinator);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isSubLspOf')->willReturn($testUserFromSubLsp);

        $lspUser = new LspUser('lsp-guid', $user, $lsp);

        self::assertSame($expected, $coordinator->isSupervisorOf($lspUser));
    }
}
