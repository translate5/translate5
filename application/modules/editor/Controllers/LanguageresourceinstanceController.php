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

/***
 * Language resource controller
 */
class editor_LanguageresourceinstanceController extends ZfExtended_RestController {

    const FILE_UPLOAD_NAME = 'tmUpload';
    
    protected $entityClass = 'editor_Models_LanguageResources_LanguageResource';

    /**
     * @var editor_Models_LanguageResources_LanguageResource
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
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTag;
    
    public function init() {
        //add filter type for languages
        $finalTableForAssoc = new ZfExtended_Models_Filter_Join('LEK_customer', 'name', 'id', 'customerId');
        $this->_filterTypeMap = [
            'sourceLang' => ['string' => 'list'],
            'targetLang' => ['string' => 'list'],
            'resourcesCustomers' => [
                'string' => new ZfExtended_Models_Filter_JoinAssoc('LEK_languageresources_customerassoc', $finalTableForAssoc, 'languageResourceId', 'id')
            ]
        ];
        
        //set same join for sorting!
        $this->_sortColMap['resourcesCustomers'] = $this->_filterTypeMap['resourcesCustomers']['string'];
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        parent::init();
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     * Adds the readonly "filebased" field to the results
     */
    public function indexAction(){
        //add custom filters
        $this->handleFilterCustom();
        
        $this->view->rows =$this->entity->loadAllByServices();
        $this->view->total =$this->entity->getTotalCount();
        
        $serviceManager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $serviceManager editor_Services_Manager */
        
        $resources = [];
        
        $getResource = function(string $serviceType, string $id) use ($resources, $serviceManager) {
            if (!empty($resources[$id])) {
                return $resources[$id];
            }
            return $resources[$id] = $serviceManager->getResourceById($serviceType, $id);
        };
        
        $languageResourcesId=array_column($this->view->rows, 'id');
        $this->prepareTaskInfo($languageResourcesId);
        
        $eventLogger=ZfExtended_Factory::get('editor_Models_Logger_LanguageResources');
        /* @var $eventLogger editor_Models_Logger_LanguageResources */
        $eventLoggerGroupped=$eventLogger->getEventsCountGruped($languageResourcesId);
        
        //get all assocs grouped by language resource id
        $customerAssocModel=ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $customerAssocModel editor_Models_LanguageResources_CustomerAssoc */
        $custAssoc=$customerAssocModel->loadCustomerIdsGrouped();
        
        $languages=ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
        /* @var $languages editor_Models_LanguageResources_Languages */
        $languages=$languages->loadResourceIdsGrouped();
        
        $t = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $t ZfExtended_Zendoverwrites_Translate */
        
        foreach($this->view->rows as &$languageresource) {
            $resource = $getResource($languageresource['serviceType'], $languageresource['resourceId']);
            /* @var $resource editor_Models_LanguageResources_Resource */
            if(!empty($resource)) {
                $languageresource = array_merge($languageresource, $resource->getMetaData());
            }
            
            $languageResourceInstance = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
            /* @var $languageResourceInstance editor_Models_LanguageResources_LanguageResource */
            $languageResourceInstance->init($languageresource);
            
            $languageresource['taskList'] = $this->getTaskInfos($languageresource['id']);
            $moreInfo = '';
            $languageresource['status'] = $resource->getInitialStatus($moreInfo);
            $languageresource['statusInfo'] = $t->_($moreInfo);
            
            $id = $languageresource['id'];
            //add customer assocs
            $languageresource['resourcesCustomers'] = $this->getCustassoc($custAssoc, 'customerId', $id);
            $languageresource['useAsDefault'] = $this->getCustassocDefault($custAssoc, 'useAsDefault', $id);
            $languageresource['sourceLang'] = $this->getLanguage($languages, 'sourceLang', $id);
            $languageresource['targetLang'] = $this->getLanguage($languages, 'targetLang', $id);
            //TODO: move me to the frontend, and also to the get action
            $languageresource['eventsCount'] = isset($eventLoggerGroupped[$id]) ? (integer)$eventLoggerGroupped[$id] : 0;
        }
    }
    
