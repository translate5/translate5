<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
class editor_Models_LogRequest extends ZfExtended_Models_Entity_Abstract {
    
    protected $dbInstanceClass = 'editor_Models_Db_LogRequest';

    /**
     * Adds a new log entry, save it to the db, and return the entity instance
     * @param string $taskGuid
     */
    public static function create($taskGuid) {
        $config = Zend_Registry::get('config');
        if(empty($config->runtimeOptions->requestLogging)) {
            return;
        }
        $userSession = new Zend_Session_Namespace('user');
        $inst = ZfExtended_Factory::get(__CLASS__);
        $inst->setTaskGuid($taskGuid);
        $inst->setMethod($_SERVER['REQUEST_METHOD']);
        $inst->setRequestUri($_SERVER['REQUEST_URI']);
        $inst->setParameters(print_r($_REQUEST,1));
        $inst->setAuthUserGuid($userSession->data->userGuid);
        $inst->setAuthUserLogin($userSession->data->login);
        $inst->setAuthUserName($userSession->data->userName);
        //created timestamp automatic by DB
        $inst->save();
        return $inst;
    }
}