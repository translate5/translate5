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

use MittagQI\Translate5\Acl\Rights;
use MittagQI\Translate5\Export\QueuedExportService;
use MittagQI\Translate5\Segment\QualityService;
use MittagQI\Translate5\Task\Export\Package\Downloader;
use MittagQI\Translate5\Task\Import\ImportService;
use MittagQI\Translate5\Task\Import\ProjectWorkersService;
use MittagQI\Translate5\Task\Import\TaskDefaults;
use MittagQI\Translate5\Task\Import\TaskUsageLogger;
use MittagQI\Translate5\Task\Lock;
use MittagQI\Translate5\Task\TaskContextTrait;
use MittagQI\Translate5\Task\TaskService;
use MittagQI\Translate5\Task\Worker\Export\HtmlWorker;
use MittagQI\ZfExtended\Controller\Response\Header;
use MittagQI\ZfExtended\Session\SessionInternalUniqueId;
use PhpOffice\PhpSpreadsheet\Writer\Exception;

class editor_TaskController extends ZfExtended_RestController
{
    use TaskContextTrait;

    public const BACKEND = 'backend';

    protected $entityClass = 'editor_Models_Task';

    /**
     * aktueller Datumsstring
     * @var string
     */
    protected $now;

    /**
     * logged in user
     */
    protected ?ZfExtended_Models_User $authenticatedUser;

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
     * The download-actions need to be csrf unprotected!
     */
    protected array $_unprotectedActions = ['export', 'excelexport', 'kpi', 'packagestatus'];

    protected ImportService $importService;

    protected TaskDefaults $defaults;

    protected TaskUsageLogger $taskUsageLogger;

    protected ProjectWorkersService $workersHandler;

    protected editor_Workflow_Default $workflow;

    protected editor_Workflow_Manager $workflowManager;

    private QualityService $qualityService;

