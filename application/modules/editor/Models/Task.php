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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Task Object Instance as needed in the application
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method string getTaskNr() getTaskNr()
 * @method void setTaskNr() setTaskNr(string $nr)
 * @method string getForeignId() getForeignId()
 * @method void setForeignId() setForeignId(string $id)
 * @method string getTaskName() getTaskName()
 * @method void setTaskName() setTaskName(string $name)
 * @method string getForeignName() getForeignName()
 * @method void setForeignName() setForeignName(string $name)
 * @method integer getSourceLang() getSourceLang()
 * @method void setSourceLang() setSourceLang(int $id)
 * @method integer getTargetLang() getTargetLang()
 * @method void setTargetLang() setTargetLang(int $id)
 * @method integer getRelaisLang() getRelaisLang()
 * @method void setRelaisLang() setRelaisLang(int $id)
 * @method string getLockedInternalSessionUniqId() getLockedInternalSessionUniqId()
 * @method void setLockedInternalSessionUniqId() setLockedInternalSessionUniqId(string $id)
 * @method string getLocked() getLocked()
 * @method string getLockingUser() getLockingUser()
 * @method void setLockingUser() setLockingUser(string $guid)
 * @method string getPmGuid() getPmGuid()
 * @method void setPmGuid() setPmGuid(string $guid)
 * @method string getPmName() getPmName()
 * @method void setPmName() setPmName(string $guid)
 * @method string getState() getState()
 * @method void setState() setState(string $state)
 * @method string getWorkflow() getWorkflow()
 * @method void setWorkflow() setWorkflow(string $workflow)
 * @method integer getWorkflowStep() getWorkflowStep()
 * @method void setWorkflowStep() setWorkflowStep(int $stepNr)
 * @method string getWorkflowStepName() getWorkflowStepName()
 * @method void setWorkflowStepName() setWorkflowStepName(string $stepName)
 * @method integer getWordCount() getWordCount()
 * @method void setWordCount() setWordCount(int $wordcount)
 * @method string getOrderdate() getOrderdate()
 * @method void setOrderdate() setOrderdate(string $datetime)
 * @method boolean getReferenceFiles() getReferenceFiles()
 * @method void setReferenceFiles() setReferenceFiles(bool $flag)
 * @method boolean getTerminologie() getTerminologie()
 * @method void setTerminologie() setTerminologie(bool $flag)
 * @method boolean getEnableSourceEditing() getEnableSourceEditing()
 * @method void setEnableSourceEditing() setEnableSourceEditing(bool $flag)
 * @method boolean getEdit100PercentMatch() getEdit100PercentMatch()
 * @method void setEdit100PercentMatch() setEdit100PercentMatch(bool $flag)
 * @method boolean getLockLocked() getLockLocked()
 * @method void setLockLocked() setLockLocked(bool $flag)
 * @method string getQmSubsegmentFlags() getQmSubsegmentFlags() get Original Flags from DB
 * @method void setQmSubsegmentFlags() setQmSubsegmentFlags(string $flags) set Original Flags in DB
 * @method void delete() delete() see editor_Models_Task_Remover for complete task removal
 * @method boolean getEmptyTargets() getEmptyTargets()
 * @method void setEmptyTargets() setEmptyTargets(bool $emptyTargets)
 * @method string getImportAppVersion() getImportAppVersion()
 * @method void setImportAppVersion() setImportAppVersion(string $version)
 * @method integer getCustomerId() getCustomerId()
 * @method void setCustomerId() setCustomerId(int $customerId)
 * @method string getUsageMode() getUsageMode()
 * @method void setUsageMode() setUsageMode(string $usageMode)
 * @method int getSegmentCount() getSegmentCount()
 * @method void setSegmentCount() setSegmentCount(int $segmentCount)
 * @method integer getSegmentFinishCount() getSegmentFinishCount()
 * @method void setSegmentFinishCount() setSegmentFinishCount(int $segmentFinishCount)
 * @method string getTaskType() getTaskType()
 * @method void setTaskType() setTaskType(string $taskType)
 * @method int getProjectId() getProjectId()
 * @method void setProjectId() setProjectId(int $projectId)
 * @method boolean getDiffExportUsable() getDiffExportUsable()
 * @method void setDiffExportUsable() setDiffExportUsable(bool $flag)
 * 
 */
class editor_Models_Task extends ZfExtended_Models_Entity_Abstract {
    const STATE_OPEN = 'open';
    const STATE_END = 'end';
    const STATE_IMPORT = 'import';
    const STATE_ERROR = 'error';
    const STATE_UNCONFIRMED = 'unconfirmed';

    const USAGE_MODE_COMPETITIVE = 'competitive';
    const USAGE_MODE_COOPERATIVE = 'cooperative';
    const USAGE_MODE_SIMULTANEOUS = 'simultaneous';

    const ASSOC_TABLE_ALIAS = 'LEK_taskUserAssoc';
    const TABLE_ALIAS = 'LEK_task';

    const INTERNAL_LOCK = '*translate5InternalLock*';

    const INITIAL_TASKTYPE_DEFAULT = 'default';
    const INITIAL_TASKTYPE_PROJECT = 'project';
    const INITIAL_TASKTYPE_PROJECT_TASK = 'projectTask';

    /**
     * All tasktypes that editor_Models_Validator_Task will consider as valid.
     * @var array
     */
    public static $validTaskTypes = [self::INITIAL_TASKTYPE_DEFAULT, self::INITIAL_TASKTYPE_PROJECT, self::INITIAL_TASKTYPE_PROJECT_TASK];

    /**
     * Currently only used for getConfig, should be used for all relevant customer stuff in this class
     */
    protected static $customerCache = [];

    protected $dbInstanceClass = 'editor_Models_Db_Task';
    protected $validatorInstanceClass = 'editor_Models_Validator_Task';

