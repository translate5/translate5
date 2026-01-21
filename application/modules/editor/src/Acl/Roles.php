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

/**
 * Holds additional roles for translate5
 *
 * @codeCoverageIgnore
 */
final class Roles
{
    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const SYSTEMADMIN = 'systemadmin';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const ADMIN = 'admin';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const API = 'api';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const PM = 'pm';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const CLIENTPM = 'clientpm';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const PMLIGHT = 'pmlight';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const EDITOR = 'editor';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const TASK_OVERVIEW = 'taskOverview';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const BASIC = 'basic';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const NORIGHTS = 'noRights';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const EDITOR_ONLY_OVERRIDE = 'editor-only-override';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const ERP = 'erp';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const INSTANTTRANSLATE = 'instantTranslate';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const INSTANTTRANSLATEWRITETM = 'instantTranslateWriteTm';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const INSTANT_TRANSLATE_HUMAN_REVISION_ALLOWED = 'instantTranslateHumanRevisionAllowed';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const PRODUCTION = 'production';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const TERMPM_ALLCLIENTS = 'termPM_allClients';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const TERMPM = 'termPM';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const TERMSEARCH = 'termSearch';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const TERMCUSTOMERSEARCH = 'termCustomerSearch';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const TERMFINALIZER = 'termFinalizer';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const TERMPROPOSER = 'termProposer';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const TERMREVIEWER = 'termReviewer';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const TM_MAINTENANCE = 'TMMaintenance';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const TM_MAINTENANCE_ALL_CLIENTS = 'TMMaintenance_allClients';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const JOB_COORDINATOR = 'jobCoordinator';

    // region sub-roles for the client-pm
    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const CLIENTPM_PROJECTS = 'clientpm_projects';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const CLIENTPM_LANGRESOURCES = 'clientpm_langresources';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const CLIENTPM_CUSTOMERS = 'clientpm_customers';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const CLIENTPM_USERS = 'clientpm_users';
    // endregion

    private const FRONTEND_ROLES = [
        self::EDITOR,
        self::TASK_OVERVIEW,
        self::EDITOR_ONLY_OVERRIDE,
        self::CLIENTPM,
        self::PM,
        self::PMLIGHT,
        self::API,
        self::ADMIN,
        self::SYSTEMADMIN,
        self::INSTANTTRANSLATE,
        self::INSTANTTRANSLATEWRITETM,
        self::INSTANT_TRANSLATE_HUMAN_REVISION_ALLOWED,
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
        self::JOB_COORDINATOR => [
            self::API,
            self::ADMIN,
            self::SYSTEMADMIN,
            self::PM,
            self::CLIENTPM,
            self::PMLIGHT,
        ],
        self::CLIENTPM => [
            self::API,
            self::ADMIN,
            self::SYSTEMADMIN,
            self::PM,
            self::PMLIGHT,
            self::JOB_COORDINATOR,
        ],
        self::PMLIGHT => [
            self::API,
            self::ADMIN,
            self::SYSTEMADMIN,
            self::PM,
            self::CLIENTPM,
            self::JOB_COORDINATOR,
        ],
        self::TM_MAINTENANCE => [
            self::TM_MAINTENANCE_ALL_CLIENTS,
        ],
        self::TERMPM_ALLCLIENTS => [
            self::TERMPM,
        ],
    ];

    public static function getGeneralRoles(): array
    {
        return [
            self::EDITOR,
            self::TASK_OVERVIEW,
        ];
    }

    public static function getAdminRoles(): array
    {
        return [
            self::ADMIN,
            self::SYSTEMADMIN,
            self::API,
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
            self::CLIENTPM,
        ];
    }

    public static function getRolesRequireClient(): array
    {
        return [
            self::INSTANTTRANSLATE,
            self::INSTANTTRANSLATEWRITETM,
            self::INSTANT_TRANSLATE_HUMAN_REVISION_ALLOWED,
            self::TERMPM,
            self::TERMSEARCH,
            self::TERMCUSTOMERSEARCH,
            self::TERMFINALIZER,
            self::TERMPROPOSER,
            self::TERMREVIEWER,
            self::TM_MAINTENANCE,
            self::CLIENTPM,
        ];
    }

    public static function getRolesNotRequireClient(): array
    {
        return array_diff(
            self::FRONTEND_ROLES,
            self::getRolesRequireClient(),
            self::getAdminRoles(),
            self::getGeneralRoles()
        );
    }
}
