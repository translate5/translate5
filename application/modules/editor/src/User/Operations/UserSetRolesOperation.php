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

namespace MittagQI\Translate5\User\Operations;

use MittagQI\Translate5\Acl\Exception\ConflictingRolesExceptionInterface;
use MittagQI\Translate5\Acl\Exception\RolesCannotBeSetForUserException;
use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\Acl\Validation\RolesValidator;
use MittagQI\Translate5\User\Contract\UserSetRolesOperationInterface;
use MittagQI\Translate5\User\Model\User;
use Zend_Acl_Exception;
use ZfExtended_ValidateException;

/**
 * Ment to be used to initialize roles for a user.
 * So only on User creation or in special cases where the roles need to be reinitialized.
 */
final class UserSetRolesOperation implements UserSetRolesOperationInterface
{
    public function __construct(
        private readonly RolesValidator $rolesValidator,
        private readonly Roles $roles,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            RolesValidator::create(),
            Roles::create(),
        );
    }

    /**
     * @param string[] $roles
     * @throws ConflictingRolesExceptionInterface
     * @throws RolesCannotBeSetForUserException
     * @throws Zend_Acl_Exception
     * @throws ZfExtended_ValidateException
     */
    public function setRoles(User $user, array $roles): void
    {
        $this->rolesValidator->assertRolesDontConflict($roles);

        $roles = $this->roles->expandListWithAutoRoles($roles, []);

        $this->rolesValidator->assertRolesCanBeSetForUser($roles, $user);

        $user->setRoles($roles);

        $user->validate();
    }
}