    /**
     * Retrieves specific language from the given language container
     * @param array $data
     * @param string $index the datafield to get
     * @param int $id the language resource id 
     * @return array
     */
    protected function getLanguage(array $languages, $index, $id) {
        if(empty($languages[$id]) || empty($languages[$id][$index])){
            return  [];
        }
        return $languages[$id][$index];
    }
    
    /**
     * Retrieves specific data from the given data container
     * @param array $data
     * @param string $index the datafield to get
     * @param int $id the language resource id 
     * @return array
     */
    protected function getCustassoc(array $data, $index, $id) {
        if(empty($data[$id])){
            return [];
        }
        //remove 0 and null values
        return array_filter(array_column($data[$id], $index));
    }
    
    /***
     * Retrives the useAsDefault customers for the given language resource
     * @param array $data
     * @param string $index the datafield to get
     * @param int $id the language resource id 
     * @return array
     */
    protected function getCustassocDefault(array $data, $index, $id){
        if(empty($data[$id])){
            return [];
        }
        //get the useAsDefault array indexes
        $default=$this->getCustassoc($data, $index, $id);
        $customerIds=[];
        //get the customer ids for those array indexes
        foreach ($default as $key=>$value){
            $customerIds[]=$data[$id][$key]['customerId'];
        }
        return $customerIds;
    }
    
    /**
     * Adds status information to the get request
     * {@inheritDoc}
     * @see ZfExtended_RestController::getAction()
     */
    public function getAction() {
        parent::getAction();
        $serviceManager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $serviceManager editor_Services_Manager */
        
        $this->prepareTaskInfo([$this->entity->getId()]);
        $this->view->rows->taskList = $this->getTaskInfos($this->entity->getId());

        //load associated customers to the resource
        $customerAssoc=ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */
        
        $assocs=$customerAssoc->loadByLanguageResourceId($this->entity->getId());
        
        $default=[];
        $customers=[];
        //get the customers and the default customers
        foreach ($assocs as $value) {
            if(!empty($value['useAsDefault'])){
                $default[]=$value['customerId'];
            }
            if(!empty($value['customerId'])){
                $customers[]=$value['customerId'];
            }
        }
        $this->view->rows->resourcesCustomers=$customers;
        $this->view->rows->useAsDefault=$default;
        
        $t = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $t ZfExtended_Zendoverwrites_Translate */
        
        $resource = $serviceManager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());
        /* @var $resource editor_Models_LanguageResources_Resource */
        if(empty($resource)) {
            $this->view->rows->status = editor_Services_Connector_Abstract::STATUS_NOCONNECTION;
            $this->view->rows->statusInfo = $t->_('Keine Verbindung zur Ressource oder Ressource nicht gefunden.');
            return;
        }
        $meta = $resource->getMetaData();
        foreach($meta as $key => $v) {
            $this->view->rows->{$key} = $v;
        }
        
        $eventLogger=ZfExtended_Factory::get('editor_Models_Logger_LanguageResources');
        /* @var $eventLogger editor_Models_Logger_LanguageResources */
        $eventLoggerGroupped=$eventLogger->getEventsCountGruped([$this->entity->getId()]);
        $this->view->rows->eventsCount = isset($eventLoggerGroupped[$this->entity->getId()]) ? (integer)$eventLoggerGroupped[$this->entity->getId()] : 0;
        
