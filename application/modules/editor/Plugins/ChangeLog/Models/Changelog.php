<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
			 http://www.gnu.org/licenses/agpl.html

END LICENSE AND COPYRIGHT
*/

/**
 * Changelog Entity Object
 * 
 * 
 */
class editor_Plugins_ChangeLog_Models_Changelog extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Plugins_ChangeLog_Models_Db_Changelog';
    protected $aclRoleValue = array(
        "noRights"=>0,
        "basic"=>1,
        "editor"=>2,
        "pm"=>4,
        "admin"=>8
    );

    public function loadAllForUser($userGroupId) {
        $db = $this->db->getAdapter();
        //adopt loadAll here with uiserGroup check
        $s = $this->db->select()->where('LEK_change_log.userGroup & '.$db->quote($userGroupId, 'INTEGER').'');
        return $this->loadFilterdCustom($s);
    }
    
    public function getTotalCount($userGroupId){
        $db = $this->db->getAdapter();
        $s = $this->db->select()->where('LEK_change_log.userGroup & '.$db->quote($userGroupId, 'INTEGER').'');
        return $this->computeTotalCount($s);
    }
    
    
    public function moreChangeLogs($lastSeen, $userGroupId){
        $db = $this->db->getAdapter();
        $s = $this->db->select()
                        ->where('LEK_change_log.id > '.$db->quote($lastSeen, 'INTEGER').'')
                        ->where('LEK_change_log.userGroup & '.$db->quote($userGroupId, 'INTEGER').'');
        return $this->loadFilterdCustom($s);//when there are more changelogs > $lastSeen && ('translate5.LEK_change_log.userGroup & '.$userGroupId.'');
    }
    
    /***
     * Updates the LEK_user_changelog_info for user to the latest changelogId
     * @param int $userId
     * @param int $changelogId
     */
    public function updateChangelogUserInfo($userId,$changelogId){
    	$db = $this->db->getAdapter();
    	$sql = 'REPLACE INTO LEK_user_changelog_info (userId,changelogId) '.
    		   'VALUES('.$db->quote($userId, 'INTEGER').','.$db->quote($changelogId, 'INTEGER').')';
    	$db->query($sql);
    }
    
    public function getLastChangelogForUserId($userId){
        $s = $this->db->select()
        ->from(array("cli" => "LEK_user_changelog_info"), array("cli.changelogId"))
        ->setIntegrityCheck(false)
        ->where('cli.userId=?',$userId);
        
        $retval=$this->db->fetchAll($s)->toArray();
        
        if(empty($retval)){
            return -1;
        }
        
        return $retval[0]['changelogId'];
    }
    /**
     * Generates usergroupid based on the aclRoles
     * @return number
     */
    public function getUsergroup(){
        $this->checkGroups();
        $user = new Zend_Session_Namespace('user');
        $userGroupId=0;
        foreach($user->data->roles as $role) {
            if(isset($this->aclRoleValue[$role])) {
                $userGroupId+=$this->aclRoleValue[$role];
            }
        }
        return $userGroupId;
    }
    
    protected function checkGroups() {
        $aclConfig = ZfExtended_Acl::getInstance()->_aclConfigObject->toArray();
        $configured = array_values($aclConfig['roles']);
        $used = array_keys($this->aclRoleValue);
        sort($used);
        sort($configured);
        if($used != $configured) {
            throw new ZfExtended_Exception('In aclConfig.ini configured roles ('.join(';', $configured).') are not equal to the roles configured in Changelog Model ('.join(';', $used).')');
        }
    }
}