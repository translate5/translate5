<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
namespace Core\OpenId;

use ZfExtended_ErrorCodeException;

/**
 * Open id client exception
 *
 */
class ClientException extends ZfExtended_ErrorCodeException {
    
    /**
     *
     * @var string
     */
    protected $domain = 'core.openidconnect';
    
    static protected array $localErrorCodes = [
        'E1165' => 'Error on openid authentication.',
        //the following messages are shown in the frontend, so they should not expose sensitive information:
        'E1328' => 'OpenID connect authentication is only usable with SSL/HTTPS enabled!',
        'E1329' => 'OpenID connect: The default server and the claim roles are not defined.',
        'E1330' => 'The customer server roles are empty but there are roles from the provider.',
        'E1331' => 'Invalid claims roles for the allowed server customer roles',
    ];
}