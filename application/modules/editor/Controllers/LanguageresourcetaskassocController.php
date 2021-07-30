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
 * Controller for the LanguageResources Associations
 */
class editor_LanguageresourcetaskassocController extends ZfExtended_RestController {

    protected $entityClass = 'editor_Models_LanguageResources_Taskassoc'; //→ _Taskassoc

    /**
     * @var editor_Models_LanguageResources_Taskassoc
     */
    protected $entity;
    
    /**
     * ignoring ID field for POST Requests
     * @var array
     */
    protected $postBlacklist = array('id');
    
    public function init() {
        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1050' => 'Referenced language resource not found.',
            'E1051' => 'Cannot remove language resource from task since task is used at the moment.',
        ], 'editor.languageresource.taskassoc');
        parent::init();
    }
    
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
        
        $result=$this->entity->getAssocTasksWithResources($taskGuid->value);
        
        $this->view->rows = $result;
        $this->view->total = count($result);
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function postAction(){
        try {
            parent::postAction();
            $this->fireAfterAssocChangeEvent('post' ,$this->entity);
        }
        catch(Zend_Db_Statement_Exception $e){
            $m = $e->getMessage();
            //duplicate entries are OK, since the user tried to create it
            if(strpos($m,'SQLSTATE') !== 0 || stripos($m,'Duplicate entry') === false) {
                throw $e;
            }
            //but we have to load and return the already existing duplicate 
            $this->entity->loadByTaskGuidAndTm($this->data->taskGuid, $this->data->languageResourceId);
            $this->view->rows = $this->entity->getDataObject();
        }
    }
    
    public function deleteAction(){
        try {
            $this->entityLoad();
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            if($task->isUsed($this->entity->getTaskGuid())) {
                throw ZfExtended_Models_Entity_Conflict::createResponse('E1050',[
                    'Die Aufgabe wird bearbeitet, die Sprachressource kann daher im Moment nicht von der Aufgabe entfernt werden!'
                ]);
            }
            $clone=clone $this->entity;
            $this->entity->delete();
            $this->fireAfterAssocChangeEvent('delete' ,$clone);
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            //do nothing since it was already deleted, and thats ok since user tried to delete it
        }
    }
    
    /**
     * does some prechecking of the data
     * {@inheritDoc}
     * @see ZfExtended_RestController::decodePutData()
     */
    protected function decodePutData() {
        parent::decodePutData();
        
        //this flag may not be set via API
        unset($this->data->autoCreatedOnImport);
        
        $languageresource = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $languageresource editor_Models_LanguageResources_LanguageResource */
        try{
            $languageresource->load($this->data->languageResourceId);
        }
        catch(ZfExtended_NotFoundException $e) {
            throw ZfExtended_Models_Entity_Conflict::createResponse('E1050', [
                'languageResourceId' => 'Die gewünschte Sprachressource gibt es nicht!'
            ],['languageresourceId' => $this->data->languageResourceId]);
        }
        $resource = $languageresource->getResource();
        
        //segments can only be updated when resource is writable:
        $this->data->segmentsUpdateable = $resource->getWritable() && $this->data->segmentsUpdateable;
    }
    
    /***
     * Fire after post/delete special event with language resources service name in it.
     * The event and the service name will be separated with #
     * ex: afterPost#OpenTM2
     *     afterDelete#TermCollection
     *     
     * @param string $action
     * @param editor_Models_LanguageResources_Taskassoc
     * @return editor_Models_LanguageResources_LanguageResource
     */
    protected function fireAfterAssocChangeEvent($action,editor_Models_LanguageResources_Taskassoc $entity): editor_Models_LanguageResources_LanguageResource{
        $lr = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $lr editor_Models_LanguageResources_LanguageResource */
        $lr->load($entity->getLanguageResourceId());
        
        //fire event with name of the saved language resource service name
        //separate with # so it is more clear that is is not regular after/before action event
        //ex: afterPost#OpenTM2
        $eventName="after".ucfirst($action).'#'.$lr->getServiceName();
        $this->events->trigger($eventName, $this, array(
            'entity' => $entity,
        ));
        return $lr;
    }
}
