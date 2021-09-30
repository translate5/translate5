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