    /**
     * Sequenzer for QM SubSegmentFlags
     * @var integer
     */
    protected $qmFlagId = 0;

    /**
     * @var editor_Models_Task_Meta
     */
    protected $meta;

    /**
     * @var string
     */
    protected $taskDataPath;

    /**
     * On cloning we need a new taskGuid and id
     * {@inheritDoc}
     * @see ZfExtended_Models_Entity_Abstract::__clone()
     */
    public function __clone() {
        $data = $this->row->toArray();
        unset($data['id']);
        unset($data['taskGuid']);
        //before all other operations make a new row object
        $this->init($data);
        $this->createTaskGuidIfNeeded();
    }
    
    /**
     * Returns a Zend_Config Object; if task specific settings exist, they are set now.
     * @return Zend_Config
     */
    protected function getConfig() {
        // This is a temporary preparation for implementing TRANSLATE-471.
        if (empty($this->getCustomerId())) {
            // Step 1a: start with systemwide config
            $config = new Zend_Config(Zend_Registry::get('config')->toArray(), true);
        }
        else {
            // Step 1b: anything customer-specific for this task?
            $config = $this->_getCachedCustomer($this->getCustomerId())->getConfig();
        }

        // Step 2: anything task-specific for this task?
        // TODO...

        $config->setReadOnly();
        return $config;
    }

    /**
     * Add a tasktype for the validation.
     * @param string $taskType
     */
    public static function addValidTaskType($taskType) {
        self::$validTaskTypes[] = $taskType;
    }

    /**
     * Return tasktypes for the validation.
     * @return array
     */
    public static function getValidTaskTypes() {
        return self::$validTaskTypes;
    }

    /**
     * access customer instances in a cached way
     * @param int $id
     * @return editor_Models_Customer
     */
    protected function _getCachedCustomer(int $id): editor_Models_Customer {
        if(empty(self::$customerCache[$id])) {
            $customer = ZfExtended_Factory::get('editor_Models_Customer');
            /* @var $customer editor_Models_Customer */
            $customer->load($id);
            self::$customerCache[$id] = $customer;
        }
        return self::$customerCache[$id];
    }

