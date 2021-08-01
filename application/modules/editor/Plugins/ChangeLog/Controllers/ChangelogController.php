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
 * Controller for the Plugin ChangeLog configured LanguageResource 
 */
	  
class editor_Plugins_ChangeLog_ChangelogController extends ZfExtended_RestController {
    protected $entityClass = 'editor_Models_Changelog';

    /**
     * @var editor_Models_Changelog
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
        
        $user = new Zend_Session_Namespace('user');
        //user group bit map of the authenticated user
        $userGroup = $this->entity->getUsergroup($user->data);
        
        $results = $this->entity->loadAllForUser($userGroup);
        $totalcount =$this->entity->getTotalCount($userGroup);
        
        //when there are results and the id filter was set, the load was triggered automatically
        if(!empty($results) && $this->entity->getFilter()->hasFilter('id')){
            $userId = $user->data->id;
            //update always the last seen changelog id, since when this page is called,
            // the user has seen the changelogs, so no calculation of the id or so is needed 
            $lastSeen = $this->entity->updateChangelogUserInfo($user->data);
            
            //since we dont use metaData otherwise, we can overwrite it completly:
            $this->view->metaData = new stdClass();
            $this->view->metaData->lastSeenChangelogId = $lastSeen;
        }
        $this->view->rows = $results;
        $this->view->total = $totalcount;
    }
    
    public function postAction(){
    	throw new BadMethodCallException();
    }

    
    public function deleteAction(){
    	throw new BadMethodCallException();
    }
}