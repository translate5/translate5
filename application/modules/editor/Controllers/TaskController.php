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

use MittagQI\Translate5\Task\CurrentTask;
use MittagQI\Translate5\Task\Export\Package\Downloader;
use MittagQI\Translate5\Task\Lock;
use MittagQI\Translate5\Task\TaskContextTrait;
use MittagQI\ZfExtended\Cors;

/**
 *
 */
class editor_TaskController extends ZfExtended_RestController {

    use TaskContextTrait;
    use editor_Controllers_Task_ImportTrait;


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

        ->addContext('transfer', [
            'headers' => [
                'Content-Type'          => 'text/xml',
            ]
        ])
        ->addActionContext('export', 'transfer')

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
        ->addContext('excelhistory', [
            'headers' => [
                'Content-Type'          => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // TODO Content-Type prüfen
            ]
        ])
        ->addActionContext('export', 'excelhistory')

        ->addContext('package', [
            'headers' => [
                'Content-Type'          => 'application/zip',
            ]
        ])->addActionContext('export', 'package')

        ->initContext();
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
     * uses $this->entity->loadAll
     */
    protected function loadAllForProjectOverview() {
        $rows = $this->loadAll();
        $customerData = $this->getCustomersForRendering($rows);
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $isTransfer = $file->getTransfersPerTasks(array_column($rows, 'taskGuid'));
        foreach ($rows as &$row) {
            unset($row['qmSubsegmentFlags']); // unneccessary in the project overview
            $row['customerName'] = empty($customerData[$row['customerId']]) ? '' : $customerData[$row['customerId']];
            $row['isTransfer'] = isset($isTransfer[$row['taskGuid']]);
        }
        return $rows;
    }
    
    /**
     * returns all (filtered) tasks with added user data and quality data
     * uses $this->entity->loadAll
     */
    protected function loadAllForTaskOverview() {
        $rows = $this->loadAll();
        $taskGuids = array_map(function($item){
            return $item['taskGuid'];
        },$rows);

        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $fileCount = $file->getFileCountPerTasks($taskGuids);
        $isTransfer = $file->getTransfersPerTasks($taskGuids);

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
            $userData = $this->getUsersForRendering($rows);
        }

