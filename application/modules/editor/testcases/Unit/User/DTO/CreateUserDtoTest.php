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

namespace MittagQI\Translate5\Test\Unit\User\DTO;

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\User\DTO\CreateUserDto;
use MittagQI\Translate5\User\Exception\AttemptToSetLspForNonJobCoordinatorException;
use PHPUnit\Framework\TestCase;

class CreateUserDtoTest extends TestCase
{
    public function testCreateUserDtoFromRequest(): void
    {
        $guid = 'guid';

        $data = [
            'login' => 'login',
            'email' => 'email',
            'firstName' => 'firstName',
            'surName' => 'surName',
            'gender' => null,
            'roles' => 'role1,' . Roles::JOB_COORDINATOR,
            'customers' => '11,12',
            'lsp' => '112',
            'passwd' => 'password',
            'parentIds' => 'password-guid',
            'locale' => 'en',
        ];

        $dto = CreateUserDto::fromRequestData($guid, $data);

        self::assertSame($guid, $dto->guid);
        self::assertSame($data['login'], $dto->login);
        self::assertSame($data['email'], $dto->email);
        self::assertSame($data['firstName'], $dto->firstName);
        self::assertSame($data['surName'], $dto->surName);
        self::assertSame(\ZfExtended_Models_User::GENDER_NONE, $dto->gender);
        self::assertSame(['role1', Roles::JOB_COORDINATOR], $dto->roles);
        self::assertSame([11, 12], $dto->customers);
        self::assertSame(112, $dto->lsp);
        self::assertSame($data['passwd'], $dto->password);
        self::assertSame($data['parentIds'], $dto->parentId);
        self::assertSame($data['locale'], $dto->locale);
    }

    public function testThrowsExceptionOnLspPassedForNotCoordinator(): void
    {
        $this->expectException(AttemptToSetLspForNonJobCoordinatorException::class);

        $data = [
            'login' => 'login',
            'email' => 'email',
            'firstName' => 'firstName',
            'surName' => 'surName',
            'gender' => null,
            'roles' => 'role1,role2',
            'customers' => '11,12',
            'lsp' => '112',
            'passwd' => 'password',
            'parentIds' => 'password-guid',
            'locale' => 'en',
        ];

        CreateUserDto::fromRequestData('guid', $data);
    }
}