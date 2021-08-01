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
 * Changelog Entity Object
 */
class editor_Models_Changelog extends ZfExtended_Models_Entity_Abstract {
    const ALL_GROUPS = 15;
    /**
     * @var string
     */
    protected $dbInstanceClass = 'editor_Models_Db_Changelog';
    
    /**
     * ACL Map
     * @var array
     */
    protected $aclRoleValue = array(
        "noRights"=>0,
        "basic"=>1,
        "editor"=>2,
        "pm"=>4,
        "admin"=>8,
        "api"=>0, //for the API role we assume just no rights for changelog reading.
    );

    /**
     * Load all changelog entries for a specific user group
     * @param int $userGroupId
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
     * @param int $userGroupId
     * @return integer
     */
    public function getTotalCount($userGroupId){
        $db = $this->db->getAdapter();
        $s = $this->db->select()->where('LEK_change_log.userGroup & '.$db->quote($userGroupId, 'INTEGER').'');
        return $this->computeTotalCount($s);
    }
    
    /**
     * Load all changelogs of a specific  
     * @param int $lastSeen
     * @param int $userGroupId
     */
    public function moreChangeLogs($lastSeen, $userGroupId){
        $s = $this->db->select()
                        ->where('LEK_change_log.id > ?', $lastSeen, Zend_Db::INT_TYPE)
                        ->where('LEK_change_log.userGroup & ?', $userGroupId, Zend_Db::INT_TYPE);
        return $this->loadFilterdCustom($s);//when there are more changelogs > $lastSeen && ('translate5.LEK_change_log.userGroup & '.$userGroupId.'');
    }
    
    /**
     * returns the highest changelog ID, optionally filtered by usergroup
     * @param int $userGroup optional
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
     * @return integer the id on which was updated
     */
    public function updateChangelogUserInfo(stdClass $userData){
        $changelogId = $this->maxChangeLogId($this->getUsergroup($userData));
        $db = $this->db->getAdapter();
        $sql = 'REPLACE INTO LEK_user_changelog_info (userId,changelogId) VALUES(?,?)';
        $db->query($sql, [$userData->id, $changelogId]);
        return $changelogId;
    }
    
    /**
     * Updates the version for all entries with an greater ID as the given ID
     * returns the rowcount of changed entries 
     * 
     * @param int $lastOldId
     * @param string $version
     * @return int 
     */
    public function updateVersion($lastOldId, $version) {
        $updated = $this->db->update([
            'version' => $version
        ], ['id > ?' => $lastOldId]);
        return $updated;
    }
    
    /**
     * returns the changelog id which the user has seen the last time, -1 if he never have seen any changelogs before
     * @param int $userId
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
     * If the user has a role not configured here, this has no influence on the bit map
     * 
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
        $configured = ZfExtended_Acl::getInstance()->getRoles();
        $used = array_keys($this->aclRoleValue);
        sort($used);
        sort($configured);
        foreach($used as $role) {
            if(!defined('ACL_ROLE_'.strtoupper($role))){
                error_log('In DB available roles ('.join(';', $configured).') does not contain all roles configured in Changelog Model ('.join(';', $used).')');
            }
        }
    }
    
    /**
     * returns the highest changelog entry id, 0 if there is no one
     * @return integer
     */
    public function getMaxId() {
        $db = $this->db;
        $select = $db->select()->from($db->info($db::NAME) , ['maxid' => 'MAX(id)']);
        $res = $select->query();
        return (int)$res->fetchObject()->maxid;
    }
}