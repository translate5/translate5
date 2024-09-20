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

namespace MittagQI\Translate5\Test\Unit\User\Validation;

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\LspUser;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\User\Exception\InvalidParentUserProvidedForJobCoordinatorException;
use MittagQI\Translate5\User\Exception\InvalidParentUserProvidedForLspUserException;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Validation\ParentUserValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ParentUserValidatorTest extends TestCase
{
    private LspUserRepositoryInterface|MockObject $lspUserRepository;

    private JobCoordinatorRepository|MockObject $coordinatorRepository;

    private ParentUserValidator $validator;

    public function setUp(): void
    {
        $this->lspUserRepository = $this->createMock(LspUserRepositoryInterface::class);
        $this->coordinatorRepository = $this->createMock(JobCoordinatorRepository::class);

        $this->validator = new ParentUserValidator(
            $this->lspUserRepository,
            $this->coordinatorRepository,
        );
    }

    public function testNothingHappensIfChildUserIsNotLspUser(): void
    {
        $parentUser = $this->createMock(User::class);
        $childUser = $this->createMock(User::class);

        $this->lspUserRepository->method('findByUser')->willReturn(null);

        $this->validator->assertUserCanBeSetAsParentTo($parentUser, $childUser);

        $this->assertTrue(true);
    }

    public function testThrowsExceptionOnAttemptToSetNotCoordinatorAsParentForLspUser(): void
    {
        $this->expectException(InvalidParentUserProvidedForLspUserException::class);

        $parentUser = $this->createMock(User::class);

        $childUser = $this->createMock(User::class);
        $childUser->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $lsp = $this->createMock(LanguageServiceProvider::class);

        $lspUser = new LspUser('guid', $childUser, $lsp);

        $this->createMock(LspUser::class);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);

        $this->coordinatorRepository->method('findByUser')->willReturn(null);

        $this->validator->assertUserCanBeSetAsParentTo($parentUser, $childUser);
    }

    public function testThrowsExceptionOnAttemptToSetCoordinatorOfDifferentLspAsParentForLspUser(): void
    {
        $this->expectException(InvalidParentUserProvidedForLspUserException::class);

        $parentUser = $this->createMock(User::class);

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isCoordinatorOf')->willReturn(false);

        $childUser = $this->createMock(User::class);
        $childUser->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $lsp = $this->createMock(LanguageServiceProvider::class);

        $lspUser = new LspUser($childUser->getUserGuid(), $childUser, $lsp);

        $this->createMock(LspUser::class);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);

        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $this->validator->assertUserCanBeSetAsParentTo($parentUser, $childUser);
    }

    public function parentRoleProvider(): array
    {
        return [
            [Roles::PM],
            [Roles::ADMIN],
            [Roles::SYSTEMADMIN],
        ];
    }

    /**
     * @dataProvider parentRoleProvider
     */
    public function testAllowedToSetParentOfRoleToDirectCoordinator(string $parentRole): void
    {
        $parentUser = $this->createMock(User::class);
        $parentUser->method('isPm')->willReturn($parentRole === Roles::PM);
        $parentUser->method('isAdmin')->willReturn(in_array($parentRole, [Roles::ADMIN, Roles::SYSTEMADMIN]));

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isCoordinatorOf')->willReturn(false);

        $childUser = $this->createMock(User::class);
        $childUser->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);
        $childUser->method('getRoles')->willReturn([Roles::JOB_COORDINATOR]);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(true);

        $lspUser = new LspUser($childUser->getUserGuid(), $childUser, $lsp);

        $this->createMock(LspUser::class);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);

        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $this->validator->assertUserCanBeSetAsParentTo($parentUser, $childUser);

        $this->assertTrue(true);
    }

    public function testThrowsExceptionOnAttemptToSetParentOfNotAllowedRoleToCoordinator(): void
    {
        $this->expectException(InvalidParentUserProvidedForJobCoordinatorException::class);

        $parentUser = $this->createMock(User::class);
        $parentUser->method('isPm')->willReturn(false);
        $parentUser->method('isAdmin')->willReturn(false);

        $childUser = $this->createMock(User::class);
        $childUser->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);
        $childUser->method('getRoles')->willReturn([Roles::JOB_COORDINATOR]);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(true);

        $lspUser = new LspUser($childUser->getUserGuid(), $childUser, $lsp);

        $this->createMock(LspUser::class);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);

        $this->coordinatorRepository->method('findByUser')->willReturn(null);

        $this->validator->assertUserCanBeSetAsParentTo($parentUser, $childUser);

        $this->assertTrue(true);
    }

    public function testAllowedToSetCoordinatorOfSameLspToCoordinator(): void
    {
        $parentUser = $this->createMock(User::class);
        $parentUser->method('isPm')->willReturn(false);
        $parentUser->method('isAdmin')->willReturn(false);

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isCoordinatorOf')->willReturn(true);

        $childUser = $this->createMock(User::class);
        $childUser->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);
        $childUser->method('getRoles')->willReturn([Roles::JOB_COORDINATOR]);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(true);

        $lspUser = new LspUser($childUser->getUserGuid(), $childUser, $lsp);

        $this->createMock(LspUser::class);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);

        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $this->validator->assertUserCanBeSetAsParentTo($parentUser, $childUser);

        $this->assertTrue(true);
    }

    public function testAllowedToSetCoordinatorOfParentLspToCoordinator(): void
    {
        $parentUser = $this->createMock(User::class);
        $parentUser->method('isPm')->willReturn(false);
        $parentUser->method('isAdmin')->willReturn(false);

        $parentLsp = $this->createMock(LanguageServiceProvider::class);
        $parentLsp->method('same')->willReturn(false);

        $coordinator = new JobCoordinator(bin2hex(random_bytes(16)), $parentUser, $parentLsp);

        $childUser = $this->createMock(User::class);
        $childUser->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);
        $childUser->method('getRoles')->willReturn([Roles::JOB_COORDINATOR]);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(false);
        $lsp->method('isSubLspOf')->willReturn(true);

        $lspUser = new LspUser($childUser->getUserGuid(), $childUser, $lsp);

        $this->createMock(LspUser::class);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);

        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $this->validator->assertUserCanBeSetAsParentTo($parentUser, $childUser);

        $this->assertTrue(true);
    }

    public function testThrowsExceptionOnSetNotRelatedCoordinatorToCoordinator(): void
    {
        $this->expectException(InvalidParentUserProvidedForJobCoordinatorException::class);

        $parentUser = $this->createMock(User::class);
        $parentUser->method('isPm')->willReturn(false);
        $parentUser->method('isAdmin')->willReturn(false);

        $parentLsp = $this->createMock(LanguageServiceProvider::class);
        $parentLsp->method('same')->willReturn(false);

        $coordinator = new JobCoordinator(bin2hex(random_bytes(16)), $parentUser, $parentLsp);

        $childUser = $this->createMock(User::class);
        $childUser->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);
        $childUser->method('getRoles')->willReturn([Roles::JOB_COORDINATOR]);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(false);
        $lsp->method('isSubLspOf')->willReturn(false);

        $lspUser = new LspUser($childUser->getUserGuid(), $childUser, $lsp);

        $this->createMock(LspUser::class);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);

        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $this->validator->assertUserCanBeSetAsParentTo($parentUser, $childUser);
    }
}
