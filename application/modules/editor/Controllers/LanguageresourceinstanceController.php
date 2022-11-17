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

use MittagQI\Translate5\Task\Current\NoAccessException;
use MittagQI\Translate5\Task\TaskContextTrait;

/***
 * Language resource controller
 */
class editor_LanguageresourceinstanceController extends ZfExtended_RestController {
    use TaskContextTrait;

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

    /**
     * @var editor_Models_Categories
     */
    protected $categories;

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws NoAccessException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    public function init() {
        //add filter type for languages
        $finalTableForAssoc = new ZfExtended_Models_Filter_Join('LEK_customer', 'name', 'id', 'customerId');
        $this->_filterTypeMap = [
            'sourceLang' => ['string' => 'list'],
            'targetLang' => ['string' => 'list'],
            'customerIds' => [
                'string' => new ZfExtended_Models_Filter_JoinAssoc('LEK_languageresources_customerassoc', $finalTableForAssoc, 'languageResourceId', 'id')
            ]
        ];

        //set same join for sorting!
        $this->_sortColMap['customerIds'] = $this->_filterTypeMap['customerIds']['string'];
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->categories = ZfExtended_Factory::get('editor_Models_Categories');
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
        $eventLoggerGroupped=$eventLogger->getLatesEventsCount($languageResourcesId);

        //get all assocs grouped by language resource id
        $customerAssocModel=ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $customerAssocModel editor_Models_LanguageResources_CustomerAssoc */
        $custAssoc=$customerAssocModel->loadCustomerIdsGrouped();

        // for assigned categories
        $categoryAssocModel = ZfExtended_Factory::get('editor_Models_LanguageResources_CategoryAssoc');
        /* @var $categoryAssocModel editor_Models_LanguageResources_CategoryAssoc */
        $categoryAssocs = $categoryAssocModel->loadCategoryIdsGrouped();

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
            if(empty($resource)) {
                $languageresource['status'] = editor_Services_Connector_Abstract::STATUS_ERROR;
                $languageresource['statusInfo'] = $t->_('Die verwendete Resource wurde aus der Konfiguration entfernt.');
            }
            else {
                $moreInfo = '';
                $languageresource['status'] = $resource->getInitialStatus($moreInfo);
                $languageresource['statusInfo'] = $t->_($moreInfo);
            }

            $id = $languageresource['id'];

            //add customer assocs
            $languageresource['customerIds'] = $this->getCustassoc($custAssoc, 'customerId', $id);
            $languageresource['customerUseAsDefaultIds'] = $this->getCustassocByIndex($custAssoc, 'useAsDefault', $id);
            $languageresource['customerWriteAsDefaultIds'] = $this->getCustassocByIndex($custAssoc, 'writeAsDefault', $id);
            $languageresource['customerPivotAsDefaultIds'] = $this->getCustassocByIndex($custAssoc, 'pivotAsDefault', $id);
            
            $languageresource['sourceLang'] = $this->getLanguage($languages, 'sourceLang', $id);
            $languageresource['targetLang'] = $this->getLanguage($languages, 'targetLang', $id);

            // categories (for the moment: just display labels for info, no editing)
            $categoryLabels = [];
            foreach ($this->getCategoryassoc($categoryAssocs, 'categoryId', $id) as $categoryId) {
                $categoryLabels[] = $this->renderCategoryCustomLabel($categoryId);
            }
            $languageresource['categories'] = $categoryLabels;

            $languageresource['eventsCount'] = isset($eventLoggerGroupped[$id]) ? (integer)$eventLoggerGroupped[$id] : 0;

            if(isset($languageresource['specificData']) && !empty($languageresource['specificData'])){
                $languageresource['specificData'] = $this->translateSpecificData($languageresource['specificData'],$languageresource['serviceName']);
            }
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

    /**
     * Retrieves specific data from the given data container
     * @param array $data
     * @param string $index the datafield to get
     * @param int $id the language resource id
     * @return array
     */
    protected function getCategoryassoc(array $data, $index, $id) {
        if(empty($data[$id])){
            return [];
        }
        //remove 0 and null values
        return array_filter(array_column($data[$id], $index));
    }

    /***
     * Returns customer assoc active flag fields (useAsDefault,writeAsDefault or pivotAsDefault) for given customer assoc data
     * and give language resource id
     *
     * @param array $data
     * @param string $index the datafield to get
     * @param int $id the language resource id
     * @return array : filtered customer ids
     */
    protected function getCustassocByIndex(array $data, $index, $id){
        if(empty($data[$id])){
            return [];
        }
        // get the active flag indexes array indexes
        $default=$this->getCustassoc($data, $index, $id);
        $customerIds=[];
        //get the customer ids for those array indexes
        foreach ($default as $key=>$value){
            $customerIds[]=$data[$id][$key]['customerId'];
        }
        return $customerIds;
    }

    /**
     * Renders the label of a category with details (currently: original Id).
     * @param integer $categoryId
     * @return string
     */
    protected function renderCategoryCustomLabel($categoryId) {
        $this->categories->load($categoryId);
        return $this->categories->getLabel().' ('.$this->categories->getOriginalCategoryId().')';
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

        $this->addAssocData();

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
        $eventLoggerGroupped=$eventLogger->getLatesEventsCount([$this->entity->getId()]);
        $this->view->rows->eventsCount = isset($eventLoggerGroupped[$this->entity->getId()]) ? (integer)$eventLoggerGroupped[$this->entity->getId()] : 0;

        $connector = $serviceManager->getConnector($this->entity);
        $this->view->rows->status = $connector->getStatus($this->entity->getResource());
        $this->view->rows->statusInfo = $t->_($connector->getLastStatusInfo());

        $languages=ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
        /* @var $languages editor_Models_LanguageResources_Languages */
        $languages=$languages->loadResourceIdsGrouped($this->entity->getId());

        $this->view->rows->sourceLang = $this->getLanguage($languages, 'sourceLang', $this->entity->getId());
        $this->view->rows->targetLang = $this->getLanguage($languages, 'targetLang', $this->entity->getId());

        if(property_exists($this->view->rows,'specificData') && strlen($this->view->rows->specificData) > 0){
            $this->view->rows->specificData = $this->translateSpecificData($this->view->rows->specificData,$this->view->rows->serviceName);
        }
    }

    /**
     * Adds associated data to the result object
     */
    protected function addAssocData() {
        $this->prepareTaskInfo([$this->entity->getId()]);
        $this->view->rows->taskList = $this->getTaskInfos($this->entity->getId());

        //load associated customers to the resource
        $customerAssoc=ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */

        $customerAssocs = $customerAssoc->loadByLanguageResourceId($this->entity->getId());
        // all assoc customers with customerId as key and useAsDefault flag as value
        $useAsDefault = array_column($customerAssocs,'useAsDefault','customerId');

        $this->view->rows->customerIds = array_keys($useAsDefault);
        // filter out all useAsDefault with value 0
        $this->view->rows->customerUseAsDefaultIds = array_keys(array_filter($useAsDefault));
        
        // Filter out writable as default from use as default array. If assoc is writeAsDefault it must be useAsDefault to.
        $writeAsDefault = array_column($customerAssocs,'writeAsDefault','customerId');
        $this->view->rows->customerWriteAsDefaultIds = array_keys(array_filter($writeAsDefault));


        // Filter out pivot as default from use as default array. If assoc is pivotAsDefault it must be useAsDefault to.
        $pivotAsDefault = array_column($customerAssocs,'pivotAsDefault','customerId');
        $this->view->rows->customerPivotAsDefaultIds = array_keys(array_filter($pivotAsDefault));

        // categories that are assigned to the resource
        $categoryIds = [];
        $categoryAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_CategoryAssoc');
        /* @var $categoryAssoc editor_Models_LanguageResources_CategoryAssoc */
        $categoryAssocs = $categoryAssoc->loadByLanguageResourceId($this->entity->getId());
        $categoryIds = array_column($categoryAssocs,'categoryId');
        // for the moment: just display labels for info
        $categoryLabels = [];
        foreach ($categoryIds as $categoryId) {
            $categoryLabels[] = $this->renderCategoryCustomLabel($categoryId);
        }
        $this->view->rows->categories = $categoryLabels;
    }

    /***
     * Handle custom filtering in source,target,taskList and customerIds.
     * The filters are extended so thay can filter using string values.
     * TODO: can this be moved/implemented as assoc fiter ?
     */
    protected function handleFilterCustom(){
        $sourceFilter=null;
        $targetFilter=null;
        $useAsDefault=null;
        $writeAsDefault=null;
        $pivotAsDefault=null;
        $taskList=null;

        $this->entity->getFilter()->hasFilter('sourceLang',$sourceFilter);
        $this->entity->getFilter()->hasFilter('targetLang',$targetFilter);

        $this->entity->getFilter()->hasFilter('customerUseAsDefaultIds',$useAsDefault);
        $this->entity->getFilter()->hasFilter('customerWriteAsDefaultIds',$writeAsDefault);
        $this->entity->getFilter()->hasFilter('customerPivotAsDefaultIds',$pivotAsDefault);
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
        if(isset($useAsDefault)) {
            if(isset($useAsDefault->value) && is_string($useAsDefault->value)) {
                $resultList=$searchEntity($useAsDefault->value,'editor_Models_Customer_Customer');
                $handleFilter($useAsDefault,$resultList,'editor_Models_LanguageResources_CustomerAssoc','loadByCustomerIdsUseAsDefault','languageResourceId');
            }
            else {
                $this->entity->getFilter()->deleteFilter('customerUseAsDefaultIds');
            }
        }

        //check if filtering for writeAsDefault should be done
        if(isset($writeAsDefault)) {
            if(isset($writeAsDefault->value) && is_string($writeAsDefault->value)) {
                $resultList=$searchEntity($writeAsDefault->value,'editor_Models_Customer_Customer');
                $handleFilter($writeAsDefault,$resultList,'editor_Models_LanguageResources_CustomerAssoc','loadByCustomerIdsWriteAsDefault','languageResourceId');
            }
            else {
                $this->entity->getFilter()->deleteFilter('customerWriteAsDefaultIds');
            }
        }

        //check if filtering for writeAsDefault should be done
        if(isset($pivotAsDefault)) {
            if(isset($pivotAsDefault->value) && is_string($pivotAsDefault->value)) {
                $resultList=$searchEntity($pivotAsDefault->value,'editor_Models_Customer_Customer');
                $handleFilter($pivotAsDefault,$resultList,'editor_Models_LanguageResources_CustomerAssoc','loadByCustomerIdsPivotAsDefault','languageResourceId');
            }
            else {
                $this->entity->getFilter()->deleteFilter('customerPivotAsDefaultIds');
            }
        }
        
        //check if filtering for taskList should be done
        if(isset($taskList)){
            if(isset($taskList->value) && is_string($taskList->value)){
                $resultList=$searchEntity($taskList->value,'editor_Models_Task','taskGuid');
                $handleFilter($taskList,$resultList,'MittagQI\Translate5\LanguageResource\TaskAssociation','loadByTaskGuids','languageResourceId');
            }
            else {
                $this->entity->getFilter()->deleteFilter('taskList');
            }
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

        /* @var $assocs MittagQI\Translate5\LanguageResource\TaskAssociation */
        $assocs = ZfExtended_Factory::get('MittagQI\Translate5\LanguageResource\TaskAssociation');

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
        $this->data = $this->getAllParams(); //since its a fileupload, this is a normal POST
        $this->setDataInEntity($this->postBlacklist);
        $this->entity->createLangResUuid();

        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */
        $resource = $manager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());

        $sourceLangId = $this->getParam('sourceLang');
        $targetLangId = $this->getParam('targetLang');

        $validLanguages = $this->validateLanguages($resource, $sourceLangId, $targetLangId);

        if(!$validLanguages || !$this->validate()){
            return;
        }

        $sourceLangCode = null;
        $targetLangCode = null;
        //find the language codes for the current resource
        //in each resource separate language code matching should be introduced
        //because some of the resources are supporting different type of language codes
        //rfc as a language code will be used when no custom matching is implemented for the resource
        if(!empty($sourceLangId)){
            $sourceLangCode = $resource->getLanguageCodeSource($sourceLangId);
        }
        if(!empty($targetLangId)){
            $targetLangCode = $resource->getLanguageCodeTarget($targetLangId);
        }

        //set the entity resource type from the $resource
        $this->entity->setResourceType($resource->getType());

        //save first to generate the languageResource id
        $this->data['id'] = $this->entity->save();

        // especially tests are not respecting the array format ...
        editor_Utils::ensureFieldsAreArrays($this->data, ['customerIds', 'customerUseAsDefaultIds', 'customerWriteAsDefaultIds', 'customerPivotAsDefaultIds']);

        //check and save customer assoc db entry
        $customerAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var editor_Models_LanguageResources_CustomerAssoc $customerAssoc */
        try {
            $customerAssoc->saveAssocRequest($this->entity->getId(), $this->data);
        }
        catch (ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
            $this->entity->delete();
            throw $e;
        }

        //check and save categories assoc db entry
        $categoryAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_CategoryAssoc');
        /* @var editor_Models_LanguageResources_CategoryAssoc $categoryAssoc */
        try {
            $categoryAssoc->saveAssocRequest($this->data);
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
            $this->entity->delete();
            throw $e;
        }

        //save the resource languages to
        $resourceLanguages = ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
        /* @var editor_Models_LanguageResources_Languages $resourceLanguages */
        $resourceLanguages->setSourceLang($sourceLangId);
        $resourceLanguages->setSourceLangCode($sourceLangCode);
        $resourceLanguages->setTargetLang($targetLangId);
        $resourceLanguages->setTargetLangCode($targetLangCode);
        $resourceLanguages->setLanguageResourceId($this->data['id']);
        if (!empty($sourceLangId) || !empty($targetLangId)) {
            $resourceLanguages->save();
        }

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
        $this->decodePutAssociative = true;
        $this->decodePutData();
        parent::putAction();
        $customerAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */
        // especially tests are not respecting the array format ...
        editor_Utils::ensureFieldsAreArrays($this->data, ['customerIds', 'customerUseAsDefaultIds', 'customerWriteAsDefaultIds', 'customerPivotAsDefaultIds']);
        $customerAssoc->updateAssocRequest(
            $this->entity->getId(),
            $this->data);
        $this->addAssocData();
    }

    /**
     * {@inheritDoc}
     * @see ZfExtended_RestController::decodePutData()
     * @return void
     */
    protected function decodePutData()
    {
        parent::decodePutData();
        unset($this->data->langResUuid);
    }
    
    /**
     * Imports an additional file which is transferred to the desired languageResource
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

    public function tbxexportAction() {

        // Load utils
        class_exists('editor_Utils');

        // Get params
        $params = $this->getRequest()->getParams();

        // Check params
        editor_Utils::jcheck([
            'collectionId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => 'LEK_languageresources'
            ],
            'tbxBasicOnly,exportImages' => [
                'req' => true,
                'rex' => '~(0|1)~'
            ]
        ], $params);

        // Turn off limitations?
        ignore_user_abort(1); set_time_limit(0);

        // Export collection
        ZfExtended_Factory::get('editor_Models_Export_Terminology_Tbx')->exportCollectionById(
            $params['collectionId'],
            (new Zend_Session_Namespace('user'))->data->userName,
            $params['tbxBasicOnly'],
            $params['exportImages']
        );
    }

    public function exportAction() {
        $proposals=ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $proposals editor_Models_Terminology_Models_TermModel */

        $collectionIds=$this->getParam('collectionId');
        if(is_string($collectionIds)){
            $collectionIds=explode(',', $collectionIds);
        }
        $termCollection = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $termCollection editor_Models_TermCollection_TermCollection */
        $allowedCollections = $termCollection->getCollectionForAuthenticatedUser();
        $rows = $proposals->loadProposalExportData(array_intersect($collectionIds, $allowedCollections), $this->getParam('exportDate'));
        if(empty($rows)){
            $this->view->message='No results where found.';
            return;
        }
        $proposals->exportProposals($rows);
    }

