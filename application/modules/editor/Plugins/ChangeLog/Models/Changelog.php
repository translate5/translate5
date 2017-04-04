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
    /**
     * @var string
     */
    protected $dbInstanceClass = 'editor_Plugins_ChangeLog_Models_Db_Changelog';
    
    /**
     * ACL Map
     * @var array
     */
    protected $aclRoleValue = array(
        "noRights"=>0,
        "basic"=>1,
        "editor"=>2,
        "pm"=>4,
        "admin"=>8
    );

    /**
     * Load all changelog entries for a specific user group
     * @param integer $userGroupId
     * @return array
     */
    public function loadAllForUser($userGroupId) {
        $db = $this->db->getAdapter();
        //adopt loadAll here with uiserGroup check
        $s = $this->db->select()->where('LEK_change_log.userGroup & '.$db->quote($userGroupId, 'INTEGER').'');
        return $this->loadFilterdCustom($s);
    }
    
    /**
     * Get total count of changelog entries for a specific user group
     * @param integer $userGroupId
     * @return integer
     */
    public function getTotalCount($userGroupId){
        $db = $this->db->getAdapter();
        $s = $this->db->select()->where('LEK_change_log.userGroup & '.$db->quote($userGroupId, 'INTEGER').'');
        return $this->computeTotalCount($s);
    }
    
    /**
     * Load all changelogs of a specific  
     * @param integer $lastSeen
     * @param integer $userGroupId
     */
    public function moreChangeLogs($lastSeen, $userGroupId){
        $s = $this->db->select()
                        ->where('LEK_change_log.id > ?', $lastSeen, Zend_Db::INT_TYPE)
                        ->where('LEK_change_log.userGroup & ?', $userGroupId, Zend_Db::INT_TYPE);
        return $this->loadFilterdCustom($s);//when there are more changelogs > $lastSeen && ('translate5.LEK_change_log.userGroup & '.$userGroupId.'');
    }
    
    /**
     * returns the highest changelog ID, optionally filtered by usergroup
     * @param integer $userGroup optional
     * @return integer
     */
    protected function maxChangeLogId($userGroup = null) {
        $db = $this->db;
        $s = $db->select()
                    ->from($db->info($db::NAME), ['maxid' => 'MAX(id)']);
        
        if(!empty($userGroup)) {
            $s->where('LEK_change_log.userGroup & ?', $userGroup, Zend_Db::INT_TYPE);
        }
        
        $result = $this->db->fetchAll($s)->toArray();
        return $result[0]['maxid'];
    }
    
    /***
     * Saves to one user which changelog id he has seen last, the saved id is returned
     * @param int $userId
     * @param int $changelogId
     * @return the id on which was updated
     */
    public function updateChangelogUserInfo(stdClass $userData){
        $changelogId = $this->maxChangeLogId($this->getUsergroup($userData));
        $db = $this->db->getAdapter();
        $sql = 'REPLACE INTO LEK_user_changelog_info (userId,changelogId) VALUES(?,?)';
        $db->query($sql, [$userData->id, $changelogId]);
        return $changelogId;
    }
    
    /**
     * returns the changelog id which the user has seen the last time, -1 if he never have seen any changelogs before
     * @param integer $userId
     * @return integer
     */
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
     * Generates usergroup bit map based on the aclRoles of the user
     * @param stdClass $userData
     * @return integer
     */
    public function getUsergroup(stdClass $userData){
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