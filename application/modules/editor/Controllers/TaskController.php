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
 *
 */
class editor_TaskController extends ZfExtended_RestController {

    protected $entityClass = 'editor_Models_Task';
    
    /**
     * aktueller Datumsstring
     * @var string
     */
    protected $now;
    
    /**
     * logged in user
     * @var Zend_Session_Namespace
     */
    protected $user;

    /**
     * @var editor_Models_Task
     */
    protected $entity;
    
    /**
     * Cached map of userGuids to userNames
     * @var array
     */
    protected $cachedUserInfo = array();
    
    /**
     * loadAll counter buffer
     * @var integer
     */
    protected $totalCount;
    
    /**
     * Specific Task Filter Class to use
     * @var string
     */
    protected $filterClass = 'editor_Models_Filter_TaskSpecific';
    
    /**
     * @var editor_Workflow_Abstract 
     */
    protected $workflow;
    
    /**
     * @var editor_Workflow_Manager 
     */
    protected $workflowManager;
    
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager;
    
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    /**
     * @var editor_Models_Import_UploadProcessor
     */
    protected $upload;
    
    /**
     * @var Zend_Config
     */
    protected $config;
    
    /**
     * @var ZfExtended_Logger
     */
    protected $taskLog;
    
    /**
     *  @var editor_Logger_Workflow
     */
    protected $log = false;

    public function init() {
        $this->_filterTypeMap = [
            'customerId' => [
                //'string' => new ZfExtended_Models_Filter_JoinHard('editor_Models_Db_Customer', 'name', 'id', 'customerId')
                'string' => new ZfExtended_Models_Filter_Join('LEK_customer', 'name', 'id', 'customerId')
            ]
        ];
        //set same join for sorting!
        $this->_sortColMap['customerId'] = $this->_filterTypeMap['customerId']['string'];
        
        parent::init();
        $this->now = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
        $this->user = new Zend_Session_Namespace('user');
        $this->workflowManager = ZfExtended_Factory::get('editor_Workflow_Manager');
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $this->upload = ZfExtended_Factory::get('editor_Models_Import_UploadProcessor');
        $this->config = Zend_Registry::get('config');

        //create a new logger instance writing only to the configured taskLogger
        $this->taskLog = ZfExtended_Factory::get('ZfExtended_Logger', [[
            'writer' => [
                'tasklog' => $this->config->resources->ZfExtended_Resource_Logger->writer->tasklog
            ] 
        ]]);
        
        $this->log = ZfExtended_Factory::get('editor_Logger_Workflow', [$this->entity]);
        
        //add context xliff2 as valid format
        $this->_helper
        ->getHelper('contextSwitch')
        ->addContext('xliff2', [
            'headers' => [
                'Content-Type'          => 'text/xml',
            ]
        ])
        ->addContext('importArchive', [
            'headers' => [
                'Content-Type'          => 'application/zip',
            ]
        ])
        ->addActionContext('export', 'xliff2')
        ->addActionContext('export', 'importArchive')
        ->initContext();
        
    }
    
    /**
     * init the internal used workflow
     * @param string $wfId workflow ID. optional, if omitted use the workflow of $this->entity
     */
    protected function initWorkflow($wfId = null) {
        if(empty($wfId)) {
            $wfId = $this->entity->getWorkflow();
        }
        try {
            $this->workflow = $this->workflowManager->getCached($wfId);
        }
        catch (Exception $e) {
            $this->workflow = $this->workflowManager->getCached('default');
        }
    }
    
    /**
     * 
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction() {
        //set default sort
        $f = $this->entity->getFilter();
        $f->hasSort() || $f->addSort('orderdate', true);
        
        $this->view->rows = $this->loadAll();
        $this->view->total = $this->totalCount;
    }
    
    /**
     * uses $this->entity->loadAll, but unsets qmSubsegmentFlags for all rows and
     * set qmSubEnabled for all rows
     */
    public function loadAll()
    {
        // here no check for pmGuid, since this is done in task::loadListByUserAssoc
        $isAllowedToLoadAll = $this->isAllowed('backend', 'loadAllTasks'); 
        $filter = $this->entity->getFilter();
        /* @var $filter editor_Models_Filter_TaskSpecific */
        $filter->convertStates($isAllowedToLoadAll);
        $assocFilter = $filter->isUserAssocNeeded();
        if(!$assocFilter && $isAllowedToLoadAll) {
            $this->totalCount = $this->entity->getTotalCount();
            $rows = $this->entity->loadAll();
        }
        else {
            $filter->setUserAssocNeeded();
            $this->totalCount = $this->entity->getTotalCountByUserAssoc($this->user->data->userGuid, $isAllowedToLoadAll);
            $rows = $this->entity->loadListByUserAssoc($this->user->data->userGuid, $isAllowedToLoadAll);
        }
        
        $taskGuids = array_map(function($item){
            return $item['taskGuid'];
        },$rows);
        
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $fileCount = $file->getFileCountPerTasks($taskGuids);
        
        $userAssocInfos = array();
        $allAssocInfos = $this->getUserAssocInfos($taskGuids, $userAssocInfos);

        //load the task assocs
        $languageResourcemodel = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /*@var $languageResourcemodel editor_Models_LanguageResources_LanguageResource */
        $resultlist = $languageResourcemodel->loadByAssociatedTaskGuidList($taskGuids);
        
        //group all assoc by taskguid
        $taskassocs = array();
        foreach ($resultlist as $res){
            if(!isset($taskassocs[$res['taskGuid']])){
                $taskassocs[$res['taskGuid']] = array();
            }
            array_push($taskassocs[$res['taskGuid']], $res);
        }
        
        $customerData = $this->getCustomersForRendering($rows);
        
        foreach ($rows as &$row) {
            $row['lastErrors'] = $this->getLastErrorMessage($row['taskGuid'], $row['state']);
            $this->initWorkflow($row['workflow']);
            //adding QM SubSegment Infos to each Task
            $row['qmSubEnabled'] = false;
            if($this->config->runtimeOptions->editor->enableQmSubSegments &&
                    !empty($row['qmSubsegmentFlags'])) { 
                $row['qmSubEnabled'] = true;
            }
            unset($row['qmSubsegmentFlags']);
            
            $row['customerName'] = empty($customerData[$row['customerId']]) ? '' : $customerData[$row['customerId']];
            
            $this->addUserInfos($row, $row['taskGuid'], $userAssocInfos, $allAssocInfos);
            $row['fileCount'] = empty($fileCount[$row['taskGuid']]) ? 0 : $fileCount[$row['taskGuid']];
            
            //add task assoc if exist
            if(isset($taskassocs[$row['taskGuid']])){
                $row['taskassocs'] = $taskassocs[$row['taskGuid']];
            }
        }
        return $rows;
    }
    
