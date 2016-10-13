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
    
    //id userGroup issue
    //1     12      TRANSLATE-XX1 → pm + admins
    //2     2      TRANSLATE-XX2  → editor only
    //3     6      TRANSLATE-XX3  → pm + editor
    
    //15 & 12;
    //usersGroups & userGroupTable
    
    //Thomas is admin (15) 1 + 2 + 4 + 8
    //Aleks is pm (7) 1 + 2 + 4
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
        //set default sort
        $f = $this->entity->getFilter();
        //default sorting by date
        $f->hasSort() || $f->addSort('dateOfChange', true);
        $userGroupId=$this->entity->getUsergroup();
        $results = $this->entity->loadAllForUser($userGroupId);
        $totalcount =$this->entity->getTotalCount($userGroupId);
        $user = new Zend_Session_Namespace('user');
        $userId = $user->data->id;
        if(!empty($results)){
            //update the user_changelog_info table for user with the latest seen changelog
            $lastInsertedid=max(array_column($results, 'id'));
            $lastChangelogFromDb=$this->entity->getLastChangelogForUserId($userId);
            if($lastInsertedid>$lastChangelogFromDb){
                $this->entity->updateChangelogUserInfo($userId, $lastInsertedid);
            }
        }
        $this->view->rows = $results;
        $this->view->total =$totalcount;
    }
    
    public function postAction(){
    	throw new BadMethodCallException();
    }

    
    public function deleteAction(){
    	throw new BadMethodCallException();
    }
}