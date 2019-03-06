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
 * LogTask Entity Objekt, used / called directly where Task and UserTask States are modified
 */
class editor_Models_LogTask extends ZfExtended_Models_Entity_Abstract {
    
    protected $dbInstanceClass = 'editor_Models_Db_LogTask';

    /**
     * Adds a new log entry, save it to the db, and return the entity instance
     * @param string $taskGuid
     * @param string $state
     * @param ZfExtended_Models_User $authenticatedUser infos about the currently logged in user, and initiator of the action
     * @param ZfExtended_Models_User $affectedUser optional, infos about the user associated to the task, this users state was changed, can be empty if state is not associated to an user
     */
    public static function create(string $taskGuid, string $state, ZfExtended_Models_User $authenticatedUser, ZfExtended_Models_User $affectedUser = null) {
        $inst = ZfExtended_Factory::get(__CLASS__);
        $inst->setTaskGuid($taskGuid);
        $inst->setState($state);
        $inst->setAuthUserGuid($authenticatedUser->getUserGuid());
        $inst->setAuthUserLogin($authenticatedUser->getLogin());
        $inst->setAuthUserName($authenticatedUser->getUserName());
        if(!empty($affectedUser)) {
            $inst->setUserGuid($affectedUser->getUserGuid());
            $inst->setUserLogin($affectedUser->getLogin());
            $inst->setUserName($affectedUser->getUserName());
        }
        //created timestamp automatic by DB
        $inst->save();
        return $inst;
    }
    
    /**
     * Adds a new log entry, save it to the db, and return the entity instance
     * @param string $taskGuid
     * @param string $message
     * @param ZfExtended_Models_User $authenticatedUser infos about the currently logged in user, and initiator of the action
     * @param ZfExtended_Models_User $affectedUser optional, infos about the user associated to the task, this users state was changed, can be empty if state is not associated to an user
     */
    public static function createWorkflow(string $taskGuid, string $message, ZfExtended_Models_User $authenticatedUser, ZfExtended_Models_User $affectedUser = null) {
        $inst = ZfExtended_Factory::get(__CLASS__);
        $inst->setTaskGuid($taskGuid);
        $inst->setState('WORKFLOW');
        $inst->setMessage($message);
        $inst->setAuthUserGuid($authenticatedUser->getUserGuid());
        $inst->setAuthUserLogin($authenticatedUser->getLogin());
        $inst->setAuthUserName($authenticatedUser->getUserName());
        if(!empty($affectedUser)) {
            $inst->setUserGuid($affectedUser->getUserGuid());
            $inst->setUserLogin($affectedUser->getLogin());
            $inst->setUserName($affectedUser->getUserName());
        }
        //created timestamp automatic by DB
        $inst->save();
        return $inst;
    }
    
    /**
     * Adds a new log entry, save it to the db, and return the entity instance
     * @param string $taskGuid
     * @param string $state
     * @param string $authenticatedUserGuid
     * @param string $affectedUserGuid optional, can be empty if state is not user associated
     */
    public static function createWithUserGuid(string $taskGuid, string $state, string $authenticatedUserGuid, $affectedUserGuid = null) {
        $authUser = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $authUser ZfExtended_Models_User */
        $authUser->loadByGuid($authenticatedUserGuid);
        if(empty($affectedUserGuid)){
            $user = null;
        } else {
            $user = ZfExtended_Factory::get('ZfExtended_Models_User');
            /* @var $user ZfExtended_Models_User */
            $user->loadByGuid($affectedUserGuid);
        }
        return self::create($taskGuid,$state,$authUser,$user);
    }
}