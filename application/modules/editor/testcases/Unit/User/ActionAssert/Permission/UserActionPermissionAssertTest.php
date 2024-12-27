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

namespace MittagQI\Translate5\Test\Unit\User\ActionAssert\Permission;

use BackedEnum;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class UserActionPermissionAssertTest extends TestCase
{
    public function testAssertGranted(): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $assert1 = new class implements PermissionAssertInterface
        {

            public function assertGranted(BackedEnum $action, object $object, PermissionAssertContext $context): void
            {
                TestCase::assertSame(UserAction::Delete, $action);
                TestCase::assertInstanceOf(User::class, $object);
            }

            public function supports(BackedEnum $action): bool
            {
                return true;
            }
        };

        $assert2 = new class implements PermissionAssertInterface
        {

            public function assertGranted(BackedEnum $action, object $object, PermissionAssertContext $context): void
            {
                throw new ExpectationFailedException('Method was not expected to be called');
            }

            public function supports(BackedEnum $action): bool
            {
                return false;
            }
        };

        $auditor = new UserActionPermissionAssert([$assert1, $assert2]);
        $auditor->assertGranted(UserAction::Delete, $user, $context);
    }

    public function testIsGranted(): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $assert1 = new class implements PermissionAssertInterface
        {

            public function assertGranted(BackedEnum $action, object $object, PermissionAssertContext $context): void
            {
                TestCase::assertSame(UserAction::Delete, $action);
                TestCase::assertInstanceOf(User::class, $object);
            }

            public function supports(BackedEnum $action): bool
            {
                return true;
            }
        };

        $assert2 = new class implements PermissionAssertInterface
        {

            public function assertGranted(BackedEnum $action, object $object, PermissionAssertContext $context): void
            {
                throw new ExpectationFailedException('Method was not expected to be called');
            }

            public function supports(BackedEnum $action): bool
            {
                return false;
            }
        };

        $auditor = new UserActionPermissionAssert([$assert1, $assert2]);

        self::assertTrue($auditor->isGranted(UserAction::Delete, $user, $context));
    }

    public function testNotGranted(): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $assert1 = new class implements PermissionAssertInterface
        {

            public function assertGranted(BackedEnum $action, object $object, PermissionAssertContext $context): void
            {
                throw new class extends \Exception implements PermissionExceptionInterface
                {
                };
            }

            public function supports(BackedEnum $action): bool
            {
                return true;
            }
        };

        $auditor = new UserActionPermissionAssert([$assert1]);
        self::assertFalse($auditor->isGranted(UserAction::Delete, $user, $context));
    }

    public function testAssertGrantedException(): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $assert1 = new class implements PermissionAssertInterface
        {

            public function assertGranted(BackedEnum $action, object $object, PermissionAssertContext $context): void
            {
                throw new class extends \Exception implements PermissionExceptionInterface
                {
                };
            }

            public function supports(BackedEnum $action): bool
            {
                return true;
            }
        };

        $auditor = new UserActionPermissionAssert([$assert1]);

        $this->expectException(PermissionExceptionInterface::class);
        $auditor->assertGranted(UserAction::Delete, $user, $context);
    }
}