    public function init(): void
    {
        $this->_filterTypeMap = [
            'customerId' => [
                'string' => new ZfExtended_Models_Filter_Join('LEK_customer', 'name', 'id', 'customerId'),
            ],
            'workflowState' => [
                'list' => new ZfExtended_Models_Filter_Join('LEK_taskUserAssoc', 'state', 'taskGuid', 'taskGuid'),
            ],
            'workflowUserRole' => [
                'list' => new ZfExtended_Models_Filter_Join('LEK_taskUserAssoc', 'role', 'taskGuid', 'taskGuid'),
            ],
            'userName' => [
                'list' => new ZfExtended_Models_Filter_Join('LEK_taskUserAssoc', 'userGuid', 'taskGuid', 'taskGuid'),
            ],
            'segmentFinishCount' => [
                'numeric' => 'percent',
                'totalField' => 'segmentEditableCount',
            ],
            'userState' => [
                'list' => new ZfExtended_Models_Filter_Join('LEK_taskUserAssoc', 'state', 'taskGuid', 'taskGuid'),
            ],
            'orderdate' => [
                'numeric' => 'date',
            ],
            'assignmentDate' => [
                'numeric' => new ZfExtended_Models_Filter_Join('LEK_taskUserAssoc', 'assignmentDate', 'taskGuid', 'taskGuid', 'date'),
            ],
            'finishedDate' => [
                'numeric' => new ZfExtended_Models_Filter_Join('LEK_taskUserAssoc', 'finishedDate', 'taskGuid', 'taskGuid', 'date'),
            ],
            'deadlineDate' => [
                'numeric' => new ZfExtended_Models_Filter_Join('LEK_taskUserAssoc', 'deadlineDate', 'taskGuid', 'taskGuid', 'date'),
            ],
        ];

        //set same join for sorting!
        $this->_sortColMap['customerId'] = $this->_filterTypeMap['customerId']['string'];

        ZfExtended_UnprocessableEntity::addCodes([
            'E1064' => 'The referenced customer does not exist (anymore).',
        ], 'editor.task');
        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1159' => 'Task usageMode can only be modified, if no user is assigned to the task.',
            'E1163' => 'Your job was removed, therefore you are not allowed to access that task anymore.',
            'E1164' => 'You tried to open the task for editing,'
                    . ' but in the meantime you are not allowed to edit the task anymore.',
        ], 'editor.task');

        parent::init();
        $this->now = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
        $this->authenticatedUser = ZfExtended_Authentication::getInstance()->getUser();
        $this->workflowManager = ZfExtended_Factory::get(editor_Workflow_Manager::class);
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $this->config = Zend_Registry::get('config');
        $this->defaults = new TaskDefaults();
        $this->taskUsageLogger = ZfExtended_Factory::get(
            TaskUsageLogger::class,
            [ZfExtended_Factory::get(editor_Models_TaskUsageLog::class)]
        );
        $this->importService = new ImportService();
        $this->workersHandler = new ProjectWorkersService();
        $this->qualityService = new QualityService();

        //create a new logger instance writing only to the configured taskLogger
        $this->taskLog = ZfExtended_Factory::get(ZfExtended_Logger::class, [[
            'writer' => [
                'tasklog' => $this->config->resources->ZfExtended_Resource_Logger->writer->tasklog,
            ],
        ]]);

        $this->log = ZfExtended_Factory::get('editor_Logger_Workflow', [$this->entity]);

        //add context of valid export formats:
        // currently: xliff2, importArchive, excel
        $this->_helper
            ->getHelper('contextSwitch')
            ->addContext('xliff2', [
                'headers' => [
                    'Content-Type' => 'text/xml',
                ],
            ])
            ->addActionContext('export', 'xliff2')
            ->addContext('transfer', [
                'headers' => [
                    'Content-Type' => 'text/xml',
                ],
            ])
            ->addActionContext('export', 'transfer')
            ->addContext('importArchive', [
                'headers' => [
                    'Content-Type' => 'application/zip',
                ],
            ])
            ->addActionContext('export', 'importArchive')
            ->addContext('filetranslation', [
                'headers' => [
                    'Content-Type' => 'application/octet-stream',
                ],
            ])
            ->addActionContext('export', 'filetranslation')
            ->addContext('xlsx', [
                'headers' => [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // TODO Content-Type prüfen
                ],
            ])
            ->addActionContext('kpi', 'xlsx')
            ->addContext('excelhistory', [
                'headers' => [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // TODO Content-Type prüfen
                ],
            ])
            ->addActionContext('export', 'excelhistory')
            ->addContext('package', [
                'headers' => [
                    'Content-Type' => 'application/zip',
                ],
            ])->addActionContext('export', 'package')
            ->addContext('html', [
                'headers' => [
                    'Content-Type' => 'text/xml',
                ],
            ])->addActionContext('export', 'package')
            ->initContext();
    }

    /**
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction()
    {
        //set default sort
        $this->addDefaultSort();
        if ($this->handleProjectRequest()) {
            $this->view->rows = $this->loadAllForProjectOverview();
        } else {
            $this->view->rows = $this->loadAllForTaskOverview();
        }

        // Load overall and users-specific progress for each task in $this->view->rows
        ZfExtended_Factory
            ::get(editor_Models_TaskProgress::class)
                ->loadForRows($this->view->rows);

        $this->view->total = $this->totalCount;
    }

    /**
     * For requests that get the Key Performance Indicators (KPI)
     * for the currently filtered tasks. If the tasks are not to be limited
     * to those that are visible in the grid, the request must have set the
     * limit accordingly (= for all filtered tasks: no limit).
     */
    public function kpiAction()
    {
        //set default sort
        $this->addDefaultSort();
        $rows = $this->loadAll();

        $kpi = ZfExtended_Factory::get('editor_Models_KPI');
        /* @var $kpi editor_Models_KPI */
        $kpi->setTasks($rows);
        $kpiStatistics = $kpi->getStatistics();

        // For Front-End:
        $this->view->{$kpi::KPI_TRANSLATOR} = $kpiStatistics[$kpi::KPI_TRANSLATOR];
        $this->view->{$kpi::KPI_REVIEWER} = $kpiStatistics[$kpi::KPI_REVIEWER];
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
    public function userlistAction()
    {
        //set default sort
        $this->addDefaultSort();
        //set the default table to lek_task
        $this->entity->getFilter()->setDefaultTable('LEK_task');
        $this->view->rows = $this->entity->loadUserList($this->authenticatedUser->getUserGuid());
    }

    /**
     * loads all tasks according to the set filters
     * @return array
     */
    protected function loadAll()
    {
        // here no check for pmGuid, since this is done in task::loadListByUserAssoc
        $isAllowedToLoadAll = $this->isAllowed(Rights::ID, Rights::LOAD_ALL_TASKS);
        //set the default table to lek_task
        $this->entity->getFilter()->setDefaultTable('LEK_task');
        if ($isAllowedToLoadAll) {
            $this->totalCount = $this->entity->getTotalCount();
            $rows = $this->entity->loadAll();
        } else {
            $this->totalCount = $this->entity->getTotalCountByUserAssoc(
                $this->authenticatedUser->getUserGuid(),
                $isAllowedToLoadAll
            );
            $rows = $this->entity->loadListByUserAssoc($this->authenticatedUser->getUserGuid(), $isAllowedToLoadAll);
        }

        return $rows;
    }

    /**
     * returns all (filtered) tasks with added user data
     * uses $this->entity->loadAll
     */
    protected function loadAllForProjectOverview()
    {
        $rows = $this->loadAll();
        $customerData = $this->getCustomersForRendering($rows);
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $isTransfer = $file->getTransfersPerTasks(array_column($rows, 'taskGuid'));

        // If the config for mailto link in project grid pm user column is configured
        $isMailTo = $this->config->runtimeOptions->frontend->tasklist->pmMailTo;
        if ($isMailTo) {
            $userData = $this->getUsersForRendering($rows);
        }

        foreach ($rows as &$row) {
            unset($row['qmSubsegmentFlags']); // unneccessary in the project overview
            $row['customerName'] = empty($customerData[$row['customerId']]) ? '' : $customerData[$row['customerId']];
            $row['isTransfer'] = isset($isTransfer[$row['taskGuid']]);
            if ($isMailTo) {
                $row['pmMail'] = empty($userData[$row['pmGuid']]) ? '' : $userData[$row['pmGuid']];
            }
        }

        return $rows;
    }

    /**
     * returns all (filtered) tasks with added user data and quality data
     * uses $this->entity->loadAll
     */
    protected function loadAllForTaskOverview(): array
    {
        $rows = $this->loadAll();

        //if we have no paging parameters, we omit all additional data gathering to improve performace!
        if ($this->getParam('limit', 0) === 0 && ! $this->getParam('filter', false)) {
            return $rows;
        }

        $taskGuids = array_map(fn ($item) => $item['taskGuid'], $rows);

        $file = ZfExtended_Factory::get(editor_Models_File::class);
        $fileCount = $file->getFileCountPerTasks($taskGuids);
        $isTransfer = $file->getTransfersPerTasks($taskGuids);

        $this->_helper->TaskUserInfo->initUserAssocInfos($rows);

        //load the task assocs
        $languageResourcemodel = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);
        $resultlist = $languageResourcemodel->loadByAssociatedTaskGuidList($taskGuids);

        //group all assoc by taskguid
        $taskassocs = [];
        foreach ($resultlist as $res) {
            if (! isset($taskassocs[$res['taskGuid']])) {
                $taskassocs[$res['taskGuid']] = [];
            }
            array_push($taskassocs[$res['taskGuid']], $res);
        }

        //if the config for mailto link in task grid pm user column is configured
        $isMailTo = $this->config->runtimeOptions->frontend->tasklist->pmMailTo;

        $customerData = $this->getCustomersForRendering($rows);

        if ($isMailTo) {
            $userData = $this->getUsersForRendering($rows);
        }

        $tua = ZfExtended_Factory::get(editor_Models_TaskUserAssoc::class);
        $sessionUser = ZfExtended_Authentication::getInstance()->getUser();

        foreach ($rows as &$row) {
            try {
                $tua->loadByStepOrSortedState($sessionUser->getUserGuid(), $row['taskGuid'], $row['workflowStepName']);
                $row['hasCriticalErrors'] = $this->qualityService->taskHasCriticalErrors($row['taskGuid'], $tua);
            } catch (ZfExtended_Models_Entity_NotFoundException) {
                // In case if current user is not associated to task (PM for example)
                $row['hasCriticalErrors'] = $this->qualityService->taskHasCriticalErrors($row['taskGuid']);
            }
            $row['lastErrors'] = $this->getLastErrorMessage($row['taskGuid'], $row['state']);
            $this->initWorkflow($row['workflow']);

            $row['customerName'] = empty($customerData[$row['customerId']]) ? '' : $customerData[$row['customerId']];

            $isEditAll = $this->isAllowed(Rights::ID, Rights::EDIT_ALL_TASKS) || $this->isAuthUserTaskPm($row['pmGuid']);

            $this->_helper->TaskUserInfo->initForTask($this->workflow, $this->entity, $this->isTaskProvided());
            $this->_helper->TaskUserInfo->addUserInfos($row, $isEditAll);

            $row['fileCount'] = empty($fileCount[$row['taskGuid']]) ? 0 : $fileCount[$row['taskGuid']];
            $row['isTransfer'] = isset($isTransfer[$row['taskGuid']]);

            //add task assoc if exist
            if (isset($taskassocs[$row['taskGuid']])) {
                $row['taskassocs'] = $taskassocs[$row['taskGuid']];
            }

            if ($isMailTo) {
                $row['pmMail'] = empty($userData[$row['pmGuid']]) ? '' : $userData[$row['pmGuid']];
            }

            if (empty($this->entity->getTaskGuid())) {
                $this->entity->init($row);
            }
            // add quality related stuff
            $this->addQualitiesToResult($row);
            // add user-segment assocs
            $this->addMissingSegmentrangesToResult($row);
        }
        // sorting of qualityErrorCount can only be done after QS data is attached
        if ($this->entity->getFilter()->hasSort('qualityErrorCount')) {
            $direction = SORT_ASC;

            foreach ($this->entity->getFilter()->getSort() as $sort) {
                if ($sort->property == 'qualityErrorCount' && $sort->direction === 'DESC') {
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
     */
    protected function addQualitiesToResult(array &$row)
    {
        //TODO: for now we leave this as it is, if this produces performance problems, find better way for loading this config
        $taskConfig = $this->entity->getConfig();
        $qualityProps = editor_Models_Db_SegmentQuality::getNumQualitiesAndFaultsForTask($row['taskGuid']);
        // adding number of quality errors, evaluated in the export actions
        $row['qualityErrorCount'] = $qualityProps->numQualities;
        // adding if the task has internal tag errors, will prevent xliff exports (Note: this can be emulated for dev, see editor_Models_Quality_AbstractView::EMULATE_PROBLEMS
        $row['qualityHasFaults'] = (editor_Models_Quality_AbstractView::EMULATE_PROBLEMS) ? true : ($qualityProps->numFaults > 0);
        // adding QM SubSegment Infos to each Task, evaluated in the export actions
        $row['qualityHasMqm'] = ($taskConfig->runtimeOptions->autoQA->enableMqmTags && ! empty($row['qmSubsegmentFlags']));
        unset($row['qmSubsegmentFlags']); // unneccessary in the task overview
    }

    /**
     * Add the number of segments that are not assigned to a user
     * although some other segments ARE assigned to users of this role.
     */
    protected function addMissingSegmentrangesToResult(array &$row)
    {
        //ignore for non-simultaneous task
        if ($row['usageMode'] !== $this->entity::USAGE_MODE_SIMULTANEOUS) {
            return;
        }
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        $row['missingsegmentranges'] = $tua->getAllNotAssignedSegments($row['taskGuid']);
    }

    /**
     * Returns a mapping of customerIds and Names to the given rows of tasks
     * @return array
     */
    protected function getCustomersForRendering(array $rows)
    {
        if (empty($rows)) {
            return [];
        }

        $customerIds = array_map(function ($item) {
            return $item['customerId'];
        }, $rows);

        if (empty($customerIds)) {
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
    protected function getUsersForRendering(array $rows)
    {
        if (empty($rows)) {
            return [];
        }

        $userGuids = array_map(function ($item) {
            return $item['pmGuid'];
        }, $rows);

        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $userData = $user->loadByGuids($userGuids);
        $ret = [];
        foreach ($userData as $data) {
            $ret[$data['userGuid']] = $data['email'];
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
    public function postAction(): void
    {
        $this->entity->init();
        //$this->decodePutData(); → not needed, data was set directly out of params because of file upload
        $this->data = $this->getAllParams();

        settype($this->data['wordCount'], 'integer');
        settype($this->data['enableSourceEditing'], 'integer');
        settype($this->data['lockLocked'], 'integer');

        if (array_key_exists('deadlineDate', $this->data) && empty($this->data['deadlineDate'])) {
            $this->data['deadlineDate'] = null;
        }

        if (array_key_exists('enddate', $this->data)) {
            unset($this->data['enddate']);
        }

        if (array_key_exists('autoStartImport', $this->data)) {
            //if the value exists we assume boolean
            settype($this->data['autoStartImport'], 'boolean');
        } else {
            //if not explicitly disabled the import starts always automatically to be compatible with legacy API users
            $this->data['autoStartImport'] = true;
        }

        if (empty($this->data['pmGuid']) || ! $this->isAllowed(Rights::ID, 'editorEditTaskPm')) {
            $this->data['pmGuid'] = $this->authenticatedUser->getUserGuid();
            $this->data['pmName'] = $this->authenticatedUser->getUsernameLong();
        } else {
            $pm = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            $pm->loadByGuid($this->data['pmGuid']);
            $this->data['pmName'] = $pm->getUsernameLong();
        }

        if (empty($this->data['taskType'])) {
            $this->data['taskType'] = editor_Task_Type_Default::ID;
        }

        $this->processClientReferenceVersion();

        $this->setDataInEntity();
        $this->prepareLanguages();

        $this->entity->createTaskGuidIfNeeded();
        $this->entity->setImportAppVersion(ZfExtended_Utils::getAppVersion());

        //if the visual review mapping type is set, se the task metadata overridable
        if (isset($this->data['mappingType'])) {
            $meta = $this->entity->meta();
            $meta->setMappingType($this->data['mappingType']);
        }

        $customer = $this->prepareCustomer();

        $c = $this->config;
        if ($customer) {
            $c = $customer->getConfig();
        }
        // check if the relasiLang field is provided. If it is not provided, check and set default value from config.
        if (false === ($this->data['relaisLang'] ?? false)) {
            // check and set the default pivot language is configured
            $this->entity->setDefaultPivotLanguage($this->entity, $customer);
        }

        // set the usageMode from config if not set
        $this->entity->setUsageMode($this->data['usageMode'] ?? $c->runtimeOptions->import->initialTaskUsageMode);

        //init workflow id for the task, based on given value, customer or general config as fallback
        if (! array_key_exists('workflow', $this->data)) {
            $this->entity->setWorkflow($c->runtimeOptions->workflow->initialWorkflow);
        }

        $this->entity->setEdit100PercentMatch(
            (int) ($this->data['edit100PercentMatch'] ?? $c->runtimeOptions->import->edit100PercentMatch)
        );

        if (! $this->validate()) {
            // we have to prevent attached events, since when we get here the task is not created,
            // which would lead to task not found errors, but we want to result the validation error
            $event = Zend_EventManager_StaticEventManager::getInstance();
            $event->clearListeners(get_class($this), 'afterPostAction');

            return;
        }

        $this->initWorkflow();

        try {
            $tasks = $this->importService->importFromPost(
                $this->entity,
                $this->getRequest(),
                $this->data,
            );
        } catch (ZfExtended_ErrorCodeException $e) {
            if ($e instanceof editor_Models_Import_ConfigurationException) {
                $this->handleConfigurationException($e, $this->entity);
            } elseif ($e instanceof ZfExtended_Models_Entity_Exceptions_IntegrityConstraint) {
                $this->handleIntegrityConstraint($e);
            } elseif ($e instanceof editor_Models_Import_DataProvider_Exception) {
                $this->handleDataProviderException($e);
            }

            throw $e;
        }

        //warn the api user for the targetDeliveryDate usage
        $this->targetDeliveryDateWarning();

        //update the entity projectId
        $this->entity->setProjectId($this->entity->getId());
        $this->entity->save();

        //reload because entityVersion could be changed somewhere
        $this->entity->load((int) $this->entity->getId());

        if ($this->data['autoStartImport']) {
            $this->workersHandler->startImportWorkers($this->entity);
        }

        $this->view->success = true;
        $this->view->rows = $this->entity->getDataObject();
        settype($this->view->rows->projectTasks, 'array');
        $this->view->rows->projectTasks = $tasks;
    }

    /**
     * prepares the languages in $this->data for the import
     * @return int the target language count
     */
    protected function prepareLanguages(): void
    {
        if (! is_array($this->data['targetLang'])) {
            $lang = (string) $this->data['targetLang'];
            // enable sending target lang as comma-seperated array
            $this->data['targetLang'] = str_contains($lang, ',') ? explode(',', $lang) : [$lang];
        }

        $this->_helper->Api->convertLanguageParameters($this->data['sourceLang']);
        $this->entity->setSourceLang($this->data['sourceLang']);

        //with projects multiple targets are supported:
        foreach ($this->data['targetLang'] as &$target) {
            $this->_helper->Api->convertLanguageParameters($target);
        }

        // TODO: Remove the code bellow when frontend sorting is implemented. See: TRANSLATE-3254 Sort the target languages alphabetically on task creation
        // sort the target langauges only when the translate5 wizard is used to create task. In api tasks, the order
        // of the langauges, files and file types should be provided by the user and not changed
        if ((bool) $this->getParam('importWizardUsed', false) === true) {
            // sort the languages alphabetically
            $this->_helper->Api->sortLanguages($this->data['targetLang']);
        }

        //task is handled as a project (one source language, multiple target languages, each combo one own task)
        $targetLangCount = count($this->data['targetLang']);
        if ($targetLangCount > 1) {
            //with multiple target languages, the current task will be a project!
            $this->entity->setTargetLang(0);
        } else {
            $this->entity->setTargetLang(reset($this->data['targetLang']));
        }

        // If the relaisLang is not set, do nothing. In that case, the language default is set later on in the import
        if (isset($this->data['relaisLang'])) {
            if (empty($this->data['relaisLang'])) {
                $this->data['relaisLang'] = 0;
            } else {
                $this->_helper->Api->convertLanguageParameters($this->data['relaisLang']);
            }
            $this->entity->setRelaisLang($this->data['relaisLang']);
        }
    }

    /**
     * Loads the customer by id, or number, or the default customer
     * stores the customerid internally and in this->data
     * @return editor_Models_Customer_Customer the loaded customer if any
     */
    protected function prepareCustomer(): ?editor_Models_Customer_Customer
    {
        $result = $customer = ZfExtended_Factory::get('editor_Models_Customer_Customer');
        /* @var $customer editor_Models_Customer_Customer */

        if (empty($this->data['customerId'])) {
            $customer->loadByDefaultCustomer();
        } else {
            try {
                $customer->load($this->data['customerId']);
            } catch (ZfExtended_Models_Entity_NotFoundException $e) {
                try {
                    $customer->loadByNumber($this->data['customerId']);
                } catch (ZfExtended_Models_Entity_NotFoundException $e) {
                    // do nothing here, then the validation is triggered to feedback the user
                    $result = null;
                }
            }
        }

        $this->entity->setCustomerId((int) $customer->getId());
        $this->data['customerId'] = (int) $customer->getId();

        return $result;
    }

    /**
     * Starts the import of the task
     */
    public function importAction()
    {
        $this->getAction();
        $this->workersHandler->startImportWorkers($this->entity);
    }

    /***
     * Operation for pre task-import operations (change task config, task property or queue custom workers)
     */
    public function preimportOperation()
    {
        $projectId = $this->entity->getProjectId();
        $usageMode = $this->getParam('usageMode', false);
        $workflow = $this->getParam('workflow', false);
        $notifyAssociatedUsers = $this->getParam('notifyAssociatedUsers', false);
        if (! $projectId) {
            return;
        }
        $projectLoader = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $projectLoader editor_Models_Task */
        $projectTaks = $projectLoader->loadProjectTasks($projectId, true);

        foreach ($projectTaks as $task) {
            $saveModel = false;
            $model = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $model editor_Models_Task */
            $model->load($task['id']);

            if (! empty($usageMode)) {
                $saveModel = true;
                $model->setUsageMode($usageMode);
            }

            if (! empty($workflow)) {
                $saveModel = true;
                $model->setWorkflow($workflow);
            }

            if ($notifyAssociatedUsers !== false) {
                $saveModel = true;
                $taskConfig = ZfExtended_Factory::get('editor_Models_TaskConfig');
                /* @var $taskConfig editor_Models_TaskConfig */
                // INFO: runtimeOptions.workflow.notifyAllUsersAboutTask can be overwritten only on customer level but from the frontend we are able to
                // change this value via checkbox in the import wizard. This to take effect on all tasks, we insert the config value in the task config table, which
                // value will be evaluated later (after task import in the notification class)
                $taskConfig->updateInsertConfig($model->getTaskGuid(), 'runtimeOptions.workflow.notifyAllUsersAboutTask', $notifyAssociatedUsers);
            }

            $saveModel && $model->save();
        }
    }

    /**
     * This Operation refreshes (re-evaluates / retags) all qualities
     */
    public function autoqaOperation()
    {
        editor_Segment_Quality_Manager::autoqaOperation($this->entity);
    }

    /**
     * Starts the export of a task into an excel file
     */
    public function excelexportAction()
    {
        $this->entityLoad();

        // run excel export
        $exportExcel = ZfExtended_Factory::get(editor_Models_Export_Excel::class, [$this->entity]);
        $this->log->info('E1011', 'Task exported as excel file and locked for further processing.');
        $exportExcel->exportAsDownload();
    }

    /**
     * Starts the reimport of an earlier exported excel into the task
     */
    public function excelreimportAction()
    {
        $this->getAction();

        // do nothing if task is not in state "is Excel exported"
        if ($this->entity->getState() != editor_Models_Task::STATE_EXCELEXPORTED) {
            $this->view->success = false;

            return;
        }

        try {
            $tempFilename = date('Y-m-d__H_i_s') . '__' . rand() . '.xslx';
            $uploadTarget = $this->entity->getAbsoluteTaskDataPath() . '/excelReimport/';
            // create upload target directory /data/importedTasks/<taskGuid>/excelReimport/ (if not exist already)
            if (! is_dir($uploadTarget)) {
                mkdir($uploadTarget, 0755);
            }
            // move uploaded excel into upload target
            if (! move_uploaded_file($_FILES['excelreimportUpload']['tmp_name'], $uploadTarget . $tempFilename)) {
                // throw exception 'E1141' => 'Excel Reimport: upload failed.'
                throw new editor_Models_Excel_ExImportException('E1141', [
                    'task' => $this->entity,
                ]);
            }
            $excelReimport = ZfExtended_Factory::get(
                editor_Models_Import_Excel::class,
                [
                    $this->entity,
                    $tempFilename,
                    $this->authenticatedUser->getUserGuid(),
                ]
            );
            // on error an editor_Models_Excel_ExImportException is thrown
            $excelReimport->reimport($this->translate, $this->restMessages, $this->authenticatedUser);
            $this->log->info('E1011', 'Task re-imported from excel file and unlocked for further processing.');
        } catch (editor_Models_Excel_ExImportException $e) {
            $this->handleExcelreimportException($e);
        }
        $this->view->success = true;
    }

    /**
     * Handles the exceptions happened on excel reimport
     * @throws ZfExtended_ErrorCodeException
     */
    protected function handleExcelreimportException(ZfExtended_ErrorCodeException $e)
    {
        $codeToFieldAndMessage = [
            'E1138' => ['excelreimportUpload', 'Die Excel Datei gehört nicht zu dieser Aufgabe.'],
            'E1139' => ['excelreimportUpload', 'Die Anzahl der Segmente in der Excel-Datei und in der Aufgabe sind unterschiedlich!'],
            'E1140' => ['excelreimportUpload', 'Ein oder mehrere Segmente sind in der Excel-Datei leer, obwohl in der Orginalaufgabe Inhalt vorhanden war.'],
            'E1141' => ['excelreimportUpload', 'Dateiupload fehlgeschlagen. Bitte versuchen Sie es erneut.'],
        ];
        $code = $e->getErrorCode();
        if (empty($codeToFieldAndMessage[$code])) {
            throw $e;
        }
        // the Import exceptions causing unprossable entity exceptions are logged on level info
        $this->log->exception($e, [
            'level' => ZfExtended_Logger::LEVEL_INFO,
        ]);

        throw ZfExtended_UnprocessableEntity::createResponseFromOtherException($e, [
            //fieldName => error message to field
            $codeToFieldAndMessage[$code][0] => $codeToFieldAndMessage[$code][1],
        ]);
    }

    /**
     * clone the given task into a new task
     * @throws BadMethodCallException
     * @throws ZfExtended_Exception
     * @throws Zend_Db_Statement_Exception
     * @throws Exception
     */
    public function cloneAction(): void
    {
        if (! $this->_request->isPost()) {
            throw new BadMethodCallException('Only HTTP method POST allowed!');
        }

        $this->getAction();

        //the dataprovider has to be created from the old task
        $dpFactory = ZfExtended_Factory::get(editor_Models_Import_DataProvider_Factory::class);
        $dataProvider = $dpFactory->createFromTask($this->entity);

        $cloner = ZfExtended_Factory::get(editor_Task_Cloner::class);

        $this->entity = $cloner->clone($this->entity);

        if ($this->validate()) {
            // set meta data in controller as in post request
            $metaData = $this->entity->meta()->toArray();
            unset($metaData['id'], $metaData['taskGuid']);

            foreach ($metaData as $field => $value) {
                $this->data[$field] = $value;
            }

            try {
                $this->importService->processUploadedFile(
                    $this->entity,
                    $dataProvider,
                    $this->data,
                    ZfExtended_Authentication::getInstance()->getUser()
                );
            } catch (ZfExtended_ErrorCodeException $e) {
                if ($e instanceof editor_Models_Import_ConfigurationException) {
                    $this->handleConfigurationException($e, $this->entity);
                } elseif ($e instanceof ZfExtended_Models_Entity_Exceptions_IntegrityConstraint) {
                    $this->handleIntegrityConstraint($e);
                } elseif ($e instanceof editor_Models_Import_DataProvider_Exception) {
                    $this->handleDataProviderException($e);
                }

                throw $e;
            }

            $cloner->cloneDependencies();
            $this->workersHandler->startImportWorkers($this->entity);
            //reload because entityVersion could be changed somewhere
            $this->entity->load((int) $this->entity->getId());
            $this->log->request();
            $this->view->success = true;
            $this->view->rows = $this->entity->getDataObject();
        }
    }

    /**
     * returns the logged events for the given task
     * @throws ZfExtended_Models_Entity_NoAccessException|ReflectionException
     */
    public function eventsAction(): void
    {
        $this->entityLoad();

        $this->isTaskAccessibleForCurrentUser();

        $events = ZfExtended_Factory::get(editor_Models_Logger_Task::class);

        //filter and limit for events entity
        $offset = $this->_getParam('start');
        $limit = $this->_getParam('limit');
        settype($offset, 'integer');
        settype($limit, 'integer');
        $events->limit(max(0, $offset), $limit);

        $filter = ZfExtended_Factory::get($this->filterClass, [
            $events,
            $this->getRequest()->getRawParam('filter'),
        ]);

        $filter->setSort($this->_getParam('sort', '[{"property":"id","direction":"DESC"}]'));
        $events->filterAndSort($filter);

        $this->view->rows = $events->loadByTaskGuid($this->entity->getTaskGuid());
        $this->view->total = $events->getTotalByTaskGuid($this->entity->getTaskGuid());
    }

    /**
     * currently taskController accepts only 2 changes by REST
     * - set locked: this sets the session_id implicitly and in addition the
     *   corresponding userGuid, if the passed locked value is set
     *   if locked = 0, task is unlocked
     * - set finished: removes locked implictly, and sets the userGuid of the "finishers"
     * @see ZfExtended_RestController::putAction()
     */
    public function putAction()
    {
        $this->entity->load($this->_getParam('id'));

        if ($this->entity->isProject()) {
            //project modification is not allowed. This will be changed in future.
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1284' => 'Projects are not editable.',
            ]);

            throw ZfExtended_Models_Entity_Conflict::createResponse('E1284', [
                'Projekte können nicht bearbeitet werden.',
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

        if (isset($this->data->edit100PercentMatch)) {
            settype($this->data->edit100PercentMatch, 'integer');
        }

        $this->checkTaskAttributeField();
        //was formerly in JS: if a userState is transfered, then entityVersion has to be ignored!
        // but what we do is to check the previous userState. So we have control if entity was not uptodate regarding state, and we could assume the wanted transition since we have a start (the previous) and an end (the new) state
        if (! empty($this->data->userState)) {
            unset($this->data->entityVersion);
        }
        if (isset($this->data->enableSourceEditing)) {
            $this->data->enableSourceEditing = (bool) $this->data->enableSourceEditing;
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

        if ($oldTask->getState() != $this->entity->getState()) {
            $this->logInfo('Status change to {status}', [
                'status' => $this->entity->getState(),
            ]);
        } else {
            //id is always set as modified, therefore we don't log task changes if id is the only modified
            $modified = $this->entity->getModifiedValues();
            if (! array_key_exists('id', $modified) || count($modified) > 1) {
                $this->logInfo('Task modified - prev. value was: ');
            }
        }

        //updateUserState does also call workflow "do" methods!
        $this->updateUserState($this->authenticatedUser->getUserGuid());

        //closing a task must be done after all workflow "do" calls which triggers some events
        $this->closeAndUnlock();

        //if the edit100PercentMatch is changed, update the value for all segments in the task
        if (isset($this->data->edit100PercentMatch)) {
            $bulkUpdater = ZfExtended_Factory::get('editor_Models_Segment_AutoStates_BulkUpdater');
            /* @var editor_Models_Segment_AutoStates_BulkUpdater $bulkUpdater */
            $bulkUpdater->updateSegmentsEdit100PercentMatch($this->entity, (bool) $this->data->edit100PercentMatch);
        }

        //if the totals segment count is not set, update it before the entity is saved
        if ($this->entity->getSegmentCount() < 1) {
            $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
            $this->entity->setSegmentCount($segment->getTotalSegmentsCount($taskguid));
        }

        // Recalculate task progress and assign results into view
        $this->appendTaskProgress($this->entity);

        $this->entity->save();
        $obj = $this->entity->getDataObject();

        $userAssocInfos = $this->_helper->TaskUserInfo->initUserAssocInfos([$obj]);
        $this->invokeTaskUserTracking($taskguid, $userAssocInfos[$taskguid]['role'] ?? '');

        //because we are mixing objects (getDataObject) and arrays (loadAll) as entity container we have to cast here
        $row = (array) $obj;
        $isEditAll =
            $this->isAllowed(Rights::ID, Rights::EDIT_ALL_TASKS) || $this->isAuthUserTaskPm($row['pmGuid']);
        $this->_helper->TaskUserInfo->initForTask($this->workflow, $this->entity, $this->isTaskProvided());
        $this->_helper->TaskUserInfo->addUserInfos($row, $isEditAll, $this->data->userState ?? null);
        $this->view->rows = (object) $row;

        if ($this->isOpenTaskRequest()) {
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
    protected function checkTaskAccess()
    {
        $mayLoadAllTasks = $this->isAllowed(Rights::ID, Rights::LOAD_ALL_TASKS)
            || $this->isAuthUserTaskPm($this->entity->getPmGuid());

        try {
            $tua = editor_Models_Loaders_Taskuserassoc::loadByTask(
                $this->authenticatedUser->getUserGuid(),
                $this->entity
            );
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $tua = null;
        }

        //mayLoadAllTasks is only true, if the current "PM" is not associated to the task directly.
        // If it is (pm override false) directly associated, the workflow must be considered it the task is openable / writeable.
        $mayLoadAllTasks = $mayLoadAllTasks && (empty($tua) || $tua->getIsPmOverride());

        //if the user may load all tasks, check workflow access is non sense
        if ($mayLoadAllTasks) {
            return;
        }

        $isTaskDisallowEditing = $this->isEditTaskRequest() && ! $this->workflow->isWriteable($tua);
        $isTaskDisallowReading = $this->isViewTaskRequest() && ! $this->workflow->isReadable($tua);

        //if now there is no tua, that means it was deleted in the meantime.
        // A PM will not reach here, a editor user may not access the task then anymore
        if (empty($tua)) {
            //if the task was already in session, we must delete it.
            //If not the user will always receive an error in JS, and would not be able to do anything.
            $this->unregisterTask();

            throw ZfExtended_Models_Entity_Conflict::createResponse('E1163', [
                'userState' => 'Ihre Zuweisung zur Aufgabe wurde entfernt, daher können Sie diese nicht mehr zur Bearbeitung öffnen.',
            ]);
        }

        //the tua state was changed by a PM, then the task may not be edited anymore by the user
        if ($isTaskDisallowEditing && $this->data->userStatePrevious != $tua->getState()) {
            $this->unregisterTask();

            throw ZfExtended_Models_Entity_Conflict::createResponse('E1164', [
                'userState' => 'Sie haben versucht die Aufgabe zur Bearbeitung zu öffnen. Das ist in der Zwischenzeit nicht mehr möglich.',
            ]);
        }
        //if reading is allowed the edit request is converted to a read request later by openAndLock
        //if reading is also disabled, we have to throw no access here
        if ($isTaskDisallowEditing && $isTaskDisallowReading) {
            $this->unregisterTask();
            //no access as generic fallback
            $this->log->info('E9999', 'Debug data to E9999 - Keine Zugriffsberechtigung!', [
                '$mayLoadAllTasks' => $mayLoadAllTasks,
                'tua' => $tua ? $tua->getDataObject() : 'no tua',
                'isPmOver' => $tua && $tua->getIsPmOverride(),
                'loadAllTasks' => $this->isAllowed(Rights::ID, Rights::LOAD_ALL_TASKS),
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
     * @throws Zend_Exception
     * @throws ZfExtended_ErrorCodeException
     */
    private function checkTaskStateTransition(): void
    {
        $closingTask = ($this->data->state ?? null) === 'end';

        if ($closingTask && null !== $this->entity->getLocked()) {
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
    protected function validateUsageMode()
    {
        if (! $this->entity->isModified('usageMode')) {
            return;
        }
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        $used = $tua->loadByTaskGuidList([$this->entity->getTaskGuid()]);
        if (empty($used)) {
            return;
        }

        //FIXME also throw an exception if task is locked
        throw ZfExtended_Models_Entity_Conflict::createResponse('E1159', [
            'usageMode' => [
                'usersAssigned' => 'Der Nutzungsmodus der Aufgabe kann verändert werden, wenn kein Benutzer der Aufgabe zugewiesen ist.',
            ],
        ]);
    }

    /**
     * Add pixelMapping-data to the view (= for the fonts used in the task).
     */
    protected function addPixelMapping()
    {
        $pixelMapping = ZfExtended_Factory::get('editor_Models_PixelMapping');

        /* @var $pixelMapping editor_Models_PixelMapping */
        try {
            $pixelMappingForTask = $pixelMapping->getPixelMappingForTask($this->entity->getTaskGuid(), $this->entity->getAllFontsInTask());
        } catch (ZfExtended_Exception $e) {
            $pixelMappingForTask = [];
        }
        $this->view->rows->pixelMapping = $pixelMappingForTask;
    }

    /**
     * returns the last error to the taskGuid if given taskStatus is error
     * @param string $taskGuid
     * @param string $taskStatus
     */
    protected function getLastErrorMessage($taskGuid, $taskStatus)
    {
        if ($taskStatus != editor_Models_Task::STATE_ERROR) {
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
    protected function isOpenTaskRequest(): bool
    {
        return $this->isEditTaskRequest() || $this->isViewTaskRequest();
    }

    /**
     * returns true if PUT Requests opens a task for open or finish
     * @return boolean
     */
    protected function isLeavingTaskRequest(): bool
    {
        if (empty($this->data->userState)) {
            return false;
        }

        return $this->data->userState == $this->workflow::STATE_OPEN || $this->data->userState == $this->workflow::STATE_FINISH;
    }

    /**
     * returns true if PUT Requests opens a task for editing
     * @return boolean
     */
    protected function isEditTaskRequest(): bool
    {
        if (empty($this->data->userState)) {
            return false;
        }

        return $this->data->userState == $this->workflow::STATE_EDIT;
    }

    /**
     * returns true if PUT Requests opens a task for viewing(readonly)
     * @return boolean
     */
    protected function isViewTaskRequest(): bool
    {
        if (empty($this->data->userState)) {
            return false;
        }

        return $this->data->userState == $this->workflow::STATE_VIEW;
    }

    /**
     * invokes taskUserTracking if its an opening or an editing request
     * (no matter if the workflow-users of the task are to be anonymized or not)
     */
    protected function invokeTaskUserTracking(string $taskguid, string $role)
    {
        if (! $this->isOpenTaskRequest()) {
            return;
        }
        $taskUserTracking = ZfExtended_Factory::get('editor_Models_TaskUserTracking');
        /* @var $taskUserTracking editor_Models_TaskUserTracking */
        $taskUserTracking->insertTaskUserTrackingEntry($taskguid, $this->authenticatedUser->getUserGuid(), $role);
    }

    /**
     * locks the current task if its an editing request
     * stores the task as active task if its an opening or an editing request
     */
    protected function openAndLock()
    {
        $task = $this->entity;
        /* @var $task editor_Models_Task */
        if ($this->isEditTaskRequest()) {
            $isMultiUser = $task->getUsageMode() == $task::USAGE_MODE_SIMULTANEOUS;
            $unconfirmed = $task->getState() == $task::STATE_UNCONFIRMED;
            //first check for confirmation on task level, if unconfirmed, don't lock just set to view mode!
            //if no multiuser, try to lock for user
            //if multiuser, try a system lock
            if ($unconfirmed || ! ($isMultiUser ? $task->lock($this->now, $task::USAGE_MODE_SIMULTANEOUS) : $task->lockForSessionUser($this->now))) {
                $this->data->userState = $this->workflow::STATE_VIEW;
            }
        }
        if ($this->isOpenTaskRequest()) {
            $task->createMaterializedView();
            $this->events->trigger(
                "afterTaskOpen",
                $this,
                [
                    'task' => $task,
                    'view' => $this->view,
                    'openState' => $this->data->userState,
                ]
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
    protected function closeAndUnlock()
    {
        $task = $this->entity;
        $hasState = ! empty($this->data->userState);
        $isEnding = isset($this->data->state) && $this->data->state == $task::STATE_END;
        $resetToOpen = $hasState && $this->data->userState == $this->workflow::STATE_EDIT && $isEnding;
        if ($resetToOpen) {
            //This state change will be saved at the end of this method.
            $this->data->userState = $this->workflow::STATE_OPEN;
        }
        if (! $isEnding && (! $this->isLeavingTaskRequest())) {
            return;
        }
        $this->entity->unlockForUser($this->authenticatedUser->getUserGuid());
        $this->unregisterTask();

        if ($resetToOpen) {
            $this->updateUserState($this->authenticatedUser->getUserGuid(), true);
        }
        $this->events->trigger(
            "afterTaskClose",
            $this,
            [
                'task' => $task,
                'view' => $this->view,
                'openState' => $this->data->userState ?? null,
            ]
        );
    }

    /**
     * unregisters the task from the session and close all open services
     */
    protected function unregisterTask()
    {
        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */
        $manager->closeForTask($this->entity);
    }

    /**
     * Updates the transferred User Assoc State to the given userGuid (normally the current user)
     * Per Default all state changes trigger something in the workflow. In some circumstances this should be disabled.
     * @param bool $disableWorkflowEvents optional, defaults to false
     */
    protected function updateUserState(string $userGuid, $disableWorkflowEvents = false): void
    {
        if (empty($this->data->userState)) {
            return;
        }
        settype($this->data->userStatePrevious, 'string');

        if (! in_array($this->data->userState, $this->workflow->getStates())) {
            throw new ZfExtended_ValidateException('Given UserState ' . $this->data->userState . ' does not exist.');
        }

        $isEditAllTasks = $this->isAllowed(Rights::ID, Rights::EDIT_ALL_TASKS)
            || $this->isAuthUserTaskPm($this->entity->getPmGuid());
        $isOpen = $this->isOpenTaskRequest();

        $userTaskAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');

        /* @var $userTaskAssoc editor_Models_TaskUserAssoc */
        try {
            if ($isEditAllTasks) {
                $userTaskAssoc = editor_Models_Loaders_Taskuserassoc::loadByTaskForceWorkflowRole($userGuid, $this->entity);
            } else {
                $userTaskAssoc = editor_Models_Loaders_Taskuserassoc::loadByTask($userGuid, $this->entity);
            }

            $isPmOverride = (bool) $userTaskAssoc->getIsPmOverride();
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            if (! $isEditAllTasks) {
                if ($this->isLeavingTaskRequest()) {
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

        if ($isOpen) {
            $userTaskAssoc->setUsedInternalSessionUniqId(
                SessionInternalUniqueId::getInstance()->get()
            );
            $userTaskAssoc->setUsedState($this->data->userState);
        } else {
            if ($isPmOverride && $isEditAllTasks) {
                $this->log->info('E1011', 'PM left task');
                $userTaskAssoc->deletePmOverride();

                return;
            }

            $userTaskAssoc->setUsedInternalSessionUniqId(null);
            $userTaskAssoc->setUsedState(null);
        }

        if ($this->workflow->isStateChangeable($userTaskAssoc, $this->data->userStatePrevious)) {
            $userTaskAssoc->setState($this->data->userState);
        }

        if ($disableWorkflowEvents) {
            $userTaskAssoc->save();
        } else {
            $this->workflow->hookin()->doWithUserAssoc(
                $oldUserTaskAssoc,
                $userTaskAssoc,
                function (?string $state) use ($userTaskAssoc) {
                    if ($state) {
                        TaskService::validateForTaskFinish($state, $userTaskAssoc, $this->entity);
                    }
                    $userTaskAssoc->save();
                }
            );
        }

        if ($oldUserTaskAssoc->getState() != $this->data->userState) {
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
    protected function addMqmQualities()
    {
        $qualityMqmCategories = $this->entity->getQmSubsegmentFlags();
        $this->view->rows->qualityHasMqm = false;
        $taskConfig = $this->entity->getConfig();
        if ($taskConfig->runtimeOptions->autoQA->enableMqmTags && ! empty($qualityMqmCategories)) {
            $this->view->rows->qualityMqmCategories = $this->entity->getMqmTypesTranslated(false);
            $this->view->rows->qualityMqmSeverities = $this->entity->getMqmSeveritiesTranslated(false);
            $this->view->rows->qualityHasMqm = true;
        }
    }

    /**
     * @see ZfExtended_RestController::additionalValidations()
     */
    protected function additionalValidations()
    {
        // validate the taskType
        $this->validateTaskType();
    }

    /**
     * Validate the taskType: check if given tasktype is allowed according to role
     * @throws ZfExtended_UnprocessableEntity
     * @throws Zend_Acl_Exception
     */
    protected function validateTaskType()
    {
        $acl = ZfExtended_Acl::getInstance();

        $isTaskTypeAllowed = $acl->isInAllowedRoles(
            ZfExtended_Authentication::getInstance()->getUserRoles(),
            \editor_Task_Type::ID,
            $this->entity->getTaskType()->id()
        );

        if (! $isTaskTypeAllowed) {
            ZfExtended_UnprocessableEntity::addCodes([
                'E1217' => 'TaskType not allowed.',
            ], 'editor.task');

            throw new ZfExtended_UnprocessableEntity('E1217');
        }
    }

    /**
     * (non-PHPdoc)
     * @throws ZfExtended_Models_Entity_NoAccessException
     * @see ZfExtended_RestController::getAction()
     */
    public function getAction()
    {
        parent::getAction();
        $this->initWorkflow();

        $hasRightForTask = $this->isTaskAccessibleForCurrentUser();

        $obj = $this->entity->getDataObject();

        $this->_helper->TaskUserInfo->initUserAssocInfos([$obj]);

        //because we are mixing objects (getDataObject) and arrays (loadAll) as entity container we have to cast here
        $row = (array) $obj;

        $isEditAll = $this->isAllowed(Rights::ID, Rights::EDIT_ALL_TASKS) || $hasRightForTask;
        $this->_helper->TaskUserInfo->initForTask($this->workflow, $this->entity, $this->isTaskProvided());
        $this->_helper->TaskUserInfo->addUserInfos($row, $isEditAll);
        $this->addMissingSegmentrangesToResult($row);
        $this->view->rows = (object) $row;

        unset($this->view->rows->qmSubsegmentFlags);

        //add task assoc to the task
        $languageResourcemodel = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /*@var $languageResourcemodel editor_Models_LanguageResources_LanguageResource */
        $resultlist = $languageResourcemodel->loadByAssociatedTaskGuid($this->entity->getTaskGuid());
        $this->view->rows->taskassocs = $resultlist;

        // Add pixelMapping-data for the fonts used in the task.
        // We do this here to have it immediately available e.g. when opening segments.
        $this->addPixelMapping();
        $this->view->rows->lastErrors = $this->getLastErrorMessage($this->entity->getTaskGuid(), $this->entity->getState());

        $this->view->rows->workflowProgressSummary = $this->_helper->TaskStatistics->getWorkflowProgressSummary($this->entity);
    }

    public function deleteAction()
    {
        $forced = $this->getParam('force', false) && $this->isAllowed(Rights::ID, Rights::TASK_FORCE_DELETE);
        $this->entityLoad();
        $this->checkStateDelete($this->entity, $forced);

        //we enable task deletion for importing task
        $forced = $forced || $this->entity->isImporting() || $this->entity->isProject();

        $this->processClientReferenceVersion();
        $remover = ZfExtended_Factory::get(editor_Models_Task_Remover::class, [$this->entity]);
        $remover->remove($forced);
    }

    /***
     * Check the status of the package export.
     * @return void
     * @throws Throwable
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws \MittagQI\Translate5\Task\Current\NoAccessException
     */
    public function packagestatusAction(): void
    {
        $this->initCurrentTask(false);
        $task = $this->getCurrentTask();

        try {
            $data = [];

            $downloader = ZfExtended_Factory::get(Downloader::class);

            $downloadLink = $this->getParam('download_link');

            if (! empty($downloadLink)) {
                $downloader->download($task, $downloadLink);
                exit;
            }
            $data['file_available'] = $downloader->isAvailable($this->getParam('workerId'));

            if ($data['file_available']) {
                $data['download_link'] = $downloader->getDownloadLink($task, $this->getParam('workerId'));

                Lock::taskUnlock($task);
            }

            echo Zend_Json::encode($data);
        } catch (Throwable $throwable) {
            Lock::taskUnlock($task);

            throw $throwable;
        }
    }

    /**
     * does the export as zip file.
     * @throws ZfExtended_Models_Entity_Conflict
     * @throws ZfExtended_NoAccessException
     * @throws Exception
     * @throws Zend_Exception
     */
    public function exportAction(): void
    {
        if ($this->isMaintenanceLoginLock(30)) {
            //since file is fetched for download we simply print out that text without decoration.
            echo 'Maintenance is scheduled, exports are not possible at the moment.';
            exit;
        }

        // Info: only 1 task export per task is allowed. In case user trys to export task which has already running
        // exports, the user will get error message. This is checked by checkExportAllowed and this function must be
        // used if other export types are implemented in future

        $context = $this->_helper->getHelper('contextSwitch')->getCurrentContext() ?? '';

        if ($context === 'package') {
            $this->assertTranslatorPackageAllowed();
        }

        $this->getAction();

        $diff = (bool) $this->getRequest()->getParam('diff');

        switch ($context) {
            case 'importArchive':
                $this->logInfo('Task import archive downloaded');
                $this->downloadImportArchive();

                return;

            case 'excelhistory':
                if (! $this->isAllowed(Rights::ID, Rights::EDITOR_EXPORT_EXCELHISTORY)) {
                    throw new ZfExtended_NoAccessException();
                }
                // run history excel export
                /** @var editor_Models_Export_TaskHistoryExcel $exportExcel */
                $exportExcel = ZfExtended_Factory::get('editor_Models_Export_TaskHistoryExcel', [$this->entity]);
                $exportExcel->exportAsDownload();

                return;

            case 'xliff2':
                $finalExportWorker = editor_Models_Export_Exported_Worker::factory($context);

                $this->entity->checkExportAllowed($finalExportWorker::class);

                $worker = ZfExtended_Factory::get('editor_Models_Export_Xliff2Worker');
                $diff = false;
                /* @var editor_Models_Export_Xliff2Worker $worker */
                $exportFolder = $worker->initExport($this->entity);

                break;

            case 'package':

                if ($this->entity->isLocked($this->entity->getTaskGuid())) {
                    $this->view->assign('error', 'Unable to export task package. The task is locked');
                    // in case the request is not from translate5 ui,
                    // json with the assigned view variables will be returned
                    // check the view bellow for more info
                    echo $this->view->render('task/packageexporterror.phtml');
                }

                try {
                    $this->entity->checkStateAllowsActions();

                    $packageDownloader = ZfExtended_Factory::get(Downloader::class);
                    $workerId = $packageDownloader->run($this->entity, $diff);

                    $this->view->assign('taskId', $this->entity->getId());
                    $this->view->assign('workerId', $workerId);

                    // in case the request is not from translate5 ui,
                    // json with the assigned view variables will be returned
                    // check the view bellow for more info
                    echo $this->view->render('task/packageexport.phtml');
                } catch (Throwable $exception) {
                    Lock::taskUnlock($this->entity);

                    $this->taskLog->exception($exception, [
                        'extra' => [
                            'task' => $this->entity,
                        ],
                    ]);
                    $this->view->assign('error', $exception->getMessage());
                    // in case the request is not from translate5 ui,
                    // json with the assigned view variables will be returned
                    // check the view bellow for more info
                    echo $this->view->render('task/packageexporterror.phtml');
                }
                exit;

            case 'html':
                $this->entity->checkStateAllowsActions();

                $exportService = QueuedExportService::create();
                $token = ZfExtended_Utils::uuid();
                $workerId = HtmlWorker::queueExportWorker(
                    $this->entity,
                    $exportService->composeExportDir($token)
                );

                $exportService->makeQueueRecord($token, $workerId, "{$this->entity->getTaskName()}.html");

                $title = $this->view->translate('HTML-Übersicht herunterladen');

                $this->redirect("/editor/queuedexport/$token?title=$title");

                break;

            case 'filetranslation':
            case 'transfer':
            default:
                /* @var editor_Models_Export_Exported_Worker $finalExportWorker */
                $finalExportWorker = editor_Models_Export_Exported_Worker::factory($context);

                $this->entity->checkExportAllowed($finalExportWorker::class);

                $worker = ZfExtended_Factory::get('editor_Models_Export_Worker');
                $exportFolder = $worker->initExport($this->entity, $diff);

                break;
        }

        if (! isset($worker) || ! isset($finalExportWorker)) {
            throw new LogicException('Worker not set');
        }

        if (! isset($exportFolder)) {
            throw new LogicException('Export folder not set');
        }

        $workerId = $worker->queue();

        // Setup worker. 'cookie' in 2nd arg is important only if $context is 'transfer'
        $inited = $finalExportWorker->setup($this->entity->getTaskGuid(), [
            'exportFolder' => $exportFolder,
            'cookie' => Zend_Session::getId(),
            'userId' => ZfExtended_Authentication::getInstance()->getUserId(),
        ]);

        // If $content is not 'filetranslation' or 'transfer' assume init return value is zipFile name
        if (! in_array($context, ['filetranslation', 'transfer'])) {
            $zipFile = $inited;
        }

        //TODO for the API usage of translate5 blocking on export makes no sense
        // better would be a URL to fetch the latest export or so (perhaps using state 202?)
        $finalExportWorker->setBlocking(); //we have to wait for the underlying worker to provide the download
        $finalExportWorker->queue($workerId);

        if ($context === 'transfer') {
            $this->logInfo('Task exported. reimport started', [
                'context' => $context,
                'diff' => $diff,
            ]);
            echo $this->view->render('task/ontransfer.phtml');
            exit;
        }

        if ($context === 'filetranslation') {
            $this->provideFiletranslationDownload($exportFolder);
            exit;
        }

        $taskguiddirectory = $this->getParam('taskguiddirectory');
        if (is_null($taskguiddirectory)) {
            $taskguiddirectory = $this->config->runtimeOptions->editor->export->taskguiddirectory;
        }
        // remove the taskGuid from root folder name in the exported package
        if ($context === 'xliff2' || ! $taskguiddirectory) {
            ZfExtended_Utils::cleanZipPaths(new SplFileInfo($zipFile), basename($exportFolder));
        }

        if ($diff) {
            $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
            /* @var ZfExtended_Zendoverwrites_Translate $translate */
            $suffix = $translate->_(' - mit Aenderungen nachverfolgen.zip');
        } else {
            $suffix = '.zip';
        }

        $this->logInfo('Task exported', [
            'context' => $context,
            'diff' => $diff,
        ]);
        $this->provideZipDownload($zipFile, $suffix);

        //rename file after usage to export.zip to keep backwards compatibility
        rename($zipFile, dirname($zipFile) . DIRECTORY_SEPARATOR . 'export.zip');
        exit;
    }

    /**
     * extracts the translated file from given $zipFile and sends it to the browser.
     */
    protected function provideFiletranslationDownload($exportFolder)
    {
        clearstatcache(); //ensure that files modfied by other plugins are available in stat cache
        $content = scandir($exportFolder);
        $foundFile = null;
        foreach ($content as $file) {
            //skip dots and all xlf files,
            if ($file == '.' || $file == '..') {
                continue;
            }
            //if no non xlf file was found yet, we use the xlf as found file
            if (empty($foundFile) && preg_match('/\.xlf$/i', $file)) {
                $foundFile = $file;

                //but we try to look for another file, so continue here
                continue;
            }
            //if we found a non xlf file, this file is used:
            $foundFile = $file;

            break;
        }

        $clean = function () use ($exportFolder) {
            $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
                'Recursivedircleaner'
            );
            $recursivedircleaner->delete($exportFolder);
        };

        $translatedfile = $exportFolder . DIRECTORY_SEPARATOR . $foundFile;
        if (empty($foundFile) || ! file_exists($translatedfile)) {
            $clean();

            throw new ZfExtended_NotFoundException('Requested file not found!');
        }

        $pathInfo = pathinfo($translatedfile);

        // where do we find what, what is named how.
        $targetLangRfc = $this->entity->getTargetLanguage()->getRfc5646();
        $filenameExport = $pathInfo['filename'] . '_' . $targetLangRfc;

        if (! empty('.' . $pathInfo['extension'])) {
            $filenameExport .= '.' . $pathInfo['extension'];
        }
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        Header::sendDownload(
            $filenameExport,
            null,
            'no-cache',
            -1,
            [
                'Content-Transfer-Encoding' => 'binary',
                'Content-Description' => 'File Transfer',
                'Expires' => '0',
                'Pragma' => 'public',
            ]
        );

        readfile($translatedfile);
        $clean(); //remove export dir
    }

    /**
     * sends the given $zipFile to the browser, the $nameSuffix is added to the filename provided to the browser
     * @param string $zipFile
     * @param string $nameSuffix
     */
    protected function provideZipDownload($zipFile, $nameSuffix)
    {
        // disable layout and view
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        Header::sendDownload(
            $this->entity->getTasknameForDownload($nameSuffix),
            'application/zip'
        );

        readfile($zipFile);
    }

    /**
     * provides the import archive file for download
     * @throws ZfExtended_NoAccessException
     * @throws ZfExtended_NotFoundException
     */
    protected function downloadImportArchive()
    {
        if (! $this->isAllowed(Rights::ID, Rights::DOWNLOAD_IMPORT_ARCHIVE)) {
            throw new ZfExtended_NoAccessException("The Archive ZIP can not be accessed");
        }
        $archiveZip = new SplFileInfo($this->entity->getAbsoluteTaskDataPath() . '/' . editor_Models_Import_DataProvider_Abstract::TASK_ARCHIV_ZIP_NAME);
        if (! $archiveZip->isFile()) {
            throw new ZfExtended_NotFoundException("Archive Zip for task " . $this->entity->getTaskGuid() . " could not be found");
        }
        $this->provideZipDownload($archiveZip, ' - ImportArchive.zip');
    }

    /**
     * @throws ZfExtended_NoAccessException
     */
    public function assertTranslatorPackageAllowed(): void
    {
        if (! $this->isAllowed(Rights::ID, Rights::EDITOR_PACKAGE_EXPORT)) {
            throw new ZfExtended_NoAccessException("Not allowed to export translator package");
        }
        if (! $this->isAllowed(Rights::ID, Rights::EDITOR_PACKAGE_REIMPORT)) {
            throw new ZfExtended_NoAccessException("Not allowed to import package");
        }
    }

    /**
     * Check if the given pmGuid(userGuid) is the same with the current logged user userGuid
     *
     * @return boolean
     */
    protected function isAuthUserTaskPm($taskPmGuid): bool
    {
        return $this->authenticatedUser->getUserGuid() === $taskPmGuid;
    }

    /**
     * Check if the user has rights to modify task attributes
     */
    protected function checkTaskAttributeField()
    {
        $fieldToRight = [
            'taskName' => 'editorEditTaskTaskName',
            'orderdate' => 'editorEditTaskOrderDate',
            'pmGuid' => 'editorEditTaskPm',
            'pmName' => 'editorEditTaskPm',
        ];

        //pre check pm change first
        if (! empty($this->data->pmGuid) && $this->isAllowed(Rights::ID, 'editorEditTaskPm')) {
            //if the pmGuid is modified, set the pmName
            $userModel = ZfExtended_Factory::get('ZfExtended_Models_User');
            /* @var $userModel  ZfExtended_Models_User*/
            $user = $userModel->loadByGuid($this->data->pmGuid);
            $this->data->pmName = $user->getUsernameLong();
        }

        //then loop over all allowed fields
        foreach ($fieldToRight as $field => $right) {
            if (! empty($this->data->$field) && ! $this->isAllowed(Rights::ID, $right)) {
                unset($this->data->$field);
                $this->log->warn('E1011', 'The user is not allowed to modify the tasks field {field}', [
                    'field' => $field,
                ]);
            }
        }
    }

    /**
     * Can be triggered with various actions from outside to trigger workflow stuff in translate5
     */
    public function workflowAction()
    {
        if (! $this->_request->isPost()) {
            throw new BadMethodCallException('Only HTTP method POST allowed!');
        }
        $this->entityLoad();
        $this->log->request();
        $this->initWorkflow($this->entity->getWorkflow());
        $this->view->trigger = $this->getParam('trigger');
        $this->view->success = $this->workflow->hookin()->doDirectTrigger($this->entity, $this->getParam('trigger'));
        if ($this->view->success) {
            return;
        }
        $errors = [
            'trigger' => 'Trigger is invalid. Valid triggers are listed below.',
        ];
        $e = new ZfExtended_ValidateException();
        $e->setErrors($errors);
        $this->view->validTrigger = $this->workflow->hookin()->getDirectTrigger();
        $this->handleValidateException($e);
    }

    /**
     * Search the task id position in the current filter
     */
    public function positionAction()
    {
        //TODO The optimal way to implement this, is like similar to the segment::positionAction in a general way so that it is usable for all entities.
        $this->addDefaultSort();
        $this->handleProjectRequest();
        $rows = $this->loadAll();
        $id = (int) $this->_getParam('id');
        $index = false;
        if (! empty($rows)) {
            $index = array_search($id, array_column($rows, 'id'));
        }
        if ($index === false) {
            $index = -1;
        }
        $this->view->index = $index;
        unset($this->view->rows);
    }

    /***
     * Report worker progress for given taskGuid
     * @throws ZfExtended_ErrorCodeException
     */
    public function importprogressAction()
    {
        $taskGuid = $this->getParam('taskGuid');
        if (empty($taskGuid)) {
            throw new editor_Models_Task_Exception('E1339');
        }
        $this->view->progress = $this->getTaskImportProgress($taskGuid);
    }

    /**
     * Get/calculate the taskImport progress for given taskGuid
     */
    protected function getTaskImportProgress(string $taskGuid): array
    {
        /** @var editor_Models_Task_WorkerProgress $progress */
        $progress = ZfExtended_Factory::get('editor_Models_Task_WorkerProgress');

        return $progress->calculateProgress($taskGuid);
    }

    /**
     * Logs a info message to the current task to the task_log table ONLY!
     * @param string $message message to be logged
     * @param array $extraData optional, extra data to the log entry
     */
    protected function logInfo($message, array $extraData = [])
    {
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
    protected function targetDeliveryDateWarning($isPost = false)
    {
        $date = false;
        if (is_array($this->data) && isset($this->data['targetDeliveryDate'])) {
            $date = $this->data['targetDeliveryDate'];
        }
        if (is_object($this->data) && isset($this->data->targetDeliveryDate)) {
            $date = $this->data->targetDeliveryDate;
        }
        if ($date === false) {
            return;
        }
        //different instance for column creation needed:
        $taskMeta = $this->entity->meta();
        $taskMeta->addMeta('targetDeliveryDate', $taskMeta::META_TYPE_STRING, null, 'Temporary field to store the targetDeliveryDate until all API users has migrated.');
        $taskMeta = $this->entity->meta(true);
        $taskMeta->setTargetDeliveryDate($date);
        $taskMeta->save();

        $this->log->warn('E1210', 'The targetDeliveryDate for the task is deprecated. Use the LEK_taskUserAssoc deadlineDate instead.');
    }

    /***
     * Add the task default if sort is not provided
     */
    protected function addDefaultSort()
    {
        $f = $this->entity->getFilter();
        $f->hasSort() || $f->addSort('orderdate', true);
    }

    /**
     * Handle the project/task load request.
     * @return boolean true if loading projects, or false if tasks only
     */
    protected function handleProjectRequest(): bool
    {
        $projectOnly = (bool) $this->getRequest()->getParam('projectsOnly', false);
        $filter = $this->entity->getFilter();
        $taskTypes = editor_Task_Type::getInstance();
        if ($filter->hasFilter('projectId') && ! $projectOnly) {
            //filter for all tasks in the project(return also the single task projects)
            $filter->addFilter((object) [
                'field' => 'taskType',
                'value' => $taskTypes->getProjectTypes(true),
                'type' => 'notInList',
                'comparison' => 'in',
            ]);

            return false;
        }

        if ($projectOnly) {
            $filterValues = $taskTypes->getProjectTypes();
        } else {
            $filterValues = $taskTypes->getNonInternalTaskTypes();
        }

        $filter->addFilter((object) [
            'field' => 'taskType',
            'value' => $filterValues,
            'type' => 'list',
            'comparison' => 'in',
        ]);

        return $projectOnly;
    }

    /***
     * Check if the given task/project can be deleted based on the task state. When project task is provided,
     * all project tasks will be checked
     * @throws ZfExtended_Models_Entity_Conflict
     */
    protected function checkStateDelete(editor_Models_Task $taskEntity, bool $forced)
    {
        // if it is not project, do regular check
        if ($taskEntity->isProject()) {
            $model = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $model editor_Models_Task */
            $tasks = $model->loadProjectTasks((int) $this->entity->getProjectId(), true);
            // if it is project, load all project tasks, and check the state for each one of them
            foreach ($tasks as $projectTask) {
                $model->init($projectTask);
                $this->checkStateDelete($model, $forced);
            }
        } else {
            //if task is erroneous then it is also deleteable, regardless of its locking state
            if (! $taskEntity->isImporting() && ! $taskEntity->isErroneous() && ! $forced) {
                $taskEntity->checkStateAllowsActions();
            }
        }
    }

    protected function handleCancelImport(): void
    {
        $isAllowedToCancel = $this->isAllowed(
            Rights::ID,
            Rights::EDITOR_CANCEL_IMPORT
        ) || $this->isAuthUserTaskPm(
            $this->entity->getPmGuid()
        );

        //if no state is set or user is not allowed to cancel, do nothing
        if (empty($this->data->state)) {
            return;
        }

        // if task is importing or in special export state and state is tried to be set to something other as error,
        // unset state and do nothing here
        if (($this->entity->isImporting() || $this->entity->isSpecialExportState())
            && ($this->data->state != $this->entity::STATE_ERROR || ! $isAllowedToCancel)) {
            unset($this->data->state);

            return;
        }

        //override the entity version check
        if (isset($this->data->entityVersion)) {
            unset($this->data->entityVersion);
        }

        $worker = new ZfExtended_Models_Worker();

        try {
            $worker->loadFirstOf(editor_Models_Import_Worker::class, $this->entity->getTaskGuid());
            $worker->setState($worker::STATE_DEFUNCT);
            $worker->save();
            $worker->defuncRemainingOfGroup();
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            //if no import worker found, nothing can be stopped
        }
        $this->entity->unlock();
        $this->log->info('E1011', 'Task import cancelled', [
            'task' => $this->entity,
        ]);
    }

    /**
     * init the internal used workflow
     * @param string $wfId workflow ID. optional, if omitted use the workflow of $this->entity
     */
    protected function initWorkflow(string $wfId = null): void
    {
        if (empty($wfId) && isset($this->entity)) {
            $wfId = $this->entity->getWorkflow();
        }

        try {
            $this->workflow = $this->workflowManager->getCached($wfId);
        } catch (Exception) {
            $this->workflow = $this->workflowManager->getCached('default');
        }
    }

    /**
     * Converts the ConfigurationException caused by wrong user input to ZfExtended_UnprocessableEntity exceptions
     * @throws editor_Models_Import_ConfigurationException
     * @throws ZfExtended_UnprocessableEntity
     */
    protected function handleConfigurationException(
        editor_Models_Import_ConfigurationException $e,
        editor_Models_Task $task
    ): void {
        $codeToFieldAndMessage = [
            'E1032' => ['sourceLang', 'Die übergebene Quellsprache "{language}" ist ungültig!'],
            'E1033' => ['targetLang', 'Die übergebene Zielsprache "{language}" ist ungültig!'],
            'E1034' => [
                'relaisLang',
                'Es wurde eine Relaissprache gesetzt, aber im Importpaket befinden sich keine Relaisdaten.',
            ],
            'E1039' => ['importUpload', 'Das importierte Paket beinhaltet kein gültiges "{review}" Verzeichnis.'],
            'E1040' => ['importUpload', 'Das importierte Paket beinhaltet keine Dateien im "{review}" Verzeichnis.'],
        ];
        $code = $e->getErrorCode();
        if (empty($codeToFieldAndMessage[$code])) {
            throw $e;
        }

        $log = ZfExtended_Factory::get('editor_Logger_Workflow', [$task]);
        // the config exceptions causing unprossable entity exceptions are logged on level info
        $log->exception($e, [
            'level' => ZfExtended_Logger::LEVEL_INFO,
        ]);

        throw ZfExtended_UnprocessableEntity::createResponseFromOtherException($e, [
            //fieldName => error message to field
            $codeToFieldAndMessage[$code][0] => $codeToFieldAndMessage[$code][1],
        ]);
    }

    /**
     * Converts the IntegrityConstraint Exceptions caused by wrong user input to
     * ZfExtended_UnprocessableEntity exceptions
     *
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_UnprocessableEntity
     * @throws ZfExtended_ErrorCodeException
     */
    protected function handleIntegrityConstraint(ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e)
    {
        //check if the error comes from the customer assoc or not
        if (! $e->isInMessage('REFERENCES `LEK_customer`')) {
            throw $e;
        }

        throw ZfExtended_UnprocessableEntity::createResponse('E1064', [
            'customerId' => 'Der referenzierte Kunde existiert nicht (mehr)',
        ], [], $e);
    }

    /***
     * @param editor_Models_Import_DataProvider_Exception $e
     * @return mixed
     * @throws ZfExtended_ErrorCodeException
     */
    protected function handleDataProviderException(editor_Models_Import_DataProvider_Exception $e)
    {
        //FIXME ZfExtended_Models_Entity_Conflict::addCodes(); is missing / ecode is duplicated!
        throw ZfExtended_Models_Entity_Conflict::createResponse('E1369', [
            'targetLang[]' => 'No work files found for one of the target languages.'
                . ' This happens when the user selects multiple target languages in the dropdown'
                . ' and then imports a bilingual file via drag and drop.',
        ], [], $e);
    }

    /**
     * Check if the current authenticated user can access the task. This method expect the entity to be loaded and
     * will throw exception if the current user has no rights to access the task at all.
     * @throws ZfExtended_Models_Entity_NoAccessException
     */
    public function isTaskAccessibleForCurrentUser(): bool
    {
        $hasRightForTask = $this->isAuthUserTaskPm($this->entity->getPmGuid());
        $tua = null;

        try {
            $tua = editor_Models_Loaders_Taskuserassoc::loadByTask(
                $this->authenticatedUser->getUserGuid(),
                $this->entity
            );
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            //do nothing here
        }

        // to access a task the user must either have the loadAllTasks right,
        // or must be the tasks PM, or must be associated to the task
        $isTaskAccessible = $this->isAllowed(
            Rights::ID,
            Rights::LOAD_ALL_TASKS
        ) || $hasRightForTask || ! is_null($tua);
        if (! $isTaskAccessible) {
            unset($this->view->rows);

            throw new ZfExtended_Models_Entity_NoAccessException();
        }

        return $hasRightForTask;
    }
}