    /**
     * Fetch an array with Task User Assoc Data for the currently logged in User.
     * Returns an array with an entry for each task, key is the taskGuid
     * @return array
     */
    protected function getUserAssocInfos($taskGuids, &$userAssocInfos) {
        $userAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $userAssoc editor_Models_TaskUserAssoc */
        $userGuid = $this->user->data->userGuid;
        $assocs = $userAssoc->loadByTaskGuidList($taskGuids);
        $res = array();
        foreach($assocs as $assoc) {
            if(!isset($res[$assoc['taskGuid']])) {
                $res[$assoc['taskGuid']] = array(); 
            }
            if($userGuid == $assoc['userGuid']) {
                $userAssocInfos[$assoc['taskGuid']] = $assoc;
            }
            $userInfo = $this->getUserinfo($assoc['userGuid']);
            $assoc['userName'] = $userInfo['surName'].', '.$userInfo['firstName'];
            $assoc['login'] = $userInfo['login'];
            //set only not pmOverrides
            if(empty($assoc['isPmOverride'])) {
                $res[$assoc['taskGuid']][] = $assoc;
            }
        }
        $userSorter = function($first, $second){
            if($first['userName'] > $second['userName']) {
                return 1;
            }
            if($first['userName'] < $second['userName']) {
                return -1;
            }
            return 0;
        };
        foreach($res as $taskGuid => $taskUsers) {
            usort($taskUsers, $userSorter);
            $res[$taskGuid] = $taskUsers; 
        }
        return $res;
    }

    /**
     * replaces the userGuid with the username
     * Doing this on client side would be possible, but then it must be ensured that UsersStore is always available and loaded before TaskStore. 
     * @param string $userGuid
     */
    protected function getUserinfo($userGuid) {
        $notfound = array(); //should not be, but can occur after migration of old data!
        if(empty($userGuid)) {
            return $notfound;
        }
        if(isset($this->cachedUserInfo[$userGuid])) {
            return $this->cachedUserInfo[$userGuid];
        }
        if(empty($this->tmpUserDb)) {
            $this->tmpUserDb = ZfExtended_Factory::get('ZfExtended_Models_Db_User');
            /* @var $this->tmpUserDb ZfExtended_Models_Db_User */
        }
        $s = $this->tmpUserDb->select()->where('userGuid = ?', $userGuid);
        $row = $this->tmpUserDb->fetchRow($s);
        if(!$row) {
            return $notfound; 
        }
        $this->cachedUserInfo[$userGuid] = $row->toArray();
        return $row->toArray(); 
    }
    
    /**
     * returns the commonly used username: Firstname Lastname (login)
     * @param array $userinfo
     */
    protected function getUsername(array $userinfo) {
        if(empty($userinfo)) {
            return '- not found -'; //should not be, but can occur after migration of old data!
        }
        return $userinfo['firstName'].' '.$userinfo['surName'].' ('.$userinfo['login'].')';
    }

    /**
     * Returns a mapping of customerIds and Names to the given rows of tasks
     * @param array $rows
     * @return array
     */
    protected function getCustomersForRendering(array $rows) {
        if(empty($rows)){
           return [];
        }
        
        $customerIds = array_map(function($item){
            return $item['customerId'];
        },$rows);

        if(empty($customerIds)){
            throw new ZfExtended_BadMethodCallException("No customers are found in the task list. The list of was: ".error_log(print_r($rows,1)));
        }
        
        $customer = ZfExtended_Factory::get('editor_Models_Customer');
        /* @var $customer editor_Models_Customer */
        $customerData = $customer->loadByIds($customerIds);
        return array_combine(array_column($customerData, 'id'), array_column($customerData, 'name'));
    }
    