        foreach ($rows as &$row) {
            $row['lastErrors'] = $this->getLastErrorMessage($row['taskGuid'], $row['state']);
            $this->initWorkflow($row['workflow']);
    
            $row['customerName'] = empty($customerData[$row['customerId']]) ? '' : $customerData[$row['customerId']];

            $isEditAll = $this->isAllowed('backend', 'editAllTasks') || $this->isAuthUserTaskPm($row['pmGuid']);

            $this->_helper->TaskUserInfo->initForTask($this->workflow, $this->entity, $this->isTaskProvided());
            $this->_helper->TaskUserInfo->addUserInfos($row, $isEditAll);

            $row['fileCount'] = empty($fileCount[$row['taskGuid']]) ? 0 : $fileCount[$row['taskGuid']];
            $row['isTransfer'] = isset($isTransfer[$row['taskGuid']]);

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
            // add quality related stuff
            $this->addQualitiesToResult($row);
            // add user-segment assocs
            $this->addMissingSegmentrangesToResult($row);
        }
        // sorting of qualityErrorCount can only be done after QS data is attached
        if($this->entity->getFilter()->hasSort('qualityErrorCount')){
            $direction = SORT_ASC;
            foreach($this->entity->getFilter()->getSort() as $sort){
                if($sort->property == 'qualityErrorCount' && $sort->direction === 'DESC'){
                    $direction = SORT_DESC;
                    break;
                }
            }
            $columns = array_column($rows, 'qualityErrorCount');
            array_multisort($columns, $direction, $rows);
        }
        return $rows;
    }
    /**
     * Adds the quality related props to the task model for the task overview (not project overview)
     * @param array $row
     */
    protected function addQualitiesToResult(array &$row){
        //TODO: for now we leave this as it is, if this produces performance problems, find better way for loading this config
        $taskConfig = $this->entity->getConfig();
        $qualityProps = editor_Models_Db_SegmentQuality::getNumQualitiesAndFaultsForTask($row['taskGuid']);
        // adding number of quality errors, evaluated in the export actions
        $row['qualityErrorCount'] = $qualityProps->numQualities;
        // adding if the task has internal tag errors, will prevent xliff exports (Note: this can be emulated for dev, see editor_Models_Quality_AbstractView::EMULATE_PROBLEMS
        $row['qualityHasFaults'] = (editor_Models_Quality_AbstractView::EMULATE_PROBLEMS) ? true : ($qualityProps->numFaults > 0);
        // adding QM SubSegment Infos to each Task, evaluated in the export actions
        $row['qualityHasMqm'] = ($taskConfig->runtimeOptions->autoQA->enableMqmTags && !empty($row['qmSubsegmentFlags']));
        unset($row['qmSubsegmentFlags']); // unneccessary in the task overview
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

        $customer = ZfExtended_Factory::get('editor_Models_Customer_Customer');
        /* @var $customer editor_Models_Customer_Customer */
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
     * @throws Zend_Exception
     * @throws Exception
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
        if(array_key_exists('enddate', $this->data)) {
            unset($this->data['enddate']);
        }
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
            $this->data['taskType'] = editor_Task_Type_Default::ID;
        }

        $this->data['pmName'] = $pm->getUsernameLong();
        $this->processClientReferenceVersion();

        $this->setDataInEntity();

        $targetLangCount = $this->prepareLanguages();
        $singleTask = $this->prepareTaskType($targetLangCount);

        $this->entity->createTaskGuidIfNeeded();
        $this->entity->setImportAppVersion(ZfExtended_Utils::getAppVersion());

        //if the visual review mapping type is set, se the task metadata overridable
        if(isset($this->data['mappingType'])){
            $meta = $this->entity->meta();
            $meta->setMappingType($this->data['mappingType']);
        }

        $customer = $this->prepareCustomer();

        if($customer) {
            $c = $customer->getConfig();
        }
        else {
            $c = $this->config;
        }

        // check and set the default pivot language is configured
        $this->setDefaultPivotOnTaskCreate($c);

        // set the usageMode from config if not set
        $this->entity->setUsageMode($this->data['usageMode'] ?? $c->runtimeOptions->import->initialTaskUsageMode);

        //init workflow id for the task, based on customer or general config as fallback
        $this->entity->setWorkflow($c->runtimeOptions->workflow->initialWorkflow);


        if(!$this->validate()){
            //we have to prevent attached events, since when we get here the task is not created, which would lead to task not found errors,
            // but we want to result the validation error
            $event = Zend_EventManager_StaticEventManager::getInstance();
            $event->clearListeners(get_class($this), "afterPostAction");
            return;
        }

        $this->initWorkflow();

        if($singleTask){
            $tasks = $this->handleTaskImport();
        }else{
            $tasks = $this->handleProjectUpload();
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
        settype($this->view->rows->projectTasks,'array');
        $this->view->rows->projectTasks = $tasks;
    }

    /**
     * prepares the tasks type, by considering the language count and the initial given task type via API
     * returns if it should be a single task (project = task) or a project with sub tasks
     */
    protected function prepareTaskType(int $targetLangCount): bool {
        $taskType = editor_Task_Type::getInstance();
        $taskType->calculateImportTypes($targetLangCount > 1, $this->data['taskType']);
        $this->entity->setTaskType($taskType->getImportProjectType());
        return $taskType->getImportTaskType() === $taskType->getImportProjectType();
    }

    /**
     * prepares the languages in $this->data for the import
     * @return int the target language count
     */
    protected function prepareLanguages(): int {
        if(!is_array($this->data['targetLang'])) {
            $this->data['targetLang'] = [$this->data['targetLang']];
        }

        $this->_helper->Api->convertLanguageParameters($this->data['sourceLang']);
        $this->entity->setSourceLang($this->data['sourceLang']);

        //with projects multiple targets are supported:
        foreach($this->data['targetLang'] as &$target) {
            $this->_helper->Api->convertLanguageParameters($target);
        }

        // sort the languages alphabetically
        $this->_helper->Api->sortLanguages($this->data['targetLang']);

        //task is handled as a project (one source language, multiple target languages, each combo one own task)
        $targetLangCount = count($this->data['targetLang']);
        if($targetLangCount > 1) {
            //with multiple target languages, the current task will be a project!
            $this->entity->setTargetLang(0);
        }
        else {
            $this->entity->setTargetLang(reset($this->data['targetLang']));
        }

        // If the relaisLang is not set, do nothing. In that case, the language default is set later on in the import
        if(isset($this->data['relaisLang'])){
            if(empty($this->data['relaisLang'])){
                $this->data['relaisLang'] = 0;
            } else {
                $this->_helper->Api->convertLanguageParameters($this->data['relaisLang']);
            }
            $this->entity->setRelaisLang($this->data['relaisLang']);
        }


        return $targetLangCount;
    }

    /**
     * Loads the customer by id, or number, or the default customer
     * stores the customerid internally and in this->data
     * @return editor_Models_Customer_Customer the loaded customer if any
     */
    protected function prepareCustomer(): ?editor_Models_Customer_Customer {
        $result = $customer = ZfExtended_Factory::get('editor_Models_Customer_Customer');
        /* @var $customer editor_Models_Customer_Customer */

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
                    $result = null;
                }
            }
        }

        $this->entity->setCustomerId((int) $customer->getId());
        $this->data['customerId'] = (int) $customer->getId();
        return $result;
    }

    /***
     * Sets task defaults for given task (default languageResources, default userAssocs)
     * @param editor_Models_Task $task
     * @throws Zend_Cache_Exception
     */
    protected function setTaskDefaults(editor_Models_Task $task): void
    {
        $defaults = $this->_helper->taskDefaults;
        /* @var Editor_Controller_Helper_TaskDefaults $defaults */
        $defaults->addDefaultLanguageResources($task);
        $defaults->addDefaultPivotResources($task);
        $defaults->addDefaultUserAssoc($task);
        $defaults->handlePivotAutostart($task);
    }

    /**
     * Starts the import of the task
     */
    public function importAction() {
        $this->getAction();
        $this->startImportWorkers();
    }

    /***
     * Operation for pre task-import operations (change task config, task property or queue custom workers)
     */
    public function preimportOperation(){

        $projectId = $this->entity->getProjectId();
        $usageMode = $this->getParam('usageMode',false);
        $workflow = $this->getParam('workflow',false);
        $notifyAssociatedUsers = $this->getParam('notifyAssociatedUsers',false);
        if(!$projectId){
            return;
        }
        $projectLoader = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $projectLoader editor_Models_Task */
        $projectTaks = $projectLoader->loadProjectTasks($projectId,true);


        foreach ($projectTaks as $task) {
            $saveModel = false;
            $model = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $model editor_Models_Task */
            $model->load($task['id']);

            if(!empty($usageMode)){
                $saveModel = true;
                $model->setUsageMode($usageMode);
            }

            if(!empty($workflow)){
                $saveModel = true;
                $model->setWorkflow($workflow);
            }

            if($notifyAssociatedUsers !== false){
                $saveModel = true;
                $taskConfig = ZfExtended_Factory::get('editor_Models_TaskConfig');
                /* @var $taskConfig editor_Models_TaskConfig */
                // INFO: runtimeOptions.workflow.notifyAllUsersAboutTask can be overwritten only on customer level but from the frontend we are able to
                // change this value via checkbox in the import wizard. This to take effect on all tasks, we insert the config value in the task config table, which
                // value will be evaluated later (after task import in the notification class)
                $taskConfig->updateInsertConfig($model->getTaskGuid(),'runtimeOptions.workflow.notifyAllUsersAboutTask',$notifyAssociatedUsers);
            }

            $saveModel && $model->save();
        }
    }
    /**
     * This Operation refreshes (recalculates / retags) all qualities
     */
    public function autoqaOperation(){        
        editor_Segment_Quality_Manager::autoqaOperation($this->entity);
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
            //TODO should be an asynchronous process (queue instead run)
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
     * clone the given task into a new task
     * @throws BadMethodCallException
     * @throws ZfExtended_Exception
     * @throws Zend_Db_Statement_Exception
     * @throws Exception
     */
    public function cloneAction() {
        if(!$this->_request->isPost()) {
            throw new BadMethodCallException('Only HTTP method POST allowed!');
        }
        $this->getAction();

        //the dataprovider has to be created from the old task
        /** @var editor_Models_Import_DataProvider_Factory $dpFactory */
        $dpFactory = ZfExtended_Factory::get('editor_Models_Import_DataProvider_Factory');
        $dataProvider = $dpFactory->createFromTask($this->entity);

        /** @var editor_Task_Cloner $cloner */
        $cloner = ZfExtended_Factory::get('editor_Task_Cloner');

        $this->entity = $cloner->clone($this->entity);

        if($this->validate()) {
            // set meta data in controller as in post request
            $metaData = $this->entity->meta()->toArray();
            unset($metaData['id'], $metaData['taskGuid']);
            foreach($metaData as $field => $value){
                $this->data[$field] = $value;
            }
            $this->processUploadedFile($this->entity, $dataProvider); //creates task_meta via editor_Models_Import::import
            $cloner->cloneDependencies();
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
        
        // check if the user is allowed to open the task based on the session. The user is not able to open 2 different task in same time.
        $this->decodePutData();

        //throws exceptions if task not closable
        $this->checkTaskStateTransition();

        $this->handleCancelImport();

        //task manipulation is allowed additionally on excel export (for opening read only, changing user states etc)
        $this->entity->checkStateAllowsActions([editor_Models_Task::STATE_EXCELEXPORTED]);

        $taskguid = $this->entity->getTaskGuid();
        $this->log->request();

        $oldTask = clone $this->entity;

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
        if (isset($this->data->enddate)) {
            unset($this->data->enddate);
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

        $this->workflow->hookin()->doWithTask($oldTask, $this->entity);

        if($oldTask->getState() != $this->entity->getState()) {
            $this->logInfo('Status change to {status}', ['status' => $this->entity->getState()]);
        }
        else {
            //id is always set as modified, therefore we don't log task changes if id is the only modified
            $modified = $this->entity->getModifiedValues();
            if(!array_key_exists('id', $modified) || count($modified) > 1) {
                $this->logInfo('Task modified - prev. value was: ');
            }
        }

        //updateUserState does also call workflow "do" methods!
        $this->updateUserState($this->user->data->userGuid);

        //closing a task must be done after all workflow "do" calls which triggers some events
        $this->closeAndUnlock();

        //if the edit100PercentMatch is changed, update the value for all segments in the task
        if(isset($this->data->edit100PercentMatch)){
            $bulkUpdater = ZfExtended_Factory::get('editor_Models_Segment_AutoStates_BulkUpdater');
            /* @var editor_Models_Segment_AutoStates_BulkUpdater $bulkUpdater */
            $bulkUpdater->updateSegmentsEdit100PercentMatch($this->entity, (boolean)$this->data->edit100PercentMatch);
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
        $this->_helper->TaskUserInfo->initForTask($this->workflow, $this->entity, $this->isTaskProvided());
        $this->_helper->TaskUserInfo->addUserInfos($row, $isEditAll, $this->data->userState ?? null);
        $this->view->rows = (object)$row;

        if($this->isOpenTaskRequest()){
            $this->addMqmQualities();
        }
        unset($this->view->rows->qmSubsegmentFlags);

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
     * Check if task is allowed to be transferred to the particular state
     *
     * @return void
     * @throws Zend_Exception
     * @throws ZfExtended_ErrorCodeException
     */
    private function checkTaskStateTransition(): void
    {
        $closingTask = ($this->data->state ?? null) === 'end';

        if($closingTask && null !== $this->entity->getLocked()) {
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1161' => 'The task can not be set to ended by a PM, because a user has opened the task for editing.',
            ]);

            throw ZfExtended_Models_Entity_Conflict::createResponse('E1161', [
                'Die Aufgabe kann nicht von einem PM beendet werden, weil ein Benutzer die Aufgabe zur Bearbeitung geöffnet hat.',
            ]);
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
     * returns true if PUT Requests opens a task for open or finish
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
            $userTaskAssoc->setWorkflow($this->workflow->getName());
            $userTaskAssoc->setWorkflowStepName('');
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


        if($disableWorkflowEvents) {
            $userTaskAssoc->save();
        }
        else {
            $this->workflow->hookin()->doWithUserAssoc($oldUserTaskAssoc, $userTaskAssoc, function() use ($userTaskAssoc) {
                $userTaskAssoc->save();
            });
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
    protected function addMqmQualities() {
        $qualityMqmCategories = $this->entity->getQmSubsegmentFlags();
        $this->view->rows->qualityHasMqm = false;
        $taskConfig = $this->entity->getConfig();
        if($taskConfig->runtimeOptions->autoQA->enableMqmTags && !empty($qualityMqmCategories)) {
            $this->view->rows->qualityMqmCategories = $this->entity->getMqmTypesTranslated(false);
            $this->view->rows->qualityMqmSeverities = $this->entity->getMqmSeveritiesTranslated(false);
            $this->view->rows->qualityHasMqm = true;
        }
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
        $isTaskTypeAllowed = $acl->isInAllowedRoles($this->user->data->roles, 'initial_tasktype', $this->entity->getTaskType()->id());
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
            unset($this->view->rows);
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
        
        $isEditAll = $this->isAllowed('backend', 'editAllTasks') || $isTaskPm;
        $this->_helper->TaskUserInfo->initForTask($this->workflow, $this->entity, $this->isTaskProvided());
        $this->_helper->TaskUserInfo->addUserInfos($row, $isEditAll);
        $this->addMissingSegmentrangesToResult($row);
        $this->view->rows = (object)$row;

        unset($this->view->rows->qmSubsegmentFlags);

        //add task assoc to the task
        $languageResourcemodel = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /*@var $languageResourcemodel editor_Models_LanguageResources_LanguageResource */
        $resultlist = $languageResourcemodel->loadByAssociatedTaskGuid($taskguid);
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

        $this->checkStateDelete($this->entity,$forced);

        //we enable task deletion for importing task
        $forced=$forced || $this->entity->isImporting() || $this->entity->isProject();

        $this->processClientReferenceVersion();
        $remover = ZfExtended_Factory::get('editor_Models_Task_Remover', array($this->entity));
        /* @var $remover editor_Models_Task_Remover */
        $remover->remove($forced);
    }

    /**
     * does the export as zip file.
     */
    public function exportAction() {
        if($this->isMaintenanceLoginLock(30)) {
            //since file is fetched for download we simply print out that text without decoration.
            echo 'Maintenance is scheduled, exports are not possible at the moment.';
            exit;
        }

        $this->getAction();

        $diff = (boolean)$this->getRequest()->getParam('diff');
        $context = $this->_helper->getHelper('contextSwitch')->getCurrentContext();

        switch ($context) {
            case 'importArchive':
                $this->logInfo('Task import archive downloaded');
                $this->downloadImportArchive();
                return;

            case 'excelhistory':
                if(!$this->isAllowed('frontend', 'editorExportExcelhistory')) {
                    throw new ZfExtended_NoAccessException();
                }
                // run history excel export
                /** @var editor_Models_Export_TaskHistoryExcel $exportExcel */
                $exportExcel = ZfExtended_Factory::get('editor_Models_Export_TaskHistoryExcel', [$this->entity]);
                $exportExcel->exportAsDownload();
                return;

            case 'xliff2':
                $this->entity->checkStateAllowsActions();
                $worker = ZfExtended_Factory::get('editor_Models_Export_Xliff2Worker');
                $diff = false;
                /* @var $worker editor_Models_Export_Xliff2Worker */
                $exportFolder = $worker->initExport($this->entity);
                break;

            case 'package':
                if( $this->entity->isLocked($this->entity->getTaskGuid())){
                    $this->view->assign('error','Unable to export task package. The task is locked');
                    echo $this->view->render('task/packageexport.phtml');
                    exit;
                }
                try {
                    $this->entity->checkStateAllowsActions();
                    Lock::taskLock($this->entity,Downloader::TASK_PACKAGE_EXPORT_STATE);

                    $packageDownloader = ZfExtended_Factory::get(Downloader::class);
                    $packageDownloader->downloadPackage($this->entity,$diff);
                    $this->logInfo('Task package exported', ['context' => $context, 'diff' => $diff]);
                    Lock::taskUnlock($this->entity);
                }catch (Throwable $exception){
                    Lock::taskUnlock($this->entity);
                    $this->log->exception($exception,[
                        'extra' => [
                            'task' => $this->entity
                        ]
                    ]);
                    $this->view->assign('error','Error on task package export. For more info check the event log.');
                    echo $this->view->render('task/packageexport.phtml');
                }
                exit;
            case 'filetranslation':
            case 'transfer':
            default:
                $this->entity->checkStateAllowsActions();
                $worker = ZfExtended_Factory::get('editor_Models_Export_Worker');
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

        // Get worker
        /* @var $worker editor_Models_Export_Exported_Worker */
        $worker = editor_Models_Export_Exported_Worker::factory($context);

        // Setup worker. 'cookie' in 2nd arg is important only if $context is 'transfer'
        $inited = $worker->setup($this->entity->getTaskGuid(), [
            'exportFolder' => $exportFolder,
            'cookie' => Zend_Session::getId()
        ]);

        // If $content is not 'filetranslation' or 'transfer' assume init return value is zipFile name
        if (!in_array($context, ['filetranslation', 'transfer'])) {
            $zipFile = $inited;
        }

        //TODO for the API usage of translate5 blocking on export makes no sense
        // better would be a URL to fetch the latest export or so (perhaps using state 202?)
        $worker->setBlocking(); //we have to wait for the underlying worker to provide the download
        $worker->queue($workerId);

        if ($context == 'transfer') {
            $this->logInfo('Task exported. reimport started', ['context' => $context, 'diff' => $diff]);
            echo $this->view->render('task/ontransfer.phtml');
            exit;
        }

        if($context == 'filetranslation') {
            $this->provideFiletranslationDownload($exportFolder);
            exit;
        }

        $taskguiddirectory = $this->getParam('taskguiddirectory');
        if(is_null($taskguiddirectory)) {
            $taskguiddirectory = $this->config->runtimeOptions->editor->export->taskguiddirectory;
        }
        // remove the taskGuid from root folder name in the exported package
        if ($context == 'xliff2' || !$taskguiddirectory) {
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
        // CORS header
        Cors::sendResponseHeader();
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
        // CORS header
        Cors::sendResponseHeader();
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
        $this->view->success = $this->workflow->hookin()->doDirectTrigger($this->entity, $this->getParam('trigger'));
        if($this->view->success) {
            return;
        }
        $errors = array('trigger' => 'Trigger is invalid. Valid triggers are listed below.');
        $e = new ZfExtended_ValidateException();
        $e->setErrors($errors);
        $this->view->validTrigger = $this->workflow->hookin()->getDirectTrigger();
        $this->handleValidateException($e);
    }
    
    /**
     * Search the task id position in the current filter
     */
    public function positionAction() {
        //TODO The optimal way to implement this, is like similar to the segment::positionAction in a general way so that it is usable for all entities.
        $this->addDefaultSort();
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
     * Report worker progress for given taskGuid
     * @throws ZfExtended_ErrorCodeException
     */
    public function importprogressAction() {
        $taskGuid = $this->getParam('taskGuid');
        if(empty($taskGuid)){
            throw new editor_Models_Task_Exception('E1339');
        }
        $this->view->progress = $this->getTaskImportProgress($taskGuid);
    }

    /**
     * Get/calculate the taskImport progress for given taskGuid
     * @param string $taskGuid
     * @return array
     */
    protected function getTaskImportProgress(string $taskGuid): array {
        /** @var editor_Models_Task_WorkerProgress $progress */
        $progress = ZfExtended_Factory::get('editor_Models_Task_WorkerProgress');
        return $progress->calculateProgress($taskGuid);
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
        $filter = $this->entity->getFilter();
        $taskTypes = editor_Task_Type::getInstance();
        if($filter->hasFilter('projectId') && !$projectOnly){
            //filter for all tasks in the project(return also the single task projects)
            $filter->addFilter((object)[
                'field' => 'taskType',
                'value' => $taskTypes->getProjectTypes(true),
                'type' => 'notInList',
                'comparison' => 'in'
            ]);
            return false;
        }

        if($projectOnly){
            $filterValues = $taskTypes->getProjectTypes();
        } else {
            $filterValues = $taskTypes->getNonInternalTaskTypes();
        }
        
        $filter->addFilter((object)[
            'field' => 'taskType',
            'value' =>$filterValues,
            'type' => 'list',
            'comparison' => 'in'
        ]);
        return $projectOnly;
    }

    /***
     * Check if the given task/project can be deleted based on the task state. When project task is provided,
     * all project tasks will be checked
     * @throws ZfExtended_Models_Entity_Conflict
     */
    protected function checkStateDelete(editor_Models_Task $taskEntity, bool $forced){

        // if it is not project, do regular check
        if($taskEntity->isProject()){
            $model = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $model editor_Models_Task */
            $tasks = $model->loadProjectTasks($this->entity->getProjectId(),true);
            // if it is project, load all project tasks, and check the state for each one of them
            foreach ($tasks as $projectTask){
                $model->init($projectTask);
                $this->checkStateDelete($model,$forced);
            }
        } else {
            //if task is erroneous then it is also deleteable, regardless of its locking state
            if(!$taskEntity->isImporting() && !$taskEntity->isErroneous() && !$forced){
                $taskEntity->checkStateAllowsActions();
            }
        }
    }

    protected function handleCancelImport() {
        $isAllowedToCancel = $this->isAllowed('frontend', 'editorCancelImport') || $this->isAuthUserTaskPm($this->entity->getPmGuid());

        //if no state is set or user is not allowed to cancel, do nothing
        if(empty($this->data->state)) {
            return;
        }

        //if task is importing and state is tried to be set to something other as error, unset state and do nothing here
        if($this->entity->isImporting() && ($this->data->state != $this->entity::STATE_ERROR || !$isAllowedToCancel)){
            unset($this->data->state);
            return;
        }

        //override the entity version check
        if(isset($this->data->entityVersion)) {
            unset($this->data->entityVersion);
        }

        $worker = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $worker ZfExtended_Models_Worker */
        try {
            $worker->loadFirstOf('editor_Models_Import_Worker', $this->entity->getTaskGuid());
            $worker->setState($worker::STATE_DEFUNCT);
            $worker->save();
            $worker->defuncRemainingOfGroup();
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            //if no import worker found, nothing can be stopped
        }
        $this->entity->unlock();
        $this->log->info('E1011', 'Task import cancelled', ['task' => $this->entity]);
    }

    /***
     * Check and set the default pivot langauge based on customer specific config.
     * If the pivot field is not provided on task post and for the current task customer
     * there is configured defaultPivotLanguage, the configured pivot language will be set as task pivot
     * @param Zend_Config $c
     * @return void
     */
    protected function setDefaultPivotOnTaskCreate(Zend_Config $c): void
    {
        // check if the relasiLang field is provided. If it is not provided, check and set default value from config.
        $pivotLang = $this->data['relaisLang'] ?? false;

        if($pivotLang === false && !empty($c->runtimeOptions->project->defaultPivotLanguage)){
            // get default pivot language value from the config
            $defaultPivot = $c->runtimeOptions->project->defaultPivotLanguage;
            try {
                /** @var editor_Models_Languages $language */
                $language = ZfExtended_Factory::get('editor_Models_Languages');
                $language->loadByRfc5646($defaultPivot);

                $this->entity->setRelaisLang($language->getId());
            }catch (Throwable $exception){
                // in case of wrong configured variable and the load language fails, do nothing
            }
        }
    }
}
