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
     * Cached map of userGuids and taskGuid to userNames
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
        
        ZfExtended_UnprocessableEntity::addCodes([
            'E1064' => 'The referenced customer does not exist (anymore).'
        ], 'editor.task');
        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1159' => 'Task usageMode can only be modified, if no user is assigned to the task.',
            'E1163' => 'Your job was removed, therefore you are not allowed to access that task anymore.',
            'E1164' => 'You tried to open the task for editing, but in the meantime you are not allowed to edit the task anymore.',
        ], 'editor.task');
        
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
        
        //add context of valid export formats:
        // currently: xliff2, importArchive, excel
        $this->_helper
        ->getHelper('contextSwitch')
        
        ->addContext('xliff2', [
            'headers' => [
                'Content-Type'          => 'text/xml',
            ]
        ])
        ->addActionContext('export', 'xliff2')
        
        ->addContext('importArchive', [
            'headers' => [
                'Content-Type'          => 'application/zip',
            ]
        ])
        ->addActionContext('export', 'importArchive')
        
        ->addContext('xlsx', [
            'headers' => [
                'Content-Type'          => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // TODO Content-Type prüfen
            ]
        ])
        ->addActionContext('kpi', 'xlsx')
        
        
        /*
        ->addContext('excel', [
            'headers' => [
                'Content-Type'          => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        ])
        ->addActionContext('export', 'excel')
        
        ->addContext('excelReimport', [
            'headers' => [
                'Content-Type'          => 'text/xml',
            ]
        ])
        ->addActionContext('export', 'excelReimport')
        */
        
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
        
        $rows = $this->loadAll();
        $this->view->rows = $rows;
        $this->view->total = $this->totalCount;
    }
    
    /**
     * For requests that get the Key Performance Indicators (KPI)
     * for the currently filtered tasks. If the tasks are not to be limited
     * to those that are visible in the grid, the request must have set the 
     * limit accordingly (= for all filtered tasks: no limit).
     */
    public function  kpiAction() {
        $f = $this->entity->getFilter();
        $f->hasSort() || $f->addSort('orderdate', true);
        $rows = $this->loadAll();
        
        $kpi = ZfExtended_Factory::get('editor_Models_KPI');
        /* @var $kpi editor_Models_KPI */
        $kpi->setTasks($rows);
        $kpiStatistics = $kpi->getStatistics();
        
        // For Front-End:
        $this->view->averageProcessingTime = $kpiStatistics['averageProcessingTime'];
        $this->view->excelExportUsage = $kpiStatistics['excelExportUsage'];
        
        // ... or as Metadata-Excel-Export (= task-overview, filter, key performance indicators KPI):
        $context = $this->_helper->getHelper('contextSwitch')->getCurrentContext();
        if ($context == 'xlsx') {
            $exportMetaData = ZfExtended_Factory::get('editor_Models_Task_Export_Metadata');
            /* @var $exportMetaData editor_Models_Task_Export_Metadata */
            $exportMetaData->setTasks($rows);
            $exportMetaData->setFilters(json_decode($this->getParam('filter')));
            $exportMetaData->setColumns(json_decode($this->getParam('visibleColumns')));
            $exportMetaData->setKpiStatistics($kpiStatistics);
            $exportMetaData->exportAsDownload();
        }
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
        
        //if the config for mailto link in task grid pm user column is configured
        $isMailTo=$this->config->runtimeOptions->frontend->tasklist->pmMailTo;
        
        $customerData = $this->getCustomersForRendering($rows);

        if($isMailTo){
            $userData=$this->getUsersForRendering($rows);
        }
        
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
            
            if($isMailTo) {
                $row['pmMail'] = empty($userData[$row['pmGuid']]) ? '' : $userData[$row['pmGuid']];
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
            $userInfo = $this->getUserinfo($assoc['userGuid'], $assoc['taskGuid']);
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
     * returns the username for the given userGuid.
     * Doing this on client side would be possible, but then it must be ensured that UsersStore is always available and loaded before TaskStore. 
     * @param string $userGuid
     * @param string $taskGuid
     * @return array
     */
    protected function getUserinfo($userGuid, $taskGuid) {
        $notfound = array(); //should not be, but can occur after migration of old data!
        if(empty($userGuid)) {
            return $notfound;
        }
        if(isset($this->cachedUserInfo[$userGuid])) {
            // cache for user
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
        $userInfo = $row->toArray();
        
        $this->cachedUserInfo[$userGuid] = $userInfo;
        return $userInfo; 
    }
    
    /**
     * returns the commonly used username: Firstname Lastname (login)
     * @param array $userinfo
     */
    protected function getUsername(array $userinfo) {
        if(empty($userinfo)) {
            return '- not found -'; //should not be, but can occur e.g. after migration of old data or for lockingUsername
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
            return [];
        }
        
        $customer = ZfExtended_Factory::get('editor_Models_Customer');
        /* @var $customer editor_Models_Customer */
        $customerData = $customer->loadByIds($customerIds);
        return array_combine(array_column($customerData, 'id'), array_column($customerData, 'name'));
    }
    
    /***
     * Return a mapping of user guid and user email
     * @param array $rows
     * @return array|array
     */
    protected function getUsersForRendering(array $rows) {
        if(empty($rows)){
            return [];
        }
        
        $userGuids = array_map(function($item){
            return $item['pmGuid'];
        },$rows);
            
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $userData = $user->loadByGuids($userGuids);
        $ret=[];
        foreach ($userData as $data){
            $ret[$data['userGuid']]=$data['email'];
        }
        return $ret;
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
        $pm = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $pm ZfExtended_Models_User */
        if(empty($this->data['pmGuid']) || !$this->isAllowed('frontend','editorEditTaskPm')) {
            $this->data['pmGuid'] = $this->user->data->userGuid;
            $pm->init((array)$this->user->data);
        }
        else {
            //TODO test what happens with new error logging if PM does not exist? 
            $pm->loadByGuid($this->data['pmGuid']);
        }
        $this->data['pmName'] = $pm->getUsernameLong();
        $this->processClientReferenceVersion();
        $this->_helper->Api->convertLanguageParameters($this->data['sourceLang']);
        $this->_helper->Api->convertLanguageParameters($this->data['targetLang']);
        $this->_helper->Api->convertLanguageParameters($this->data['relaisLang']);
        
        $this->setDataInEntity();
        $this->entity->setUsageMode($this->config->runtimeOptions->import->initialTaskUsageMode);
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
    
    
    /**
     * Starts the export of a task into an excel file
     */
    public function excelexportAction() {
        $this->entityLoad();
        
        // run excel export
        $exportExcel = ZfExtended_Factory::get('editor_Models_Export_Excel', [$this->entity]);
        $this->log->info('E1011', 'Task exported as excel file and locked for further processing.');
        /* @var $exportExcel editor_Models_Export_Excel */
        $exportExcel->exportAsDownload();
    }
    
    /**
     * Starts the reimport of an earlier exported excel into the task
     */
    public function excelreimportAction() {
        $this->getAction();
        
        $worker = ZfExtended_Factory::get('editor_Models_Excel_Worker');
        /* @var $worker editor_Models_Excel_Worker */
        
        try {
            $tempFilename = $worker->prepareImportFile($this->entity);
            
            $worker->init($this->entity->getTaskGuid(), [
                'filename' => $tempFilename,
                'currentUserGuid' => $this->user->data->userGuid,
            ]);
            //TODO should be an synchronous process (queue instead run) 
            // currently running import as direct run / synchronous process. 
            // Reason is just the feedback for the user, which the user should get directly in the browser
            $worker->run();
            $this->log->info('E1011', 'Task re-imported from excel file and unlocked for further processing.');
        }
        catch(editor_Models_Excel_ExImportException $e) {
            $this->handleExcelreimportException($e);
        }
        
        if ($segmentErrors = $worker->getSegmentErrors()) {
            $logger = Zend_Registry::get('logger')->cloneMe('editor.task.exceleximport');
            /* @var $logger ZfExtended_Logger */
            
            $msg = 'Error on excel reimport in the following segments. Please check the following segment(s):';
            // log warning 'E1141' => 'Excel Reimport: at least one segment needs to be controlled.',
            $logger->warn('E1142', $msg."\n{segments}", [
                'task' => $this->entity,
                'segments' => join("\n", array_map(function(excelExImportSegmentContainer $item) {
                    return '#'.$item->nr.': '.$item->comment;
                }, $segmentErrors)),
            ]);
            $msg = $this->translate->_('Die Excel-Datei konnte reimportiert werden, die nachfolgenden Segmente beinhalten aber Fehler und müssen korrigiert werden:');
            $this->restMessages->addWarning($msg, $logger->getDomain(), null, array_map(function(excelExImportSegmentContainer $item) {
                return ['type' => $item->nr, 'error' => $item->comment];
            }, $segmentErrors));
            $user = ZfExtended_Factory::get('ZfExtended_Models_User');
            /* @var $user ZfExtended_Models_User */
            $user->init((array) $this->user->data);
            $worker->mailSegmentErrors($user);
        }
        $this->view->success = true;
    }
    
    /**
     * Handles the exceptions happened on excel reimport
     * @param ZfExtended_ErrorCodeException $e
     * @throws ZfExtended_ErrorCodeException
     */
    protected function handleExcelreimportException(ZfExtended_ErrorCodeException $e) {
        $codeToFieldAndMessage = [
            'E1138' => ['excelreimportUpload', 'Die Excel Datei gehört nicht zu dieser Aufgabe.'],
            'E1139' => ['excelreimportUpload', 'Die Anzahl der Segmente in der Excel-Datei und in der Aufgabe sind unterschiedlich!'],
            'E1140' => ['excelreimportUpload', 'Ein oder mehrere Segmente sind in der Excel-Datei leer, obwohl in der Orginalaufgabe Inhalt vorhanden war.'],
            'E1141' => ['excelreimportUpload', 'Dateiupload fehlgeschlagen. Bitte versuchen Sie es erneut.'],
        ];
        $code = $e->getErrorCode();
        if(empty($codeToFieldAndMessage[$code])) {
            throw $e;
        }
        // the Import exceptions causing unprossable entity exceptions are logged on level info
        $this->log->exception($e, [
            'level' => ZfExtended_Logger::LEVEL_INFO
        ]);
        
        throw ZfExtended_UnprocessableEntity::createResponseFromOtherException($e, [
            //fieldName => error message to field
            $codeToFieldAndMessage[$code][0] => $codeToFieldAndMessage[$code][1]
        ]);
    }
    
    protected function startImportWorkers() {
        $workerModel = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $workerModel ZfExtended_Models_Worker */
        $workerModel->loadFirstOf('editor_Models_Import_Worker', $this->entity->getTaskGuid());
        $worker = ZfExtended_Worker_Abstract::instanceByModel($workerModel);
        $worker && $worker->schedulePrepared();
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
        
        try {
            $import->import($dp);
        }
        catch(editor_Models_Import_ConfigurationException $e) {
            $this->handleConfigurationException($e);
        }
        catch(ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
            $this->handleIntegrityConstraint($e);
        }
    }
    
    /**
     * Converts the ConfigurationException caused by wrong user input to ZfExtended_UnprocessableEntity exceptions
     * @param editor_Models_Import_ConfigurationException $e
     * @throws editor_Models_Import_ConfigurationException
     * @throws ZfExtended_UnprocessableEntity
     */
    protected function handleConfigurationException(editor_Models_Import_ConfigurationException $e) {
        $codeToFieldAndMessage = [
            'E1032' => ['sourceLang', 'Die übergebene Quellsprache "{language}" ist ungültig!'],
            'E1033' => ['targetLang', 'Die übergebene Zielsprache "{language}" ist ungültig!'],
            'E1034' => ['relaisLang', 'Es wurde eine Relaissprache gesetzt, aber im Importpaket befinden sich keine Relaisdaten.'],
            'E1039' => ['importUpload', 'Das importierte Paket beinhaltet kein gültiges "{proofRead}" Verzeichnis.'],
            'E1040' => ['importUpload', 'Das importierte Paket beinhaltet keine Dateien im "{proofRead}" Verzeichnis.'],
        ];
        $code = $e->getErrorCode();
        if(empty($codeToFieldAndMessage[$code])) {
            throw $e;
        }
        // the config exceptions causing unprossable entity exceptions are logged on level info
        $this->log->exception($e, [
            'level' => ZfExtended_Logger::LEVEL_INFO
        ]);
        
        throw ZfExtended_UnprocessableEntity::createResponseFromOtherException($e, [
            //fieldName => error message to field
            $codeToFieldAndMessage[$code][0] => $codeToFieldAndMessage[$code][1]
        ]);
    }
    
    /**
     * Converts the IntegrityConstraint Exceptions caused by wrong user input to ZfExtended_UnprocessableEntity exceptions
     * @param ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_UnprocessableEntity
     */
    protected function handleIntegrityConstraint(ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
        //check if the error comes from the customer assoc or not
        if(! $e->isInMessage('REFERENCES `LEK_customer`')) {
            throw $e;
        }
        throw ZfExtended_UnprocessableEntity::createResponse('E1064', [
            'customerId' => 'Der referenzierte Kunde existiert nicht (mehr)'
        ], [], $e);
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
        $oldTaskGuid=$data['taskGuid'];
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
        ZfExtended_Utils::cleanZipPaths($copy, editor_Models_Import_DataProvider_Abstract::TASK_TEMP_IMPORT);
        $this->upload->initByGivenZip($copy);
        
        if($this->validate()) {
            $this->processUploadedFile();
            $this->cloneLanguageResources($oldTaskGuid,$this->entity->getTaskGuid());
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
        
        //task manipulation is allowed additionally on excel export (for opening read only, changing user states etc)
        $this->entity->checkStateAllowsActions([editor_Models_Excel_AbstractExImport::TASK_STATE_ISEXCELEXPORTED]);
        
        $taskguid = $this->entity->getTaskGuid();
        $this->log->request();
        
        $oldTask = clone $this->entity;
        $this->decodePutData();
        if(isset($this->data->edit100PercentMatch)){
            settype($this->data->edit100PercentMatch, 'integer');
        }
        
        $this->checkTaskAttributeField();
        //was formerly in JS: if a userState is transfered, then entityVersion has to be ignored!
        // but what we do is to check the previous userState. So we have control if entity was not uptodate regarding state, and we could assume the wanted transition since we have a start (the previous) and an end (the new) state
        if(!empty($this->data->userState)) {
            unset($this->data->entityVersion);
        }
        if(isset($this->data->enableSourceEditing)){
            $this->data->enableSourceEditing = (boolean)$this->data->enableSourceEditing;
        }
        $this->processClientReferenceVersion();
        $this->setDataInEntity();
        $this->validateUsageMode();
        $this->entity->validate();
        $this->initWorkflow();
        
        $mayLoadAllTasks = $this->isAllowed('backend', 'loadAllTasks') || $this->isAuthUserTaskPm($this->entity->getPmGuid());
        $tua = $this->workflow->getTaskUserAssoc($taskguid, $this->user->data->userGuid);
        //mayLoadAllTasks is only true, if the current "PM" is not associated to the task directly. 
        // If it is (pm override false) directly associated, the workflow must be considered it the task is openable / writeable.  
        $mayLoadAllTasks = $mayLoadAllTasks && (empty($tua) || $tua->getIsPmOverride());
        $isTaskDisallowEditing = $this->isEditTaskRequest() && !$this->workflow->isWriteable($tua);
        $isTaskDisallowReading = $this->isViewTaskRequest() && !$this->workflow->isReadable($tua);
        if(!$mayLoadAllTasks && ($isTaskDisallowEditing || $isTaskDisallowReading)){
            //if the task was already in session, we must delete it. 
            //If not the user will always receive an error in JS, and would not be able to do anything.
            $this->unregisterTask(); //FIXME XXX the changes in the session made by this method is not stored in the session!
            if(empty($tua)) {
                throw ZfExtended_Models_Entity_Conflict::createResponse('E1163',[
                    'userState' => 'Ihre Zuweisung zur Aufgabe wurde entfernt, daher können Sie diese nicht mehr zur Bearbeitung öffnen.',
                ]);
            }
            if($isTaskDisallowEditing && $this->data->userStatePrevious != $tua->getState()) {
                throw ZfExtended_Models_Entity_Conflict::createResponse('E1164',[
                    'userState' => 'Sie haben versucht die Aufgabe zur Bearbeitung zu öffnen. Das ist in der Zwischenzeit nicht mehr möglich.',
                ]);
            }
            //no access as generic fallback
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
        
        //if the edit100PercentMatch is changed, update the value for all segments in the task
        if(isset($this->data->edit100PercentMatch)){
            $this->entity->updateSegmentsEdit100PercentMatch($this->entity, (boolean)$this->data->edit100PercentMatch);
        }
        
        $this->entity->save();
        $obj = $this->entity->getDataObject();
        
        $userAssocInfos = array();
        $allAssocInfos = $this->getUserAssocInfos(array($taskguid), $userAssocInfos);
        
        $this->invokeTaskUserTracking($taskguid, $userAssocInfos);
        
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
    
    /**
     * Throws a ZfExtended_Models_Entity_Conflict if usageMode is changed and the task has already assigned users
     */
    protected function validateUsageMode() {
        if(!$this->entity->isModified('usageMode')) {
            return;
        }
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        $used = $tua->loadByTaskGuidList([$this->entity->getTaskGuid()]);
        if(empty($used)) {
            return;
        }
        throw ZfExtended_Models_Entity_Conflict::createResponse('E1159', [
            'usageMode' => [
                'usersAssigned' => 'Der Nutzungsmodus der Aufgabe kann verändert werden, wenn kein Benutzer der Aufgabe zugewiesen ist.'
            ]
        ]);
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
     * Adds additional user based infos to the given array.
     * If the given taskguid is assigned to a client for anonymizing data, the added user-data is anonymized already.
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
        
        $row['lockingUsername'] = null;
        
        if(!empty($row['lockingUser'])){
            $row['lockingUsername'] = $this->getUsername($this->getUserinfo($row['lockingUser'],$taskguid));
        }
        
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
        foreach($row['segmentFields'] as &$field) {
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
        
        // anonymize userinfo?
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskguid);
        if ($task->anonymizeUsers()) {
            $workflowAnonymize = ZfExtended_Factory::get('editor_Workflow_Anonymize');
            /* @var $workflowAnonymize editor_Workflow_Anonymize */
            if(!empty($row['lockingUser'])) {
                $row = $workflowAnonymize->anonymizeUserdata($taskguid, $row['lockingUser'], $row);
            }
            if(!empty($row['users'])) {
                foreach ($row['users'] as &$rowUser) {
                    $rowUser = $workflowAnonymize->anonymizeUserdata($taskguid, $rowUser['userGuid'], $rowUser);
                }
            }
        }
    }
    
    /**
     * returns true if PUT Requests opens a task for editing or readonly
     * @return boolean
     */
    protected function isOpenTaskRequest(): bool {
        return $this->isEditTaskRequest() || $this->isViewTaskRequest();
    }
    
    /**
     * returns true if PUT Requests opens a task for editing or readonly
     * @return boolean
     */
    protected function isLeavingTaskRequest(): bool {
        if(empty($this->data->userState)) {
            return false;
        }
        return $this->data->userState == $this->workflow::STATE_OPEN || $this->data->userState == $this->workflow::STATE_FINISH;
    }
    
    /**
     * returns true if PUT Requests opens a task for editing
     * @return boolean
     */
    protected function isEditTaskRequest(): bool {
        if(empty($this->data->userState)) {
            return false;
        }
        return $this->data->userState == $this->workflow::STATE_EDIT;
    }
    
    /**
     * returns true if PUT Requests opens a task for viewing(readonly)
     * @return boolean
     */
    protected function isViewTaskRequest(): bool {
        if(empty($this->data->userState)) {
            return false;
        }
        return $this->data->userState == $this->workflow::STATE_VIEW;
    }
    
    /**
     * invokes taskUserTracking if its an opening or an editing request
     * (no matter if the workflow-users of the task are to be anonymized or not)
     * param string $taskguid
     * @param array $userAssocInfos
     */
    protected function invokeTaskUserTracking($taskguid, $userAssocInfos) {
        if(!$this->isOpenTaskRequest()){
            return;
        }
        $taskUserTracking = ZfExtended_Factory::get('editor_Models_TaskUserTracking');
        /* @var $taskUserTracking editor_Models_TaskUserTracking */
        $taskUserTracking->insertTaskUserTrackingEntry($taskguid, $this->user->data->userGuid, $userAssocInfos[$taskguid]['role']);
    }
    
    /**
     * locks the current task if its an editing request
     * stores the task as active task if its an opening or an editing request
     */
    protected function openAndLock() {
        $task = $this->entity;
        if($this->isEditTaskRequest()){
            $unconfirmed = $task->getState() == $task::STATE_UNCONFIRMED;
            //first check for confirmation on task level, if unconfirmed, don't lock just set to view mode!
            if($unconfirmed || !$task->lock($this->now)){
                $this->data->userState = $this->workflow::STATE_VIEW;
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
        $task = $this->entity;
        $hasState = !empty($this->data->userState);
        $isEnding = isset($this->data->state) && $this->data->state == $task::STATE_END;
        $resetToOpen = $hasState && $this->data->userState == $workflow::STATE_EDIT && $isEnding;
        if($resetToOpen) {
            //This state change will be saved at the end of this method.
            $this->data->userState = $workflow::STATE_OPEN;
        }
        if(!$isEnding && (!$this->isLeavingTaskRequest())){
            return;
        }
        if($this->entity->getLockingUser() == $this->user->data->userGuid && !$this->entity->unlock()) {
            throw new Zend_Exception('task '.$this->entity->getTaskGuid().
                    ' could not be unlocked by user '.$this->user->data->userGuid);
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
     * @param bool $disableWorkflowEvents optional, defaults to false
     */
    protected function updateUserState(string $userGuid, $disableWorkflowEvents = false) {
        if(empty($this->data->userState)) {
            return;
        }
        settype($this->data->userStatePrevious, 'string');

        if(!in_array($this->data->userState, $this->workflow->getStates())) {
            throw new ZfExtended_ValidateException('Given UserState '.$this->data->userState.' does not exist.');
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
                if($this->isLeavingTaskRequest()) {
                    $messages = Zend_Registry::get('rest_messages');
                    /* @var $messages ZfExtended_Models_Messages */
                    $messages->addError('Achtung: die aktuell geschlossene Aufgabe wurde Ihnen entzogen.');
                    return; //just allow the user to leave the task - but send a message
                }
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
        
        if($this->workflow->isStateChangeable($userTaskAssoc, $this->data->userStatePrevious)) {
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
                $this->logInfo('Task import archive downloaded');
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
        // we have to ensure that each export worker get its own export directory. 
        
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
            /* @var $translate ZfExtended_Zendoverwrites_Translate */
            $suffix = $translate->_(' - mit Aenderungen nachverfolgen.zip');
        }
        else {
            $suffix = '.zip';
        }
        
        $this->logInfo('Task exported', ['context' => $context, 'diff' => $diff]);
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

    /***
     * Clone existing language resources from oldTaskGuid for newTaskGuid.
     */
    protected function cloneLanguageResources(string $oldTaskGuid,string $newTaskGuid){
        try{

            $model=ZfExtended_Factory::get('editor_Models_LanguageResources_Taskassoc');
            /* @var $model editor_Models_LanguageResources_Taskassoc */
            $assocs=$model->loadByTaskGuids([$oldTaskGuid]);
            if(empty($assocs)){
                return;
            }
            foreach($assocs as $assoc){
                unset($assoc['id']);
                if(!empty($assoc['autoCreatedOnImport'])) {
                    //do not clone such TermCollection associations, since they are recreated through the cloned import package
                    continue; 
                }
                $assoc['taskGuid'] = $newTaskGuid;
                $model->init($assoc);
                $model->save();
            }
        }catch(ZfExtended_Models_Entity_NotFoundException $e){
            return;
        }
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