    /**
     * creates a task and starts import of the uploaded task files 
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function postAction() {
        $this->entity->init();
        //$this->decodePutData(); → not needed, data was set directly out of params because of file upload
        $this->data = $this->getAllParams();
        settype($this->data['wordCount'], 'integer');
        settype($this->data['enableSourceEditing'], 'integer');
        settype($this->data['edit100PercentMatch'], 'integer');
        settype($this->data['lockLocked'], 'integer');
        if(array_key_exists('autoStartImport', $this->data)) {
            //if the value exists we assume boolean
            settype($this->data['autoStartImport'], 'boolean');
        }
        else {
            //if not explicitly disabled the import starts always automatically to be compatible with legacy API users
            $this->data['autoStartImport'] = true;
        }
        $this->data['pmGuid'] = $this->user->data->userGuid;
        $pm = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $pm ZfExtended_Models_User */
        $pm->init((array)$this->user->data);
        $this->data['pmName'] = $pm->getUsernameLong();
        $this->processClientReferenceVersion();
        $this->convertToLanguageIds();
        $this->setDataInEntity();
        $this->entity->createTaskGuidIfNeeded();
        $this->entity->setImportAppVersion(ZfExtended_Utils::getAppVersion());
        
        if(empty($this->data['customerId'])){
            $this->entity->setDefaultCustomerId();
            $this->data['customerId'] = $this->entity->getCustomerId();
        }
        
        //init workflow id for the task
        $defaultWorkflow = $this->config->runtimeOptions->import->taskWorkflow;
        $this->entity->setWorkflow($this->workflowManager->getIdToClass($defaultWorkflow));
        
