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

use ZfExtended_Acl;

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
     * Retrieves the roles a user can be assigned to in the frontend
     * @return string[]
     */
    public static function getFrontendRoles(): array
    {
        $allRoles = ZfExtended_Acl::getInstance()?->getAllRoles() ?? [];
        $frontendRoles = [];

        foreach (self::FRONTEND_ROLES as $role) {
            if (in_array($role, $allRoles, true)) {
                $frontendRoles[] = $role;
            }
        }

        // may someone adds new roles and forgets to put them in the order here
        // Important: clientpm-roles must not be added also not the internal roles "no-rights" and "basic"
        foreach ($allRoles as $role) {
            if (
                ! in_array($role, self::getClientPmSubRoles(), true)
                && $role !== self::BASIC
                && $role !== self::NORIGHTS
                && ! in_array($role, $frontendRoles, true)
            ) {
                $frontendRoles[] = $role;
            }
        }

        return $frontendRoles;
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

    private static function getClientRestrictedRoles(): array
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

    private static function getNotClientRestrictedRoles(): array
    {
        return array_diff(self::FRONTEND_ROLES, self::getClientRestrictedRoles());
    }

    public static function isClientRestricted(array $userRoles): bool
    {
        return array_intersect(self::getClientRestrictedRoles(), $userRoles) !== [];
    }

    /**
     * Removes dependant/subroles if the clientpm role is not set
     * or removes the client-pm-role if 'pm' is set
     */
    public static function filterRoles(array $userRoles): array
    {
        if (! in_array(self::CLIENTPM, $userRoles, true)) {
            $newRoles = [];

            foreach ($userRoles as $role) {
                if (! str_starts_with($role, self::CLIENTPM)) {
                    $newRoles[] = $role;
                }
            }

            return $newRoles;
        }

        return $userRoles;
    }
}
