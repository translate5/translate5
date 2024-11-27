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

use editor_Models_Segment_AutoStates as AutoStates;
use MittagQI\Translate5\Acl\Rights;
use MittagQI\Translate5\Task\FileTypeSupport;
use MittagQI\ZfExtended\Session\SessionInternalUniqueId;

/**
 * Task Object Instance as needed in the application
 * @method string getId()
 * @method void setId(string|int $id)
 * @method string getTaskGuid()
 * @method void setTaskGuid(string $guid)
 * @method string getTaskNr()
 * @method void setTaskNr(string $nr)
 * @method string getForeignId()
 * @method void setForeignId(string $id)
 * @method string getTaskName()
 * @method void setTaskName(string $name)
 * @method string getForeignName()
 * @method void setForeignName(string $name)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method string getSourceLang()
 * @method void setSourceLang(string|int $id)
 * @method string getTargetLang()
 * @method void setTargetLang(string|int $id)
 * @method string getRelaisLang()
 * @method void setRelaisLang(string|int $id)
 * @method null|string getLockedInternalSessionUniqId()
 * @method void setLockedInternalSessionUniqId(?string $id)
 * @method null|string getLocked()
 * @method null|string getLockingUser()
 * @method void setLockingUser(string $guid)
 * @method string getPmGuid()
 * @method void setPmGuid(string $guid)
 * @method string getPmName()
 * @method void setPmName(string $guid)
 * @method string getState()
 * @method void setState(string $state)
 * @method string getWorkflow()
 * @method void setWorkflow(string $workflow)
 * @method string getWorkflowStep()
 * @method void setWorkflowStep(string|int $stepNr)
 * @method string getWorkflowStepName()
 * @method string getWordCount()
 * @method void setWordCount(string|int $wordcount)
 * @method null|string getOrderdate()
 * @method void setOrderdate(?string $datetime)
 * @method null|string getDeadlineDate()
 * @method void setDeadlineDate(?string $datetime)
 * @method null|string getEnddate()
 * @method void setEnddate(?string $datetime)
 * @method string getReferenceFiles()
 * @method void setReferenceFiles(string|int $flag)
 * @method string getEnableSourceEditing()
 * @method void setEnableSourceEditing(string|int $flag)
 * @method string getEdit100PercentMatch()
 * @method void setEdit100PercentMatch(string|int $flag)
 * @method string getLockLocked()
 * @method void setLockLocked(string|int $flag)
 * @method null|string getQmSubsegmentFlags() get Original Flags from DB
 * @method void setQmSubsegmentFlags(?string $flags) set Original Flags in DB
 * @method void delete() see editor_Models_Task_Remover for complete task removal
 * @method string getEmptyTargets()
 * @method void setEmptyTargets(bool $emptyTargets)
 * @method null|string getImportAppVersion()
 * @method void setImportAppVersion(?string $version)
 * @method null|string getCustomerId()
 * @method void setCustomerId(string|int|null $customerId)
 * @method string getUsageMode()
 * @method void setUsageMode(string $usageMode)
 * @method string|null getSegmentCount()
 * @method void setSegmentCount(string|int $segmentCount)
 * @method string getSegmentEditableCount()
 * @method void setSegmentEditableCount(string|int $segmentEditableCount)
 * @method string getSegmentFinishCount()
 * @method void setSegmentFinishCount(string|int $segmentFinishCount)
 * @method void setTaskType(string $taskType)
 * @method null|string getProjectId()
 * @method void setProjectId(string|int|null $projectId)
 * @method string getDiffExportUsable()
 * @method void setDiffExportUsable(string|int $flag)
 * @method string getReimportable()
 * @method void setReimportable(string|int $reimportable)
 * @method string getCreated()
 * @method string getModified()
 */
class editor_Models_Task extends ZfExtended_Models_Entity_Abstract
{
    public const STATE_OPEN = 'open';

    public const STATE_END = 'end';

    public const STATE_PREPARATION = 'preparation';

    public const STATE_POST_PROCESSING = 'postprocessing';

    public const STATE_IMPORT = 'import';

    public const STATE_PROJECT = 'project'; //seems to be used as import status for projects!

    public const STATE_ERROR = 'error';

    public const STATE_UNCONFIRMED = 'unconfirmed';

    public const STATE_EXCELEXPORTED = 'ExcelExported';

    public const STATE_PACKAGE_EXPORT = 'PackageExport';

    public const STATE_REIMPORT = 'reimport';

    public const USAGE_MODE_COMPETITIVE = 'competitive';

    public const USAGE_MODE_COOPERATIVE = 'cooperative';

    public const USAGE_MODE_SIMULTANEOUS = 'simultaneous';

    public const ASSOC_TABLE_ALIAS = 'LEK_taskUserAssoc';

    public const TABLE_ALIAS = 'LEK_task';

    public const INTERNAL_LOCK = '*translate5InternalLock*';

    /**
     * The directory inside a task's data dir where log's can be stored (e.g. logs from docker-services)
     */
    public const LOG_DIR = 'log';

    public const NON_EXCLUSIVE_STATES = [self::STATE_OPEN, self::STATE_END, self::STATE_UNCONFIRMED];

    /**
     * Currently only used for getConfig, should be used for all relevant customer stuff in this class
     */
    protected static $customerCache = [];

    protected $dbInstanceClass = 'editor_Models_Db_Task';

    protected $validatorInstanceClass = 'editor_Models_Validator_Task';

    /**
     * Tasks must be filtered by role-driven restrictions
     */
    protected ?array $clientAccessRestriction = [
        'field' => 'customerId',
    ];

    /**
     * @var editor_Models_Task_Meta
     */
    protected $meta;

    protected ?string $taskDataPath;

    /**
     * A Cache for the faulty segments the task holds
     * @var int[][]
     */
    protected $faultySegmentsCache = [];

    /**
     * A Cache for the evaluation of similar languages
     * @var editor_Models_Languages[]
     */
    protected $languageCache = [];

    /**
     * On cloning we need a new taskGuid and id
     * {@inheritDoc}
     * @see ZfExtended_Models_Entity_Abstract::__clone()
     */
    public function __clone()
    {
        $data = $this->row->toArray();
        unset($data['id']);
        unset($data['taskGuid']);
        //resetting meta is crucial here - we are cloning the task object not its subsequent data in DB too!
        $this->meta = null;
        //before all other operations make a new row object
        $this->init($data);
        $this->createTaskGuidIfNeeded();
    }