    public function xlsxexportAction() {

        // Load utils
        class_exists('editor_Utils');

        // Get params
        $params = $this->getRequest()->getParams();

        // Check params
        $_ = editor_Utils::jcheck([
            'collectionId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => 'LEK_languageresources'
            ]
        ], $params);

        // Turn off time limit
        set_time_limit(0);

        // Export collection
        /** @var editor_Models_Export_Terminology_Xlsx $xlsx */
        $xlsx = ZfExtended_Factory::get('editor_Models_Export_Terminology_Xlsx');

        // If session's 'download' flag is set
        if ($_SESSION['download'] ?? false) {

            // Build file path
            $file = $xlsx->file($_['collectionId']['id']);

            // Unset session's download flag
            unset($_SESSION['download']);

            // Convert collection name to filename
            $filename = rawurlencode($_['collectionId']['name']);

            // Set up headers
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');
            header('Content-Type: text/xml');
            header('Content-Disposition: attachment; filename*=UTF-8\'\'' . $filename . '.xlsx; filename=' . $filename . '.xlsx');

            // Flush the entire file
            readfile($file);

            // Delete the file
            unlink($file);

            // Exit
            exit;
        }

        // Do export
        $xlsx->exportCollectionById($params['collectionId']);
    }

    /***
     * This is used for the tests. It will return the proposals for the current date and for the
     * assigned collections of the customers of the authenticated user
     */
    public function testexportAction() {
        $proposals = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $proposals editor_Models_Terminology_Models_TermModel */
        $termCollection = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $termCollection editor_Models_TermCollection_TermCollection */
        $collectionIdA = $termCollection->getCollectionForAuthenticatedUser();
        if ($required = $this->getParam('collectionId')) {
            $required = is_array($required) ? $required : explode(',', $required);
            $collectionIdA = array_intersect($collectionIdA, $required);
        }
        $this->view->rows = $proposals->loadProposalExportData($collectionIdA, date('Y-m-d'));
    }


    /**
     * Loads all task information entities for the given languageResource
     * The returned data is no real task entity, although the task model is used in the frontend!
     */
    public function tasksAction() {
        try {
            $this->getAction();
        } catch (editor_Services_Connector_Exception $e) {
            $e->addExtraData(['languageResource' => $this->entity]);
            throw $e;
        }

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

        $assoc = ZfExtended_Factory::get('MittagQI\Translate5\LanguageResource\TaskAssociation');
        /* @var $assoc MittagQI\Translate5\LanguageResource\TaskAssociation */
        $taskinfo = $assoc->getTaskInfoForLanguageResources([$this->entity->getId()]);
        //FIXME replace lockingUser guid with concrete username and show it in the frontend!
        $this->view->rows = $taskinfo;
        $this->view->total = count($taskinfo);
    }

    /**
     * Validates if choosen languages can be used by the choosen resource
     * Validates also the existence of the languages in the Lang DB
     * @param editor_Models_LanguageResources_Resource $resource
     * @param mixed $sourceLang
     * @param mixed $targetLang
     * @return boolean
     */
    protected function validateLanguages(editor_Models_LanguageResources_Resource $resource, &$sourceLang, &$targetLang): bool {
        //when termcollection is a resource, the languages are handled by the tbx import
        if($resource instanceof editor_Services_TermCollection_Resource){
            return true;
        }

        $hasSourceLang = $resource->hasSourceLang($this->_helper->Api->convertLanguageParameters($sourceLang));
        $hasTargetLang = $resource->hasTargetLang($this->_helper->Api->convertLanguageParameters($targetLang));

        //both languages can be dealed by the resource, all OK
        if($hasSourceLang && $hasTargetLang) {
            return true;
        }

        $errors = [];
        $t = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $t ZfExtended_Zendoverwrites_Translate */

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

        if(!empty($this->uploadErrors)){
            return ;
        }

        //setting the TM filename here, but can be overwritten in the connectors addTm method
        // for example when we get a new name from the service
        if(is_array($importInfo) && isset($importInfo[self::FILE_UPLOAD_NAME]['name'])) {
            $filename = $importInfo[self::FILE_UPLOAD_NAME]['name'];
        }
        else {
            $filename = '';
        }
        $this->entity->addSpecificData('fileName', $filename);

        $this->queueServiceImportWorker($importInfo ?? [], true);
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
     * @param editor_Services_Connector $connector
     * @return array|null meta data about the upload or null when there was no file
     * @throws Zend_File_Transfer_Exception
     * @throws Zend_Validate_Exception
     */
    protected function handleFileUpload(editor_Services_Connector $connector): ?array {
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
        try {
            //this will throw an Zend_File_Transfer_Exception when the file does not exist
            $importInfo = $upload->getFileInfo(self::FILE_UPLOAD_NAME);
        } catch (Zend_File_Transfer_Exception $e) {
            //no tmUpload field was given, this can happen only from the api
            //allow empty filebased language resource without file upload
            return null;
        }

        //checking general upload errors
        $errorNr = $importInfo[self::FILE_UPLOAD_NAME]['error'];

        if($errorNr === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if($errorNr !== UPLOAD_ERR_OK) {
            $this->uploadErrors[] = ZfExtended_FileUploadException::getUploadErrorMessage($errorNr);
            return $importInfo;
        }

        //currently, an error means wrong filetype
        if($upload->hasErrors()) {
            $this->uploadErrors[] = 'Die ausgewählte Ressource kann Dateien diesen Typs nicht verarbeiten!';
        }

        if(empty($importInfo[self::FILE_UPLOAD_NAME]['size'])) {
            $this->uploadErrors[] = 'Die ausgewählte Datei war leer!';
        }
        return $importInfo;
    }

    /***
     * Init and queue the service import worker
     * @param array $importInfo
     * @param boolean $addNew
     */
    protected function queueServiceImportWorker(array $importInfo, bool $addNew)
    {
        $worker=ZfExtended_Factory::get('editor_Services_ImportWorker');
        /* @var $worker editor_Services_ImportWorker */

        $params = $this->getAllParams();
        $params['languageResourceId'] = $this->entity->getId();

        if(!empty($importInfo) && !empty($importInfo[self::FILE_UPLOAD_NAME])) {
            $this->handleUploadLanguageResourcesFile($importInfo[self::FILE_UPLOAD_NAME]);
            $params['fileinfo'] = $importInfo[self::FILE_UPLOAD_NAME];
        }
        else {
            $params['fileinfo'] = [];
        }

        $params['addnew']=$addNew;
        $params['userGuid']=editor_User::instance()->getGuid();

        if (!$worker->init(null, $params)) {
            $this->uploadErrors[] = 'File import in language resources Error on worker init()';
            return;
        }

        //set the language resource status to importing
        $this->entity->addSpecificData('status',editor_Services_Connector_FilebasedAbstract::STATUS_IMPORT);
        $this->entity->save();

        $workerId = $worker->queue();

        $this->events->trigger('serviceImportWorkerQueued',argv: [
            'entity' => $this->entity,
            'workerId' => $workerId,
            'params' => $this->getAllParams()
        ]);
    }

    /***
     * Move the upload file to the tem directory so it can be used by the worker.
     * The fileinfo temp_name will be modified
     * @param array $fileinfo
     */
    protected function handleUploadLanguageResourcesFile(array &$fileinfo){
        //create unique temp file name
        $newFileLocation=tempnam(sys_get_temp_dir(), 'LanguageResources'.$fileinfo['name']);
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
        //load the entity
        $this->entityLoad();

        // clone the current entity, so it can be re-applied later again after the entity is removed fron the database.
        // there may be some post-delete events where the deleted entity should be checked
        $clone = clone $this->entity;

        // if the current entity is term collection, init the entity as term collection
        if($this->entity->isTc()){
            $collection = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
            /* @var $collection editor_Models_TermCollection_TermCollection */
            $collection->init($this->entity->toArray());
            $this->entity = $collection;
        }
        $this->processClientReferenceVersion();

        //encapsulate the deletion in a transaction to rollback if for example the real file based resource can not be deleted
        $this->entity->db->getAdapter()->beginTransaction();
        try {
            $entity = clone $this->entity;
            //delete the entity in the DB
            $this->entity->delete();
        }
        catch(ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
            //if there are associated tasks we can not delete the language resource
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1158' => 'A Language Resources cannot be deleted as long as tasks are assigned to this Language Resource.'
            ], 'editor.languageresources');
            throw new ZfExtended_Models_Entity_Conflict('E1158');
        }
        try {
            $manager = ZfExtended_Factory::get('editor_Services_Manager');
            /* @var $manager editor_Services_Manager */
            $connector = $manager->getConnector($entity);
            $deleteInResource = !$this->getParam('deleteLocally', false);
            //try to delete the resource via the connector
            $deleteInResource && $connector->delete();
            //if this is successfull we commit the DB delete
            $this->entity->db->getAdapter()->commit();
        }
        catch (Exception $e) {
            //if not we rollback and throw the original exception
            $this->entity->db->getAdapter()->rollBack();
            throw $e;
        }
        $this->entity = $clone;
    }

    /**
     * performs a languageResource query
     * @throws ZfExtended_Models_Entity_NoAccessException
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws editor_Models_ConfigException
     */
    public function queryAction() {
        $this->initCurrentTask();
        $languageResourceId = (int) $this->_getParam('languageResourceId');

        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segment->load((int) $this->_getParam('segmentId'));

        //check taskGuid of segment against loaded taskguid for security reasons
        //checks if the current task is associated to the languageResource
        $this->entity->checkTaskAndLanguageResourceAccess($this->getCurrentTask()->getTaskGuid(),$languageResourceId, $segment);

        $this->entity->load($languageResourceId);

        $connector = $this->getConnector();
        $result = $connector->query($segment);

        if($this->entity->getResourceType() == editor_Models_Segment_MatchRateType::TYPE_TM){
            $result=$this->markDiff($segment, $result,$connector);
        }

        $this->view->segmentId = $segment->getId(); //return the segmentId back, just for reference
        $this->view->languageResourceId = $this->entity->getId();
        $this->view->resourceType=$this->entity->getResourceType();
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
     * @throws ZfExtended_Models_Entity_NoAccessException
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws editor_Models_ConfigException
     * @throws editor_Services_Exceptions_NoService
     */
    public function searchAction() {
        $this->initCurrentTask();
        $query = $this->_getParam('query');
        $languageResourceId = (int) $this->_getParam('languageResourceId');
        $field = $this->_getParam('field');
        $offset = $this->_getParam('offset', null);

        //check provided field
        if($field !== 'source') {
            $field == 'target';
        }

        //checks if the current task is associated to the languageResource
        $this->entity->checkTaskAndLanguageResourceAccess($this->getCurrentTask()->getTaskGuid(), $languageResourceId);

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

    public function translateAction(){
        $this->initCurrentTask();

        $query = $this->_getParam('searchText');
        $languageResourceId = (int) $this->_getParam('languageResourceId');

        //checks if the current task is associated to the languageResource
        $this->entity->checkTaskAndLanguageResourceAccess($this->getCurrentTask()->getTaskGuid(), $languageResourceId);

        $this->entity->load($languageResourceId);

        $connector = $this->getConnector();
        $result = $connector->translate($query);
        $result = $result->getResult()[0] ?? [];
        $this->view->translations = $result->metaData['alternativeTranslations'] ?? $result;
    }

    /**
     * returns the connector to be used
     * @return editor_Services_Connector
     * @throws editor_Models_ConfigException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    protected function getConnector() {
        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */
        $task = $this->getCurrentTask();
        return $manager->getConnector($this->entity, $task->getSourceLang(), $task->getTargetLang(), $task->getConfig());
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

        $results=$result->getResult() ?? [];
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

    /***
     * @param string $specificData
     * @param string $serviceName
     * @return string
     * @throws Zend_Exception
     * @throws Zend_Json_Exception
     */
    protected function translateSpecificData(string $specificData, string $serviceName): string
    {
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */;

        $data = Zend_Json::decode($specificData);
        $return = [];

        $keysToIgnore = ['status'];

        foreach ($data as $key=>$value) {
            if(in_array($key,$keysToIgnore)){
                continue;
            }
            $toAdd = [
                'type' => $key.'_'.$serviceName,
                'text' => $translate->_($key.'_'.$serviceName),
                'value' => $value
            ];
            // fileName should always appear as first element
            if($key === 'fileName'){
                array_unshift($return,$toAdd);
            }else {
                array_push($return, $toAdd);
            }
        }

        return empty($return) ? '' : Zend_Json::encode($return);
    }
}
