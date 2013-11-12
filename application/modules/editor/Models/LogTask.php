<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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