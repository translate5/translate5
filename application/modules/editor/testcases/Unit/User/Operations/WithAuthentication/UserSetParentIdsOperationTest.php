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

namespace MittagQI\Translate5\Test\Unit\User\Operations\WithAuthentication;

use MittagQI\Translate5\User\Contract\UserSetParentIdsOperationInterface;
use MittagQI\Translate5\User\Operations\WithAuthentication\UserSetParentIdsOperation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_Acl;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Models_User as User;

class UserSetParentIdsOperationTest extends TestCase
{
    private ZfExtended_Acl|MockObject $acl;

    private UserSetParentIdsOperationInterface|MockObject $generalOperation;

    private ZfExtended_AuthenticationInterface|MockObject $authentication;

    private UserSetParentIdsOperation $operation;

    protected function setUp(): void
    {
        $this->acl = $this->createMock(ZfExtended_Acl::class);
        $this->generalOperation = $this->createMock(UserSetParentIdsOperationInterface::class);
        $this->authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);

        $this->operation = new UserSetParentIdsOperation(
            $this->acl,
            $this->generalOperation,
            $this->authentication,
        );
    }

    public function emptyParentIdProvider(): array
    {
        return [
            [''],
            [null],
        ];
    }

    /**
     * @dataProvider emptyParentIdProvider
     */
    public function testSetAuthUserOnEmptyParent(?string $parentId): void
    {
        $user = $this->createMock(User::class);

        $authUser = $this->createMock(User::class);
        $authUser->method('__call')->willReturnMap([
            ['getId', [], '12'],
        ]);

        $this->authentication->method('getUser')->willReturn($authUser);

        $this->generalOperation->expects(self::once())->method('setParentIds')->with($user, $authUser->getId());

        $this->operation->setParentIds($user, $parentId);
    }

    public function testSetAuthUserWhenAuthUserNotAllowedToSeeAllUsers(): void
    {
        $user = $this->createMock(User::class);

        $authUser = $this->createMock(User::class);
        $authUser->method('__call')->willReturnMap([
            ['getId', [], '12'],
        ]);

        $this->authentication->method('getUser')->willReturn($authUser);
        $this->acl->method('isInAllowedRoles')->willReturn(false);

        $this->generalOperation->expects(self::once())->method('setParentIds')->with($user, $authUser->getId());

        $this->operation->setParentIds($user, 'guid');
    }

    public function testSetAuthUserOnAclException(): void
    {
        $user = $this->createMock(User::class);

        $authUser = $this->createMock(User::class);
        $authUser->method('__call')->willReturnMap([
            ['getId', [], '12'],
        ]);

        $this->authentication->method('getUser')->willReturn($authUser);
        $this->acl->method('isInAllowedRoles')->willThrowException(new \Zend_Acl_Exception());

        $this->generalOperation->expects(self::once())->method('setParentIds')->with($user, $authUser->getId());

        $this->operation->setParentIds($user, 'guid');
    }

    public function testSetParentId(): void
    {
        $user = $this->createMock(User::class);

        $authUser = $this->createMock(User::class);
        $authUser->method('__call')->willReturnMap([
            ['getId', [], '12'],
        ]);

        $this->authentication->method('getUser')->willReturn($authUser);
        $this->acl->method('isInAllowedRoles')->willReturn(true);

        $this->generalOperation->expects(self::once())->method('setParentIds')->with($user, 'guid');

        $this->operation->setParentIds($user, 'guid');
    }
}
