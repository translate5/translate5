<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
     * @var array
     */
    protected $groupedTaskInfo = array();
    
    /**
     * @var array
     */
    protected $uploadErrors = array();
    
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
        
        $this->prepareTaskInfo(array_column($this->view->rows, 'id'));
        
        foreach($this->view->rows as &$tmmt) {
            $resource = $getResource($tmmt['serviceType'], $tmmt['resourceId']);
            /* @var $resource editor_Plugins_MatchResource_Models_Resource */
            if(!empty($resource)) {
                $tmmt = array_merge($tmmt, $resource->getMetaData());
            }
            
            $tmmtInstance = ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_Tmmt');
            /* @var $tmmtInstance editor_Plugins_MatchResource_Models_Tmmt */
            $tmmtInstance->init($tmmt);
            
            $tmmt['taskList'] = $this->getTaskInfos($tmmt['id']);
            $tmmt['status'] = 'loading';
        }
    }
    
    /**
     * Adds status information to the get request
     * {@inheritDoc}
     * @see ZfExtended_RestController::getAction()
     */
    public function getAction() {
        parent::getAction();
        
        $serviceManager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $serviceManager editor_Plugins_MatchResource_Services_Manager */
        
        $this->prepareTaskInfo([$this->entity->getId()]);
        $this->view->rows->taskList = $this->getTaskInfos($this->entity->getId());
        
        $resource = $serviceManager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());
        /* @var $resource editor_Plugins_MatchResource_Models_Resource */
        if(empty($resource)) {
            $this->view->rows->status = editor_Plugins_MatchResource_Services_Connector_Abstract::STATUS_NOCONNECTION;
            $this->view->rows->statusInfo = 'Configured resource not found!';
            return;
        }
        $meta = $resource->getMetaData();
        foreach($meta as $key => $v) {
            $this->view->rows->{$key} = $v;
        }
        
        $moreInfo = ''; //init as empty string, filled on demand by reference
        $connector = $serviceManager->getConnector($this->entity);
        $this->view->rows->status = $connector->getStatus($moreInfo);
        $this->view->rows->statusInfo = $moreInfo;
    }
    
    private function prepareTaskInfo($tmmtids) {
        /* @var $assocs editor_Plugins_MatchResource_Models_Taskassoc */
        $assocs = ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_Taskassoc');
        
        $taskinfo = $assocs->getTaskInfoForTmmts($tmmtids);
        if(empty($taskinfo)) {
            return;
        }
        //group array by tmmtid
        $this->groupedTaskInfo = $this->convertTasknames($taskinfo);
    }
    
    /**
     * receives a list of task and task assoc data, returns a list of taskNames grouped by tmmt
     * @param array $taskInfoList
     * @return string[]
     */
    protected function convertTasknames(array $taskInfoList) {
        $result = [];
        foreach($taskInfoList as $one) {
            if(!isset($result[$one['tmmtId']])) {
                $result[$one['tmmtId']] = array();
            }
            $taskToPrint = $one['taskName'];
            if(!empty($one['taskNr'])) {
                $taskToPrint .= ' ('.$one['taskNr'].')';
            }
            $result[$one['tmmtId']][] = $taskToPrint;
        }
        return $result;
    }

    /***
     * return array with task info (taskName's) for the given tmmtids 
     */
    private function getTaskInfos($tmmtid){
        if(empty($this->groupedTaskInfo[$tmmtid])) {
            return [];
        }
        return $this->groupedTaskInfo[$tmmtid];
    }
    
    /**
     * provides the uploaded file in a filebased TM as download
     * 
     * This method is very opentm2 specific. If we want more generalization: 
     *  - JS needs to know about the valid export types of the requested TM system
     *  - The Connector must be able to decide if a given type can be exported or not
     *    (like for uploads the getValidFiletypes, just for exports there should be a getValidExportTypes)
     */
    public function downloadAction() {
        //call GET to load entity internally
        $this->getAction();
        
        //get type from extension, the part between :ID and extension does not matter
        $type = $this->getParam('type', '.tm');
        $type = explode('.', $type);
        $type = strtoupper(end($type));
        
        $serviceManager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $serviceManager editor_Plugins_MatchResource_Services_Manager */
        
        $resource = $serviceManager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());
        
        if(! $resource->getFilebased()) {
            throw new ZfExtended_Models_Entity_NotFoundException('Requested tmmt is not filebased!');
        }
        
        $connector = $serviceManager->getConnector($this->entity);
        /* @var $connector editor_Plugins_MatchResource_Services_Connector_FilebasedAbstract */
        
        //just reuse importvalidtypes here, nothing other implemented yet 
        $validExportTypes = $connector->getValidFiletypes();
        
        if(empty($validExportTypes[$type])){
            throw new ZfExtended_NotFoundException('Can not download in format '.$type);
        }
        
        $data = $connector->getTm($validExportTypes[$type]);
        header('Content-Type: '.$validExportTypes[$type], TRUE);
        $type = '.'.strtolower($type);
        header('Content-Disposition: attachment; filename="'.rawurlencode($this->entity->getFileName()).$type.'"');
        echo $data;
        exit;
    }
    
    public function postAction(){
        $this->entity->init();
        $this->data = $this->getAllParams();
        $this->setDataInEntity($this->postBlacklist);
        
        $manager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $manager editor_Plugins_MatchResource_Services_Manager */
        $resource = $manager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());
        
        //validateLanguages prefills also the lang rfc values! So it must be called before the fileupload, 
        // since there will be communication with the underlying resource which needs the RFC values
        $validLanguages = $this->validateLanguages($resource);

        if(!$validLanguages || !$this->validate()){
            return;
        }
        //save first to generate the TMMT id
        $this->entity->save();
        
        if($resource->getFilebased()) {
            $this->handleInitialFileUpload($manager);
            //when there are errors, we cannot set it to true
            if(!$this->validateUpload()) {
                $this->entity->delete();
                return;
            }
            //save again to save changes made by the connector
            $this->entity->save();
        }
        
        $this->view->rows = $this->entity->getDataObject();
        $this->view->success = true;
    }

    /**
     * Imports an additional file which is transfered to the desired TMMT
     */
    public function importAction(){
        $this->getAction();
        
        $serviceManager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $serviceManager editor_Plugins_MatchResource_Services_Manager */
        
        $resource = $serviceManager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());
        
        if(!$resource->getFilebased()) {
            throw new ZfExtended_Models_Entity_NotFoundException('Requested tmmt is not filebased!');
        }
        
        //upload errors are handled in handleAdditionalFileUpload
        $this->handleAdditionalFileUpload($serviceManager);
        
        //when there are errors, we cannot set it to true
        $this->view->success = $this->validateUpload();
    }
    
    /**
     * Loads all task information entities for the given TMMT
     * The returned data is no real task entity, although the task model is used in the frontend!
     */
    public function tasksAction() {
        $this->getAction();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->decodePutData();
            if(!empty($this->data) && !empty($this->data->toReImport)) {
                foreach($this->data->toReImport as $taskGuid) {
                    $worker = ZfExtended_Factory::get('editor_Plugins_MatchResource_Worker');
                    /* @var $worker editor_Plugins_MatchResource_Worker */
            
                    // init worker and queue it
                    // Since it has to be done in a none worker request to have session access, we have to insert the worker before the taskPost 
                    if (!$worker->init($taskGuid, ['tmmtId' => $this->entity->getId()])) {
                        throw new ZfExtended_Exception('MatchResource ReImport Error on worker init()');
                    }
                    $worker->queue();
                }
            }
        }
        
        $assoc = ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_Taskassoc');
        /* @var $assoc editor_Plugins_MatchResource_Models_Taskassoc */
        $taskinfo = $assoc->getTaskInfoForTmmts([$this->entity->getId()]);
        //FIXME replace lockingUser guid with concrete username and show it in the frontend!
        $this->view->rows = $taskinfo;
        $this->view->total = count($taskinfo);
    }
    
    /**
     * Validates if choosen languages can be used by the choosen resource
     * Validates also the existence of the languages in the Lang DB 
     * @param editor_Plugins_MatchResource_Models_Resource $resource
     * @return boolean
     */
    protected function validateLanguages(editor_Plugins_MatchResource_Models_Resource $resource) {
        
        $sourceLang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $sourceLang editor_Models_Languages */
        $sourceLang->load($this->entity->getSourceLang());
        
        $targetLang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $targetLang editor_Models_Languages */
        $targetLang->load($this->entity->getTargetLang());
        
        $hasSourceLang = $resource->hasSourceLang($sourceLang);
        $hasTargetLang = $resource->hasTargetLang($targetLang);
        
        //cache the RF5646 language key in the TMMT since this value is used more often as our internal ID
        $this->entity->setSourceLangRfc5646($sourceLang->getRfc5646());
        $this->entity->setTargetLangRfc5646($targetLang->getRfc5646());
        
        //both languages can be dealed by the resource, all OK
        if($hasSourceLang && $hasTargetLang) {
            return true;
        }
        
        $errors = [];
        $t = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $t ZfExtended_Zendoverwrites_Translate */;;
        
        if(!$hasSourceLang) {
            $errors['sourceLang'] = $t->_('Diese Quellsprache wird von der Ressource nicht unterstützt!');
        }
        if(!$hasTargetLang) {
            $errors['targetLang'] = $t->_('Diese Zielsprache wird von der Ressource nicht unterstützt!');
        }
        
        $e = new ZfExtended_ValidateException();
        $e->setErrors($errors);
        $this->handleValidateException($e);
        return false;
    }
    
    /**
     * Uploads a file into the new TMMT
     * @param editor_Plugins_MatchResource_Services_Manager $manager
     */
    protected function handleInitialFileUpload(editor_Plugins_MatchResource_Services_Manager $manager) {
        $connector = $manager->getConnector($this->entity);
        /* @var $connector editor_Plugins_MatchResource_Services_Connector_FilebasedAbstract */
        $importInfo = $this->handleFileUpload($connector);
        
        //currently the initial upload is optional
        // if this will be depending on the resource, 
        // here would be a good place to implement the check with 
        //if(!$importInfo && $resource file is mandatory) {
            //$this->uploadErrors = "dadada"
        //}
        
        //setting the TM filename here, but can be overwritten in the connectors addTm method
        // for example when we get a new name from the service
        $this->entity->setFileName($importInfo[self::FILE_UPLOAD_NAME]['name']);
        
        if(empty($this->uploadErrors) && !$connector->addTm($importInfo[self::FILE_UPLOAD_NAME])) {
            $this->uploadErrors[] = 'Hochgeladene TM Datei konnte nicht hinzugefügt werden.';
        }
    }
    
    /**
     * Uploads an additional file into the already existing TMMT
     * @param editor_Plugins_MatchResource_Services_Manager $manager
     */
    protected function handleAdditionalFileUpload(editor_Plugins_MatchResource_Services_Manager $manager) {
        $connector = $manager->getConnector($this->entity);
        /* @var $connector editor_Plugins_MatchResource_Services_Connector_FilebasedAbstract */
        $importInfo = $this->handleFileUpload($connector);
        
        if(empty($importInfo)){
            $this->uploadErrors[] = 'Keine Datei hochgeladen!';
            return;
        }
        
        if(empty($this->uploadErrors) && !$connector->addAdditionalTm($importInfo[self::FILE_UPLOAD_NAME])) {
            $this->uploadErrors[] = 'Hochgeladene TMX Datei konnte nicht hinzugefügt werden.';
        }
    }
    
    /**
     * handles the fileupload
     * @return array|boolean meta data about the upload or false when there was no file 
     */
    protected function handleFileUpload(editor_Plugins_MatchResource_Services_Connector_FilebasedAbstract $connector) {
        $upload = new Zend_File_Transfer_Adapter_Http();
        
        //check if connector / resource can deal with the uploaded file type
        $validTypes = $connector->getValidFiletypes();
        $upload->addValidators([
            new Zend_Validate_File_MimeType(array_values($validTypes)),
            new Zend_Validate_File_Extension(array_keys($validTypes)),
        ]);
        
        //init validations
        $upload->isValid(self::FILE_UPLOAD_NAME);
        $importInfo = $upload->getFileInfo(self::FILE_UPLOAD_NAME);
        
        //checking general upload errors
        $errorNr = $importInfo[self::FILE_UPLOAD_NAME]['error'];
        
        if($errorNr === UPLOAD_ERR_NO_FILE) {
            return false;
        }
        
        if($errorNr !== UPLOAD_ERR_OK) {
            $this->uploadErrors[] = ZfExtended_FileUploadException::getErrorMessage($errorNr);
            return $importInfo;
        }
        
        //currently an error means wrong filetype
        if($upload->hasErrors()) {
            $this->uploadErrors[] = 'Die ausgewählte Ressource kann Dateien diesen Typs nicht verarbeiten!';
        }
        
        /* @var $connector editor_Plugins_MatchResource_Services_Connector_Abstract */
        if(empty($importInfo[self::FILE_UPLOAD_NAME]['size'])) {
            $this->uploadErrors[] = 'Die ausgewählte Datei war leer!';
        }
        return $importInfo;
    }
    
    /**
     * translates and transport upload errors to the frontend
     * @return boolean if there are upload errors false, true otherwise
     */
    protected function validateUpload() {
        if(empty($this->uploadErrors)){
            return true;
        }
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */;
        $errors = array(self::FILE_UPLOAD_NAME => array());
        
        foreach($this->uploadErrors as $error) {
            $errors[self::FILE_UPLOAD_NAME][] = $translate->_($error);
        }
        
        $e = new ZfExtended_ValidateException(print_r($errors, 1));
        $e->setErrors($errors);
        $this->handleValidateException($e);
        return false;
    }
    
    public function deleteAction(){
        $this->entityLoad();
        $manager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $manager editor_Plugins_MatchResource_Services_Manager */
        $connector = $manager->getConnector($this->entity);
        $deleteInResource = !$this->getParam('deleteLocally', false);
        if($deleteInResource && $connector instanceof editor_Plugins_MatchResource_Services_Connector_FilebasedAbstract) {
            $connector->delete();
        }
        $this->processClientReferenceVersion();
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
        $this->entity->checkTaskAndTmmtAccess($session->taskGuid,$tmmtId, $segment);
        
        $this->entity->load($tmmtId);

        $connector = $this->getConnector();

        $result = $connector->query($segment);
        
        $this->view->segmentId = $segment->getId(); //return the segmentId back, just for reference
        $this->view->tmmtId = $this->entity->getId();
        $this->view->rows = $result->getResult();
        $this->view->total = count($this->view->rows);
    }
    
    /**
     * performs a tmmt search
     * example URL /editor/plugins_matchresource_tmmt/14/search
     * additional POST Parameters: 
     *  query: querystring
     *  field: source or target
     *  offset: the offset from where the next search should start
     * Since the GUI is dynamically loading additional content no traditional paging can be used here
     */
    public function searchAction() {
        $session = new Zend_Session_Namespace();
        $query = $this->_getParam('query');
        $tmmtId = (int) $this->_getParam('tmmtId');
        $field = $this->_getParam('field');
        $offset = $this->_getParam('offset', null);
        
        //check provided field
        if($field !== 'source') {
            $field == 'target';
        }
        
        //checks if the current task is associated to the tmmt
        $this->entity->checkTaskAndTmmtAccess($session->taskGuid,$tmmtId);
        
        $this->entity->load($tmmtId);
        
        if(! $this->entity->getResource()->getSearchable()) {
            throw new ZfExtended_Models_Entity_NoAccessException('search requests are not allowed on this match resource');
        }
        
        $connector = $this->getConnector();
        $result = $connector->search($query, $field, $offset);
        $this->view->tmmtId = $this->entity->getId();
        $this->view->nextOffset = $result->getNextOffset();
        $this->view->rows = $result->getResult();
    }
    
    /**
     * returns the connector to be used
     * @return editor_Plugins_MatchResource_Services_Connector_Abstract
     */
    protected function getConnector() {
        $manager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $manager editor_Plugins_MatchResource_Services_Manager */
        return $manager->getConnector($this->entity);
    }
}