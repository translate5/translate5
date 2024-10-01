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

namespace MittagQI\Translate5\Acl;

use MittagQI\Translate5\User\Exception\ConflictingRolesExceptionInterface;
use MittagQI\Translate5\User\Exception\RoleConflictWithRoleThatPopulatedToRolesetException;
use MittagQI\Translate5\User\Exception\RolesetHasConflictingRolesException;
use MittagQI\ZfExtended\Acl\AutoSetRoleResource;
use MittagQI\ZfExtended\Acl\SetAclRoleResource;
use Zend_Acl_Exception;
use ZfExtended_Acl;
use MittagQI\Translate5\User\Model\User;

/**
 * Holds additional roles for translate5
 */
final class Roles
{
    public const SYSTEMADMIN = 'systemadmin';

    public const ADMIN = 'admin';

    public const API = 'api';

    public const PM = 'pm';

    public const CLIENTPM = 'clientpm';

    public const PMLIGHT = 'pmlight';

    public const EDITOR = 'editor';

    public const BASIC = 'basic';

    public const NORIGHTS = 'noRights';

    public const EDITOR_ONLY_OVERRIDE = 'editor-only-override';

    public const ERP = 'erp';

    public const INSTANTTRANSLATE = 'instantTranslate';

    public const INSTANTTRANSLATEWRITETM = 'instantTranslateWriteTm';

    public const PRODUCTION = 'production';

    public const TERMPM_ALLCLIENTS = 'termPM_allClients';

    public const TERMPM = 'termPM';

    public const TERMSEARCH = 'termSearch';

    public const TERMCUSTOMERSEARCH = 'termCustomerSearch';

    public const TERMFINALIZER = 'termFinalizer';

    public const TERMPROPOSER = 'termProposer';

    public const TERMREVIEWER = 'termReviewer';

    // region sub-roles for the client-pm */
    public const CLIENTPM_PROJECTS = 'clientpm_projects';

    public const CLIENTPM_LANGRESOURCES = 'clientpm_langresources';

    public const CLIENTPM_CUSTOMERS = 'clientpm_customers';

    public const CLIENTPM_USERS = 'clientpm_users';
    // endregion

    public const TM_MAINTENANCE = 'TMMaintenance';

    public const TM_MAINTENANCE_ALL_CLIENTS = 'TMMaintenance_allClients';

    public const JOB_COORDINATOR = 'jobCoordinator';

    public const FRONTEND_ROLES = [
        self::EDITOR,
        self::EDITOR_ONLY_OVERRIDE,
        self::CLIENTPM,
        self::PM,
        self::PMLIGHT,
        self::API,
        self::ADMIN,
        self::SYSTEMADMIN,
        self::INSTANTTRANSLATE,
        self::INSTANTTRANSLATEWRITETM,
        self::TERMPM,
        self::TERMPM_ALLCLIENTS,
        self::TERMPROPOSER,
        self::TERMREVIEWER,
        self::TERMFINALIZER,
        self::TERMCUSTOMERSEARCH,
        self::ERP,
        self::PRODUCTION,
        self::TM_MAINTENANCE,
        self::TM_MAINTENANCE_ALL_CLIENTS,
        self::JOB_COORDINATOR,
    ];

    /**
     * @var array<string, string[]>
     */
    public const CONFLICTING_ROLES = [
        Roles::JOB_COORDINATOR => [
            Roles::ADMIN,
            Roles::SYSTEMADMIN,
            Roles::PM,
            Roles::CLIENTPM,
        ],
    ];

    /**
     * @codeCoverageIgnore
     */
    public function __construct(
        private readonly ZfExtended_Acl $acl,
    ) {
    }

    public static function create(): self
    {
        return new self(
            ZfExtended_Acl::getInstance(),
        );
    }

    /***
     * @param string[] $newUserRoles
     * @param string[] $oldUserRoles
     * @return string[]
     */
    public function expandListWithAutoRoles(array $newUserRoles, array $oldUserRoles): array
    {
        return $this->acl->mergeAutoSetRoles($newUserRoles, $oldUserRoles);
    }

    public static function getGeneralRoles(): array
    {
        return [
            Roles::EDITOR,
        ];
    }

    public static function getAdminRoles(): array
    {
        return [
            Roles::ADMIN,
            Roles::SYSTEMADMIN,
            Roles::API,
        ];
    }

    /**
     * Retrieves the "Sub-roles" a clientpm can have (steering the visibility of the main editor tabs)
     *
     * @return string[]
     */
    public static function getClientPmSubRoles(): array
    {
        return [
            self::CLIENTPM_PROJECTS,
            self::CLIENTPM_LANGRESOURCES,
            self::CLIENTPM_CUSTOMERS,
            self::CLIENTPM_USERS,
        ];
    }

    public static function getClientRestrictedRoles(): array
    {
        return [
            self::INSTANTTRANSLATE,
            self::INSTANTTRANSLATEWRITETM,
            self::TERMPM,
            self::TERMSEARCH,
            self::TERMCUSTOMERSEARCH,
            self::TERMFINALIZER,
            self::TERMPROPOSER,
            self::TERMREVIEWER,
            self::TM_MAINTENANCE,
            self::CLIENTPM,
            self::JOB_COORDINATOR,
        ];
    }

    public static function getManagerRoles(): array
    {
        return array_diff(
            self::FRONTEND_ROLES,
            self::getClientRestrictedRoles(),
            self::getAdminRoles(),
            self::getGeneralRoles()
        );
    }

    public static function isClientRestricted(array $userRoles): bool
    {
        return array_intersect(self::getClientRestrictedRoles(), $userRoles) !== [];
    }
}