    /**
     * returns the task type instance (can be casted to string)
     */
    public function getTaskType(): editor_Task_Type_Abstract
    {
        return editor_Task_Type::getInstance()->getType($this->get('taskType'));
    }

    /***
     * Returns all task specific configs for the current task.
     * For all configs for which there is no task specific overwrite, overwrite for the task client will be used as a value.
     * For all configs for which there is no task customer specific overwrite, instance-level config value will be used
     *
     * @param bool $disableCache : disable the config cache. Load always fresh config from the db
     * @return Zend_Config
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     */
    public function getConfig(bool $disableCache = false)
    {
        $taskConfig = ZfExtended_Factory::get(editor_Models_TaskConfig::class);
        if ($disableCache) {
            $taskConfig->cleanConfigCache();
        }

        return $taskConfig->getTaskConfig($this->getTaskGuid());
    }

    /**
     * Access customer instances in a cached way
     *
     * @throws ReflectionException
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    protected function _getCachedCustomer(int $id): editor_Models_Customer_Customer
    {
        if (empty(self::$customerCache[$id])) {
            $customer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);
            $customer->load($id);
            self::$customerCache[$id] = $customer;
        }

        return self::$customerCache[$id];
    }

    /**
     * loads the task to the given guid
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function loadByTaskGuid(string $taskGuid): void
    {
        try {
            $s = $this->db->select()->where('taskGuid = ?', $taskGuid);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if (! $row) {
            $this->notFound(__CLASS__ . '#taskGuid', $taskGuid);
        }
        //load implies loading one Row, so use only the first row
        $this->row = $row;
    }

    public function init(array|Zend_Db_Table_Row_Abstract|null $data = null, $assumeDatabase = false): void
    {
        parent::init($data, $assumeDatabase);
        $this->taskDataPath = null;
    }

    /**
     * loads all Entities out of DB associated to the user (filtered by the TaskUserAssoc table)
     * if $loadAll is true, load all tasks, user infos joined only where possible,
     *   if false only the associated tasks
     * @param bool $loadAll optional, per default false
     * @return array
     */
    public function loadListByUserAssoc(string $userGuid, $loadAll = false)
    {
        return parent::loadFilterdCustom($this->getSelectByUserAssocSql($userGuid, '*', $loadAll));
    }

    /**
     * loads all tasks associated to a specific user as PM
     * @return array
     */
    public function loadListByPmGuid(string $pmGuid)
    {
        $s = $this->db->select();
        $s->where('pmGuid = ?', $pmGuid);

        return parent::loadFilterdCustom($s);
    }

    /**
     * loads all tasks of the given tasktype that are associated to a specific user as PM
     */
    public function loadListByPmGuidAndTasktype(string $pmGuid, string $tasktype): array
    {
        $s = $this->db->select();
        $s->where('pmGuid = ?', $pmGuid);
        $s->where('tasktype = ?', $tasktype);
        $s->order('orderdate DESC');

        return parent::loadFilterdCustom($s);
    }

    /**
     * loads all tasks of the given tasktype that shall be removed (because
     * their lifetime is over).
     * @return array
     */
    public function loadListForCleanupByTasktype(string $tasktype, int $orderDaysOffset)
    {
        $s = $this->db->select();
        $s->where('tasktype = ?', $tasktype);
        $s->where('`orderDate` < (CURRENT_DATE - INTERVAL ? DAY)', $orderDaysOffset);

        return parent::loadFilterdCustom($s);
    }

