<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
			 https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/

class editor_Plugins_IpAuthentication_Models_IpBaseException extends ZfExtended_ErrorCodeException {
    
    /**
     * @var string
     */
    protected $domain = 'authentication.ipbased';
    
    protected static $localErrorCodes = [
        'E1289' => "Ip based authentication: Customer with number ({number}) does't exist.",
        'E1290' => "Ip based authentication: User with roles:({configuredRoles}) is not allowed to authenticate ip based."
    ];
}