        if($this->validate()) {
            $this->initWorkflow();
            //$this->entity->save(); => is done by the import call!
            $this->processUploadedFile();
            //reload because entityVersion could be changed somewhere
            $this->entity->load($this->entity->getId());
            
            // Language resources that are assigned as default language resource for a client,
            // are associated automatically with tasks for this client.
            $this->addDefaultLanguageResources();
            
            if($this->data['autoStartImport']) {
                $this->startImportWorkers();
            }
            $this->view->success = true;
            $this->view->rows = $this->entity->getDataObject();
        }
    }
    
    /**
     * Assign language resources by default that are set as useAsDefault for the task's client
     * (but only if the language combination matches).
     */
    protected function addDefaultLanguageResources() {
        $customerAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */
        $allUseAsDefaultCustomers = $customerAssoc->loadByCustomerIdsDefault($this->data['customerId']);
        if(empty($allUseAsDefaultCustomers)) {
            return;
        }
        $languages = ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
        /* @var $languages editor_Models_LanguageResources_Languages */
        $taskAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_Taskassoc');
        /* @var $taskAssoc editor_Models_LanguageResources_Taskassoc */
        foreach ($allUseAsDefaultCustomers as $defaultCustomer) {
            $languageResourceId = $defaultCustomer['languageResourceId'];
            if ($languages->isInCollection($this->entity->getSourceLang(),'sourceLang',$languageResourceId)
                    && $languages->isInCollection($this->entity->getTargetLang(),'targetLang',$languageResourceId) ) {
                        $taskAssoc->init();
                        $taskAssoc->setLanguageResourceId($languageResourceId);
                        $taskAssoc->setTaskGuid($this->entity->getTaskGuid());
                        $taskAssoc->save();
            }
        }
    }
    
    /**
     * Starts the import of the task
     */
    public function importAction() {
        $this->getAction();
        $this->startImportWorkers();
    }
    
    protected function startImportWorkers() {
        $workerModel = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $workerModel ZfExtended_Models_Worker */
        $workerModel->loadFirstOf('editor_Models_Import_Worker', $this->entity->getTaskGuid());
        $worker = ZfExtended_Worker_Abstract::instanceByModel($workerModel);
        $worker->schedulePrepared();
    }

    /**
     * imports the uploaded file
     * @throws Exception
     */
    protected function processUploadedFile() {
        $import = ZfExtended_Factory::get('editor_Models_Import');
        /* @var $import editor_Models_Import */
        $import->setUserInfos($this->user->data->userGuid, $this->user->data->userName);

        $import->setLanguages(
                        $this->entity->getSourceLang(), 
                        $this->entity->getTargetLang(), 
                        $this->entity->getRelaisLang(), 
                        editor_Models_Languages::LANG_TYPE_ID);
        $import->setTask($this->entity);
        $dp = $this->upload->getDataProvider();
        
        $import->import($dp);
    }
    
    /**
     * Since numeric IDs aren't really sexy to be used for languages in API, 
     *  TaskController can also deal with rfc5646 strings and LCID numbers. The LCID numbers must be prefixed with 'lcid-' for example lcid-123
     * Not found / invalid languages are converted to 0, this gives an error on import
     */
    protected function convertToLanguageIds() {
        $langFields = array('sourceLang', 'targetLang', 'relaisLang');
        foreach($langFields as $lang) {
            //ignoring if already integer like value or empty
            if(empty($this->data[$lang]) || (int)$this->data[$lang] > 0) {
                continue;
            }
            $language = ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $language editor_Models_Languages */
            try {
                if(preg_match('/^lcid-([0-9]+)$/i', $this->data[$lang], $matches)) {
                    $language->loadByLcid($matches[1]);
                }else {
                    $language->loadByRfc5646($this->data[$lang]);
                }
            }
            catch(ZfExtended_Models_Entity_NotFoundException $e) {
                $this->data[$lang] = 0;
                continue;
            }
            $this->data[$lang] = $language->getId();
        }
    }
    
    /**
     * clone the given task into a new task
     * @throws BadMethodCallException
     * @throws ZfExtended_Exception
     */
    public function cloneAction() {
        if(!$this->_request->isPost()) {
            throw new BadMethodCallException('Only HTTP method POST allowed!');
        }
        $this->getAction();
        $oldTaskPath = new SplFileInfo($this->entity->getAbsoluteTaskDataPath().'/'.editor_Models_Import_DataProvider_Abstract::TASK_ARCHIV_ZIP_NAME);
        if(!$oldTaskPath->isFile()){
            throw new ZfExtended_Exception('The task to be cloned does not have a import archive zip! Path: '.$oldTaskPath);
        }
        $copy = tempnam(sys_get_temp_dir(), 'taskclone');
        copy($oldTaskPath, $copy);
        
        $data = (array) $this->entity->getDataObject();
        unset($data['id']);
        unset($data['taskGuid']);
        unset($data['state']);
        unset($data['workflowStep']);
        unset($data['locked']);
        unset($data['lockingUser']);
        $data['state'] = 'import';
        $this->entity->init($data);
        $this->entity->createTaskGuidIfNeeded();
        $this->entity->setImportAppVersion(ZfExtended_Utils::getAppVersion());
        $copy = new SplFileInfo($copy);
        ZfExtended_Utils::cleanZipPaths($copy, '_tempImport');
        $this->upload->initByGivenZip($copy);
        
        if($this->validate()) {
            $this->processUploadedFile();
            $this->startImportWorkers();
            //reload because entityVersion could be changed somewhere
            $this->entity->load($this->entity->getId());
            $this->log->request();
            $this->view->success = true;
            $this->view->rows = $this->entity->getDataObject();
        }
    }
    
    /**
     * returns the logged events for the given task
     */
    public function eventsAction() {
        $this->getAction();
        $events = ZfExtended_Factory::get('editor_Models_Logger_Task');
        /* @var $events editor_Models_Logger_Task */
        
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
        
        $this->view->rows = $events->loadByTaskGuid($this->entity->getTaskGuid());
        $this->view->total = $events->getTotalByTaskGuid($this->entity->getTaskGuid());
    }
    
    /**
     * 
     * currently taskController accepts only 2 changes by REST
     * - set locked: this sets the session_id implicitly and in addition the 
     *   corresponding userGuid, if the passed locked value is set
     *   if locked = 0, task is unlocked
     * - set finished: removes locked implictly, and sets the userGuid of the "finishers" 
     * @see ZfExtended_RestController::putAction()
     */
    public function putAction() {
        $this->entity->load($this->_getParam('id'));
        
        $this->entity->checkStateAllowsActions();
        
        $taskguid = $this->entity->getTaskGuid();
        $this->log->request();
        
        $oldTask = clone $this->entity;
        $this->decodePutData();
        $this->checkTaskAttributeField();
        //was formerly in JS: if a userState is transfered, then entityVersion has to be ignored!
        if(!empty($this->data->userState)) {
            unset($this->data->entityVersion);
        }
        if(isset($this->data->enableSourceEditing)){
            $this->data->enableSourceEditing = (boolean)$this->data->enableSourceEditing;
        }
        $this->processClientReferenceVersion();
        $this->setDataInEntity();
        $this->entity->validate();
        $this->initWorkflow();
        
        $mayLoadAllTasks = $this->isAllowed('backend', 'loadAllTasks') || $this->isAuthUserTaskPm($this->entity->getPmGuid());
        $tua = $this->workflow->getTaskUserAssoc($taskguid, $this->user->data->userGuid);
        if(!$mayLoadAllTasks &&
                ($this->isOpenTaskRequest(true)&&
                    !$this->workflow->isWriteable($tua)
                || $this->isOpenTaskRequest(false,true)&&
                    !$this->workflow->isReadable($tua)
                )
           ){
            //if the task was already in session, we must delete it. 
            //If not the user will always receive an error in JS, and would not be able to do anything.
            $this->unregisterTask(); //FIXME XXX the changes in the session made by this method is not stored in the session!
            //wir laufen auf dem server hier öfters rein
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
        
        //opening a task must be done before all workflow "do" calls which triggers some events
        $this->openAndLock();
        
        $this->workflow->doWithTask($oldTask, $this->entity);
        
        if($oldTask->getState() != $this->entity->getState()) {
            $this->logInfo('Status change to {status}', ['status' => $this->entity->getState()]);
        }
        else {
            //id is always set as modified, therefore we don't log task changes if id is the only modified
            $modified = $this->entity->getModifiedValues();
            if(!array_key_exists('id', $modified) || count($modified) > 1) {
                $this->logInfo('Task modified: ');
            }
        }
        
        //updateUserState does also call workflow "do" methods!
        $this->updateUserState($this->user->data->userGuid);
        
        //closing a task must be done after all workflow "do" calls which triggers some events
        $this->closeAndUnlock();
        
        $this->entity->save();
        $obj = $this->entity->getDataObject();
        
        $userAssocInfos = array();
        $allAssocInfos = $this->getUserAssocInfos(array($taskguid), $userAssocInfos);
        
        //because we are mixing objects (getDataObject) and arrays (loadAll) as entity container we have to cast here
        $row = (array) $obj; 
        $this->addUserInfos($row, $taskguid, $userAssocInfos, $allAssocInfos);
            
        $this->view->rows = (object)$row;
        if($this->isOpenTaskRequest()){
            $this->addQmSubToResult();
        }
        else {
            unset($this->view->rows->qmSubsegmentFlags);
        }
        
        // Add pixelMapping-data for the fonts used in the task.
        // We do this here to have it immediately available e.g. when opening segments.
        $this->addPixelMapping();
        $this->view->rows->lastErrors = $this->getLastErrorMessage($this->entity->getTaskGuid(), $this->entity->getState());
    }
    
    protected function addPixelMapping() {
        $pixelMapping = ZfExtended_Factory::get('editor_Models_PixelMapping');
        /* @var $pixelMapping editor_Models_PixelMapping */
        try {
            $pixelMappingForTask = $pixelMapping->getPixelMappingForTask(intval($this->entity->getCustomerId()), $this->entity->getAllFontsInTask());
        }
        catch(ZfExtended_Exception $e) {
            $pixelMappingForTask = [];
        }
        $this->view->rows->pixelMapping = $pixelMappingForTask;
    }
    
    /**
     * returns the last error to the taskGuid if given taskStatus is error
     * @param string $taskGuid
     * @param string $taskStatus
     */
    protected function getLastErrorMessage($taskGuid, $taskStatus) {
        if($taskStatus != editor_Models_Task::STATE_ERROR) {
            return [];
        }
        $events = ZfExtended_Factory::get('editor_Models_Logger_Task');
        /* @var $events editor_Models_Logger_Task */
        return $events->loadLastErrors($taskGuid);
    }
    
    /**
     * Adds additional user based infos to the given array
     * @param array $row gets the row to modify as reference
     * @param string $taskguid
     * @param array $userAssocInfos
     * @param array $allAssocInfos
     */
    protected function addUserInfos(array &$row, $taskguid, array $userAssocInfos, array $allAssocInfos) {
        $isEditAll = $this->isAllowed('backend', 'editAllTasks') || $this->isAuthUserTaskPm($row['pmGuid']);
        //Add actual User Assoc Infos to each Task
        if(isset($userAssocInfos[$taskguid])) {
            $row['userRole'] = $userAssocInfos[$taskguid]['role'];
            $row['userState'] = $userAssocInfos[$taskguid]['state'];
            $row['userStep'] = $this->workflow->getStepOfRole($row['userRole']);
        }
        elseif($isEditAll && isset($this->data->userState)) {
            $row['userState'] = $this->data->userState; //returning the given userState for usage in frontend
        }
        
        //Add all User Assoc Infos to each Task
        if(isset($allAssocInfos[$taskguid])) {
            $reducer = function($accu, $item) {
                return $accu || !empty($item['usedState']);
            };
            $row['isUsed'] = array_reduce($allAssocInfos[$taskguid], $reducer, false);
            $row['users'] = $allAssocInfos[$taskguid];
        }
        
        $row['lockingUsername'] = $this->getUsername($this->getUserinfo($row['lockingUser']));
        
        $fields = ZfExtended_Factory::get('editor_Models_SegmentField');
        /* @var $fields editor_Models_SegmentField */
        
        $userPref = ZfExtended_Factory::get('editor_Models_Workflow_Userpref');
        /* @var $userPref editor_Models_Workflow_Userpref */
        
        //we load alls fields, if we are in taskOverview and are allowed to edit all 
        // or we have no userStep to filter / search by. 
        // No userStep means indirectly that we do not have a TUA (pmCheck)
        if(!$this->entity->isRegisteredInSession() && $isEditAll || empty($row['userStep'])) {
            $row['segmentFields'] = $fields->loadByTaskGuid($taskguid);
            //the pm sees all, so fix userprefs
            $userPref->setNotEditContent(false);
            $userPref->setAnonymousCols(false);
            $userPref->setVisibility($userPref::VIS_SHOW);
            $allFields = array_map(function($item) { 
                return $item['name']; 
            }, $row['segmentFields']);
            $userPref->setFields(join(',', $allFields));
        } else {
            $wf = $this->workflow;
            $userPref->loadByTaskUserAndStep($taskguid, $wf::WORKFLOW_ID, $this->user->data->userGuid, $row['userStep']);
            $row['segmentFields'] = $fields->loadByUserPref($userPref);
        }
        
        $row['userPrefs'] = array($userPref->getDataObject());
        $row['notEditContent'] = (bool)$row['userPrefs'][0]->notEditContent;
        
        //$row['segmentFields'] = $fields->loadByCurrentUser($taskguid);
        foreach($row['segmentFields'] as $key => &$field) {
            //TRANSLATE-318: replacing of a subpart of the column name is a client specific feature
            $needle = $this->config->runtimeOptions->segments->fieldMetaIdentifier;
            if(!empty($needle)) {
                $field['label'] = str_replace($needle, '', $field['label']);
            }
            $field['label'] = $this->translate->_($field['label']);
        } 
        if(empty($this->segmentFieldManager)) {
            $this->segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        }
        //sets the information if this task has default segment field layout or not
        $row['defaultSegmentLayout'] = $this->segmentFieldManager->isDefaultLayout(array_map(function($field){
            return $field['name'];
        }, $row['segmentFields']));
    }
    
    /**
     * returns true if PUT Requests opens a task for editing or readonly
     * 
     * - its not allowed to set both parameters to true
     * @param boolean $editOnly if set to true returns true only if its a real editing (not readonly) request
     * @param boolean $viewOnly if set to true returns true only if its a readonly request
     * 
     * @return boolean
     */
    protected function isOpenTaskRequest($editOnly = false,$viewOnly = false) {
        if(empty($this->data->userState)) {
            return false;
        }
        if($editOnly && $viewOnly){
            throw new Zend_Exception('editOnly and viewOnly can not both be true');
        }
        $s = $this->data->userState;
        $workflow = $this->workflow;
        return $editOnly && $s == $workflow::STATE_EDIT 
           || !$editOnly && ($s == $workflow::STATE_EDIT || $s == $workflow::STATE_VIEW)
           || $viewOnly && $s == $workflow::STATE_VIEW;
    }
    
    /**
     * locks the current task if its an editing request
     * stores the task as active task if its an opening or an editing request
     */
    protected function openAndLock() {
        $session = new Zend_Session_Namespace();
        $task = $this->entity;
        if($this->isOpenTaskRequest(true)){
            $workflow = $this->workflow;
            $unconfirmed = $task->getState() == $task::STATE_UNCONFIRMED;
            //first check for confirmation, if unconfirmed, don't lock just set to view mode!
            if($unconfirmed || !$task->lock($this->now)){
                $this->data->userState = $workflow::STATE_VIEW;
            }
        }
        if($this->isOpenTaskRequest()){
            $task->createMaterializedView();
            $task->registerInSession($this->data->userState);
            $this->events->trigger("afterTaskOpen", $this, array(
                'task' => $task, 
                'view' => $this->view, 
                'openState' => $this->data->userState)
            );
            $manager = ZfExtended_Factory::get('editor_Services_Manager');
            /* @var $manager editor_Services_Manager */
            $manager->openForTask($task);
        }
    }
    
    /**
     * unlocks the current task if its an request that closes the task (set state to open, end, finish)
     * removes the task from session
     */
    protected function closeAndUnlock() {
        $workflow = $this->workflow;
        $closingStates = array(
            $workflow::STATE_FINISH,
            $workflow::STATE_OPEN
        );
        $task = $this->entity;
        $hasState = !empty($this->data->userState);
        $isEnding = isset($this->data->state) && $this->data->state == $task::STATE_END;
        $resetToOpen = $hasState && $this->data->userState == $workflow::STATE_EDIT && $isEnding;
        if($resetToOpen) {
            //This state change will be saved at the end of this method.
            $this->data->userState = $workflow::STATE_OPEN;
        }
        if(!$isEnding && (!$hasState || !in_array($this->data->userState, $closingStates))){
            return;
        }
        if($this->entity->getLockingUser() == $this->user->data->userGuid) {
            if(!$this->entity->unlock()){
                throw new Zend_Exception('task '.$this->entity->getTaskGuid().
                        ' could not be unlocked by user '.$this->user->data->userGuid);
            }
        }
        $this->unregisterTask();
        
        if($resetToOpen) {
            $this->updateUserState($this->user->data->userGuid, true);
        }
    }
    
    /**
     * unregisters the task from the session and close all open services 
     */
    protected function unregisterTask() {
        $this->entity->unregisterInSession();
        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */
        $manager->closeForTask($this->entity);
    }
    
    /**
     * Updates the transferred User Assoc State to the given userGuid (normally the current user)
     * Per Default all state changes trigger something in the workflow. In some circumstances this should be disabled.
     * @param string $userGuid
     * @param boolean $disableWorkflowEvents optional, defaults to false
     */
    protected function updateUserState(string $userGuid, $disableWorkflowEvents = false) {
        if(empty($this->data->userState)) {
            return;
        }

        if(!in_array($this->data->userState, $this->workflow->getStates())) {
            throw new ZfExtended_Models_Entity_NotAcceptableException('Given UserState '.$this->data->userState.' does not exist.');
        }
        
        $isEditAllTasks = $this->isAllowed('backend', 'editAllTasks') || $this->isAuthUserTaskPm($this->entity->getPmGuid());
        $isOpen = $this->isOpenTaskRequest();
        $isPmOverride = false;
        
        $taskGuid = $this->entity->getTaskGuid();
        
        $userTaskAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $userTaskAssoc editor_Models_TaskUserAssoc */
        try {
            $userTaskAssoc->loadByParams($userGuid,$taskGuid);
            $isPmOverride = (boolean) $userTaskAssoc->getIsPmOverride();
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            if(! $isEditAllTasks){
                throw $e;
            }
            $userTaskAssoc->setUserGuid($userGuid);
            $userTaskAssoc->setTaskGuid($taskGuid);
            $userTaskAssoc->setRole('');
            $userTaskAssoc->setState('');
            $isPmOverride = true;
            $userTaskAssoc->setIsPmOverride($isPmOverride);
        }

        $oldUserTaskAssoc = clone $userTaskAssoc;
        
        if($isOpen){
            $session = new Zend_Session_Namespace();
            $userTaskAssoc->setUsedInternalSessionUniqId($session->internalSessionUniqId);
            $userTaskAssoc->setUsedState($this->data->userState);
        } else {
            if($isPmOverride && $isEditAllTasks) {
                $this->log->info('E1011', 'PM left task');
                $userTaskAssoc->deletePmOverride();
                return;
            }
            $userTaskAssoc->setUsedInternalSessionUniqId(null);
            $userTaskAssoc->setUsedState(null);
        }
        
        if($this->workflow->isStateChangeable($userTaskAssoc)) {
            $userTaskAssoc->setState($this->data->userState);
        }
        
        if(!$disableWorkflowEvents) {
            $this->workflow->triggerBeforeEvents($oldUserTaskAssoc, $userTaskAssoc);
        }
        $userTaskAssoc->save();
        
        if(!$disableWorkflowEvents) {
            $this->workflow->doWithUserAssoc($oldUserTaskAssoc, $userTaskAssoc);
        }
        
        if($oldUserTaskAssoc->getState() != $this->data->userState){
            $this->log->info('E1011', 'job status changed from {oldState} to {newState}', [
                'tua' => $oldUserTaskAssoc,
                'oldState' => $oldUserTaskAssoc->getState(),
                'newState' => $this->data->userState,
            ]);
        }
    }
    
    /**
     * Adds the Task Specific QM SUb Segment Infos to the request result.
     * Not usable for indexAction, must be called after entity->save and this->view->rows = Data
     */
    protected function addQmSubToResult() {
        $qmSubFlags = $this->entity->getQmSubsegmentFlags();
        $this->view->rows->qmSubEnabled = false;
        if($this->config->runtimeOptions->editor->enableQmSubSegments &&
                !empty($qmSubFlags)) { 
            $this->view->rows->qmSubFlags = $this->entity->getQmSubsegmentIssuesTranslated(false);
            $this->view->rows->qmSubSeverities = $this->entity->getQmSubsegmentSeveritiesTranslated(false);
            $this->view->rows->qmSubEnabled = true;
        }
        unset($this->view->rows->qmSubsegmentFlags);
    }
    
    /**
     * gets and validates the uploaded zip file
     */
    protected function additionalValidations() {
        $this->upload->initAndValidate();
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::getAction()
     */
    public function getAction() {
        parent::getAction();
        $taskguid = $this->entity->getTaskGuid();
        $this->initWorkflow();
        
        $obj = $this->entity->getDataObject();
        
        $userAssocInfos = array();
        $allAssocInfos = $this->getUserAssocInfos(array($taskguid), $userAssocInfos);
        
        //because we are mixing objects (getDataObject) and arrays (loadAll) as entity container we have to cast here
        $row = (array) $obj; 
        $this->addUserInfos($row, $taskguid, $userAssocInfos, $allAssocInfos);
            
        $this->view->rows = (object)$row;
        unset($this->view->rows->qmSubsegmentFlags);
        
        //add task assoc to the task
        $languageResourcemodel = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /*@var $languageResourcemodel editor_Models_LanguageResources_LanguageResource */
        $resultlist =$languageResourcemodel->loadByAssociatedTaskGuidList(array($taskguid));
        $this->view->rows->taskassocs = $resultlist;
        
        // Add pixelMapping-data for the fonts used in the task.
        // We do this here to have it immediately available e.g. when opening segments.
        $this->addPixelMapping();
        $this->view->rows->lastErrors = $this->getLastErrorMessage($this->entity->getTaskGuid(), $this->entity->getState());
    }
    
    public function deleteAction() {
        $forced = $this->getParam('force', false) && $this->isAllowed('backend', 'taskForceDelete');
        $this->entityLoad();
        //if task is erroneous then it is also deleteable, regardless of its locking state
        if(!$this->entity->isImporting() && !$this->entity->isErroneous() && !$forced){
            $this->entity->checkStateAllowsActions();
        }
        //we enable task deletion for importing task
        $forced=$forced || $this->entity->isImporting();
        
        $this->processClientReferenceVersion();
        $remover = ZfExtended_Factory::get('editor_Models_Task_Remover', array($this->entity));
        /* @var $remover editor_Models_Task_Remover */
        $remover->remove($forced);
    }
    
    /**
     * does the export as zip file.
     */
    public function exportAction() {
        parent::getAction();
        
        
        $diff = (boolean)$this->getRequest()->getParam('diff');
        
        $context = $this->_helper->getHelper('contextSwitch')->getCurrentContext();
        switch ($context) {
            case 'importArchive':
                $this->downloadImportArchive();
                return;
                
            case 'xliff2':
                $this->entity->checkStateAllowsActions();
                $worker = ZfExtended_Factory::get('editor_Models_Export_Xliff2Worker');
                $diff = false;
                /* @var $worker editor_Models_Export_Xliff2Worker */
                $exportFolder = $worker->initExport($this->entity);
                break;
            
            default:
                $this->entity->checkStateAllowsActions();
                $worker = ZfExtended_Factory::get('editor_Models_Export_Worker');
                /* @var $worker editor_Models_Export_Worker */
                $exportFolder = $worker->initExport($this->entity, $diff);
                break;
        }        


        $workerId = $worker->queue();
        
        //FIXME multiple problems here
        // it is possible that we get the following in DB (implicit ordererd by ID here):
        //      Export_Worker for ExportReq1
        //      Export_Worker for ExportReq2 → overwrites the tempExportDir of ExportReq1
        //      Export_ExportedWorker for ExportReq2 
        //      Export_ExportedWorker for ExportReq1 → works then with tempExportDir of ExportReq1 instead!
        // 
        // If we implement in future export workers which need to work on the temp export data, 
        //  we have to ensure that each export worker get its own export directory. 
        
        $worker = ZfExtended_Factory::get('editor_Models_Export_ExportedWorker');
        /* @var $worker editor_Models_Export_ExportedWorker */
        $zipFile = $worker->initZip($this->entity->getTaskGuid(), $exportFolder);
        
        
        //TODO for the API usage of translate5 blocking on export makes no sense
        // better would be a URL to fetch the latest export or so (perhaps using state 202?)
        $worker->setBlocking(); //we have to wait for the underlying worker to provide the download
        $worker->queue($workerId);
        
        //currently we can only strip the directory path for xliff2 exports, since for default exports we need this as legacy code
        // can be used in general with implementation of TRANSLATE-764
        if($context == 'xliff2') {
            ZfExtended_Utils::cleanZipPaths(new SplFileInfo($zipFile), basename($exportFolder));
        }
        
        if($diff) {
            $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
            /* @var $translate ZfExtended_Zendoverwrites_Translate */;
            $suffix = $translate->_(' - mit Aenderungen nachverfolgen.zip');
        }
        else {
            $suffix = '.zip';
        }
        
        $this->provideZipDownload($zipFile, $suffix);
        
        //rename file after usage to export.zip to keep backwards compatibility
        rename($zipFile, dirname($zipFile).DIRECTORY_SEPARATOR.'export.zip');
        exit;
    }
    
    /**
     * sends the given $zipFile to the browser, the $nameSuffix is added to the filename provided to the browser
     * @param string $zipFile
     * @param string $nameSuffix
     */
    protected function provideZipDownload($zipFile, $nameSuffix) {
        // disable layout and view
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        header('Content-Type: application/zip', TRUE);
        header('Content-Disposition: attachment; filename="'.$this->entity->getTasknameForDownload($nameSuffix).'"');
        readfile($zipFile);
    }
    
    /**
     * provides the import archive file for download
     * @throws ZfExtended_NoAccessException
     * @throws ZfExtended_NotFoundException
     */
    protected function downloadImportArchive() {
        if(!$this->isAllowed('frontend','downloadImportArchive')) {
            throw new ZfExtended_NoAccessException("The Archive ZIP can not be accessed");
        }
        $archiveZip = new SplFileInfo($this->entity->getAbsoluteTaskDataPath().'/'.editor_Models_Import_DataProvider_Abstract::TASK_ARCHIV_ZIP_NAME);
        if(!$archiveZip->isFile()){
            throw new ZfExtended_NotFoundException("Archive Zip for task ".$this->entity->getTaskGuid()." could not be found");
        }
        $this->provideZipDownload($archiveZip, ' - ImportArchive.zip');
    }
    
    /***
     * Check if the given pmGuid(userGuid) is the same with the current logged user userGuid
     * 
     * @param string $pmGuid
     * @return boolean
     */
    protected function isAuthUserTaskPm($taskPmGuid){
        return $this->user->data->userGuid===$taskPmGuid;
    }
    
    /**
     * Check if the user has rights to modify task attributes
     */
    protected function checkTaskAttributeField(){
        $fieldToRight = [
            'taskName' => 'editorEditTaskTaskName',
            'targetDeliveryDate' => 'editorEditTaskDeliveryDate',
            'realDeliveryDate' => 'editorEditTaskRealDeliveryDate',
            'orderdate' => 'editorEditTaskOrderDate',
            'pmGuid' => 'editorEditTaskPm',
            'pmName' => 'editorEditTaskPm',
        ];
        
        //pre check pm change first
        if(!empty($this->data->pmGuid) && $this->isAllowed('frontend', 'editorEditTaskPm')){
            //if the pmGuid is modified, set the pmName
            $userModel = ZfExtended_Factory::get('ZfExtended_Models_User');
            /* @var $userModel  ZfExtended_Models_User*/
            $user = $userModel->loadByGuid($this->data->pmGuid);
            $this->data->pmName = $user->getUsernameLong();
        }
        
        //then loop over all allowed fields
        foreach($fieldToRight as $field => $right) {
            if(!empty($this->data->$field) && !$this->isAllowed('frontend', $right)) {
                unset($this->data->$field);
                $this->log->warn('E1011', 'The user is not allowed to modify the tasks field {field}', ['field' => $field]);
            }
        }
    }
    
    /**
     * Can be triggered with various actions from outside to trigger workflow stuff in translate5
     */
    public function workflowAction() {
        if(!$this->_request->isPost()) {
            throw new BadMethodCallException('Only HTTP method POST allowed!');
        }
        $this->entityLoad();
        $this->log->request();
        $this->initWorkflow($this->entity->getWorkflow());
        $this->view->trigger = $this->getParam('trigger');
        $this->view->success = $this->workflow->doDirectTrigger($this->entity, $this->getParam('trigger'));
        if($this->view->success) {
            return;
        }
        $errors = array('trigger' => 'Trigger is invalid. Valid triggers are listed below.');
        $e = new ZfExtended_ValidateException();
        $e->setErrors($errors);
        $this->view->validTrigger = $this->workflow->getDirectTrigger();
        $this->handleValidateException($e);
    }
    
    /**
     * Logs a info message to the current task to the task_log table ONLY!
     * @param string $message message to be logged
     * @param array $extraData optional, extra data to the log entry
     */
    protected function logInfo($message, array $extraData = []) {
        // E1011 is he default multipurpose error code for task logging
        $extraData['task'] = $this->entity;
        $this->taskLog->info('E1011', $message, $extraData);
    }
}
