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
 * @method string getTargetDeliveryDate() getTargetDeliveryDate()
 * @method void setTargetDeliveryDate() setTargetDeliveryDate(string $datetime)
 * @method string getRealDeliveryDate() getRealDeliveryDate()
 * @method void setRealDeliveryDate() setRealDeliveryDate(string $datetime)
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
 */
class editor_Models_Task extends ZfExtended_Models_Entity_Abstract {
    const STATE_OPEN = 'open';
    const STATE_END = 'end';
    const STATE_IMPORT = 'import';
    const STATE_ERROR = 'error';
    const STATE_UNCONFIRMED = 'unconfirmed';
    
    const ASSOC_TABLE_ALIAS = 'tua';
    const TABLE_ALIAS = 't';
    
    const INTERNAL_LOCK = '*translate5InternalLock*';

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
     * loads the task to the given guid
     * @param guid $taskGuid
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
     * @param string $userGuid
     * @param string $cols column definition
     * @param bool $loadAll 
     * @return Zend_Db_Table_Select
     */
    protected function getSelectByUserAssocSql(string $userGuid, $cols = '*', $loadAll = false) {
        $alias = self::ASSOC_TABLE_ALIAS;
        $s = $this->db->select()
        ->from(array('t' => 'LEK_task'), $cols);
        if(!empty($this->filter)) {
            $this->filter->setDefaultTable('t');
        }
        if($loadAll) {
            $on = $alias.'.taskGuid = t.taskGuid AND '.$alias.'.userGuid = '.$s->getAdapter()->quote($userGuid);
            $s->joinLeft(array($alias => 'LEK_taskUserAssoc'), $on, array());
        }
        else {
            $s->joinLeft(array($alias => 'LEK_taskUserAssoc'), $alias.'.taskGuid = t.taskGuid', array())
            ->where($alias.'.userGuid = ? OR t.pmGuid = ?', $userGuid);
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
        /* @var $translate ZfExtended_Zendoverwrites_Translate */;
        if(is_null($row))$row = $this->row->toArray();
        $tree = Zend_Json::decode($row['qmSubsegmentFlags'], Zend_Json::TYPE_OBJECT);
        $result = array();
        foreach($tree->severities as $key => $label) {
            $result[] = (object)array('id' => $key, 'text' => $translate->_($label));
        }
        if($asJson){
            return Zend_Json::encode($qmFlagTree);
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
     * @return array an array with tasks which were unlocked
     */
    public function cleanupLockedJobs() {
        $validSessionIds = ZfExtended_Models_Db_Session::GET_VALID_SESSIONS_SQL;
        //this gives us the possibility to define session independant locks:
        $validSessionIds .= ' union select \''.self::INTERNAL_LOCK.'\' internalSessionUniqId';
        $where = 'not locked is null and (lockedInternalSessionUniqId not in ('.$validSessionIds.') or lockedInternalSessionUniqId is null)';
        $toUnlock = $this->db->fetchAll($this->db->select()->where($where))->toArray();
        $this->db->update(array('lockingUser' => null, 'locked' => null, 'lockedInternalSessionUniqId' => null), $where);
        return $toUnlock;
    }
    
    /**
     * locks the task
     * sets a locked-timestamp in LEK_task for the task, if locked column is null
     * 
     * @param string $datetime
     * @param bool $sessionIndependant optional, default false. If true the lock is session independant, and must therefore revoked manually! 
     * @return boolean
     * @throws Zend_Exception if something went wrong
     */
    public function lock(string $datetime, $sessionIndependant = false) {
        if($sessionIndependant) {
            $userGuid = ZfExtended_Models_User::SYSTEM_GUID;
            $sessionId = self::INTERNAL_LOCK;
        }
        else {
            $user = new Zend_Session_Namespace('user');
            $userGuid = $user->data->userGuid;
            $session = new Zend_Session_Namespace();
            $sessionId = $session->internalSessionUniqId;
        }
        $update = array(
            'locked' => $datetime,
            'lockingUser' => $userGuid,
            'lockedInternalSessionUniqId' => $sessionId,
        );
        $where = array('taskGuid = ? and locked is null'=>$this->getTaskGuid());
        $rowsUpdated = $this->db->update($update, $where);
        if($rowsUpdated === 0){
            return !is_null($this->getLocked()) && $this->getLockingUser() == $userGuid;//already locked by this same user
        }
        return true;
    }
    
    /**
     * unlocks the task
     * unsets a timestamp (sets it to NULL) in LEK_task for the task, if locked column is not null
     * @return boolean false if task had not been locked or does not exist, 
     *          true if task has been unlocked successfully
     * @throws Zend_Exception if something went wrong
     */
    public function unlock() {
        $where = array('taskGuid = ? and locked is not null'=>$this->getTaskGuid());
        $data = array(
            'locked' => NULL,
            'lockedInternalSessionUniqId' => NULL,
            'lockingUser' => NULL,
        );
        //check how many rows are updated
        return $this->db->update($data, $where) !== 0;
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
        //check how many rows are updated
        return $this->db->update($data, $where) !== 0;
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
     * @return boolean
     */
    public function isExclusiveState() {
        $nonExclusiveStates = array(self::STATE_OPEN, self::STATE_END, self::STATE_UNCONFIRMED);
        return !in_array($this->getState(), $nonExclusiveStates);
    }
    
    /**
     * returns a Zend_Config Object with task specific settings
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
        $guidHelper = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'Guid'
        );
        $this->setTaskGuid($guidHelper->create(true));
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
        $stmt = $this->db->getAdapter()->query('select state, count(*) taskCount, sum(wordCount) wordCountSum from LEK_task group by state');
        return $stmt->fetchAll();
    }
    
    /**
     * convenient method to get the task meta data
     * @return editor_Models_Task_Meta
     */
    public function meta() {
        if(empty($this->meta)) {
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
     * 
     * @throws ZfExtended_Models_Entity_Conflict
     */
    public function checkStateAllowsActions() {
        if($this->isErroneous() || $this->isExclusiveState() && $this->isLocked($this->getTaskGuid())) {
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

        
        $daysOffset=isset($taskLifetimeDays) ? $taskLifetimeDays : 100;
        
        if(!$daysOffset){
            throw new Zend_Exception('No task taskLifetimeDays configuration defined.');
            return;
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
     * Assign the task to the default customer.
     */
    public function setDefaultCustomerId() {
        $customer = ZfExtended_Factory::get('editor_Models_Customer');
        /* @var $customer editor_Models_Customer */
        $customer->loadByDefaultCustomer();
        $this->setCustomerId($customer->getId());
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
}
