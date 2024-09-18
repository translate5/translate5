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

use MittagQI\Translate5\User\DTO\PasswordDto;
use MittagQI\Translate5\User\DTO\UpdateUserDto;
use PHPUnit\Framework\TestCase;

class UpdateUserDtoTest extends TestCase
{
    public function testCreateFromRequest(): void
    {
        $data = [
            'login' => 'login',
            'email' => 'email',
            'firstName' => 'firstName',
            'surName' => 'surName',
            'gender' => 'n',
            'roles' => 'role1,role2',
            'customers' => '11,12',
            'passwd' => 'password',
            'parentIds' => 'password-guid',
            'locale' => 'en',
        ];

        $dto = UpdateUserDto::fromRequestData($data);

        self::assertSame($data['login'], $dto->login);
        self::assertSame($data['email'], $dto->email);
        self::assertSame($data['firstName'], $dto->firstName);
        self::assertSame($data['surName'], $dto->surName);
        self::assertSame($data['gender'], $dto->gender);
        self::assertSame(['role1', 'role2'], $dto->roles);
        self::assertSame([11, 12], $dto->customers);
        self::assertSame($data['parentIds'], $dto->parentId);
        self::assertSame($data['locale'], $dto->locale);

        self::assertInstanceOf(PasswordDto::class, $dto->password);
    }

    public function testCreateFromEmptyRequest(): void
    {
        $data = [
            'login' => null,
            'email' => null,
            'firstName' => null,
            'surName' => null,
            'gender' => null,
            'roles' => null,
            'customers' => null,
            'passwd' => null,
            'parentIds' => null,
            'locale' => null,
        ];

        $dto = UpdateUserDto::fromRequestData($data);

        self::assertSame($data['login'], $dto->login);
        self::assertSame($data['email'], $dto->email);
        self::assertSame($data['firstName'], $dto->firstName);
        self::assertSame($data['surName'], $dto->surName);
        self::assertSame($data['gender'], $dto->gender);
        self::assertSame($data['roles'], $dto->roles);
        self::assertSame($data['customers'], $dto->customers);
        self::assertSame($data['parentIds'], $dto->parentId);
        self::assertSame($data['locale'], $dto->locale);

        self::assertInstanceOf(PasswordDto::class, $dto->password);
    }

    public function testCreateFromRequestWithEmptyStringPassword(): void
    {
        $data = [
            'passwd' => '',
        ];

        $dto = UpdateUserDto::fromRequestData($data);

        self::assertSame(null, $dto->login);
        self::assertSame(null, $dto->email);
        self::assertSame(null, $dto->firstName);
        self::assertSame(null, $dto->surName);
        self::assertSame(null, $dto->gender);
        self::assertSame(null, $dto->roles);
        self::assertSame(null, $dto->customers);
        self::assertSame(null, $dto->parentId);
        self::assertSame(null, $dto->locale);

        self::assertInstanceOf(PasswordDto::class, $dto->password);
    }

    public function testCreateFromRequestWithoutPassword(): void
    {
        $data = [];

        $dto = UpdateUserDto::fromRequestData($data);

        self::assertSame(null, $dto->login);
        self::assertSame(null, $dto->email);
        self::assertSame(null, $dto->firstName);
        self::assertSame(null, $dto->surName);
        self::assertSame(null, $dto->gender);
        self::assertSame(null, $dto->roles);
        self::assertSame(null, $dto->customers);
        self::assertSame(null, $dto->parentId);
        self::assertSame(null, $dto->locale);
        self::assertSame(null, $dto->password);
    }
}