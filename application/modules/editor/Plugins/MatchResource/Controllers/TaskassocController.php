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
 * Controller for the Plugin MatchResource Associations
 */
class editor_Plugins_MatchResource_TaskassocController extends ZfExtended_RestController {

    protected $entityClass = 'editor_Plugins_MatchResource_Models_Taskassoc'; //→ _Taskassoc

    /**
     * @var editor_Plugins_MatchResource_Models_Taskassoc
     */
    protected $entity;
    
    /**
     * ignoring ID field for POST Requests
     * @var array
     */
    protected $postBlacklist = array('id');
    
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
        $filter = $this->entity->getFilter();
        if(!$filter->hasFilter('taskGuid', $taskGuid)) { //handle the rest default case
            $this->view->rows = $this->entity->loadAll();
            $this->view->total = $this->entity->getTotalCount();
            return;
        }
        $serviceManager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $serviceManager editor_Plugins_MatchResource_Services_Manager */
        
        $resources = [];
        
        $getResource = function(string $serviceType, string $id) use ($resources, $serviceManager) {
            if (!empty($resources[$id])) {
                return $resources[$id];
            }
            return $resources[$id] = $serviceManager->getResourceById($serviceType, $id);
        };
        
        $reval = $this->entity->loadByAssociatedTaskAndLanguage($taskGuid->value);
        
        foreach($reval as &$tmmt) {
            $resource = $getResource($tmmt['serviceType'], $tmmt['resourceId']);
            $tmmt['searchable'] = empty($resource) ? false : $resource->getSearchable();
        }
        
        $this->view->rows = $reval;
        $this->view->total = count($reval);
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function postAction(){
        try {
            parent::postAction();
        }
        catch(Zend_Db_Statement_Exception $e){
            $m = $e->getMessage();
            //duplicate entries are OK, since the user tried to create it
            if(strpos($m,'SQLSTATE') !== 0 || stripos($m,'Duplicate entry') === false) {
                throw $e;
            }
            //but we have to load and return the already existing duplicate 
            $this->entity->loadByTaskGuidAndTm($this->data->taskGuid, $this->data->tmmtId);
            $this->view->rows = $this->entity->getDataObject();
        }
    }
    
    public function deleteAction(){
        try {
            $this->entityLoad();
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            if($task->isUsed($this->entity->getTaskGuid())) {
                throw new ZfExtended_VersionConflictException("Die Aufgabe wird bearbeitet, die Matchressource kann daher im Moment nicht gelöscht werden!");
            }
            $this->entity->delete();
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            //do nothing since it was already deleted, and thats ok since user tried to delete it
        }
    }
}