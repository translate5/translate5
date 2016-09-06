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
    	//folow the modefy tmmt_model
    protected $entityClass = 'editor_Plugins_ChangeLog_Models_Changelog';

    /**
     * @var editor_Plugins_ChangeLog_Models_Changelog
     */
    protected $entity;
    
    /**
     * @var array
     */
    protected $groupedTaskInfo = array();
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
        $user = new Zend_Session_Namespace('user');
        $userId = $user->data->id;
        
        $changeLogArray = $this->entity->getChangeLogForUser($userId);
        
        if(!empty($changeLogArray)){
	        $lastInsertedid=max(array_column($changeLogArray, 'id'));
    	    $this->entity->updateChangelogUserInfo($userId, $lastInsertedid);
	        $this->view->rows = $changeLogArray;
	        $this->view->total = count($changeLogArray);
        }
        
        //$this->view->rows = $this->entity->loadAll();
    }
    
    private function prepareTaskInfo() {
    	/* @var $assocs editor_Plugins_MatchResource_Models_Taskassoc */
    	$assocs = ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_Taskassoc');
    
    	$tmmtids = array_column($this->view->rows, 'id');
    
    	$taskinfo = $assocs->getTaskInfoForTmmts($tmmtids);
    	if(empty($taskinfo)) {
    		return;
    	}
    	//group array by tmmtid
    	$this->groupedTaskInfo = array();
    	foreach($taskinfo as $one) {
    		if(!isset($this->groupedTaskInfo[$one['tmmtId']])) {
    			$this->groupedTaskInfo[$one['tmmtId']] = array();
    		}
    		$taskToPrint = $one['taskName'];
    		if(!empty($one['taskNr'])) {
    			$taskToPrint .= ' ('.$one['taskNr'].')';
    		}
    		$this->groupedTaskInfo[$one['tmmtId']][] = $taskToPrint;
    	}
    }
    
    /***
     * return array with task info (taskName's) for the given tmmtids
     */
    private function getTaskInfos($tmmtid){
    	if(empty($this->groupedTaskInfo[$tmmtid])) {
    		return null;
    	}
    	return $this->groupedTaskInfo[$tmmtid];
    }
    
    public function postAction(){
    	$this->entity->init();
    	$this->data = $this->_getAllParams();
    	$this->setDataInEntity($this->postBlacklist);
    
    }

    
    public function deleteAction(){
    	$this->entityLoad();
    	$this->entity->delete();
    }
    
      
}