<?php
/*
 START LICENSE AND COPYRIGHT

This file is part of translate5

Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
as published by the Free Software Foundation and appearing in the file agpl3-license.txt
included in the packaging of this file.  Please review the following information
to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
http://www.gnu.org/licenses/agpl.html

There is a plugin exception available for use with this release of translate5 for
open source applications that are distributed under a license other than AGPL:
Please see Open Source License Exception for Development of Plugins for translate5
http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
folder of translate5.

@copyright  Marc Mittag, MittagQI - Quality Informatics
@author     MittagQI - Quality Informatics
@license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Changelog Entity Object
 * 
 * 
 */
class editor_Plugins_ChangeLog_Models_Changelog extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Plugins_ChangeLog_Models_Db_Changelog';
    
    protected $validatorInstanceClass = 'editor_Plugins_ChangeLog_Models_Validator_Changelog';
    
    /***
     * This will return unlisted changelogs for user
     */
    public function getChangeLogForUser($userId,$userGroup){
    	$s = $this->db->select()
    	->from(array("cl" => "translate5.LEK_change_log"), array("cl.*"))
    	->setIntegrityCheck(false)
    	->joinLeft(array("ucl" => "translate5.LEK_user_changelog_info"),"(cl.userGroup = ucl.userGroup AND ucl.userId=".$userId.")","")
    	->where('IF(ucl.id>=0,cl.id > ucl.changelogId,1=1)')
    	->where('cl.userGroup=?',$userGroup);
    	return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Updates the LEK_user_changelog_info for user to the latest changelogId
     * @param int $userId
     * @param int $changelogId
     * @param int $userGroup
     */
    public function updateChangelogUserInfo($userId,$changelogId,$userGroup){
    	$db = $this->db->getAdapter();
    	$sql = 'REPLACE INTO LEK_user_changelog_info (userId,changelogId,userGroup) '.
    		   'VALUES('.$db->quote($userId, 'INTEGER').','.$db->quote($changelogId, 'INTEGER').','.$db->quote($userGroup, 'INTEGER').')';
    	$db->query($sql);
    }
    
    /***
     * Loads all changeLog's for userGroup
     * @param int $userGroup
     */
    public function getChangelogForUserGroup($userGroup){
    	$s = $this->db->select()
    	->from(array("cl" => "translate5.LEK_change_log"), array("cl.*"))
    	->where('cl.userGroup=?',$userGroup);
    	return $this->db->fetchAll($s)->toArray();
    }
}