    /***
     * Load all task assoc users for non anonymized tasks.
     * This is used for the user workflow filter in the advance filter store.
     * INFO:Associated users for the anonimized tasks will not be loaded
     *
     * @param string $userGuid
     * @return array
     * @throws ReflectionException
     * @throws Zend_Acl_Exception
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Db_Table_Select_Exception
     */
    public function loadUserList(string $userGuid): array
    {
        $userModel = ZfExtended_Factory::get(ZfExtended_Models_User::class);

        // here no check for pmGuid, since this is done in task::loadListByUserAssoc
        $loadAll = ZfExtended_Authentication::getInstance()->isUserAllowed(Rights::ID, Rights::LOAD_ALL_TASKS);
        $ignoreAnonStuff = $this->rolesAllowReadAnonymizedUsers();

        $anonSql = '';
        if (! $ignoreAnonStuff) {
            //filter out all anonimized tasks
            //task is anonymized if runtimeOptions.customers.anonymizeUsers is set to 1 on task level
            //if anonymizeUsers is not defined on task level, the task customer anonymizeUsers value is used
            //if anonymizeUsers is also not defined on customer level, then the instance anonymizeUsers value is used
            $anonSql = 'AND filter.taskGuid NOT IN(SELECT IF((SELECT IF(t.value IS NOT NULL,t.value, if(c.value IS NOT NULL,c.value,z.value = 1)) FROM Zf_configuration z
                        LEFT JOIN LEK_customer_config c on z.name = c.name
                        LEFT JOIN LEK_task_config t on t.name = z.name
                        WHERE ((t.taskGuid = LEK_task.taskGuid) OR (c.customerId = LEK_task.customerId AND t.taskGuid IS NULL)) 
                        AND z.name =  "runtimeOptions.customers.anonymizeUsers") = 1,LEK_task.taskGuid,NULL) AS s
                        FROM LEK_task
                        GROUP BY LEK_task.taskGuid
                        HAVING s IS NOT NULL) ';
        }

        if ($loadAll) {
            $s = $this->db->select()->setIntegrityCheck(false);
        } else {
            $s = $this->getSelectByUserAssocSql($userGuid, '*', $loadAll);
        }
        //apply the frontend task filters
        $this->applyFilterAndSort($s);
        //the inner query is the current task list with activ filters
        $userCols = 'Zf_users.`' . join('`, Zf_users.`', $userModel->getPublicColumns()) . '`';
        $sql = ' SELECT ' . $userCols . ',filter.taskGuid from Zf_users, ' .
            ' (' . $s->assemble() . ') as filter ' .
             ' INNER JOIN LEK_taskUserAssoc ON LEK_taskUserAssoc.taskGuid=filter.taskGuid ' .
             ' WHERE Zf_users.userGuid = LEK_taskUserAssoc.userGuid ' .
             $anonSql .
             ' GROUP BY Zf_users.id ' .
             ' ORDER BY Zf_users.surName; ';

        $stmt = $this->db->getAdapter()->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * gets the total count of all tasks associated to the user (filtered by the TaskUserAssoc table)
     * if $loadAll is true, load all tasks, user infos joined only where possible,
     *   if false only the associated tasks
     * @param bool $loadAll
     * @return int
     */
    public function getTotalCountByUserAssoc(string $userGuid, $loadAll = false)
    {
        $s = $this->getSelectByUserAssocSql($userGuid, [
            'numrows' => 'count(*)',
        ], $loadAll);
        $this->applyFilterToSelect($s);

        return $this->db->fetchRow($s)->numrows;
    }

    /**
     * returns the SQL to retrieve the tasks of an user oder of all users joined with the users assoc infos
     * if $loadAll is true, load all tasks, user infos joined only where possible,
     *   if false only the associated tasks to the user
     * TODO: when state filter is refactored, remove the tasuserassoc object cast here
     * @param string $cols column definition
     * @param bool $loadAll
     * @return Zend_Db_Table_Select
     */
    protected function getSelectByUserAssocSql(string $userGuid, $cols = '*', $loadAll = false)
    {
        $s = $this->db->select()
            ->from('LEK_task', $cols);
        $defaultTable = $this->db->info($this->db::NAME);
        if (! empty($this->filter)) {
            $this->filter->setDefaultTable($defaultTable);
        }
        if ($loadAll) {
            $on = 'LEK_taskUserAssoc_1.taskGuid = LEK_task.taskGuid AND LEK_taskUserAssoc_1.userGuid = ' . $s->getAdapter()->quote($userGuid);
            $s->joinLeft([
                'LEK_taskUserAssoc_1' => 'LEK_taskUserAssoc',
            ], $on, []);
        } else {
            $s->joinLeft([
                'LEK_taskUserAssoc_1' => 'LEK_taskUserAssoc',
            ], 'LEK_taskUserAssoc_1.taskGuid = LEK_task.taskGuid', [])
                ->where('LEK_taskUserAssoc_1.userGuid = ? OR LEK_task.pmGuid = ?', $userGuid);
        }

        return $s;
    }

    /**
     * returns the Taskname ready to use in content-disposition filename http header
     * Does a simple concatination of prefix name and suffix
     * @param string $suffix filename suffix
     * @param string $prefix optional filename prefix
     * @return string
     */
    public function getTasknameForDownload(string $suffix, $prefix = '')
    {
        //see TS-2156 and https://stackoverflow.com/questions/1401317/remove-non-utf8-characters-from-string
        $name = preg_replace('/[^[:print:]\n]/u', '', iconv("UTF-8", "UTF-8//IGNORE", $this->getTaskName()));

        return rawurlencode(iconv('UTF-8', 'ASCII//TRANSLIT', $prefix . $name . $suffix));
    }

    /**
     * @param bool $asJson if true, json is returned, otherwhise assoc-array
     * @return mixed depending on $asJson
     */
    public function getMqmTypesTranslated($asJson = true)
    {
        // ugly defaults when no data set to avoid exceptions. Generally, no code should request this when MQMs are not configured
        if ($this->row->qmSubsegmentFlags == null) {
            if ($asJson) {
                return null;
            }

            return [];
        }
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */;
        $walk = function (array $qmFlagTree) use ($translate, &$walk) {
            foreach ($qmFlagTree as $node) {
                $node->text = $translate->_($node->text);
                if (isset($node->children) && is_array($node->children)) {
                    $walk($node->children, $walk);
                }
            }

            return $qmFlagTree;
        };
        $tree = Zend_Json::decode($this->row->qmSubsegmentFlags, Zend_Json::TYPE_OBJECT);
        if (! isset($tree->qmSubsegmentFlags)) {
            throw new Zend_Exception('qmSubsegmentFlags JSON Structure not OK, missing field qmSubsegmentFlags');
        }
        $qmFlagTree = $walk($tree->qmSubsegmentFlags);
        if ($asJson) {
            return Zend_Json::encode($qmFlagTree);
        }

        return $qmFlagTree;
    }

    /**
     * @return array('issueId'=>'issueText',...)
     */
    public function getMqmTypesFlat()
    {
        $flatTree = [];
        $walk = function (array $qmFlagTree) use (&$walk, &$flatTree) {
            foreach ($qmFlagTree as $node) {
                $flatTree[$node->id] = $node->text;
                if (isset($node->children) && is_array($node->children)) {
                    $walk($node->children);
                }
            }
        };
        $tree = Zend_Json::decode($this->row->qmSubsegmentFlags, Zend_Json::TYPE_OBJECT);
        if (! isset($tree->qmSubsegmentFlags)) {
            throw new Zend_Exception('qmSubsegmentFlags JSON Structure not OK, missing field qmSubsegmentFlags');
        }
        $walk($tree->qmSubsegmentFlags);

        return $flatTree;
    }

    /**
     * @return stdClass
     */
    public function getMqmSeverities()
    {
        $tree = Zend_Json::decode($this->row->qmSubsegmentFlags, Zend_Json::TYPE_OBJECT);
        if (! isset($tree->severities)) {
            throw new Zend_Exception('qmSubsegmentFlags JSON Structure not OK, missing field severities');
        }

        return $tree->severities;
    }

    /**
     * returns all configured Severities as JSON or PHP Data Structure:
     * [{
     *   id: 'sev1',
     *   text: 'Severity 1'
     * },{
     *   id: 'sev2',
     *   text: 'Severity 2'
     * }]
     * @param bool $asJson
     * @param Zend_Db_Table_Row_Abstract $row | null - if null, $this->row is used
     * @return string|array depends on $asJson
     */
    public function getMqmSeveritiesTranslated($asJson = true, array $row = null)
    {
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */
        if (is_null($row)) {
            $row = $this->row->toArray();
        }
        $tree = Zend_Json::decode($row['qmSubsegmentFlags'], Zend_Json::TYPE_OBJECT);
        $result = [];
        foreach ($tree->severities as $key => $label) {
            $result[] = (object) [
                'id' => $key,
                'text' => $translate->_($label),
            ];
        }
        if ($asJson) {
            return Zend_Json::encode($tree);
        }

        return $result;
    }

    /**
     * returns the relative path to the persistent data directory of the given or loaded taskguid
     * @param string $taskGuid optional, if not given use the taskguid of internal loaded task
     */
    public function getRelativeTaskDataPath($taskGuid = null): string
    {
        if (empty($taskGuid)) {
            $taskGuid = $this->getTaskGuid();
        }

        //use the TaskGuid as directory name, remove curly brackets "{" and "}"
        return trim($taskGuid, '{}');
    }

    /**
     * returns the absolute path to the task data directory
     */
    public function getAbsoluteTaskDataPath(): string
    {
        if (empty($this->taskDataPath)) {
            $taskDataRel = $this->getRelativeTaskDataPath();
            $config = Zend_Registry::get('config');
            $this->taskDataPath = $config->runtimeOptions->dir->taskData . DIRECTORY_SEPARATOR . $taskDataRel;
        }

        return $this->taskDataPath;
    }

    /**
     * The log dir for various task-specific logs, will be created if it does not exist
     */
    public function getAbsoluteTaskLogPath(bool $createIfNeccessary = true): string
    {
        $logDir = $this->getAbsoluteTaskDataPath() . DIRECTORY_SEPARATOR . self::LOG_DIR;
        if ($createIfNeccessary && ! is_dir($logDir)) {
            if (! mkdir($logDir, 0777, true) && ! is_dir($logDir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $logDir));
            }
        }

        return $logDir;
    }

    /**
     * creates the TaskData Directory, throws a Zend_Exception if not possible
     * @throws Zend_Exception
     * @return SplFileInfo InfoObject of the TaskData Directory
     */
    public function initTaskDataDirectory()
    {
        $taskDataRel = $this->getRelativeTaskDataPath();
        $config = Zend_Registry::get('config');
        if (empty($config->runtimeOptions->dir->taskData)) {
            throw new Zend_Exception('Config runtimeOptions.dir.taskData is NOT set!');
        }
        $taskDataRoot = new SplFileInfo($config->runtimeOptions->dir->taskData);
        if (! $taskDataRoot->isDir() && ! mkdir($taskDataRoot)) {
            throw new Zend_Exception('TaskData root Directory could not be created: "' . $taskDataRoot->getPathname() . '".');
        }
        if (! $taskDataRoot->isWritable()) {
            throw new Zend_Exception('TaskData root Directory is not writeable: "' . $taskDataRoot->getPathname() . '".');
        }

        $taskData = new SplFileInfo($taskDataRoot . DIRECTORY_SEPARATOR . $taskDataRel);
        if (! $taskData->isDir() && ! mkdir($taskData)) {
            throw new Zend_Exception('TaskData Directory could not be created, check parent folders:  "' . $taskData->getPathname() . '".');
        }

        if ($taskData->isWritable()) {
            return $this->taskDataPath = $taskData;
        }

        throw new Zend_Exception('TaskData Directory is not writeable:  "' . $taskData->getPathname() . '".');
    }

    /**
     * creates (if needed) the materialized view to the task
     */
    public function createMaterializedView()
    {
        $mv = ZfExtended_Factory::get('editor_Models_Segment_MaterializedView', [$this->getTaskGuid()]);
        /* @var $mv editor_Models_Segment_MaterializedView */
        $mv->create();
    }

    /**
     * drops the materialized view to the task
     */
    public function dropMaterializedView()
    {
        $mv = ZfExtended_Factory::get('editor_Models_Segment_MaterializedView', [$this->getTaskGuid()]);
        /* @var $mv editor_Models_Segment_MaterializedView */
        $mv->drop();
    }

    /**
     * update the workflowStep of a specific task
     * @param bool $increaseStep optional, by default true, increases then the workflow step nr
     */
    public function updateWorkflowStep(string $stepName, $increaseStep = true)
    {
        $data = [
            'workflowStepName' => $stepName,
        ];
        if ($increaseStep) {
            $data['workflowStep'] = new Zend_Db_Expr('`workflowStep` + 1');
            //step nr is not updated in task entity! For correct value we have to reload the task and load the value form DB.
        }
        $this->__call('setWorkflowStepName', [$stepName]);
        $this->db->update($data, [
            'taskGuid = ?' => $this->getTaskGuid(),
        ]);
        ZfExtended_Factory
            ::get(editor_Models_TaskProgress::class)
                ->updateSegmentFinishCount($this);
    }

    /**
     * This method may not be called directly!
     * Either call editor_Models_Task::updateWorkflowStep
     * or if you are in Workflow Context call editor_Workflow_Default::setNextStep
     * @param string $stepName
     * @throws BadMethodCallException
     */
    public function setWorkflowStepName($stepName)
    {
        throw new BadMethodCallException('setWorkflowStepName may not be called directly. Either via Task::updateWorkflowStep or in Workflow Context via Workflow::setNextStep');
    }

    /**
     * Convenience API
     */
    public function isTranslation(): bool
    {
        return (int) $this->getEmptyTargets() === 1;
    }

    /**
     * Convenience API
     */
    public function isReview(): bool
    {
        return (int) $this->getEmptyTargets() !== 1;
    }

    /**
     * Retrieves, if the full source and target language are equal, eg. en-GB === en-GB
     */
    public function isSourceAndTargetLanguageEqual(): bool
    {
        return ($this->getSourceLang() === $this->getTargetLang());
    }

    /**
     * Retrieves, if the rfc5646 source and target language are equal, eg. en === en
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function isSourceAndTargetLanguageSimilar(): bool
    {
        return ($this->isSourceAndTargetLanguageEqual()
            || ($this->getSourceLanguage()->getMajorRfc5646() == $this->getTargetLanguage()->getMajorRfc5646()));
    }

    /**
     * Retrieves a cached language
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    protected function getCachedLanguage(int $id): editor_Models_Languages
    {
        if (array_key_exists($id, $this->languageCache)) {
            return $this->languageCache[$id];
        }
        $this->languageCache[$id] = ZfExtended_Factory::get(editor_Models_Languages::class);
        $this->languageCache[$id]->load($id);

        return $this->languageCache[$id];
    }

    /**
     * Retrieves the source lang as language-object
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getSourceLanguage(): editor_Models_Languages
    {
        return $this->getCachedLanguage((int) $this->getSourceLang());
    }

    /**
     * Retrieves the target lang as language-object
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getTargetLanguage(): editor_Models_Languages
    {
        return $this->getCachedLanguage((int) $this->getTargetLang());
    }

    /**
     * unlocks all tasks, where the associated session is invalid
     */
    public function cleanupLockedJobs()
    {
        $validSessionIds = ZfExtended_Models_Db_Session::GET_VALID_SESSIONS_SQL;
        //the below not like "*translate5InternalLock*%" gives us the possibility to define session independant locks:
        $where = 'not locked is null and (
            lockedInternalSessionUniqId not in (' . $validSessionIds . ')
            and lockedInternalSessionUniqId not like "*translate5InternalLock*%"
            or lockedInternalSessionUniqId is null)';
        $this->db->update([
            'lockingUser' => null,
            'locked' => null,
            'lockedInternalSessionUniqId' => null,
        ], $where);

        //clean up remaining multi user task locks where no user is editing anymore
        $multiUserId = self::INTERNAL_LOCK . self::USAGE_MODE_SIMULTANEOUS;
        $usedMultiUserLocks = 'SELECT t.id
        FROM (SELECT id, taskGuid FROM LEK_task t WHERE t.lockedInternalSessionUniqId = "' . $multiUserId . '") t
        JOIN LEK_taskUserAssoc tua on tua.taskGuid = t.taskGuid
        WHERE not tua.usedState is null AND not tua.usedInternalSessionUniqId is null';
        $where = 'not locked is null and lockedInternalSessionUniqId = "' . $multiUserId . '" and id not in (' . $usedMultiUserLocks . ')';

        $this->db->update([
            'lockingUser' => null,
            'locked' => null,
            'lockedInternalSessionUniqId' => null,
        ], $where);
    }

    /**
     * locks the task
     * sets a locked-timestamp in LEK_task for the task, if locked column is null
     *
     * @param string $lockId String to distinguish different lock types
     * @return boolean
     */
    public function lock(string $datetime, string $lockId = ''): bool
    {
        return $this->_lock($datetime, ZfExtended_Models_User::SYSTEM_GUID, self::INTERNAL_LOCK . $lockId);
    }

    /**
     * locks the task
     * sets a locked-timestamp in LEK_task for the task, if locked column is null
     *
     * @return boolean
     */
    public function lockForSessionUser(string $datetime): bool
    {
        return $this->_lock(
            $datetime,
            ZfExtended_Authentication::getInstance()->getUserGuid(),
            SessionInternalUniqueId::getInstance()->get()
        );
    }

    /**
     * locks the task
     * sets a locked-timestamp in LEK_task for the task, if locked column is null
     *
     * @return boolean
     */
    protected function _lock(string $datetime, string $userGuid, string $sessionId): bool
    {
        $update = [
            'locked' => $datetime,
            'lockingUser' => $userGuid,
            'lockedInternalSessionUniqId' => $sessionId,
        ];
        $where = [
            'taskGuid = ? and locked is null' => $this->getTaskGuid(),
        ];
        $rowsUpdated = $this->db->update($update, $where);
        if ($rowsUpdated === 0) {
            //true if no system lock, if system lock evaluate if sessionId (and therefore the type) equals
            $checkSystemLockType = strpos($sessionId, self::INTERNAL_LOCK) === false || $sessionId === $this->getLockedInternalSessionUniqId();

            //already locked by the same user with the same system lock type (if applicable).
            return ! is_null($this->getLocked()) && $this->getLockingUser() == $userGuid && $checkSystemLockType;
        }

        return true;
    }

    /**
     * unlocks the task, does not check user or multi user state!
     * @return boolean false if task had not been locked or does not exist,
     *          true if task has been unlocked successfully
     */
    public function unlock()
    {
        $where = [
            'taskGuid = ? and locked is not null' => $this->getTaskGuid(),
        ];
        $data = [
            'locked' => null,
            'lockedInternalSessionUniqId' => null,
            'lockingUser' => null,
        ];
        $success = $this->db->update($data, $where) !== 0;
        //check how many rows are updated
        $this->events->trigger('unlock', $this, [
            'task' => $this,
            'success' => $success,
        ]);

        return $success;
    }

    /**
     * unlocks the task, for a specific user. Checks if user is allowed to unlock (lockingUser = givenUser) and respects multiuser editing
     * @param string|null $taskGuid optional, use the internally loaded taskGuid by default
     * @return boolean false if task had not been locked or does not exist,
     *          true if task has been unlocked successfully
     */
    public function unlockForUser(string $userGuid, string $taskGuid = null): bool
    {
        $taskGuid = $this->db->getAdapter()->quote($taskGuid ?? $this->getTaskGuid());
        $userGuid = $this->db->getAdapter()->quote($userGuid);
        $multiUserId = $this->db->getAdapter()->quote(self::INTERNAL_LOCK . self::USAGE_MODE_SIMULTANEOUS);

        $where = 'taskGuid = %1$s
        AND locked is not null
        AND (lockingUser = %2$s
            OR lockedInternalSessionUniqId = %3$s
            AND taskGuid NOT IN (SELECT taskGuid
                FROM LEK_taskUserAssoc
                WHERE taskGuid = %1$s
                AND userGuid != %2$s
                AND not usedState is null AND not usedInternalSessionUniqId is null)
            )';

        $data = [
            'locked' => null,
            'lockedInternalSessionUniqId' => null,
            'lockingUser' => null,
        ];

        //check how many rows are updated
        return $this->db->update($data, sprintf($where, $taskGuid, $userGuid, $multiUserId)) !== 0;
    }

    /**
     * marks the task erroneous and unlocks its
     * @return boolean false if task had not been updated or does not exist,
     */
    public function setErroneous()
    {
        $data = [
            'state' => self::STATE_ERROR,
            'locked' => null,
            'lockedInternalSessionUniqId' => null,
            'lockingUser' => null,
        ];
        $where = [
            'taskGuid = ?' => $this->getTaskGuid(),
        ];
        $success = $this->db->update($data, $where) !== 0;
        //check how many rows are updated
        //since the task is also unlocked here, we have to fire the according event too!
        $this->events->trigger('unlock', $this, [
            'task' => $this,
            'success' => $success,
        ]);

        return $success;
    }

    /**
     * returns if tasks has import errors
     * @return boolean
     */
    public function isErroneous()
    {
        return $this->getState() == self::STATE_ERROR;
    }

    /**
     * returns if tasks is importing
     * @return boolean
     */
    public function isImporting()
    {
        return in_array($this->getState(), [self::STATE_IMPORT, self::STATE_PREPARATION]);
    }

    /**
     * Is the task in special exporting state. When the task in this kind of state is, some actions will not be allowed.
     * ex: task editing is not allowed
     */
    public function isSpecialExportState(): bool
    {
        return in_array($this->getState(), [self::STATE_EXCELEXPORTED, self::STATE_PACKAGE_EXPORT]);
    }

    /**
     * checks if the given taskGuid is locked. If optional userGuid is given,
     * checks if is locked by given userGuid.
     * @return false|datetime returns false if not locked, lock timestamp if locked
     */
    public function isLocked(string $taskGuid, string $userGuid = null)
    {
        $s = $this->db->select()->where('taskGuid = ?', $taskGuid);
        if (! empty($userGuid)) {
            $s->where('lockingUser = ?', $userGuid);
        }
        $row = $this->db->fetchRow($s);
        if (empty($row) || empty($row['locked'])) {
            return false;
        }

        return $row['locked'];
    }

    /**
     * checks if the given taskGuid is used by any user
     */
    public function isUsed(string $taskGuid)
    {
        /* @var $tua editor_Models_TaskUserAssoc */
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        $used = $tua->loadUsed($taskGuid);

        return ! empty($used);
    }

    /**
     * returns true if current task is in an exclusive state (like import)
     * @param array $additionalNonExclusive additional states to be handled non-exclusive
     */
    public function isExclusiveState(array $additionalNonExclusive = []): bool
    {
        return ! in_array($this->getState(), array_merge(self::NON_EXCLUSIVE_STATES, $additionalNonExclusive));
    }

    /**
     * Retrieves if the optional deadline-date is set
     * and the deadline is valid (= AFTER the creation date)
     */
    public function hasValidDeadlineDate(): bool
    {
        return ! empty($this->getDeadlineDate())
            && strtotime($this->getDeadlineDate()) >= strtotime($this->getCreated());
    }

    /**
     * Retrieves the file-type-support for a task.
     * Can only be used for saved tasks
     * @throws ZfExtended_Exception
     */
    public function getFileTypeSupport(): FileTypeSupport
    {
        return FileTypeSupport::taskInstance($this);
    }

    /**
     * creates a random task guid if no one is set
     */
    public function createTaskGuidIfNeeded()
    {
        $taskguid = $this->getTaskGuid();
        if (! empty($taskguid)) {
            return;
        }
        $this->setTaskGuid(ZfExtended_Utils::guid(true));
    }

    /**
     * returns true if current task is a real project and is never treated as importable task, that means:
     *   - a project can not be imported directly, only its sub tasks may be processed
     *   - a project can not be opened / edited, it is only an abstract construction to contain the sub tasks
     */
    public function isProject(): bool
    {
        return $this->getTaskType()->isProject() && ! $this->getTaskType()->isTask();
    }

    /**
     * generates a statistics summary to the given task
     * @return stdClass
     */
    public function getStatistics()
    {
        $result = new stdClass();
        $result->taskGuid = $this->getTaskGuid();
        $result->taskName = $this->getTaskName();
        $result->wordCount = $this->getWordCount();
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $result->segmentCount = $segment->count($this->getTaskGuid());
        $result->segmentCountEditable = $segment->count($this->getTaskGuid(), true);

        return $result;
    }

    /**
     * generates a task overview statistics summary
     * @return array
     */
    public function getSummary()
    {
        $stmt = $this->db->getAdapter()->query('select taskType, state, count(*) taskCount, sum(wordCount) wordCountSum, sum(segmentCount) segmentCountSum from LEK_task group by taskType, state');

        return $stmt->fetchAll();
    }

    /**
     * convenient method to get the task meta data
     * @param bool $reinit if true reinits the internal meta object completely (after adding a field for example)
     * @return editor_Models_Task_Meta
     */
    public function meta(bool $reinit = false)
    {
        $meta = $this->meta ?? $this->meta = ZfExtended_Factory::get('editor_Models_Task_Meta');
        $taskGuid = $this->getTaskGuid();
        if ($meta->getTaskGuid() != $taskGuid || $reinit) {
            try {
                $meta->loadByTaskGuid($taskGuid);
            } catch (ZfExtended_Models_Entity_NotFoundException) {
                $meta->init([
                    'taskGuid' => $taskGuid,
                ]);
            }
        }

        return $meta;
    }

    /**
     * Check if the current task status allows this action
     * @param array $allow additional states to be handled non exclusive
     * @throws ZfExtended_Models_Entity_Conflict|Zend_Exception
     */
    public function checkStateAllowsActions(array $allow = []): void
    {
        if ($this->isErroneous() || ($this->isExclusiveState($allow) && $this->isLocked($this->getTaskGuid()))) {
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1046' => 'The current task status does not allow that action.',
            ]);

            throw ZfExtended_Models_Entity_Conflict::createResponse('E1046', [
                'Der aktuelle Status der Aufgabe verbietet diese Aktion!',
            ], [
                'task' => $this,
                'isLocked' => $this->isLocked($this->getTaskGuid()),
                'isErroneous' => $this->isErroneous(),
                'isExclusiveState' => $this->isExclusiveState(),
            ]);
        }
    }

    /**
     * Check and throw exception if the task export is not allowed.
     * It is not allowed when the task is in not allowed state or if task export is already running
     * @throws ZfExtended_Models_Entity_Conflict
     * @throws Zend_Exception
     */
    public function checkExportAllowed(string $exportClass): void
    {
        // first check if disabled by state
        $this->checkStateAllowsActions();

        $model = new ZfExtended_Models_Worker();
        // check if there are running exports
        if ($model->isExportRunning($this->getTaskGuid(), $exportClass)) {
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1538' => 'Task export: the task already contains running or pending exports. Try again later',
            ]);

            throw ZfExtended_Models_Entity_Conflict::createResponse('E1538', [
                'Task export: the task already contains running or pending exports. Try again later',
            ], [
                'task' => $this,
            ]);
        }
    }

    /**
     * returns a logger instance with the given domain and the current task as default extra data
     * @throws Zend_Exception
     */
    public function logger(string $domain = 'editor.task'): ZfExtended_Logger
    {
        return Zend_Registry::get('logger')->cloneMe($domain, [
            'task' => $this,
        ]);
    }

    /***
     * Search task by given search string.
     * The search will provide any match on taskName field.
     *
     * @param string $searchString
     * @return array|array
     */
    public function search($searchString, $fields = [])
    {
        $s = $this->db->select();
        if (! empty($fields)) {
            $s->from($this->tableName, $fields);
        }
        $s->where('lower(taskName) LIKE lower(?)', '%' . $searchString . '%');

        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * Update the terminology flag based on if there is a term collection assigned as language resource to the task.
     * @param array $ignoreAssocs : the provided languageresources taskassoc ids will be ignored
     */
    public function updateIsTerminologieFlag(string $taskGuid, array $ignoreAssocs = []): void
    {
        $service = ZfExtended_Factory::get(editor_Services_TermCollection_Service::class);
        $assoc = ZfExtended_Factory::get(MittagQI\Translate5\LanguageResource\TaskAssociation::class);
        $result = $assoc->loadAssocByServiceName($taskGuid, $service->getName(), $ignoreAssocs);
        $hasTerminology = ! empty($result);
        //update DB directly
        $this->db->update([
            'terminologie' => $hasTerminology,
        ], [
            'taskGuid = ?' => $taskGuid,
        ]);

        //if current instance holds that task, update that too
        if ($this->getTaskGuid() === $taskGuid) {
            $this->setTerminologie($hasTerminology);
        }
    }

    /**
     * Overwrite getTerminologie: Return the DB value or false depending on the taskType.
     * @return boolean
     */
    public function getTerminologie()
    {
        if ($this->getTaskType()->isTerminologyDisabled()) {
            // For some task types, terms don't need to be tagged (= no TermTagger needed).
            return false;
        }

        return parent::get('terminologie');
    }

    /**
     * Overwrite setTerminologie: saves false instead of the given value depending on the taskType.
     * @param bool $flag
     */
    public function setTerminologie($flag)
    {
        if ($this->getTaskType()->isTerminologyDisabled()) {
            // For some task types, terms don't need to be tagged (= no TermTagger needed).
            $flag = false;
        }
        parent::set('terminologie', $flag);
    }

    /**
     * overwrites task save to update enddate if needed
     * @return mixed
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function save()
    {
        //automatically set enddate field
        $state = $this->getState();
        $oldState = $this->getOldValue('state');
        if ($state === self::STATE_END && $state != $oldState) {
            $this->setEnddate(NOW_ISO);
        } elseif ($oldState == self::STATE_END && $state != self::STATE_END) {
            $this->setEnddate(null); //old value was ended and task is set to open again
        }

        return parent::save();
    }

    /**
     * Return all combinations of font-family and font-size that are used in the task.
     * @return array
     */
    public function getAllFontsInTask()
    {
        // TODO: Get these infos from the config-data of the taskTemplate (unfortunately not implemented yet).
        // Workaround (!!!!): check the task's segments.
        $segMeta = ZfExtended_Factory::get('editor_Models_Segment_Meta');

        /* @var $segMeta editor_Models_Segment_Meta  */
        return $segMeta->getAllFontsInTask($this->getTaskGuid());
        /*
         [0] => Array
             (
                 [font] => Arial
                 [fontSize] => 12
             )

         [1] => Array
             (
                 [font] => Arial
                 [fontSize] => 14
         )

         [2] => Array
             (
                 [font] => Verdana
                 [fontSize] => 14
         )
         */
    }

    /**
     * Are the usernames for the task to be anonymized?
     * No personal information about other workflow users is visible in the workflow,
     * (1) if anonymizeUsers is checked (set to true)
     * (2) if the currently logged in user does not have the role admin, PM or api.
     * If the $checkUser-param is set to "false", the user-check is omitted (= only the
     * task's anonymizeUsers-config is taken into account).
     * @param bool $checkUser optional, set to false to check only the config (and do not consider the ACLs behind the currently logged in user or the roles given in $customRoles)
     * @param bool $customRoles (optional) if checkUser = true the here provided roles are used for ACL check instead the currently logged in user
     * @return boolean
     */
    public function anonymizeUsers(bool $checkUser = true, array $customRoles = null)
    {
        $config = $this->getConfig();
        if (! $config->runtimeOptions->customers->anonymizeUsers) {
            return false;
        }
        if ($checkUser === false) {
            return $config->runtimeOptions->customers->anonymizeUsers; // = true if we get here
        }

        return ! ($this->rolesAllowReadAnonymizedUsers($customRoles));
    }

    /**
     * returns true if the given roles (or the roles of the current user) disallow seeing all user data
     * @throws Zend_Acl_Exception
     */
    protected function rolesAllowReadAnonymizedUsers(array $rolesToCheck = null): bool
    {
        if (empty($rolesToCheck)) {
            $rolesToCheck = ZfExtended_Authentication::getInstance()->getUserRoles();
        }
        $aclInstance = ZfExtended_Acl::getInstance();

        return $aclInstance->isInAllowedRoles($rolesToCheck, Rights::ID, Rights::READ_ANONYMYZED_USERS);
    }

    /**
     * Get info to be further used to count finished segments per current task and per each user associated with that task
     */
    public function getWorkflowEndedOrFinishedAutoStates(): ?array
    {
        // Get workflow, and return if got nothing
        $workflow = $this->getTaskActiveWorkflow();
        if (empty($workflow)) {
            return null;
        }

        // Get states
        $states = $this->getTaskRoleAutoStates() ?: [];

        // Check if workflow is ended
        $isWorkflowEnded = $workflow->isEnded($this);

        // If workflow is not ended, and we do not have any states to the current steps' role, we do not update anything
        if (! $isWorkflowEnded && ! $states) {
            return null;
        }

        // Return $isWorkflowEnded flag and finished autoStates array
        return [$isWorkflowEnded, $states];
    }

    /***
     * FIXME move this function into a workflow scope
     * Get all autostate ids for the active tasks workflow
     *
     * @return string[]|bool
     */
    public function getTaskRoleAutoStates()
    {
        try {
            $workflow = $this->getTaskActiveWorkflow();
        } catch (ZfExtended_Exception $e) {
            //the workflow with $workflowStepName does not exist
            return false;
        }
        $roleOfStep = $workflow->getRoleOfStep($this->getWorkflowStepName());
        if (empty($roleOfStep)) {
            return false;
        }
        $autoState = new editor_Models_Segment_AutoStates();
        $stateMap = $autoState->getRoleToStateMap();

        return $stateMap[$roleOfStep] ?? false;
    }

    /**
     * Get the active workflow for the current task
     */
    public function getTaskActiveWorkflow(): editor_Workflow_Default
    {
        //get the current task active workflow
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');

        /* @var $wfm editor_Workflow_Manager */
        return $wfm->getActiveByTask($this);
    }

    /***
     * Load all tasks of a given project. If taskOnly is true, in the result array, the master(project) task
     * will not be included
     *
     * @param int $projectId
     * @param bool $tasksOnly
     * @return array
     */
    public function loadProjectTasks(int $projectId, bool $tasksOnly = false): array
    {
        $s = $this->db->select();
        if ($tasksOnly) {
            $s->where('taskType IN (?)', editor_Task_Type::getInstance()->getNonInternalTaskTypes());
        }
        $s->where('projectId=?', $projectId);

        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * Returns the matching of col-names as set in Editor.view.admin.TaskGrid.
     * @return array
     */
    public static function getTaskGridTextCols()
    {
        return [
            // A-Z
            'customerId' => 'Endkunde',
            'edit100PercentMatch' => '100%-Treffer editierbar',
            'emptyTargets' => 'Übersetzungsaufgabe (kein Review)',
            'enableSourceEditing' => 'Quellsprache bearbeitbar',
            'fileCount' => 'Dateien',
            'fullMatchEdit' => 'Unveränderte 100% TM Matches sind editierbar',
            'lockLocked' => 'Nur für SDLXLIFF Dateien: In importierter Datei explizit gesperrte Segmente sind in translate5 ebenfalls gesperrt',
            'orderdate' => 'Bestelldatum',
            'enddate' => 'Enddatum',
            'pmGuid' => 'Projektmanager',
            'pmName' => 'Projektmanager',
            'referenceFiles' => 'Referenzdateien',
            'relaisLang' => 'Relaissprache',
            'sourceLang' => 'Quellsprache',
            'state' => 'Status',
            'targetLang' => 'Zielsprache',
            'taskActions' => 'Aktionen',
            'taskassocs' => 'Anzahl zugewiesene Sprachressourcen',
            'taskName' => 'Aufgabenname',
            'taskNr' => 'Auftragsnr.',
            'terminologie' => 'Terminologie',
            'users' => 'Benutzer',
            'wordCount' => 'Wörter',
            'wordCountTT' => 'Anzahl Wörter',
            'workflow' => 'Workflow',
            'userCount' => 'Zahl zugewiesener Benutzer',
        ];
    }

    /**
     * Updates the usercount of a task.
     * @param string|null $taskGuid Optional, if omitted operate on current task
     */
    public function updateTask(string $taskGuid = null)
    {
        $sql = 'update `LEK_task` t, (select count(*) cnt, ? taskGuid from `LEK_taskUserAssoc` where taskGuid = ? and isPmOverride = 0) tua
            set t.userCount = tua.cnt where t.taskGuid = tua.taskGuid';
        $db = $this->db->getAdapter();
        $sql = $db->quoteInto($sql, $taskGuid ?? $this->getTaskGuid(), 'string', 2);
        $db->query($sql);
    }

    /**
     * Retrieves the ids of all faulty segments of this task (=segments with internal tag errors)
     * The result is cached so don't use this API in the context of e.g. an import
     * @return int[]
     */
    public function getFaultySegmentIds(): array
    {
        if (! array_key_exists($this->getId(), $this->faultySegmentsCache)) {
            $this->faultySegmentsCache[$this->getId()] = editor_Models_Db_SegmentQuality::getFaultySegmentIds($this->getTaskGuid());
        }

        return $this->faultySegmentsCache[$this->getId()];
    }

    /***
     * Return all active(not ended) reimportable tasks which are not ended
     * @return array
     */
    public function getAllReimportable(): array
    {
        $s = $this->db->select()
            ->where('reimportable = 1')
            ->where('state NOT IN(?)', [self::STATE_END, self::STATE_ERROR])
            ->where('taskType IN (?)', editor_Task_Type::getInstance()->getNonInternalTaskTypes());

        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * Check whether task is going to use 3rd party termtagging
     */
    public function hasThirdPartyTermTagging(): bool
    {
        return ! ! json_decode($this->getForeignId())?->glossaryClientId;
    }

    /**
     *  Check and set the default pivot langauge based on customer specific config.
     *  If the pivot field is not provided on task post and for the current task customer
     *  there is configured defaultPivotLanguage, the configured pivot language will be set as task pivot
     *
     * @throws Zend_Exception
     */
    public function setDefaultPivotLanguage(
        editor_Models_Task $project,
        ?editor_Models_Customer_Customer $customer = null,
    ): void {
        $config = null === $customer
            ? Zend_Registry::get('config')
            : $customer->getConfig();

        if (! empty($config->runtimeOptions->project->defaultPivotLanguage)) {
            // get default pivot language value from the config
            $defaultPivot = $config->runtimeOptions->project->defaultPivotLanguage;

            try {
                $language = ZfExtended_Factory::get(editor_Models_Languages::class);
                $language->loadByRfc5646($defaultPivot);

                $project->setRelaisLang((int) $language->getId());
            } catch (Throwable) {
                // in case of wrong configured variable and the load language fails, do nothing
            }
        }
    }
}
