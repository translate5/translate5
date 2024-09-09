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

namespace MittagQI\Translate5\User\Validation;

use MittagQI\Translate5\Acl\Roles;
use MittagQI\ZfExtended\Acl\Roles as BaseRoles;
use Zend_Validate_Abstract;

class RolesValidator
{
    private array $_messageTemplates = [
        'roles' => 'Sie können die Rolle {role} nicht mit einer der folgenden Rollen festlegen: {roles}',
    ];

    private array $conflictRoles = [
        Roles::JOB_COORDINATOR => [
            BaseRoles::ADMIN,
            BaseRoles::SYSTEMADMIN,
            BaseRoles::PM,
            BaseRoles::CLIENTPM,
        ],
    ];

    public function __construct(
        private readonly ZfExtended_Acl $acl,
    ) {
    }

    public function assertRolesDontConflict(array $roles): bool
    {
        $valid = true;
        $this->_setValue($value);

        if ('' === $value) {
            return true;
        }

        $roles = explode(',', $value);

        foreach ($this->conflictRoles as $role => $conflictRoles) {
            if (in_array($role, $roles) && ! empty(array_intersect($roles, $conflictRoles))) {
                $valid = false;
                $translator = $this->getTranslator();

                $message = $translator->translate($this->_messageTemplates['roles']);
                $message = str_replace(
                    ['{role}', '{roles}'],
                    [
                        $translator->translate(mb_ucfirst($role)),
                        implode(
                            ', ',
                            array_map(
                                static fn ($conflictRole) => $translator->translate(mb_ucfirst($conflictRole)),
                                $conflictRoles
                            )
                        ),
                    ],
                    $message
                );

                $this->_errors[] = 'roles';
                $this->_messages['roles'] = $message;
            }
        }

        return $valid;
    }
}
