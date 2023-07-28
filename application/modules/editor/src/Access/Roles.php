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

namespace MittagQI\Translate5\Access;

use MittagQI\ZfExtended\Access\Roles as BaseRoles;
use ZfExtended_Acl;

/**
 * Holds additional roles for translate5
 */
final class Roles extends BaseRoles {

    /* additional roles that exist in T5 */
    const EDITOR_ONLY_OVERRIDE = 'editor-only-override';
    const ERP = 'erp';
    const INSTANTTRANSLATE = 'instantTranslate';
    const INSTANTTRANSLATEWRITETM = 'instantTranslateWriteTm';
    const PRODUCTION = 'production';
    const TERMPM_ALLCLIENTS = 'termPM_allClients';
    const TERMPM = 'termPM';
    const TERMCUSTOMERSEARCH = 'termCustomerSearch';
    const TERMFINALIZER = 'termFinalizer';
    const TERMPROPOSER = 'termProposer';
    const TERMREVIEWER = 'termReviewer';

    /* subroles for the client-pm */
    const CLIENTPM_PROJECTS = 'clientpm_projects';
    const CLIENTPM_LANGRESOURCES = 'clientpm_langresources';
    const CLIENTPM_CUSTOMERS = 'clientpm_customers';
    const CLIENTPM_USERS = 'clientpm_users';

    public static $frontendOrder = [
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
    ];

    /**
     * Retrieves the roles a user can be assigned to in the frontend
     * @return string[]
     */
    public static function getFrontendRoles(): array
    {
        $allRoles = ZfExtended_Acl::getInstance()->getAllRoles();
        $fronendRoles = [];
        foreach(static::$frontendOrder as $role){
            if(in_array($role, $allRoles)){
                $fronendRoles[] = $role;
            }
        }
        // may someone adds new roles and forgets to put them in the order here
        foreach($allRoles as $role){
            if(!in_array($role, $fronendRoles)){
                $fronendRoles[] = $role;
            }
        }
        return $fronendRoles;
    }

    /**
     * retrieves the "Sub-roles" a clientpm can have (steering the visibility of the main editor tabs)
     * @return string[]
     */
    public static function getClientPmSubroles(): array
    {
        return [
            self::CLIENTPM_PROJECTS,
            self::CLIENTPM_LANGRESOURCES,
            self::CLIENTPM_CUSTOMERS,
            self::CLIENTPM_USERS
        ];
    }
}