    /**
     * loads the task to the given guid
     * @param string $taskGuid
     */
    public function loadByTaskGuid(string $taskGuid){
        try {
            $s = $this->db->select()->where('taskGuid = ?', $taskGuid);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if (!$row) {
            $this->notFound(__CLASS__ . '#taskGuid', $taskGuid);
        }
        //load implies loading one Row, so use only the first row
        $this->row = $row;
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::init()
     */
    public function init(array $data = null) {
        parent::init($data);
        $this->taskDataPath = null;
    }

    /**
     * loads all Entities out of DB associated to the user (filtered by the TaskUserAssoc table)
     * if $loadAll is true, load all tasks, user infos joined only where possible,
     *   if false only the associated tasks
     * @param string $userGuid
     * @param bool $loadAll optional, per default false
     * @return array
     */
    public function loadListByUserAssoc(string $userGuid, $loadAll = false) {
        return parent::loadFilterdCustom($this->getSelectByUserAssocSql($userGuid, '*', $loadAll));
    }

    /**
     * loads all tasks associated to a specific user as PM
     * @param string $pmGuid
     * @return array
     */
    public function loadListByPmGuid(string $pmGuid) {
        $s = $this->db->select();
        $s->where('pmGuid = ?', $pmGuid);
        return parent::loadFilterdCustom($s);
    }

    /**
     * loads all tasks of the given tasktype that are associated to a specific user as PM
     * @param string $pmGuid
     * @param string $tasktype
     * @return array
     */
    public function loadListByPmGuidAndTasktype(string $pmGuid, string $tasktype) {
        $s = $this->db->select();
        $s->where('pmGuid = ?', $pmGuid);
        $s->where('tasktype = ?', $tasktype);
        $s->order('orderdate ASC');
        return parent::loadFilterdCustom($s);
    }

    /**
     * loads all tasks of the given tasktype that shall be removed (because
     * their lifetime is over).
     * @param string $tasktype
     * @param int $orderDaysOffset
     * @return array
     */
    public function loadListForCleanupByTasktype(string $tasktype, int $orderDaysOffset) {
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
     */
    public function loadUserList(string $userGuid) {
        $quoted = $this->db->getAdapter()->quote($userGuid);
        $userModel = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */

        // here no check for pmGuid, since this is done in task::loadListByUserAssoc
        $loadAll = $userModel->isAllowed('backend', 'loadAllTasks');
        $ignoreAnonStuff = $userModel->readAnonymizedUsers();

        //FIXME in future the customer  config and task config must respected here too,
        // means a complete refactoring probably with the anon flag into the job table (also for filtering!!!)
        $config = Zend_Registry::get('config');
        if($ignoreAnonStuff) {
            //the current user may see all user data
            $anonSql = '';
        }
        elseif($config->runtimeOptions->customers->anonymizeUsers) {
            //if we get here, the user may only see the user for the task he is pm and himself
            $anonSql = 'AND filter.pmGuid = "'.$quoted.'" OR LEK_taskUserAssoc.userGuid = "'.$quoted.'"';
        }
        else {
            //the user may see only the user data from customers where the anon flag is false and where he is pm and himself
            $anonSql = 'INNER JOIN LEK_customer ON LEK_customer.id=filter.customerId AND LEK_customer.anonymizeUsers=0';
            $anonSql .= 'OR filter.pmGuid = "'.$quoted.'" OR LEK_taskUserAssoc.userGuid = "'.$quoted.'"';
        }

        if($loadAll){
            $s = $this->db->select()->setIntegrityCheck(false);
        } else {
            $s = $this->getSelectByUserAssocSql($userGuid, '*', $loadAll);
        }

        //apply the frontend task filters
        $this->applyFilterAndSort($s);
        //the inner query is the current task list with activ filters
        $sql='SELECT Zf_users.*,filter.taskGuid from Zf_users, '.
            '('.$s->assemble().') as filter '.
             'INNER JOIN LEK_taskUserAssoc ON LEK_taskUserAssoc.taskGuid=filter.taskGuid '.
             $anonSql.
             'WHERE Zf_users.userGuid = LEK_taskUserAssoc.userGuid '.
             'GROUP BY Zf_users.id '.
             'ORDER BY Zf_users.surName; ';

        $stmt = $this->db->getAdapter()->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * gets the total count of all tasks associated to the user (filtered by the TaskUserAssoc table)
     * if $loadAll is true, load all tasks, user infos joined only where possible,
     *   if false only the associated tasks
     * @param string $userGuid
     * @param bool $loadAll
     * @return number
     */
    public function getTotalCountByUserAssoc(string $userGuid, $loadAll = false) {
        $s = $this->getSelectByUserAssocSql($userGuid, array('numrows' => 'count(*)'), $loadAll);
        if(!empty($this->filter)) {
            $this->filter->applyToSelect($s, false);
        }
        return $this->db->fetchRow($s)->numrows;
    }

    /**
     * returns the SQL to retrieve the tasks of an user oder of all users joined with the users assoc infos
     * if $loadAll is true, load all tasks, user infos joined only where possible,
     *   if false only the associated tasks to the user
     * TODO: when state filter is refactored, remove the tasuserassoc object cast here
     * @param string $userGuid
     * @param string $cols column definition
     * @param bool $loadAll
     * @return Zend_Db_Table_Select
     */
    protected function getSelectByUserAssocSql(string $userGuid, $cols = '*', $loadAll = false) {
        $s = $this->db->select()
        ->from('LEK_task', $cols);
        $defaultTable=$this->db->info($this->db::NAME);
        if(!empty($this->filter)) {
            $this->filter->setDefaultTable($defaultTable);
        }
        if($loadAll) {
            $on ='LEK_taskUserAssoc_1.taskGuid = LEK_task.taskGuid AND LEK_taskUserAssoc_1.userGuid = '.$s->getAdapter()->quote($userGuid);
            $s->joinLeft(['LEK_taskUserAssoc_1'=>'LEK_taskUserAssoc'], $on, array());
        }
        else {
            $s->joinLeft(['LEK_taskUserAssoc_1'=>'LEK_taskUserAssoc'], 'LEK_taskUserAssoc_1.taskGuid = LEK_task.taskGuid', array())
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
    public function getTasknameForDownload(string $suffix, $prefix = '') {
        return iconv('UTF-8', 'ASCII//TRANSLIT', $prefix.$this->getTaskName().$suffix);
    }

    /**
     * returns the (given element of) the name of the file that has been imported
     * @param string $element optional ('filename'|'suffix'; if not set: returns basename)
     * @return string
     */
    public function getImportfilename($element = '') {
        $treeDb = ZfExtended_Factory::get('editor_Models_Foldertree');
        /* @var $treeDb editor_Models_Foldertree */
        $filepaths = $treeDb->getPaths($this->getTaskGuid(),'file');
        $importFile = $filepaths[array_key_first($filepaths)]; // FIXME: What if there is more than one file in the import-folder? Should not happen for InstantTranslate, but in general it can...
        switch ($element) {
            case 'filename':
                return pathinfo($importFile,PATHINFO_FILENAME);
            case 'suffix':
                return pathinfo($importFile,PATHINFO_EXTENSION);
            default:
                return pathinfo($importFile,PATHINFO_BASENAME);
        }
    }

    /**
     * @param bool $asJson if true, json is returned, otherwhise assoc-array
     * @return mixed depending on $asJson
     */
    public function getQmSubsegmentIssuesTranslated($asJson = true){
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */;
        $walk = function(array $qmFlagTree)use ($translate,&$walk){
            foreach ($qmFlagTree as $node) {
                $node->text = $translate->_($node->text);
                if(isset($node->children) && is_array($node->children)){
                  $walk($node->children, $walk);
                }
            }
            return $qmFlagTree;
        };
        $tree = Zend_Json::decode($this->row->qmSubsegmentFlags, Zend_Json::TYPE_OBJECT);
        if(!isset($tree->qmSubsegmentFlags)){
            throw new Zend_Exception('qmSubsegmentFlags JSON Structure not OK, missing field qmSubsegmentFlags');
        }
        $qmFlagTree = $walk($tree->qmSubsegmentFlags);
        if($asJson){
            return Zend_Json::encode($qmFlagTree);
        }
        return $qmFlagTree;
    }
    /**
     * @return array qm-flags as tree array of objects; example in json-notation:
     *      [{"text":"Accuracy","id":1,"children":[{"text":"Terminology","id":2,"children":[]}]}]
     */
    public function getQmSubsegmentIssues(){
        $tree = Zend_Json::decode($this->row->qmSubsegmentFlags, Zend_Json::TYPE_OBJECT);
        if(!isset($tree->qmSubsegmentFlags)){
            throw new Zend_Exception('qmSubsegmentFlags JSON Structure not OK, missing field qmSubsegmentFlags');
        }
        return $tree->qmSubsegmentFlags;
    }
    /**
     * @return array('issueId'=>'issueText',...)
     */
    public function getQmSubsegmentIssuesFlat(){
        $flatTree = array();
        $walk = function(array $qmFlagTree)use (&$walk,&$flatTree){
            foreach ($qmFlagTree as $node) {
                $flatTree[$node->id] = $node->text;
                if(isset($node->children) && is_array($node->children)){
                  $walk($node->children);
                }
            }
        };
        $walk($this->getQmSubsegmentIssues());
        return $flatTree;
    }
    /**
     * @return stdClass
     */
    public function getQmSubsegmentSeverities(){
        $tree = Zend_Json::decode($this->row->qmSubsegmentFlags, Zend_Json::TYPE_OBJECT);
        if(!isset($tree->severities)){
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
    public function getQmSubsegmentSeveritiesTranslated($asJson = true, array $row = null) {
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */
        if(is_null($row)) {
            $row = $this->row->toArray();
        }
        $tree = Zend_Json::decode($row['qmSubsegmentFlags'], Zend_Json::TYPE_OBJECT);
        $result = array();
        foreach($tree->severities as $key => $label) {
            $result[] = (object)array('id' => $key, 'text' => $translate->_($label));
        }
        if($asJson){
            return Zend_Json::encode($tree);
        }
        return $result;
    }


    /**
     * returns the relative path to the persistent data directory of the given or loaded taskguid
     * @param string $taskGuid optional, if not given use the taskguid of internal loaded task
     * @return string
     */
    public function getRelativeTaskDataPath($taskGuid = null) {
        if(empty($taskGuid)){
            $taskGuid = $this->getTaskGuid();
        }
        //use the TaskGuid as directory name, remove curly brackets "{" and "}"
        return trim($taskGuid, '{}');
    }

    /**
     * returns the absolute path to the task data directory
     * @return SplFileInfo
     */
    public function getAbsoluteTaskDataPath() {
        if(empty($this->taskDataPath)){
            $taskDataRel = $this->getRelativeTaskDataPath();
            $config = Zend_Registry::get('config');
            $this->taskDataPath = $config->runtimeOptions->dir->taskData.DIRECTORY_SEPARATOR.$taskDataRel;
        }
        return $this->taskDataPath;
    }

    /**
     * creates the TaskData Directory, throws a Zend_Exception if not possible
     * @throws Zend_Exception
     * @return SplFileInfo InfoObject of the TaskData Directory
     */
    public function initTaskDataDirectory() {
        $taskDataRel = $this->getRelativeTaskDataPath();
        $config = Zend_Registry::get('config');
        if(empty($config->runtimeOptions->dir->taskData)){
        	throw new Zend_Exception('Config runtimeOptions.dir.taskData is NOT set!');
        }
        $taskDataRoot = new SplFileInfo($config->runtimeOptions->dir->taskData);
        if(!$taskDataRoot->isDir()) {
            throw new Zend_Exception('TaskData root Directory does not exist: "'.$taskDataRoot->getPathname().'".');
        }
        if(!$taskDataRoot->isWritable()) {
            throw new Zend_Exception('TaskData root Directory is not writeable: "'.$taskDataRoot->getPathname().'".');
        }
        $taskData = new SplFileInfo($taskDataRoot.DIRECTORY_SEPARATOR.$taskDataRel);
        if($taskData->isDir()){
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $log->logError('Proceeding with already existing TaskData Directory: '.$taskData);
        }
        else {
            if(!mkdir($taskData)){
                throw new Zend_Exception('TaskData Directory could not be created, check parent folders:  "'.$taskData->getPathname().'".');
            }
        }
        if($taskData->isWritable()){
        	return $this->taskDataPath = $taskData;
        }
        throw new Zend_Exception('TaskData Directory is not writeable:  "'.$taskData->getPathname().'".');
    }

    /**
     * creates (if needed) the materialized view to the task
     */
    public function createMaterializedView() {
        $mv = ZfExtended_Factory::get('editor_Models_Segment_MaterializedView', array($this->getTaskGuid()));
        /* @var $mv editor_Models_Segment_MaterializedView */
        $mv->create();
    }

    /**
     * drops the materialized view to the task
     */
    public function dropMaterializedView() {
        $mv = ZfExtended_Factory::get('editor_Models_Segment_MaterializedView', array($this->getTaskGuid()));
        /* @var $mv editor_Models_Segment_MaterializedView */
        $mv->drop();
    }

    /**
     * update the workflowStep of a specific task
     * @param string $stepName
     * @param bool $increaseStep optional, by default true, increases then the workflow step nr
     */
    public function updateWorkflowStep(string $stepName, $increaseStep = true) {
        $data = [
                'workflowStepName' => $stepName,
        ];
        if($increaseStep) {
            $data['workflowStep'] =  new Zend_Db_Expr('`workflowStep` + 1');
            //step nr is not updated in task entity! For correct value we have to reload the task and load the value form DB.
        }
        $this->__call('setWorkflowStepName', [$stepName]);
        $this->db->update($data, ['taskGuid = ?' => $this->getTaskGuid()]);
        $this->updateSegmentFinishCount($this);
    }

    /**
     * This method may not be called directly!
     * Either call editor_Models_Task::updateWorkflowStep
     * or if you are in Workflow Context call editor_Workflow_Abstract::setNextStep
     * @param string $stepName
     * @throws BadMethodCallException
     */
    public function setWorkflowStepName($stepName) {
        throw new BadMethodCallException('setWorkflowStepName may not be called directly. Either via Task::updateWorkflowStep or in Workflow Context via Workflow::setNextStep');
    }

    /**
     * register this Tasks config and Guid in the session as active Task
     * @param Zend_Session_Namespace $session optional, if omitted standard SessionNamespace is generated
     * @param string $openState
     */
    public function registerInSession(string $openState,Zend_Session_Namespace $session = null) {
        if(empty($session)) {
            $session = new Zend_Session_Namespace();
        }
        $session->taskGuid = $this->getTaskGuid();
        $session->task = $this->getAsConfig();
        $session->taskOpenState = $openState;
        $session->taskWorkflow = $this->getWorkflow();
        $session->taskWorkflowStepNr = $this->getWorkflowStep();
        $session->taskWorkflowStepName = $this->getWorkflowStepName();
    }

    /**
     * deletes this Tasks config and Guid from the session as active Task
     * @param Zend_Session_Namespace $session optional, if omitted standard SessionNamespace is generated
     */
    public function unregisterInSession(Zend_Session_Namespace $session = null) {
        if(empty($session)) {
            $session = new Zend_Session_Namespace();
        }
        $session->taskGuid = null;
        $session->task = null;
        $session->taskOpenState = null;
        $session->taskWorkflowStepNr = null;
    }

    /**
     * returns true if the loaded task is registered in the session
     * @return boolean
     */
    public function isRegisteredInSession() {
        $session = new Zend_Session_Namespace();
        return !empty($session->taskGuid) && $session->taskGuid == $this->getTaskGuid();
    }

    /**
     * unlocks all tasks, where the associated session is invalid
     */
    public function cleanupLockedJobs() {
        $validSessionIds = ZfExtended_Models_Db_Session::GET_VALID_SESSIONS_SQL;
        //the below not like "*translate5InternalLock*%" gives us the possibility to define session independant locks:
        $where = 'not locked is null and (
            lockedInternalSessionUniqId not in ('.$validSessionIds.')
            and lockedInternalSessionUniqId not like "*translate5InternalLock*%"
            or lockedInternalSessionUniqId is null)';
        $this->db->update(array('lockingUser' => null, 'locked' => null, 'lockedInternalSessionUniqId' => null), $where);

        //clean up remaining multi user task locks where no user is editing anymore
        $multiUserId = self::INTERNAL_LOCK.self::USAGE_MODE_SIMULTANEOUS;
        $usedMultiUserLocks = 'SELECT t.id
        FROM (SELECT id, taskGuid FROM LEK_task t WHERE t.lockedInternalSessionUniqId = "'.$multiUserId.'") t
        JOIN LEK_taskUserAssoc tua on tua.taskGuid = t.taskGuid
        WHERE not tua.usedState is null AND not tua.usedInternalSessionUniqId is null';
        $where = 'not locked is null and lockedInternalSessionUniqId = "'.$multiUserId.'" and id not in ('.$usedMultiUserLocks.')';

        $this->db->update(array('lockingUser' => null, 'locked' => null, 'lockedInternalSessionUniqId' => null), $where);
    }

    /**
     * locks the task
     * sets a locked-timestamp in LEK_task for the task, if locked column is null
     *
     * @param string $datetime
     * @param string $lockId String to distinguish different lock types
     * @return boolean
     */
    public function lock(string $datetime, string $lockId = ''): bool {
        return $this->_lock($datetime, ZfExtended_Models_User::SYSTEM_GUID, self::INTERNAL_LOCK.$lockId);
    }

    /**
     * locks the task
     * sets a locked-timestamp in LEK_task for the task, if locked column is null
     *
     * @param string $datetime
     * @return boolean
     */
    public function lockForSessionUser(string $datetime): bool {
        $user = new Zend_Session_Namespace('user');
        $session = new Zend_Session_Namespace();
        return $this->_lock($datetime, $user->data->userGuid, $session->internalSessionUniqId);
    }


    /**
     * locks the task
     * sets a locked-timestamp in LEK_task for the task, if locked column is null
     *
     * @param string $datetime
     * @param bool $systemLock optional, default false. If true the lock is session independant, and must therefore revoked manually!
     * @param string $systemIdentifier optional, default empty string. Is used to distinguish different lock types (mainly for system locks)
     * @return boolean
     */
    protected function _lock(string $datetime, string $userGuid, string $sessionId): bool {
        $update = array(
            'locked' => $datetime,
            'lockingUser' => $userGuid,
            'lockedInternalSessionUniqId' => $sessionId,
        );
        $where = array('taskGuid = ? and locked is null'=>$this->getTaskGuid());
        $rowsUpdated = $this->db->update($update, $where);
        if($rowsUpdated === 0){
            //true if no system lock, if system lock evaluate if sessionId (and therefore the type) equals
            $checkSystemLockType = strpos($sessionId, self::INTERNAL_LOCK) === false || $sessionId === $this->getLockedInternalSessionUniqId();
            //already locked by the same user with the same system lock type (if applicable).
            return !is_null($this->getLocked()) && $this->getLockingUser() == $userGuid && $checkSystemLockType;
        }
        return true;
    }

    /**
     * unlocks the task, does not check user or multi user state!
     * @return boolean false if task had not been locked or does not exist,
     *          true if task has been unlocked successfully
     */
    public function unlock() {
        $where = array('taskGuid = ? and locked is not null'=>$this->getTaskGuid());
        $data = array(
            'locked' => NULL,
            'lockedInternalSessionUniqId' => NULL,
            'lockingUser' => NULL,
        );
        $success = $this->db->update($data, $where) !== 0;
        //check how many rows are updated
        $this->events->trigger('unlock', $this, [
            'task' => $this,
            'success' => $success,
        ]);
        return $success;
    }

    /**
     * unlocks the task, for a specific user. Checks if user is allowed to unlock (lockingUser = currentUser) and respects multiuser editing
     * @return boolean false if task had not been locked or does not exist,
     *          true if task has been unlocked successfully
     */
    public function unlockForUser($userGuid) {
        $taskGuid = $this->db->getAdapter()->quote($this->getTaskGuid());
        $userGuid = $this->db->getAdapter()->quote($userGuid);
        $multiUserId = $this->db->getAdapter()->quote(self::INTERNAL_LOCK.self::USAGE_MODE_SIMULTANEOUS);

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

        $data = array(
            'locked' => NULL,
            'lockedInternalSessionUniqId' => NULL,
            'lockingUser' => NULL,
        );
        //check how many rows are updated
        return $this->db->update($data, sprintf($where, $taskGuid, $userGuid, $multiUserId)) !== 0;
    }

    /**
     * marks the task erroneous and unlocks its
     * @return boolean false if task had not been updated or does not exist,
     * @throws Zend_Exception if something went wrong
     */
    public function setErroneous() {
        $data = [
            'state' => self::STATE_ERROR,
            'locked' => NULL,
            'lockedInternalSessionUniqId' => NULL,
            'lockingUser' => NULL
        ];
        $where = array('taskGuid = ?'=>$this->getTaskGuid());
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
    public function isErroneous() {
        return $this->getState() == self::STATE_ERROR;
    }

    /**
     * returns if tasks is importing
     * @return boolean
     */
    public function isImporting() {
        return $this->getState() == self::STATE_IMPORT;
    }


    /**
     * checks if the given taskGuid is locked. If optional userGuid is given,
     * checks if is locked by given userGuid.
     * @param string $taskGuid
     * @param string $userGuid
     * @return false|datetime returns false if not locked, lock timestamp if locked
     */
    public function isLocked(string $taskGuid, string $userGuid = null) {
        $s = $this->db->select()->where('taskGuid = ?', $taskGuid);
        if(!empty($userGuid)) {
            $s->where('lockingUser = ?', $userGuid);
        }
        $row = $this->db->fetchRow($s);
        if(empty($row) || empty($row['locked'])){
            return false;
        }
        return $row['locked'];
    }
    /**

     * checks if the given taskGuid is used by any user
     * @param string $taskGuid
     */
    public function isUsed(string $taskGuid) {
        /* @var $tua editor_Models_TaskUserAssoc */
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        $used = $tua->loadUsed($taskGuid);
        return !empty($used);
    }

    /**
     * returns true if current task is in an exclusive state (like import)
     * @param array $additionalNonExclusive additional states to be handled non exclusive
     * @return boolean
     */
    public function isExclusiveState(array $additionalNonExclusive = []) {
        $nonExclusiveStates = [self::STATE_OPEN, self::STATE_END, self::STATE_UNCONFIRMED];
        return !in_array($this->getState(), array_merge($nonExclusiveStates, $additionalNonExclusive));
    }

    /**
     * returns a Zend_Config Object with task specific settings
     * @deprecated must be changed with TRANSLATE-471
     * @return Zend_Config
     */
    public function getAsConfig() {
        return new Zend_Config(array(
            'enableSourceEditing' => (bool)$this->getEnableSourceEditing()
        ));
    }

    /**
     * creates a random task guid if no one is set
     */
    public function createTaskGuidIfNeeded() {
        $taskguid = $this->getTaskGuid();
        if(! empty($taskguid)) {
            return;
        }
        $this->setTaskGuid(ZfExtended_Utils::guid(true));
    }

    /**
     * Returns the default initial tasktype.
     * @return string
     */
    public function getDefaultTasktype () {
        return self::INITIAL_TASKTYPE_DEFAULT;
    }

    /**
     * Is the task to be hidden due to its taskType?
     * (Further implementation: https://confluence.translate5.net/display/MI/Task+Typen)
     */
    public function isHiddenTask() {
        return $this->getTaskType() != $this->getDefaultTasktype();
    }
    
    /**
     * returns true if current task is a project
     * @return boolean
     */
    public function isProject(): bool {
        return $this->getTaskType() == self::INITIAL_TASKTYPE_PROJECT;
    }
    
    /**
     * generates a statistics summary to the given task
     * @return stdClass
     */
    public function getStatistics() {
        $result = new stdClass();
        $result->taskGuid = $this->getTaskGuid();
        $result->taskName = $this->getTaskName();
        $result->wordCount = $this->getWordCount();
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $result->segmentCount = $segment->count($this->getTaskGuid());
        $result->segmentCountEditable = $segment->count($this->getTaskGuid(),true);
        return $result;
    }

    /**
     * generates a task overview statistics summary
     * @return array
     */
    public function getSummary() {
        $stmt = $this->db->getAdapter()->query('select taskType, state, count(*) taskCount, sum(wordCount) wordCountSum, sum(segmentCount) segmentCountSum from LEK_task group by taskType, state');
        return $stmt->fetchAll();
    }

    /**
     * convenient method to get the task meta data
     * @param boolean $reinit if true reinits the internal meta object completely (after adding a field for example)
     * @return editor_Models_Task_Meta
     */
    public function meta($reinit = false) {
        if(empty($this->meta) || $reinit) {
            $this->meta = ZfExtended_Factory::get('editor_Models_Task_Meta');
        }
        elseif($this->getTaskGuid() == $this->meta->getTaskGuid()) {
            return $this->meta;
        }
        try {
            $this->meta->loadByTaskGuid($this->getTaskGuid());
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->meta->init(array('taskGuid' => $this->getTaskGuid()));
        }
        return $this->meta;
    }

    /**
     * Check if the current task status allows this action
     * @param array $allow additional states to be handled non exclusive
     * @throws ZfExtended_Models_Entity_Conflict
     */
    public function checkStateAllowsActions(array $allow = []) {
        if($this->isErroneous() || $this->isExclusiveState($allow) && $this->isLocked($this->getTaskGuid())) {
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1046' => 'The current task status does not allow that action.',
            ]);
            throw ZfExtended_Models_Entity_Conflict::createResponse('E1046', [
                'Der aktuelle Status der Aufgabe verbietet diese Aktion!'
            ], [
                'task' => $this,
                'isLocked' => $this->isLocked($this->getTaskGuid()),
                'isErroneous' => $this->isErroneous(),
                'isExclusiveState' => $this->isExclusiveState(),
            ]);
        }
    }

    /***
     * Remove all ended task from the database and from the disk when there is no
     * change since (taskLifetimeDays)config days in lek_task_log
     */
    public function removeOldTasks() {
        $config = Zend_Registry::get('config');
        $taskLifetimeDays= $config->runtimeOptions->taskLifetimeDays;


        $daysOffset = $taskLifetimeDays ?? 100;

        if(!$daysOffset){
            throw new Zend_Exception('No task taskLifetimeDays configuration defined.');
        }

        $daysOffset = (int)$daysOffset; //ensure that it is plain integer
        $s = $this->db->select()
            ->where('`state` = ?', self::STATE_END)
            ->where('`modified` < (CURRENT_DATE - INTERVAL ? DAY)', $daysOffset);
        $tasks = $this->db->getAdapter()->fetchAll($s);

        if(empty($tasks)){
            return;
        }

        $taskEntity=null;
        $removedTasks=[];
        //foreach task task, check the deletable, and delete it
        foreach ($tasks as $task){
            $taskEntity=ZfExtended_Factory::get('editor_Models_Task');
            /* @var $taskEntity editor_Models_Task */
            $taskEntity->load($task['id']);

            if(!$taskEntity->isErroneous()){
                $taskEntity->checkStateAllowsActions();
            }

            //no need for entity version check, since field loaded from db will always have one

            $remover = ZfExtended_Factory::get('editor_Models_Task_Remover', array($taskEntity));
            /* @var $remover editor_Models_Task_Remover */
            $removedTasks[]=$taskEntity->getTaskName();
            $remover->remove();
        }
        $logger = Zend_Registry::get('logger');
        /* @var $logger ZfExtended_Logger */
        $logger->info('E1011', 'removeOldTasks - removed {taskCount} tasks', [
            'taskCount' => count($removedTasks),
            'taskNames' => $removedTasks
        ]);
    }

    /***
     * Search task by given search string.
     * The search will provide any match on taskName field.
     *
     * @param string $searchString
     * @return array|array
     */
    public function search($searchString,$fields=array()) {
        $s = $this->db->select();
        if(!empty($fields)){
            $s->from($this->tableName,$fields);
        }
        $s->where('lower(taskName) LIKE lower(?)','%'.$searchString.'%');
        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Update the terminologie flag based on if there is a term collection assigned as language resource to the task.
     * @param string $taskGuid
     * @param array $ignoreAssocs: the provided languageresources taskassoc ids will be ignored
     */
    public function updateIsTerminologieFlag($taskGuid,$ignoreAssocs=array()){
        $service=ZfExtended_Factory::get('editor_Services_TermCollection_Service');
        /* @var $service editor_Services_TermCollection_Service */
        $assoc=ZfExtended_Factory::get('editor_Models_LanguageResources_Taskassoc');
        /* @var $assoc editor_Models_LanguageResources_Taskassoc */
        $result=$assoc->loadAssocByServiceName($taskGuid, $service->getName(),$ignoreAssocs);
        $this->loadByTaskGuid($taskGuid);
        $this->setTerminologie(!empty($result));
        $this->save();
    }

    /**
     * Overwrite getTerminologie: Return the DB value or false depending on the taskType.
     * @return boolean
     */
    public function getTerminologie() {
        if ($this->isHiddenTask()) {
            // For hidden tasks, terms don't need to be tagged (= no TermTagger needed).
            return false;
        }
        return parent::get('terminologie');
    }

    /**
     * Overwrite setTerminologie: saves false instead of the given value depending on the taskType.
     * @param bool $flag
     */
    public function setTerminologie($flag) {
        if ($this->isHiddenTask()) {
            // For hidden tasks, terms don't need to be tagged (= no TermTagger needed).
            $flag = false;
        }
        return parent::set('terminologie', $flag);
    }

    /**
     * Return all combinations of font-family and font-size that are used in the task.
     * @return array
     */
    public function getAllFontsInTask() {
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
     * @param string|false $checkUser (optional)
     * @return boolean
     */
    public function anonymizeUsers($checkUser = true) {
        $config = $this->getConfig();
        if(!$config->runtimeOptions->customers->anonymizeUsers) {
            return false;
        }
        if($checkUser === false) {
            return $config->runtimeOptions->customers->anonymizeUsers; // = true if we get here
        }
        $userModel = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        return !($userModel->readAnonymizedUsers());
    }

    /***
     * Update the $edit100PercentMatch flag for all segments in the task.
     * @param editor_Models_Task $task
     * @param bool $edit100PercentMatch
     */
    public function updateSegmentsEdit100PercentMatch(editor_Models_Task $task,bool $edit100PercentMatch){
        // create a segment-iterator to get all segments of this task as a list of editor_Models_Segment objects
         $segments = ZfExtended_Factory::get('editor_Models_Segment_Iterator', [$task->getTaskGuid()]);
        /* @var $segments editor_Models_Segment_Iterator */
        $segmentHistory=ZfExtended_Factory::get('editor_Models_SegmentHistory');
        /* @var $segmentHistory editor_Models_SegmentHistory */
        $autoState=ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        /* @var $autoState editor_Models_Segment_AutoStates */
        foreach ($segments as $segment){
            if($segment->getEditable() == $edit100PercentMatch || $segment->getMatchRate()<100){
                continue;
            }

            $actualHistory=$segmentHistory->loadBySegmentId($segment->getId(),1);
            $actualHistory=$actualHistory[0] ?? [];

            $history=$segment->getNewHistoryEntity();

            //it is full match always
            $isFullMatch=true;
            $isLocked = $segment->meta()->getLocked() && (bool) $task->getLockLocked();

            $isEditable  = (!$isFullMatch || (bool) $edit100PercentMatch || $segment->meta()->getAutopropagated()) && !$isLocked;

            $segment->setEditable($isEditable);

            $autoStateId=$actualHistory['autoStateId'] ?? null;

            //if the autostate does not exist in the history or it is blocked, calculate same as import
            if(!$autoStateId || $autoStateId==$autoState::BLOCKED){
                $autoStateId=$autoState->calculateImportState($isEditable, $segment->isTargetTranslated());
            }
            $segment->setAutoStateId($autoStateId);
            $history->save();
            $segment->save();
        }
    }

    /***
     * Update the segment finish count based on the task workflow step valid autostates
     * @param editor_Models_Task $task
     */
    public function updateSegmentFinishCount(editor_Models_Task $task){
        $stateRoles=$this->getTaskStateRoles($task->getTaskGuid(),$task->getWorkflowStepName());
        $isWorkflowEnded=$task->getWorkflowStepName()==editor_Workflow_Abstract::STEP_WORKFLOW_ENDED;
        if(!$stateRoles && !$isWorkflowEnded){
            return;
        }

        $adapted=$this->db->getAdapter();
        //if it is workflow ended, set the count to 100% (segmentFinishCount=segmentCount)
        if($isWorkflowEnded){
            $expression='segmentCount';
        }else{
            //get the autostates for the valid task workflow states
            $expression='(SELECT COUNT(*) FROM LEK_segments WHERE autoStateId IN('.implode(',', $stateRoles).') AND taskGuid='.$adapted->quote($task->getTaskGuid()).')';
        }
        $this->db->update(['segmentFinishCount'=>new Zend_Db_Expr($expression)],['taskGuid=?' => $task->getTaskGuid()]);
    }

    /***
     * increment or decrement the segmentFinishCount value based on the given state logic
     * @param editor_Models_Task $task
     * @param int $newAutostate
     * @param int $oldAutoState
     */
    public function changeSegmentFinishCount(editor_Models_Task $task,int $newAutostate,int $oldAutoState){
        $stateRoles=$this->getTaskStateRoles($task->getTaskGuid(),$task->getWorkflowStepName());
        if(!$stateRoles){
            return;
        }
        $expression='';
        if(in_array($newAutostate, $stateRoles) && !in_array($oldAutoState, $stateRoles)){
            $expression='segmentFinishCount + 1 ';
        }elseif(in_array($oldAutoState, $stateRoles) && !in_array($newAutostate, $stateRoles)){
            $expression='segmentFinishCount - 1 ';
        }else{
            return;
        }
        $this->db->update(['segmentFinishCount'=>new Zend_Db_Expr($expression)],['taskGuid=?' => $task->getTaskGuid()]);
    }

    /***
     * Get all autostate ids for the active tasks workflow
     *
     * @param string $taskGuid
     * @param string $workflowStepName
     * @return boolean|boolean|multitype:string
     */
    public function getTaskStateRoles(string $taskGuid,string $workflowStepName){
        try {
            $workflow=$this->getTaskActiveWorkflow($taskGuid);
        } catch (ZfExtended_Exception $e) {
            //the workflow with $workflowStepName does not exist
            return false;
        }
        $roleOfStep=$workflow->getRoleOfStep($workflowStepName);
        if(empty($roleOfStep)){
            return false;
        }
        $autoState=new editor_Models_Segment_AutoStates();
        $stateMap=$autoState->getRoleToStateMap();
        return $stateMap[$roleOfStep] ?? false;
    }

    /***
     * Get the active workflow for the given taskGuid
     * @param string $taskGuid
     * @return editor_Workflow_Abstract
     */
    public function getTaskActiveWorkflow(string $taskGuid){
        //get the current task active workflow
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wfm editor_Workflow_Manager */
        return $wfm->getActive($taskGuid);
    }
    
    /***
     * Load all tasks of a given project. If taskOnly is true, in the result array, the master(project) task
     * will not be included
     *
     * @param int $projectId
     * @param bool $tasksOnly
     * @return array
     */
    public function loadProjectTasks(int $projectId,bool $tasksOnly=false) : array{
        $s=$this->db->select();
        if($tasksOnly){
            $s->where('taskType NOT IN(?)',self::INITIAL_TASKTYPE_PROJECT);
        }
        $s->where('projectId=?',$projectId);
        return $this->db->fetchAll($s)->toArray();
    }
    
    /**
     * Returns the matching of col-names as set in Editor.view.admin.TaskGrid.
     * @return array
     */
    public static function getTaskGridTextCols () {
        return array (
            // A-Z
            'customerId' => 'Endkunde',
            'edit100PercentMatch' => '100%-Treffer editierbar',
            'emptyTargets' => 'Übersetzungsaufgabe (kein Review)',
            'enableSourceEditing' => 'Quellsprache bearbeitbar',
            'fileCount' => 'Dateien',
            'fullMatchEdit' => '100% Matches sind editierbar',
            'lockLocked' => 'In importierter Datei gesperrte Segmente sind in translate5 gesperrt',
            'orderdate' => 'Bestelldatum',
            'pmGuid' => 'Projektmanager',
            'pmName' => 'Projektmanager',
            'referenceFiles' => 'Referenzdateien',
            'relaisLang' => 'Relaissprache',
            'sourceLang' => 'Quellsprache',
            'state' =>'Status',
            'targetLang' => 'Zielsprache',
            'taskActions' => 'Aktionen',
            'taskassocs' => 'Anzahl zugewiesene Sprachresourcen',
            'taskName' => 'Aufgabenname',
            'taskNr' => 'Auftragsnr.',
            'terminologie' => 'Terminologie',
            'users' => 'Benutzer',
            'wordCount' => 'Wörter',
            'wordCountTT' => 'Anzahl Wörter',
            'workflow' => 'Workflow',
            'userCount' => 'Zahl zugewiesener Benutzer',
        );
    }
}
