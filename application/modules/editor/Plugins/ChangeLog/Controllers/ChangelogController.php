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
 * Controller for the Plugin MatchResource configured Tmmt 
 */
	  
class editor_Plugins_ChangeLog_ChangelogController extends ZfExtended_RestController {
    protected $entityClass = 'editor_Plugins_ChangeLog_Models_Changelog';

    /**
     * @var editor_Plugins_ChangeLog_Models_Changelog
     */
    protected $entity;
    
    /**
     * @var array
     */
    protected $groupedTaskInfo = array();
    
    private $aclRoleValue = array(
    		"noRights"=>0,
    		"basic"=>1,
    		"editor"=>2,
    		"pm"=>4,
    		"admin"=>8
    );
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
        $user = new Zend_Session_Namespace('user');
        $userId = $user->data->id;
        $userGroup = $this->getUsergroup($user);

        $filter = $this->entity->getFilter();
        
        if($filter->hasFilter('loadAll', $loadAll)) {//FIXME the filter in frontend is set to a boolean type but here come as numeric
        	$changeLogArray = $this->entity->getChangelogForUserGroup($userGroup);
        	$this->view->rows = $changeLogArray;
        	$this->view->total = count($changeLogArray);
        	return ;
        }
        
        $changeLogArray = $this->entity->getChangeLogForUser($userId,$userGroup);
        
        if(!empty($changeLogArray)){
	        $lastInsertedid=max(array_column($changeLogArray, 'id'));
    	    $this->entity->updateChangelogUserInfo($userId, $lastInsertedid,$userGroup);
	        $this->view->rows = $changeLogArray;
	        $this->view->total = count($changeLogArray);
        }
    }
    
    public function postAction(){
    	throw new BadMethodCallException();
    }

    
    public function deleteAction(){
    	throw new BadMethodCallException();
    }
    
    /**
     * Generates usergroupid based on the aclRoles
     * @param Zend_Session_Namespace('user') $user
     * @return number
     */
    private function getUsergroup($user){
    	$userGroupId=0;
    	foreach($user->data->roles as $role) {
    		$userGroupId+=$this->aclRoleValue[$role];
    	}
    	return $userGroupId;
    }
      
}