        $moreInfo = ''; //init as empty string, filled on demand by reference
        $connector = $serviceManager->getConnector($this->entity);
        $this->view->rows->status = $connector->getStatus($moreInfo);
        $this->view->rows->statusInfo = $t->_($moreInfo);
    }
    
    /***
     * Handle custom filtering in source,target,taskList and resourcesCustomers.
     * The filters are extended so thay can filter using string values.
     */
    protected function handleFilterCustom(){
        $sourceFilter=null;
        $targetFilter=null;
        $useAsDefault=null;
        $taskList=null;
        
        $this->entity->getFilter()->hasFilter('sourceLang',$sourceFilter);
        $this->entity->getFilter()->hasFilter('targetLang',$targetFilter);
        
        $this->entity->getFilter()->hasFilter('useAsDefault',$useAsDefault);
        $this->entity->getFilter()->hasFilter('taskList',$taskList);
        
        //search the model for the filter value and set the filter value with the found matches(ids)
        $searchEntity=function($searchValue,$model,$field='id'){
            
            if(is_array($searchValue)){
                return $searchValue;
            }
            
            //search the model for the given search string
            $m=ZfExtended_Factory::get($model);
            $result=$m->search($searchValue,[$field]);
            
            //collect the found $fields in the searched model
            $ids=array_column($result,$field);
            //return the result, if now results are found->return -1 as array (this will produce no result in the filter)
            return !empty($ids)?$ids:[-1];
        };
        
        //create an languageResources id filter, from the found results in the searched entity
        $handleFilter=function($filter,$resultList,$assocModel,$assocFunction,$assocField){
            //init the filter
            $idFilter=new stdClass();
            $idFilter->type='list';
            $idFilter->field='id';
            $idFilter->table='LEK_languageresources';
            $idFilter->comparison='in';
            
            //if no ids are found, set the filter so no results are returned
            if(empty($resultList)){
                //remove the filter since the colum does not exist in the table
                $this->entity->getFilter()->deleteFilter($filter->field);
                $idFilter->value=[-1];
                $this->entity->getFilter()->addFilter($idFilter);
                return;
            }
            
            //for all matching results find the assoc ids
            $m=ZfExtended_Factory::get($assocModel);
            $result=$m->$assocFunction($resultList);
            
            //if no language resources for the customers are found, set the filter
            if(empty($result)){
                //remove the filter since the colum does not exist in the table
                $this->entity->getFilter()->deleteFilter($filter->field);
                $idFilter->value=[-1];
                $this->entity->getFilter()->addFilter($idFilter);
                return;
            }
            
            //for each results, get the assoc field
            $resids=array_column($result,$assocField);;
            
            $resids=array_unique($resids);
            
            //set the found values to the filter value, and apply the filter
            $idFilter->value=!empty($resids)?$resids:[-1];
            $this->entity->getFilter()->addFilter($idFilter);
            
            //remove the filter since the colum does not exist in the table
            $this->entity->getFilter()->deleteFilter($filter->field);
        };
        
        //check and handle the sourceLang filter
        if(isset($sourceFilter)){
            $resultList=$searchEntity($sourceFilter->value,'editor_Models_Languages');
            $handleFilter($sourceFilter,$resultList,'editor_Models_LanguageResources_Languages','loadBySourceLangIds','languageResourceId');
        }
        
        //check and handle the targetLang filter
        if(isset($targetFilter)){
            $resultList=$searchEntity($targetFilter->value,'editor_Models_Languages');
            $handleFilter($targetFilter,$resultList,'editor_Models_LanguageResources_Languages','loadByTargetLangIds','languageResourceId');
        }
        
        //check if filtering for useAsDefault should be done
        if(isset($useAsDefault) && isset($useAsDefault->value) && is_string($useAsDefault->value)){
            $resultList=$searchEntity($useAsDefault->value,'editor_Models_Customer');
            $handleFilter($useAsDefault,$resultList,'editor_Models_LanguageResources_CustomerAssoc','loadByCustomerIdsDefault','languageResourceId');
        }
        
        //check if filtering for taskList should be done
        if(isset($taskList) && isset($taskList->value) && is_string($taskList->value)){
            $resultList=$searchEntity($taskList->value,'editor_Models_Task','taskGuid');
            $handleFilter($taskList,$resultList,'editor_Models_LanguageResources_Taskassoc','loadByTaskGuids','languageResourceId');
        }
    }
    
    
    /**
     * returns the logged events for the given language resource
     */
    public function eventsAction() {
        $this->getAction();
        $events = ZfExtended_Factory::get('editor_Models_Logger_LanguageResources');
        /* @var $events editor_Models_Logger_LanguageResources */
        
        //filter and limit for events entity
        $offset = $this->_getParam('start');
        $limit = $this->_getParam('limit');
        settype($offset, 'integer');
        settype($limit, 'integer');
        $events->limit(max(0, $offset), $limit);
        
        $filter = ZfExtended_Factory::get($this->filterClass,array(
            $events,
            $this->_getParam('filter')
        ));
        
        $filter->setSort($this->_getParam('sort', '[{"property":"id","direction":"DESC"}]'));
        $events->filterAndSort($filter);
        
        $this->view->rows = $events->loadByLanguageResourceId($this->entity->getId());
        $this->view->total = $events->getTotalByLanguageResourceId($this->entity->getId());
    }
    
    private function prepareTaskInfo($languageResourceids) {
        /* @var $assocs editor_Models_LanguageResources_Taskassoc */
        $assocs = ZfExtended_Factory::get('editor_Models_LanguageResources_Taskassoc');
        
        $taskinfo = $assocs->getTaskInfoForLanguageResources($languageResourceids);
        if(empty($taskinfo)) {
            return;
        }
        //group array by languageResourceid
        $this->groupedTaskInfo = $this->convertTasknames($taskinfo);
    }
    
    /**
     * receives a list of task and task assoc data, returns a list of taskNames grouped by languageResource
     * @param array $taskInfoList
     * @return string[]
     */
    protected function convertTasknames(array $taskInfoList) {
        $result = [];
        foreach($taskInfoList as $one) {
            if(!isset($result[$one['languageResourceId']])) {
                $result[$one['languageResourceId']] = array();
            }
            $taskToPrint = $one['taskName'];
            if(!empty($one['taskNr'])) {
                $taskToPrint .= ' ('.$one['taskNr'].')';
            }
            $result[$one['languageResourceId']][] = $taskToPrint;
        }
        return $result;
    }

    /***
     * return array with task info (taskName's) for the given languageResourceids 
     */
    private function getTaskInfos($languageResourceid){
        if(empty($this->groupedTaskInfo[$languageResourceid])) {
            return [];
        }
        return $this->groupedTaskInfo[$languageResourceid];
    }
    
    /**
     * provides the uploaded file in a filebased TM as download
     * 
     * This method is very opentm2 specific. If we want more generalization: 
     *  - JS needs to know about the valid export types of the requested TM system
     *  - The Connector must be able to decide if a given type can be exported or not
     */
    public function downloadAction() {
        //call GET to load entity internally
        $this->getAction();
        
        //get type from extension, the part between :ID and extension does not matter
        $type = $this->getParam('type', '.tm');
        $type = explode('.', $type);
        $type = strtoupper(end($type));
        
        $serviceManager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $serviceManager editor_Services_Manager */
        
        $resource = $serviceManager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());
        
        if(! $resource->getFilebased()) {
            throw new ZfExtended_Models_Entity_NotFoundException('Requested languageResource is not filebased!');
        }
        
        $connector = $serviceManager->getConnector($this->entity);
        /* @var $connector editor_Services_Connector */
        
        $validExportTypes = $connector->getValidExportTypes();
        
        if(empty($validExportTypes[$type])){
            throw new ZfExtended_Models_Entity_NotFoundException('Can not download in format '.$type);
        }
        
        $data = $connector->getTm($validExportTypes[$type]);
        header('Content-Type: '.$validExportTypes[$type], TRUE);
        $type = '.'.strtolower($type);
        header('Content-Disposition: attachment; filename="'.rawurlencode($this->entity->getName()).$type.'"');
        echo $data;
        exit;
    }
    
    public function postAction(){
        $this->entity->init();
        $this->data = $this->getAllParams();
        $this->setDataInEntity($this->postBlacklist);
        
        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */
        $resource = $manager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());
        
        //validateLanguages prefills also the lang rfc values! So it must be called before the fileupload, 
        // since there will be communication with the underlying resource which needs the RFC values
        $validLanguages = $this->validateLanguages($resource);
        
        if(!$validLanguages || !$this->validate()){
            return;
        }
        //set the entity resource type from the $resource
        $this->entity->setResourceType($resource->getType());
        
        //save first to generate the languageResource id
        $this->data['id']=$this->entity->save();

        //check and save customer assoc db entry
        $customerAssoc=ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */
        $customerAssoc->saveAssocRequest($this->data);
        
        //save the resource languages to
        $resourceLanguages=ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
        /* @var $resourceLanguages editor_Models_LanguageResources_Languages */
        $resourceLanguages->saveLanguages($this->getRequest()->getParam('sourceLang'), $this->getRequest()->getParam('targetLang'), $this->data['id']);
        
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
    
    public function putAction() {
        $this->decodePutData();
        $customerAssoc=ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */
        $customerAssoc->updateAssocRequest($this->data);
        parent::putAction();
    }

    /**
     * Imports an additional file which is transfered to the desired languageResource
     */
    public function importAction(){
        $this->getAction();
        
        $serviceManager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $serviceManager editor_Services_Manager */
        
        $resource = $serviceManager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());
        
        if(!$resource->getFilebased()) {
            throw new ZfExtended_ValidateException('Requested languageResource is not filebased!');
        }
        
        //upload errors are handled in handleAdditionalFileUpload
        $this->handleAdditionalFileUpload($serviceManager);
        
        //when there are errors, we cannot set it to true
        $this->view->success = $this->validateUpload();
    }
    
    public function exportAction() {
        $proposals=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $proposals editor_Models_Term */
        
        $collectionIds=$this->getParam('collectionId');
        if(is_string($collectionIds)){
            $collectionIds=explode(',', $collectionIds);
        }
        $rows = $proposals->loadProposalExportData($this->getParam('exportDate'),$collectionIds);
        if(empty($rows)){
            $this->view->message='No results where found.';
            return;
        }
        $proposals->exportProposals($rows);
    }
    
    /**
     * Loads all task information entities for the given languageResource
     * The returned data is no real task entity, although the task model is used in the frontend!
     */
    public function tasksAction() {
        $this->getAction();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->decodePutData();
            if(!empty($this->data) && !empty($this->data->toReImport)) {
                foreach($this->data->toReImport as $taskGuid) {
                    $worker = ZfExtended_Factory::get('editor_Models_LanguageResources_Worker');
                    /* @var $worker editor_Models_LanguageResources_Worker */
            
                    // init worker and queue it
                    // Since it has to be done in a none worker request to have session access, we have to insert the worker before the taskPost 
                    if (!$worker->init($taskGuid, ['languageResourceId' => $this->entity->getId()])) {
                        throw new ZfExtended_Exception('LanguageResource ReImport Error on worker init()');
                    }
                    $worker->queue();
                }
            }
        }
        
        $assoc = ZfExtended_Factory::get('editor_Models_LanguageResources_Taskassoc');
        /* @var $assoc editor_Models_LanguageResources_Taskassoc */
        $taskinfo = $assoc->getTaskInfoForLanguageResources([$this->entity->getId()]);
        //FIXME replace lockingUser guid with concrete username and show it in the frontend!
        $this->view->rows = $taskinfo;
        $this->view->total = count($taskinfo);
    }
    
    /**
     * Validates if choosen languages can be used by the choosen resource
     * Validates also the existence of the languages in the Lang DB 
     * @param editor_Models_LanguageResources_Resource $resource
     * @return boolean
     */
    protected function validateLanguages(editor_Models_LanguageResources_Resource $resource) {
        
        //when termcollection is a resource, the languages are handled by the tbx import
        if($resource instanceof editor_Services_TermCollection_Resource){
            return true;
        }
        
        $sourceLang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $sourceLang editor_Models_Languages */
        $sourceLang->load($this->getRequest()->getParam('sourceLang'));
        
        $targetLang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $targetLang editor_Models_Languages */
        $targetLang->load($this->getRequest()->getParam('targetLang'));
        
        $hasSourceLang = $resource->hasSourceLang($sourceLang);
        $hasTargetLang = $resource->hasTargetLang($targetLang);
        
        //cache the RF5646 language key in the languageResource since this value is used more often as our internal ID
        //$this->entity->setSourceLangRfc5646($sourceLang->getRfc5646());
        //$this->entity->setTargetLangRfc5646($targetLang->getRfc5646());
        
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
     * Uploads a file into the new languageResource
     * @param editor_Services_Manager $manager
     */
    protected function handleInitialFileUpload(editor_Services_Manager $manager) {
        $connector = $manager->getConnector($this->entity);
        /* @var $connector editor_Services_Connector */
        $importInfo = $this->handleFileUpload($connector);
        
        //currently the initial upload is optional
        // if this will be depending on the resource, 
        // here would be a good place to implement the check with 
        //if(!$importInfo && $resource file is mandatory) {
            //$this->uploadErrors = "dadada"
        //}
        
        if(!empty($this->uploadErrors)){
            return ;
        }
        
        //setting the TM filename here, but can be overwritten in the connectors addTm method
        // for example when we get a new name from the service
        $this->entity->addSpecificData('fileName', $importInfo[self::FILE_UPLOAD_NAME]['name']);
        
        $this->queueServiceImportWorker($importInfo, true);
    }
    
    /**
     * Uploads an additional file into the already existing languageResource
     * @param editor_Services_Manager $manager
     */
    protected function handleAdditionalFileUpload(editor_Services_Manager $manager) {
        $connector = $manager->getConnector($this->entity);
        /* @var $connector editor_Services_Connector */
        $importInfo = $this->handleFileUpload($connector);
        
        if(empty($importInfo)){
            $this->uploadErrors[] = 'Keine Datei hochgeladen!';
            return;
        }
        
        if(!empty($this->uploadErrors)){
            return ;
        }
        
        $this->queueServiceImportWorker($importInfo, false);
    }
    
    /**
     * handles the fileupload
     * @return array|boolean meta data about the upload or false when there was no file 
     */
    protected function handleFileUpload(editor_Services_Connector $connector) {
        $upload = new Zend_File_Transfer_Adapter_Http();
        
        //check if connector / resource can deal with the uploaded file type
        $validTypes = $connector->getValidFiletypes();
        $validMimeType = array_values($validTypes);
        $validExtension = array_keys($validTypes);
        
        // =============== workaround (start) ==========================================
        // with array_values($validTypes), $validMimeType currently is (example):
        /*
        Array
        (
            [0] => Array
                (
                    [0] => application/zip
                )
        
            [1] => Array
                (
                    [0] => application/xml
                    [1] => text/xml
                )
        
        )

        */
        // but Zend_Validate_File_MimeType needs (example):
        /*
        Array
        (
            [0] => application/zip
            [1] => application/xml
            [2] => text/xml
        )

        */
        $allValidMimeTypes = [];
        foreach ($validMimeType as $key1 => $value1) {
            foreach ($value1 as $key2 => $value2) {
                $allValidMimeTypes[] = $value2;
            }
        }
        // =============== workaround (end) ============================================
        $upload->addValidators([
            new Zend_Validate_File_MimeType($allValidMimeTypes),
            new Zend_Validate_File_Extension($validExtension),
        ]);
        // CAUTON: The validators don't know which extensions are allowed for which extension.
        // The only know ALL extensions that are allowed and all MimeTypes that are allowed.
        
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
        
        if(empty($importInfo[self::FILE_UPLOAD_NAME]['size'])) {
            $this->uploadErrors[] = 'Die ausgewählte Datei war leer!';
        }
        return $importInfo;
    }
    
    /***
     * Init and queue the servce import worker
     * @param array $importInfo
     * @param boolean $addnew
     */
    protected function queueServiceImportWorker($importInfo,$addnew){
        $worker=ZfExtended_Factory::get('editor_Services_ImportWorker');
        /* @var $worker editor_Services_ImportWorker */
        
        $params=$this->getAllParams();
        
        $this->handleUploadLanguageResourcesFile($importInfo[self::FILE_UPLOAD_NAME]);
        
        
        $userSession = new Zend_Session_Namespace('user');
        
        $params['languageResourceId']=$this->entity->getId();
        $params['fileinfo']=!empty($importInfo[self::FILE_UPLOAD_NAME])? $importInfo[self::FILE_UPLOAD_NAME]:[];
        $params['addnew']=$addnew;
        $params['userGuid']=$userSession->data->userGuid;
        
        if (!$worker->init(null, $params)) {
            $this->uploadErrors[] = 'File import in language resources Error on worker init()';
            return;
        }
        
        //set the language resource status to importing
        $this->entity->addSpecificData('status',editor_Services_Connector_FilebasedAbstract::STATUS_IMPORT);
        $this->entity->save();
        
        $worker->queue();
    }
    
    /***
     * Move the upload file to the tem directory so it can be used by the worker.
     * The fileinfo temp_name will be modefied
     * @param array $fileinfo
     */
    protected function handleUploadLanguageResourcesFile(&$fileinfo){
        if(!$fileinfo){
            return;
        }
        //create unique temp file name
        $newFileLocation=tempnam(sys_get_temp_dir(), $fileinfo['name']);
        if (!is_dir(dirname($newFileLocation))) {
            mkdir(dirname($newFileLocation), 0777, true);
        }
        move_uploaded_file($fileinfo['tmp_name'],$newFileLocation);
        $fileinfo['tmp_name']=$newFileLocation;
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
        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */
        $connector = $manager->getConnector($this->entity);
        $deleteInResource = !$this->getParam('deleteLocally', false);
        $deleteInResource && $connector->delete();
        
        $userSession = new Zend_Session_Namespace('user');
        
        error_log("LanguageResource with name:".$this->entity->getName()." and id:".$this->entity->getId()." was deleted by:".$userSession->data->firstName." ".$userSession->data->surName);
        $this->processClientReferenceVersion();
        $this->entity->delete();
    }
    
    /**
     * performs a languageResource query
     */
    public function queryAction() {
        $session = new Zend_Session_Namespace();
        $languageResourceId = (int) $this->_getParam('languageResourceId');
        
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segment->load((int) $this->_getParam('segmentId'));
        
        //check taskGuid of segment against loaded taskguid for security reasons
        //checks if the current task is associated to the languageResource
        $this->entity->checkTaskAndLanguageResourceAccess((string) $session->taskGuid,$languageResourceId, $segment);
        
        $this->entity->load($languageResourceId);

        $connector = $this->getConnector();

        $result = $connector->query($segment);
        
        if($this->entity->getResourceType() ==editor_Models_Segment_MatchRateType::TYPE_TM){
            $result=$this->markDiff($segment, $result,$connector);
        }
        
        $this->view->segmentId = $segment->getId(); //return the segmentId back, just for reference
        $this->view->languageResourceId = $this->entity->getId();
        $this->view->rows = $result->getResult();
        $this->view->total = count($this->view->rows);
    }
    
    /**
     * performs a languageResource search
     * example URL /editor/languageResource/14/search
     * additional POST Parameters: 
     *  query: querystring
     *  field: source or target
     *  offset: the offset from where the next search should start
     * Since the GUI is dynamically loading additional content no traditional paging can be used here
     */
    public function searchAction() {
        $session = new Zend_Session_Namespace();
        $query = $this->_getParam('query');
        $languageResourceId = (int) $this->_getParam('languageResourceId');
        $field = $this->_getParam('field');
        $offset = $this->_getParam('offset', null);
        
        //check provided field
        if($field !== 'source') {
            $field == 'target';
        }
        
        //checks if the current task is associated to the languageResource
        $this->entity->checkTaskAndLanguageResourceAccess($session->taskGuid,$languageResourceId);
        
        $this->entity->load($languageResourceId);
        
        if(! $this->entity->getResource()->getSearchable()) {
            throw new ZfExtended_Models_Entity_NoAccessException('search requests are not allowed on this language resource');
        }
        
        $connector = $this->getConnector();
        $result = $connector->search($query, $field, $offset);
        $this->view->languageResourceId = $this->entity->getId();
        $this->view->nextOffset = $result->getNextOffset();
        $this->view->rows = $result->getResult();
    }
    
    /**
     * returns the connector to be used
     * @return editor_Services_Connector
     */
    protected function getConnector() {
        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */
        $session = new Zend_Session_Namespace();
        $task=ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($session->taskGuid);
        return $manager->getConnector($this->entity,$task->getSourceLang(),$task->getTargetLang());
    }
    
    /***
    * Mark differences between $resultSource (the result from the resource) and the $queryString(the requested search string)
    * The difference is marked in $resultSource as return value
    * @param editor_Models_Segment $segment
    * @param editor_Services_ServiceResult $result
    * @param editor_Services_Connector $connector
    * @return editor_Services_ServiceResult
    */
    protected function markDiff(editor_Models_Segment $segment,editor_Services_ServiceResult $result,editor_Services_Connector $connector){
        $queryString = $connector->getQueryString($segment);
        $queryStringTags = [];
        
        //remove track changes tag from the query string
        $trackChangeTag=ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        /* @var $trackChangeTag editor_Models_Segment_TrackChangeTag */
        $queryString=$trackChangeTag->removeTrackChanges($queryString);

        //protect the tags
        $queryString = $this->protectTags($queryString, $queryStringTags);
        
        //remove term tags from the query string
        $termTag=ZfExtended_Factory::get('editor_Models_Segment_TermTag');
        /* @var $termTag editor_Models_Segment_TermTag */
        $queryString =$termTag->remove($queryString);
        
        //convert the html special chars
        $decodeHtmlSpecial=function($string){
            return htmlspecialchars_decode($string);;
        };
        
        $queryString=$decodeHtmlSpecial($queryString);
        
        $diffTagger=ZfExtended_Factory::get('editor_Models_Export_DiffTagger_Csv');
        /* @var $diffTagger editor_Models_Export_DiffTagger_Csv */
        
        //add del/ins tags css class
        $diffTagger->insertTagAttributes['class']='tmMatchGridResultTooltip';
        $diffTagger->deleteTagAttributes['class']='tmMatchGridResultTooltip';
        
        $results=$result->getResult();
        foreach ($results as &$res) {
            $tags = []; 
            //replace the internal tags before diff
            $res->source = $this->protectTags($res->source, $tags);
            
            $res->source =$decodeHtmlSpecial($res->source);
            
            $res->source = $diffTagger->diffSegment($queryString, $res->source, null,null);
            $res->source = $this->unprotectTags($res->source, array_merge($tags, $queryStringTags));
        }
        return $result;
    }
    
    /**
     * protected the internal tags for diffing
     * @param string $segment
     * @param array $tags
     */
    protected function protectTags($segment, array &$tags) { 
        $tag = $this->internalTag;
        return $tag->replace($segment, function($match) use (&$tags, $tag) {
            $submatch = null;
            if(preg_match($tag::REGEX_STARTTAG, $match[0], $submatch)) {
                $placeholder = sprintf($tag::PLACEHOLDER_TEMPLATE, 'start-'.$submatch[1]);
            }
            elseif(preg_match($tag::REGEX_ENDTAG, $match[0], $submatch)) {
                $placeholder = sprintf($tag::PLACEHOLDER_TEMPLATE, 'end-'.$submatch[1]);
            }
            elseif(preg_match($tag::REGEX_SINGLETAG, $match[0], $submatch)) {
                $id = $match[3];
                if(in_array($id, editor_Models_Segment_Whitespace::WHITESPACE_TAGS)) {
                    //for diffing the content of the whitespace tags is important not the number on it! 
                    $placeholder = sprintf($tag::PLACEHOLDER_TEMPLATE, $id.'-'.$tag->getLength($match[0]));
                }
                else {
                    $placeholder = sprintf($tag::PLACEHOLDER_TEMPLATE, 'single-'.$submatch[1]);
                }
                
            }
            else {
                $placeholder = 'notfound';
            }
            
            $tags[$placeholder] = $match[0];
            return $placeholder;
        });
    }
    
    /**
     * unprotects / restores the content tags
     * @param string $segment
     * @param array $segment
     * @return string
     */
    protected function unprotectTags($segment, array $tags) {
        return str_replace(array_keys($tags), array_values($tags), $segment);
    }
}