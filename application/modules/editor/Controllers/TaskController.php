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
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;

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
    
    /**
     * Flag if current indexAction request should deliver tasks or projects
     */
    protected $projectRequest = false;

    public function init() {

        $this->_filterTypeMap= [
            'customerId' => [
                //'string' => new ZfExtended_Models_Filter_JoinHard('editor_Models_Db_Customer', 'name', 'id', 'customerId')
                'string' => new ZfExtended_Models_Filter_Join('LEK_customer', 'name', 'id', 'customerId')
            ],
            'workflowState' => [
                'list' => new ZfExtended_Models_Filter_Join('LEK_taskUserAssoc', 'state', 'taskGuid', 'taskGuid')
            ],
            'workflowUserRole' => [
                'list' => new ZfExtended_Models_Filter_Join('LEK_taskUserAssoc', 'role', 'taskGuid', 'taskGuid')
            ],
            'userName' => [
                'list' => new ZfExtended_Models_Filter_Join('LEK_taskUserAssoc', 'userGuid', 'taskGuid', 'taskGuid')
            ],
            'segmentFinishCount' => [
                'numeric' => 'percent',
                'totalField'=>'segmentCount'
            ],
            'userState' => [
                'list' => new ZfExtended_Models_Filter_Join('LEK_taskUserAssoc', 'state', 'taskGuid', 'taskGuid')
            ],
            'orderdate' => [
                'numeric' => 'date',
            ],
            'assignmentDate' => [
                'numeric' => new ZfExtended_Models_Filter_Join('LEK_taskUserAssoc', 'assignmentDate', 'taskGuid', 'taskGuid', 'date')
            ],
            'finishedDate' => [
                'numeric' => new ZfExtended_Models_Filter_Join('LEK_taskUserAssoc', 'finishedDate', 'taskGuid', 'taskGuid', 'date')
            ],
            'deadlineDate' => [
                'numeric' => new ZfExtended_Models_Filter_Join('LEK_taskUserAssoc', 'deadlineDate', 'taskGuid', 'taskGuid', 'date')
            ]
        ];

        //$this->_sortColMap['workflowState'] = $this->_filterTypeMap['taskState']['list'];
        //$this->_sortColMap['userRole'] = $this->_filterTypeMap['userRole']['list'];
        //$this->_sortColMap['userName'] = $this->_filterTypeMap['userName']['list'];

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

        ->addContext('filetranslation', [
            'headers' => [
                'Content-Type'          => 'application/octet-stream',
            ]
        ])
        ->addActionContext('export', 'filetranslation')

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
        $this->addDefaultSort();
        if($this->handleProjectRequest()) {
            $this->view->rows = $this->loadAllForProjectOverview();
        }
        else {
            $this->view->rows = $this->loadAllForTaskOverview();
        }
        $this->view->total = $this->totalCount;
    }

    /**
     * For requests that get the Key Performance Indicators (KPI)
     * for the currently filtered tasks. If the tasks are not to be limited
     * to those that are visible in the grid, the request must have set the
     * limit accordingly (= for all filtered tasks: no limit).
     */
    public function  kpiAction() {
        //set default sort
        $this->addDefaultSort();
        $rows = $this->loadAll();

        $kpi = ZfExtended_Factory::get('editor_Models_KPI');
        /* @var $kpi editor_Models_KPI */
        $kpi->setTasks($rows);
        $kpiStatistics = $kpi->getStatistics();

        // For Front-End:
        $this->view->{$kpi::KPI_TRANSLATOR} = $kpiStatistics[$kpi::KPI_TRANSLATOR];
        $this->view->{$kpi::KPI_REVIEWER}= $kpiStatistics[$kpi::KPI_REVIEWER];
        $this->view->{$kpi::KPI_TRANSLATOR_CHECK} = $kpiStatistics[$kpi::KPI_TRANSLATOR_CHECK];

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
    /***
     * Load all task assoc users for non anonymized tasks.
     * This is used for the user workflow filter in the advance filter store
     */
    public function userlistAction(){
        //set default sort
        $this->addDefaultSort();
        //set the default table to lek_task
        $this->entity->getFilter()->setDefaultTable('LEK_task');
        $this->view->rows=$this->entity->loadUserList($this->user->data->userGuid);
    }

    /**
     * loads all tasks according to the set filters
     * @return array
     */
    protected function loadAll(){
        // here no check for pmGuid, since this is done in task::loadListByUserAssoc
        $isAllowedToLoadAll = $this->isAllowed('backend', 'loadAllTasks');
        //set the default table to lek_task
        $this->entity->getFilter()->setDefaultTable('LEK_task');
        if($isAllowedToLoadAll) {
            $this->totalCount = $this->entity->getTotalCount();
            $rows = $this->entity->loadAll();
        }
        else {
            $this->totalCount = $this->entity->getTotalCountByUserAssoc($this->user->data->userGuid, $isAllowedToLoadAll);
            $rows = $this->entity->loadListByUserAssoc($this->user->data->userGuid, $isAllowedToLoadAll);
        }
        return $rows;
    }

    /**
     * returns all (filtered) tasks with added user data
     * uses $this->entity->loadAll, but unsets qmSubsegmentFlags for all rows and
     * set qmSubEnabled for all rows
     */
    protected function loadAllForProjectOverview() {
        $rows = $this->loadAll();
        $customerData = $this->getCustomersForRendering($rows);
        foreach ($rows as &$row) {
            $row['customerName'] = empty($customerData[$row['customerId']]) ? '' : $customerData[$row['customerId']];
        }
        return $rows;
    }
    
    /**
     * returns all (filtered) tasks with added user data
     * uses $this->entity->loadAll, but unsets qmSubsegmentFlags for all rows and
     * set qmSubEnabled for all rows
     */
    protected function loadAllForTaskOverview() {
        $rows = $this->loadAll();
        $taskGuids = array_map(function($item){
            return $item['taskGuid'];
        },$rows);

        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $fileCount = $file->getFileCountPerTasks($taskGuids);

        $this->_helper->TaskUserInfo->initUserAssocInfos($rows);

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
            
            unset($row['qmSubsegmentFlags']);

            $row['customerName'] = empty($customerData[$row['customerId']]) ? '' : $customerData[$row['customerId']];

            $isEditAll = $this->isAllowed('backend', 'editAllTasks') || $this->isAuthUserTaskPm($row['pmGuid']);

            $this->_helper->TaskUserInfo->initForTask($this->workflow, $this->entity);
            $this->_helper->TaskUserInfo->addUserInfos($row, $isEditAll);

            $row['fileCount'] = empty($fileCount[$row['taskGuid']]) ? 0 : $fileCount[$row['taskGuid']];

            //add task assoc if exist
            if(isset($taskassocs[$row['taskGuid']])){
                $row['taskassocs'] = $taskassocs[$row['taskGuid']];
            }

            if($isMailTo) {
                $row['pmMail'] = empty($userData[$row['pmGuid']]) ? '' : $userData[$row['pmGuid']];
            }
            
            if(empty($this->entity->getTaskGuid())){
                $this->entity->init($row);
            }
            $taskConfig = $this->entity->getConfig();
            //adding QM SubSegment Infos to each Task
            $row['qmSubEnabled'] = false;
            if($taskConfig->runtimeOptions->editor->enableQmSubSegments &&
                !empty($row['qmSubsegmentFlags'])) {
                    $row['qmSubEnabled'] = true;
            }
                
            $this->addMissingSegmentrangesToResult($row);
        }
        return $rows;
    }
    
    /**
     * Add the number of segments that are not assigned to a user
     * although some other segments ARE assigned to users of this role.
     */
    protected function addMissingSegmentrangesToResult(array &$row) {
        //ignore for non-simultaneous task
        if($row['usageMode']!==$this->entity::USAGE_MODE_SIMULTANEOUS){
            return;
        }
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        $row['missingsegmentranges'] = $tua->getAllNotAssignedSegments($row['taskGuid']);
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
            $pm->loadByGuid($this->data['pmGuid']);
        }

        if (empty($this->data['taskType'])) {
            $this->data['taskType'] = $this->entity->getDefaultTasktype();
        }

        $this->data['pmName'] = $pm->getUsernameLong();
        $this->processClientReferenceVersion();

        $this->setDataInEntity();

        $this->prepareLanguages();
        $this->entity->setUsageMode($this->config->runtimeOptions->import->initialTaskUsageMode);
        $this->entity->createTaskGuidIfNeeded();
        $this->entity->setImportAppVersion(ZfExtended_Utils::getAppVersion());

        //if the visual review mapping type is set, se the task meta data
        if(isset($this->data['mappingType'])){
            $meta = $this->entity->meta();
            $meta->setMappingType($this->data['mappingType']);
        }

        $this->prepareCustomer();

        //init workflow id for the task
        $defaultWorkflow = $this->config->runtimeOptions->import->taskWorkflow;
        $this->entity->setWorkflow($this->workflowManager->getIdToClass($defaultWorkflow));

        if($this->validate()) {
            $this->initWorkflow();
            //gets and validates the uploaded zip file
            $upload = ZfExtended_Factory::get('editor_Models_Import_UploadProcessor');
            /* @var $upload editor_Models_Import_UploadProcessor */
            $dpFactory = ZfExtended_Factory::get('editor_Models_Import_DataProvider_Factory');
            /* @var $dpFactory editor_Models_Import_DataProvider_Factory */
            $upload->initAndValidate();
            $dp = $dpFactory->createFromUpload($upload);

            //PROJECT with multiple target languages
            if($this->entity->isProject()) {
                $entityId=$this->entity->save();
                $this->entity->initTaskDataDirectory();
                // check/prepare/unzip our import
                $dp->checkAndPrepare($this->entity);
                // trigger an event that gives plugins a chance to hook into the import process after unpacking/checking the files and before archiving them
                $this->events->trigger("afterUploadPreparation", $this, array('task' => $this->entity, 'dataProvider' => $dp));
                //for projects this have to be done once before the single tasks are imported
                $dp->archiveImportedData();

                $this->entity->setProjectId($entityId);
                
                $languages=ZfExtended_Factory::get('editor_Models_Languages');
                /* @var $languages editor_Models_Languages */
                $languages=$languages->loadAllKeyValueCustom('id','rfc5646');
                
                foreach($this->data['targetLang'] as $target) {
                    $task = clone $this->entity;
                    $task->setProjectId($entityId);
                    $task->setTaskType($task::INITIAL_TASKTYPE_PROJECT_TASK);
                    $task->setTargetLang($target);
                    $task->setTaskName($this->entity->getTaskName().' - '.$languages[$task->getSourceLang()].' / '.$languages[$task->getTargetLang()]);
                    $this->processUploadedFile($task, $dpFactory->createFromTask($this->entity));
                    $this->addDefaultLanguageResources($task);
                }
                
                $this->entity->setState($this->entity::INITIAL_TASKTYPE_PROJECT);
                $this->entity->save();
            } else {
                //DEFAULT (SINGLE) TASK:

                //was set as array in setDataInEntity
                $this->entity->setTargetLang(reset($this->data['targetLang']));
                //$this->entity->save(); => is done by the import call!
                //handling project tasks is also done in processUploadedFile
                $this->processUploadedFile($this->entity, $dp);
                // Language resources that are assigned as default language resource for a client,
                // are associated automatically with tasks for this client.
                $this->addDefaultLanguageResources($this->entity);
            }

            //warn the api user for the targetDeliveryDate ussage
            $this->targetDeliveryDateWarning();

            //update the entity projectId
            $this->entity->setProjectId($this->entity->getId());
            $this->entity->save();
            
            //reload because entityVersion could be changed somewhere
            $this->entity->load($this->entity->getId());

            if($this->data['autoStartImport']) {
                $this->startImportWorkers();
            }

            $this->view->success = true;
            $this->view->rows = $this->entity->getDataObject();
        }
        else {
            //we have to prevent attached events, since when we get here the task is not created, which would lead to task not found errors,
            // but we want to result the validation error
            $event = Zend_EventManager_StaticEventManager::getInstance();
            $event->clearListeners(get_class($this), "afterPostAction");
        }
    }

    /**
     * prepares the languages in $this->data for the import
     */
    protected function prepareLanguages() {
        if(!is_array($this->data['targetLang'])) {
            $this->data['targetLang'] = [$this->data['targetLang']];
        }

        $this->_helper->Api->convertLanguageParameters($this->data['sourceLang']);
        $this->entity->setSourceLang($this->data['sourceLang']);

        //with projects multiple targets are supported:
        foreach($this->data['targetLang'] as &$target) {
            $this->_helper->Api->convertLanguageParameters($target);
        }

        //task is handled as a project (one source language, multiple target languages, each combo one own task)
        if(count($this->data['targetLang']) > 1) {
            //with multiple target languages, the current task will be a project!
            $this->entity->setTaskType($this->entity::INITIAL_TASKTYPE_PROJECT);
            $this->entity->setTargetLang(0);
        }
        else {
            $this->entity->setTargetLang(reset($this->data['targetLang']));
        }

        $this->_helper->Api->convertLanguageParameters($this->data['relaisLang']);
        $this->entity->setRelaisLang($this->data['relaisLang']);
    }

    /**
     * Loads the customer by id, or number, or the default customer
     * stores the customerid internally and in this->data
     */
    protected function prepareCustomer() {
        $customer = ZfExtended_Factory::get('editor_Models_Customer');
        /* @var $customer editor_Models_Customer */

        if(empty($this->data['customerId'])) {
            $customer->loadByDefaultCustomer();
        }
        else {
            try {
                $customer->load($this->data['customerId']);
            }
            catch (ZfExtended_Models_Entity_NotFoundException $e) {
                try {
                    $customer->loadByNumber($this->data['customerId']);
                }
                catch (ZfExtended_Models_Entity_NotFoundException $e) {
                    // do nothing here, then the validation is triggered to feedback the user
                }
            }
        }

        $this->entity->setCustomerId((int) $customer->getId());
        $this->data['customerId'] = (int) $customer->getId();
    }

    /**
     * Assign language resources by default that are set as useAsDefault for the task's client
     * (but only if the language combination matches).
     * @param editor_Models_Task $task
     */
    protected function addDefaultLanguageResources(editor_Models_Task $task) {
        $customerAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */
        
        $allUseAsDefaultCustomers = $customerAssoc->loadByCustomerIdsDefault($this->data['customerId']);
        
        if(empty($allUseAsDefaultCustomers)) {
            return;
        }
        
        $taskAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_Taskassoc');
        /* @var $taskAssoc editor_Models_LanguageResources_Taskassoc */
        $languages = ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
        /* @var $languages editor_Models_LanguageResources_Languages */
        $language = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $language ZfExtended_Languages */
        
        $sourceLanguages = $language->getFuzzyLanguages($task->getSourceLang(),'id',true);
        $targetLanguages = $language->getFuzzyLanguages($task->getTargetLang(),'id',true);
        
        foreach ($allUseAsDefaultCustomers as $defaultCustomer) {
            $languageResourceId = $defaultCustomer['languageResourceId'];
            $sourceLangMatch = $languages->isInCollection($sourceLanguages, 'sourceLang', $languageResourceId);
            $targetLangMatch = $languages->isInCollection($targetLanguages, 'targetLang', $languageResourceId);
            if ($sourceLangMatch && $targetLangMatch) {
                $taskAssoc->init();
                $taskAssoc->setLanguageResourceId($languageResourceId);
                $taskAssoc->setTaskGuid($task->getTaskGuid());
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

    /**
     * starts the workers of the current or given task
     * @param string $taskGuid optional, if empty use current task
     */
    protected function startImportWorkers(editor_Models_Task $task = null) {

        if(empty($task)) {
            $task = $this->entity;
        }

        $tasks=[];
        //if it is a project, start the import workers for each task project
        if($task->isProject()) {
            $tasks=$task->loadProjectTasks($task->getProjectId(),true);
        }else{
            $tasks[]=$task;
        }

        $model=ZfExtended_Factory::get('editor_Models_Task');
        /* @var $model editor_Models_Task */
        foreach ($tasks as $task){
            
            if(is_array($task)){
                $model->load($task['id']);
            }else{
                $model=$task;
            }
            
            //import workers can only be started for tasks
            if($model->isProject()) {
                continue;
            }
    
            $workerModel = ZfExtended_Factory::get('ZfExtended_Models_Worker');
            /* @var $workerModel ZfExtended_Models_Worker */
            try {
                $workerModel->loadFirstOf('editor_Models_Import_Worker', $model->getTaskGuid());
                $worker = ZfExtended_Worker_Abstract::instanceByModel($workerModel);
                $worker && $worker->schedulePrepared();
            }
            catch (ZfExtended_Models_Entity_NotFoundException $e) {
                //if there is no worker, nothing can be done
            }
        }
    }

    /**
     * imports the uploaded file into the given task
     * @param editor_Models_Task $task
     * @param editor_Models_Import_DataProvider_Abstract $dp
     * @throws Exception
     */
    protected function processUploadedFile(editor_Models_Task $task, editor_Models_Import_DataProvider_Abstract $dp) {
        $import = ZfExtended_Factory::get('editor_Models_Import');
        /* @var $import editor_Models_Import */
        $import->setUserInfos($this->user->data->userGuid, $this->user->data->userName);

        $import->setLanguages(
            $task->getSourceLang(),
            $task->getTargetLang(),
            $task->getRelaisLang(),
            editor_Models_Languages::LANG_TYPE_ID);
        $import->setTask($task);
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
            'E1039' => ['importUpload', 'Das importierte Paket beinhaltet kein gültiges "{review}" Verzeichnis.'],
            'E1040' => ['importUpload', 'Das importierte Paket beinhaltet keine Dateien im "{review}" Verzeichnis.'],
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

        //the dataprovider has to be created from the old task
        $dpFactory = ZfExtended_Factory::get('editor_Models_Import_DataProvider_Factory');
        /* @var $dpFactory editor_Models_Import_DataProvider_Factory */
        $dataProvider = $dpFactory->createFromTask($this->entity);

        $data = (array) $this->entity->getDataObject();
        $oldTaskGuid=$data['taskGuid'];
        unset($data['id']);
        unset($data['taskGuid']);
        unset($data['state']);
        unset($data['workflowStep']);
        unset($data['locked']);
        unset($data['lockingUser']);
        unset($data['userCount']);
        //is the source task a single project task
        if($this->entity->getId()==$this->entity->getProjectId()){
            $data['taskType'] = $this->entity::INITIAL_TASKTYPE_PROJECT_TASK;
        }
        $data['state'] = 'import';
        $this->entity->init($data);
        $this->entity->createTaskGuidIfNeeded();
        $this->entity->setImportAppVersion(ZfExtended_Utils::getAppVersion());

        if($this->validate()) {
            $this->processUploadedFile($this->entity, $dataProvider);
            $this->cloneLanguageResources($oldTaskGuid, $this->entity->getTaskGuid());
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

        if($this->entity->isProject()){
            //project modification is not allowed. This will be changed in future.
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1284' => 'Projects are not editable.',
            ]);
            throw ZfExtended_Models_Entity_Conflict::createResponse('E1284', [
                'Projekte können nicht bearbeitet werden.'
            ]);
        }

        //task manipulation is allowed additionally on excel export (for opening read only, changing user states etc)
        $this->entity->checkStateAllowsActions([editor_Models_Excel_AbstractExImport::TASK_STATE_ISEXCELEXPORTED]);

        $taskguid = $this->entity->getTaskGuid();
        $this->log->request();

        $oldTask = clone $this->entity;
        $this->decodePutData();

        //warn the api user for the targetDeliveryDate ussage
        $this->targetDeliveryDateWarning();

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
        if (isset($this->data->initial_tasktype)) {
            unset($this->data->initial_tasktype);
        }
        $this->processClientReferenceVersion();
        $this->setDataInEntity();
        $this->validateUsageMode();
        $this->entity->validate();
        $this->initWorkflow();

        //throws exceptions if task not accessable
        $this->checkTaskAccess();
        
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

        //if the totals segment count is not set, update it before the entity is saved
        if($this->entity->getSegmentCount() === null || $this->entity->getSegmentCount() < 1) {
            $segment = ZfExtended_Factory::get('editor_Models_Segment');
            $this->entity->setSegmentCount($segment->getTotalSegmentsCount($taskguid));
        }

        $this->entity->save();
        $obj = $this->entity->getDataObject();

        $userAssocInfos = $this->_helper->TaskUserInfo->initUserAssocInfos([$obj]);
        $this->invokeTaskUserTracking($taskguid, $userAssocInfos[$taskguid]['role'] ?? '');

        //because we are mixing objects (getDataObject) and arrays (loadAll) as entity container we have to cast here
        $row = (array) $obj;
        $isEditAll = $this->isAllowed('backend', 'editAllTasks') || $this->isAuthUserTaskPm($row['pmGuid']);
        $this->_helper->TaskUserInfo->initForTask($this->workflow, $this->entity);
        $this->_helper->TaskUserInfo->addUserInfos($row, $isEditAll, $this->data->userState ?? null);
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
     * Checks the task access by workflow
     * @throws ZfExtended_Models_Entity_NoAccessException
     * @throws ZfExtended_Models_Entity_Conflict
     */
    protected function checkTaskAccess() {
        $mayLoadAllTasks = $this->isAllowed('backend', 'loadAllTasks') || $this->isAuthUserTaskPm($this->entity->getPmGuid());
        
        try {
            $tua = editor_Models_Loaders_Taskuserassoc::loadByTask($this->user->data->userGuid, $this->entity);
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            $tua = null;
        }
        
        //mayLoadAllTasks is only true, if the current "PM" is not associated to the task directly.
        // If it is (pm override false) directly associated, the workflow must be considered it the task is openable / writeable.
        $mayLoadAllTasks = $mayLoadAllTasks && (empty($tua) || $tua->getIsPmOverride());
        
        //if the user may load all tasks, check workflow access is non sense
        if($mayLoadAllTasks) {
            return;
        }
        
        $isTaskDisallowEditing = $this->isEditTaskRequest() && !$this->workflow->isWriteable($tua);
        $isTaskDisallowReading = $this->isViewTaskRequest() && !$this->workflow->isReadable($tua);
        
        //if now there is no tua, that means it was deleted in the meantime.
        // A PM will not reach here, a editor user may not access the task then anymore
        if(empty($tua)) {
            //if the task was already in session, we must delete it.
            //If not the user will always receive an error in JS, and would not be able to do anything.
            $this->unregisterTask();
            throw ZfExtended_Models_Entity_Conflict::createResponse('E1163',[
                'userState' => 'Ihre Zuweisung zur Aufgabe wurde entfernt, daher können Sie diese nicht mehr zur Bearbeitung öffnen.',
            ]);
        }
        
        //the tua state was changed by a PM, then the task may not be edited anymore by the user
        if($isTaskDisallowEditing && $this->data->userStatePrevious != $tua->getState()) {
            $this->unregisterTask();
            throw ZfExtended_Models_Entity_Conflict::createResponse('E1164',[
                'userState' => 'Sie haben versucht die Aufgabe zur Bearbeitung zu öffnen. Das ist in der Zwischenzeit nicht mehr möglich.',
            ]);
        }
        //if reading is allowed the edit request is converted to a read request later by openAndLock
        //if reading is also disabled, we have to throw no access here
        if($isTaskDisallowEditing && $isTaskDisallowReading) {
            $this->unregisterTask();
            //no access as generic fallback
            $this->log->info('E9999', 'Debug data to E9999 - Keine Zugriffsberechtigung!',[
                '$mayLoadAllTasks' => $mayLoadAllTasks,
                'tua' => $tua ? $tua->getDataObject() : 'no tua',
                'isPmOver' => $tua && $tua->getIsPmOverride(),
                'loadAllTasks' => $this->isAllowed('backend', 'loadAllTasks'),
                'isAuthUserTaskPm' => $this->isAuthUserTaskPm($this->entity->getPmGuid()),
                '$isTaskDisallowEditing' => $isTaskDisallowEditing,
                '$isTaskDisallowReading' => $isTaskDisallowReading,
            ]);
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
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
        //FIXME also throw an exception if task is locked
        throw ZfExtended_Models_Entity_Conflict::createResponse('E1159', [
            'usageMode' => [
                'usersAssigned' => 'Der Nutzungsmodus der Aufgabe kann verändert werden, wenn kein Benutzer der Aufgabe zugewiesen ist.'
            ]
        ]);
    }

    
    /**
     * Add pixelMapping-data to the view (= for the fonts used in the task).
     */
    protected function addPixelMapping() {
        $pixelMapping = ZfExtended_Factory::get('editor_Models_PixelMapping');
        /* @var $pixelMapping editor_Models_PixelMapping */
        try {
            $pixelMappingForTask = $pixelMapping->getPixelMappingForTask($this->entity->getTaskGuid(), $this->entity->getAllFontsInTask());
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
     * @param string $taskguid
     * @param string $role
     */
    protected function invokeTaskUserTracking(string $taskguid, string $role) {
        if(!$this->isOpenTaskRequest()){
            return;
        }
        $taskUserTracking = ZfExtended_Factory::get('editor_Models_TaskUserTracking');
        /* @var $taskUserTracking editor_Models_TaskUserTracking */
        $taskUserTracking->insertTaskUserTrackingEntry($taskguid, $this->user->data->userGuid, $role);
    }

    /**
     * locks the current task if its an editing request
     * stores the task as active task if its an opening or an editing request
     */
    protected function openAndLock() {
        $task = $this->entity;
        /* @var $task editor_Models_Task */
        if($this->isEditTaskRequest()){
            $isMultiUser = $task->getUsageMode() == $task::USAGE_MODE_SIMULTANEOUS;
            $unconfirmed = $task->getState() == $task::STATE_UNCONFIRMED;
            //first check for confirmation on task level, if unconfirmed, don't lock just set to view mode!
            //if no multiuser, try to lock for user
            //if multiuser, try a system lock
            if($unconfirmed || !($isMultiUser ? $task->lock($this->now, $task::USAGE_MODE_SIMULTANEOUS) : $task->lockForSessionUser($this->now))){
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
        $task = $this->entity;
        $hasState = !empty($this->data->userState);
        $isEnding = isset($this->data->state) && $this->data->state == $task::STATE_END;
        $resetToOpen = $hasState && $this->data->userState == $this->workflow::STATE_EDIT && $isEnding;
        if($resetToOpen) {
            //This state change will be saved at the end of this method.
            $this->data->userState = $this->workflow::STATE_OPEN;
        }
        if(!$isEnding && (!$this->isLeavingTaskRequest())){
            return;
        }
        $this->entity->unlockForUser($this->user->data->userGuid);
        $this->unregisterTask();

        if($resetToOpen) {
            $this->updateUserState($this->user->data->userGuid, true);
        }
        $this->events->trigger("afterTaskClose", $this, array(
            'task' => $task,
            'view' => $this->view,
            'openState' => $this->data->userState ?? null)
        );
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

        $userTaskAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $userTaskAssoc editor_Models_TaskUserAssoc */
        try {
            
            if($isEditAllTasks){
                $userTaskAssoc=editor_Models_Loaders_Taskuserassoc::loadByTaskForceWorkflowRole($userGuid, $this->entity);
            }else{
                $userTaskAssoc=editor_Models_Loaders_Taskuserassoc::loadByTask($userGuid, $this->entity);
            }
            
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
            $userTaskAssoc->setTaskGuid($this->entity->getTaskGuid());
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
        $taskConfig = $this->entity->getConfig();
        if($taskConfig->runtimeOptions->editor->enableQmSubSegments &&
                !empty($qmSubFlags)) {
            $this->view->rows->qmSubFlags = $this->entity->getQmSubsegmentIssuesTranslated(false);
            $this->view->rows->qmSubSeverities = $this->entity->getQmSubsegmentSeveritiesTranslated(false);
            $this->view->rows->qmSubEnabled = true;
        }
        unset($this->view->rows->qmSubsegmentFlags);
    }

    /**
     * {@inheritDoc}
     * @see ZfExtended_RestController::additionalValidations()
     */
    protected function additionalValidations() {
        // validate the taskType
        $this->validateTaskType();
    }

    /**
     * Validate the taskType: check if given tasktype is allowed according to role
     * @throws ZfExtended_UnprocessableEntity
     */
    protected function validateTaskType() {
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        $isTaskTypeAllowed = $acl->isInAllowedRoles($this->user->data->roles, 'initial_tasktype', $this->entity->getTaskType());
        if (!$isTaskTypeAllowed) {
            ZfExtended_UnprocessableEntity::addCodes([
                'E1217' => 'TaskType not allowed.'
            ], 'editor.task');
            throw new ZfExtended_UnprocessableEntity('E1217');
        }
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

        $this->_helper->TaskUserInfo->initUserAssocInfos([$obj]);

        //because we are mixing objects (getDataObject) and arrays (loadAll) as entity container we have to cast here
        $row = (array) $obj;

        $isTaskPm = $this->isAuthUserTaskPm($this->entity->getPmGuid());
        $tua = null;
        try {
            $tua = editor_Models_Loaders_Taskuserassoc::loadByTask($this->user->data->userGuid, $this->entity);
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            //do nothing here
        }
        
        //to access a task the user must either have the loadAllTasks right, or must be the tasks PM, or must be associated to the task
        $isTaskAccessable = $this->isAllowed('backend', 'loadAllTasks') || $isTaskPm || !is_null($tua);
        if(!$isTaskAccessable) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
        
        $isEditAll = $this->isAllowed('backend', 'editAllTasks') || $isTaskPm;
        $this->_helper->TaskUserInfo->initForTask($this->workflow, $this->entity);
        $this->_helper->TaskUserInfo->addUserInfos($row, $isEditAll);
        $this->addMissingSegmentrangesToResult($row);
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

        
        $this->view->rows->workflowProgressSummary = $this->_helper->TaskStatistics->getWorkflowProgressSummary($this->entity);
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
        $this->getAction();
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

            case 'filetranslation':
            default:
                $this->entity->checkStateAllowsActions();
                $worker = ZfExtended_Factory::get('editor_Models_Export_Worker');
                /* @var $worker editor_Models_Export_Worker */
                $exportFolder = $worker->initExport($this->entity, $diff);
                break;
        }

        //FIXME multiple problems here with the export worker
        // it is possible that we get the following in DB (implicit ordererd by ID here):
        //      Export_Worker for ExportReq1
        //      Export_Worker for ExportReq2 → overwrites the tempExportDir of ExportReq1
        //      Export_ExportedWorker for ExportReq2
        //      Export_ExportedWorker for ExportReq1 → works then with tempExportDir of ExportReq1 instead!
        //
        // If we implement in future export workers which need to work on the temp export data,
        // we have to ensure that each export worker get its own export directory.
        $workerId = $worker->queue();

        $worker = ZfExtended_Factory::get('editor_Models_Export_ExportedWorker');
        /* @var $worker editor_Models_Export_ExportedWorker */

        if($context == 'filetranslation') {
            $zipFile = $worker->initWaitOnly($this->entity->getTaskGuid(), $exportFolder);
        }
        else {
            $zipFile = $worker->initZip($this->entity->getTaskGuid(), $exportFolder);
        }

        //TODO for the API usage of translate5 blocking on export makes no sense
        // better would be a URL to fetch the latest export or so (perhaps using state 202?)
        $worker->setBlocking(); //we have to wait for the underlying worker to provide the download
        $worker->queue($workerId);

        if($context == 'filetranslation') {
            $this->provideFiletranslationDownload($exportFolder);
            exit;
        }
        
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
        
        $languages = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languages editor_Models_Languages */
        $languages = $languages->loadAllKeyValueCustom('id','rfc5646');
        
        $downloadName = ['',$languages[$this->entity->getSourceLang()],$languages[$this->entity->getTargetLang()]];
        $downloadName = implode('_', $downloadName).$suffix;

        $this->logInfo('Task exported', ['context' => $context, 'diff' => $diff]);
        $this->provideZipDownload($zipFile, $downloadName);

        //rename file after usage to export.zip to keep backwards compatibility
        rename($zipFile, dirname($zipFile).DIRECTORY_SEPARATOR.'export.zip');
        exit;
    }

    /**
     * extracts the translated file from given $zipFile and sends it to the browser.
     * @param string $zipFile
     */
    protected function provideFiletranslationDownload($exportFolder) {
        clearstatcache(); //ensure that files modfied by other plugins are available in stat cache
        $content = scandir($exportFolder);
        $foundFile = null;
        foreach($content as $file) {
            //skip dots and all xlf files,
            if($file == '.' || $file == '..') {
                continue;
            }
            //if no non xlf file was found yet, we use the xlf as found file
            if(empty($foundFile) && preg_match('/\.xlf$/i', $file)) {
                $foundFile = $file;
                //but we try to look for another file, so continue here
                continue;
            }
            //if we found a non xlf file, this file is used:
            $foundFile = $file;
            break;
        }
        
        $clean = function() use($exportFolder) {
            $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
                'Recursivedircleaner'
                );
            $recursivedircleaner->delete($exportFolder);
        };
        
        $translatedfile = $exportFolder.DIRECTORY_SEPARATOR.$foundFile;
        if(empty($foundFile) || !file_exists($translatedfile)) {
            $clean();
            throw new ZfExtended_NotFoundException('Requested file not found!');
        }
        
        $pathInfo = pathinfo($translatedfile);
        
        // where do we find what, what is named how.
        $languages = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languages editor_Models_Languages */
        $languages->load($this->entity->getTargetLang());
        $targetLangRfc = $languages->getRfc5646();
        
        $filenameExport = $pathInfo['filename'] . '_' . $targetLangRfc;
        
        if(!empty('.' . $pathInfo['extension'])) {
            $filenameExport .= '.' . $pathInfo['extension'];
        }
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="'.$filenameExport.'"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        readfile($translatedfile);
        $clean(); //remove export dir
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
     * Search the task id position in the current filter
     */
    public function positionAction() {
        //TODO The optimal way to implement this, is like similar to the segment::positionAction in a general way so that it is usable for all entities.
        
        $this->handleProjectRequest();
        $rows=$this->loadAll();
        $id = (int) $this->_getParam('id');
        $index = false;
        if(!empty($rows)) {
            $index = array_search($id, array_column($rows, 'id'));
        }
        if($index === false){
            $index = -1;
        }
        $this->view->index = $index;
        unset($this->view->rows);
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

    /**
     * Warn the api users that the targetDeliveryDate field is not anymore available for the api.
     * The task deadlines are defined for each task-user-assoc job separately,
     *  for task creation we store the given date in the task meta table and use that in the jobs, so we do not break the API
     * @deprecated TODO: 11.02.2020 remove this function after all customers adopt there api calls
     * @see Editor_TaskuserassocController::setLegacyDeadlineDate
     */
    protected function targetDeliveryDateWarning($isPost = false) {
        $date = false;
        if(is_array($this->data) && isset($this->data['targetDeliveryDate'])){
            $date = $this->data['targetDeliveryDate'];
        }
        if(is_object($this->data) && isset($this->data->targetDeliveryDate)){
            $date = $this->data->targetDeliveryDate;
        }
        if($date === false){
            return;
        }
        //different instance for column creation needed:
        $taskMeta = $this->entity->meta();
        $taskMeta->addMeta('targetDeliveryDate', $taskMeta::META_TYPE_STRING, null, 'Temporary field to store the targetDeliveryDate until all API users has migrated.');
        $taskMeta = $this->entity->meta(true);
        $taskMeta->setTargetDeliveryDate($date);
        $taskMeta->save();

        $this->log->warn('E1210','The targetDeliveryDate for the task is deprecated. Use the LEK_taskUserAssoc deadlineDate instead.');
    }

    /***
     * Add the task default if sort is not provided
     */
    protected function addDefaultSort(){
        $f = $this->entity->getFilter();
        $f->hasSort() || $f->addSort('orderdate', true);
    }
    
    /**
     * Handle the project/task load request.
     * @return boolean true if loading projects, or false if tasks only
     */
    protected function handleProjectRequest(): bool{
        $projectOnly = (bool) $this->getRequest()->getParam('projectsOnly', false);
        $filter=$this->entity->getFilter();
        if($filter->hasFilter('projectId') && !$projectOnly){
            //filter for all tasks in the project(return also the single task projects)
            $filter->addFilter((object)[
                'field' => 'taskType',
                'value' =>[editor_Models_Task::INITIAL_TASKTYPE_PROJECT],
                'type' => 'notInList',
                'comparison' => 'in'
            ]);
            return false;
        }
        
        $filterValues = [editor_Models_Task::INITIAL_TASKTYPE_DEFAULT];
        
        if($projectOnly){
            $filterValues[]=editor_Models_Task::INITIAL_TASKTYPE_PROJECT;
        }else{
            $filterValues[]=editor_Models_Task::INITIAL_TASKTYPE_PROJECT_TASK;
        }
        
        $filter->addFilter((object)[
            'field' => 'taskType',
            'value' =>$filterValues,
            'type' => 'list',
            'comparison' => 'in'
        ]);
        return $projectOnly;
    }
}
