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

/**
 */
class editor_Models_ConfigException extends ZfExtended_ErrorCodeException {
    /**
     * @var string
     */
    protected $domain = 'core.configuration';
    
    /**
     * @var integer
     */
    protected $httpReturnCode = 422;
    
    protected static $localErrorCodes = [
        'E1292' => 'Not enough rights to modify config with level : {level}',
        'E1296' => 'Unable to modify config {name}. The task is not in import state.',
        'E1297'=> 'Unable to load task config. "taskGuid" is not set for this entity.',
        'E1298'=> 'Unable to load task customer config. "customerId" not set for this entity.',
        'E1299'=> 'Not allowed to load user config for different user.',
        'E1324'=>'Updated config with name "{name}" to "{value}"'
    ];
}