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
class editor_Plugins_MatchResource_TmmtController extends ZfExtended_RestController {

    const FILE_UPLOAD_NAME = 'tmUpload';
    
    protected $entityClass = 'editor_Plugins_MatchResource_Models_TmMt';

    /**
     * @var editor_Plugins_MatchResource_Models_TmMt
     */
    protected $entity;
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     * Adds the readonly "filebased" field to the results
     */
    public function indexAction(){
        parent::indexAction();
        $serviceManager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $serviceManager editor_Plugins_MatchResource_Services_Manager */
        
        $resources = [];
        
        $getResource = function(string $serviceType, string $id) use ($resources, $serviceManager) {
            if (!empty($resources[$id])) {
                return $resources[$id];
            }
            return $resources[$id] = $serviceManager->getResourceById($serviceType, $id);
        };
        
        foreach($this->view->rows as &$tmmt) {
            $resource = $getResource($tmmt['serviceType'], $tmmt['resourceId']);
            $tmmt['filebased'] = empty($resource) ? false : $resource->getFileBased();
            $tmmt['searchable'] = empty($resource) ? false : $resource->getSearchable();
        }
    }
    
    /**
     * provides the uploaded file in a filebased TM as download
     */
    public function downloadAction() {
        $this->getAction();
        
        $serviceManager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $serviceManager editor_Plugins_MatchResource_Services_Manager */
        
        $resource = $serviceManager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());
        
        if(! $resource->getFilebased()) {
            throw new ZfExtended_Models_Entity_NotFoundException('Requested tmmt is not filebased!');
        }
        
        $connector = $serviceManager->getConnector($this->entity);
        
        
        
        // disable layout and view
        //$this->view->layout()->disableLayout();
        //$this->_helper->viewRenderer->setNoRender(true);
        $data = $connector->getTm($mime);
        header('Content-Type: '.$mime, TRUE);
        header('Content-Disposition: attachment; filename="'.rawurlencode($this->entity->getFileName()).'"');
        echo $data;
        exit;
    }
    
    public function postAction(){
        $this->entity->init();
        $this->data = $this->_getAllParams();
        $this->setDataInEntity($this->postBlacklist);
        
        $manager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $manager editor_Plugins_MatchResource_Services_Manager */
        $resource = $manager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());
        if($resource->getFilebased()) {
            $this->handleFileUpload($manager);
        }
      
        if($this->validate()){
            $this->entity->save();
            $this->view->rows = $this->entity->getDataObject();
            $this->view->success = true;
        }
    }
    
    protected function handleFileUpload(editor_Plugins_MatchResource_Services_Manager $manager) {
        $upload = new Zend_File_Transfer_Adapter_Http();
        $upload->isValid(self::FILE_UPLOAD_NAME);
        //mandatory upload file
        $importInfo = $upload->getFileInfo(self::FILE_UPLOAD_NAME);
        $connector = $manager->getConnector($this->entity);
        /* @var $connector editor_Plugins_MatchResource_Services_ConnectorAbstract */
        if(empty($importInfo['tmUpload']['size'])) {
            $this->uploadError('Die ausgewählte Datei war leer!');
        }
        //setting the TM filename here, but can be overwritten in the connectors addTm method
        // for example when we get a new name from the service
        $this->entity->setFileName($importInfo['tmUpload']['name']);
        if(!$connector->addTm($importInfo['tmUpload']['tmp_name'])) {
            $this->uploadError('Hochgeladene TM Datei konnte nicht hinzugefügt werden.');
        }
    }
    
    protected function uploadError($msg) {
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */;
        $errors = array('tmUpload' => array($translate->_($msg)));
        $e = new ZfExtended_ValidateException(print_r($errors, 1));
        $e->setErrors($errors);
        throw $e;
    }
    
    public function deleteAction(){
        $this->entityLoad();
        $manager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $manager editor_Plugins_MatchResource_Services_Manager */
        $connector = $manager->getConnector($this->entity);
        $connector->delete();
        //$this->processClientReferenceVersion();
        $this->entity->delete();
    }
    
    /**
     * performs a tmmt query
     */
    public function queryAction() {
        $session = new Zend_Session_Namespace();
        $tmmtId = (int) $this->_getParam('tmmtId');
        
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segment->load((int) $this->_getParam('segmentId'));
        
        //check taskGuid of segment against loaded taskguid for security reasons
        //checks if the current task is associated to the tmmt
        $this->checkTaskAndTmmtAccess($tmmtId, $segment);
        
        $this->entity->load($tmmtId);

        $connector = $this->getConnector();

        $result = $connector->query($segment);
        
        $this->view->segmentId = $segment->getId(); //return the segmentId back, just for reference
        $this->view->tmmtId = $this->entity->getId();
        $this->view->total = $result->getTotal();
        $this->view->rows = $result->getResult();
    }
    
    /**
     * performs a tmmt search
     * FIXME more docu here! 
     */
    public function searchAction() {
        $session = new Zend_Session_Namespace();
        $query = $this->_getParam('query');
        $tmmtId = (int) $this->_getParam('tmmtId');
        $field = $this->_getParam('field');
        
        //pagin parameters, piped through to the connector
        $page = $this->_getParam('page', 0);
        $limit = $this->_getParam('limit', 20);
        $offset = $this->_getParam('start', 0);
        
        //check provided field
        if($field !== 'source') {
            $field == 'target';
        }
        
        //checks if the current task is associated to the tmmt
        $this->checkTaskAndTmmtAccess($tmmtId);
        
        $this->entity->load($tmmtId);
        
        if(! $this->entity->getResource()->getSearchable()) {
            throw new ZfExtended_Models_Entity_NoAccessException('search requests are not allowed on this match resource');
        }
        
        $connector = $this->getConnector();
        $connector->setPaging($page, $offset, $limit);
        
        $result = $connector->search($query, $field);
        $this->view->tmmtId = $this->entity->getId();
        $this->view->total = $result->getTotal();
        $this->view->rows = $result->getResult();
    }
    
    /**
     * checks if the given tmmt (and segmentid - optional) is usable by the currently loaded task
     * @param integer $tmmtId
     * @param editor_Models_Segment $segment
     * @throws ZfExtended_Models_Entity_NoAccessException
     */
    protected function checkTaskAndTmmtAccess(integer $tmmtId, editor_Models_Segment $segment = null) {
        $session = new Zend_Session_Namespace();
        
        //checks if the queried tmmt is associated to the task:
        $tmmtTaskAssoc = ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_Taskassoc');
        /* @var $tmmtTaskAssoc editor_Plugins_MatchResource_Models_Taskassoc */
        try {
            //for security reasons a service can only be queried when a valid task association exists and this task is loaded
            // that means the user has also access to the service. If not then not!
            $tmmtTaskAssoc->loadByTaskGuidAndTm($session->taskGuid, $tmmtId);
        } catch(ZfExtended_Models_Entity_NotFoundException $e) {
            throw new ZfExtended_Models_Entity_NoAccessException(null, null, $e);
        }
        
        if(is_null($segment)) {
            return;
        }
        
        //check taskGuid of segment against loaded taskguid for security reasons
        if ($session->taskGuid !== $segment->getTaskGuid()) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
    }
    
    /**
     * returns the connector to be used
     * @return editor_Plugins_MatchResource_Services_ConnectorAbstract
     */
    protected function getConnector() {
        $manager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $manager editor_Plugins_MatchResource_Services_Manager */
        return $manager->getConnector($this->entity);
    }
}