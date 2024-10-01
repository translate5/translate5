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
use MittagQI\Translate5\User\Exception\ConflictingRolesExceptionInterface;
use MittagQI\Translate5\Acl\Validation\RolesValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_Acl;

class RolesValidatorTest extends TestCase
{
    private ZfExtended_Acl|MockObject $acl;

    private RolesValidator $validator;

    public function setUp(): void
    {
        $this->acl = $this->createMock(ZfExtended_Acl::class);

        $this->validator = new RolesValidator($this->acl);
    }

    public function testNothingHappensOnEmptyRoleList(): void
    {
        $this->validator->assertRolesDontConflict([]);

        $this->assertTrue(true);
    }

    public function conflictingRolesProvider(): iterable
    {
        yield [[Roles::JOB_COORDINATOR, Roles::ADMIN]];
        yield [[Roles::JOB_COORDINATOR, Roles::SYSTEMADMIN]];
        yield [[Roles::JOB_COORDINATOR, Roles::PM]];
        yield [[Roles::JOB_COORDINATOR, Roles::CLIENTPM]];
    }

    /**
     * @dataProvider conflictingRolesProvider
     */
    public function testThrowsExceptionOnConflictingRoles(array $conflictingRoles): void
    {
        $this->expectException(ConflictingRolesExceptionInterface::class);

        $this->validator->assertRolesDontConflict($conflictingRoles);
    }

    public function testEverythingOkWithPotentiallyConflictingRole(): void
    {
        $this->validator->assertRolesDontConflict([Roles::JOB_COORDINATOR, 'other-role']);

        $this->assertTrue(true);
    }

    public function testThrowsExceptionWhenRolePopulatesToConflictingRole(): void
    {
        $this->expectException(ConflictingRolesExceptionInterface::class);

        $roleToBePopulated = 'role-to-be-populated';

        $this->acl
            ->method('getRightsToRolesAndResource')
            ->with([$roleToBePopulated])
            ->willReturn([Roles::JOB_COORDINATOR, Roles::ADMIN]);

        $this->validator->assertRolesDontConflict([Roles::JOB_COORDINATOR, $roleToBePopulated]);
    }
}
