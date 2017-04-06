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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
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
 * @method void setId() setId(integer $id)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method string getTaskNr() getTaskNr()
 * @method void setTaskNr() setTaskNr(string $nr)
 * @method string getTaskName() getTaskName()
 * @method void setTaskName() setTaskName(string $name)
 * @method integer getSourceLang() getSourceLang()
 * @method void setSourceLang() setSourceLang(integer $id)
 * @method integer getTargetLang() getTargetLang()
 * @method void setTargetLang() setTargetLang(integer $id)
 * @method integer getRelaisLang() getRelaisLang()
 * @method void setRelaisLang() setRelaisLang(integer $id)
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
 * @method void setWorkflowStep() setWorkflowStep(integer $stepNr)
 * @method integer getWordCount() getWordCount()
 * @method void setWordCount() setWordCount(integer $wordcount)
 * @method string getTargetDeliveryDate() getTargetDeliveryDate()
 * @method void setTargetDeliveryDate() setTargetDeliveryDate(string $datetime)
 * @method string getRealDeliveryDate() getRealDeliveryDate()
 * @method void setRealDeliveryDate() setRealDeliveryDate(string $datetime)
 * @method string getOrderdate() getOrderdate()
 * @method void setOrderdate() setOrderdate(string $datetime)
 * @method boolean getReferenceFiles() getReferenceFiles()
 * @method void setReferenceFiles() setReferenceFiles(boolean $flag)
 * @method boolean getTerminologie() getTerminologie()
 * @method void setTerminologie() setTerminologie(boolean $flag)
 * @method boolean getEnableSourceEditing() getEnableSourceEditing()
 * @method void setEnableSourceEditing() setEnableSourceEditing(boolean $flag)
 * @method boolean getEdit100PercentMatch() getEdit100PercentMatch()
 * @method void setEdit100PercentMatch() setEdit100PercentMatch(boolean $flag)
 * @method boolean getLockLocked() getLockLocked()
 * @method void setLockLocked() setLockLocked(boolean $flag)
 * @method string getQmSubsegmentFlags() getQmSubsegmentFlags() get Original Flags from DB
 * @method void setQmSubsegmentFlags() setQmSubsegmentFlags(string $flags) set Original Flags in DB
 * @method void delete() delete() see editor_Models_Task_Remover for complete task removal
 */
class editor_Models_Task extends ZfExtended_Models_Entity_Abstract {
    const STATE_OPEN = 'open';
    const STATE_END = 'end';
    const STATE_IMPORT = 'import';
    const STATE_ERROR = 'error';
    
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
     * if $leftOuterJoin is true, load all tasks, user infos joined only where possible,
     *   if false only the associated tasks
     * @param string $userGuid
     * @param boolean $leftOuterJoin optional, per default false 
     * @return array
     */
    public function loadListByUserAssoc(string $userGuid, $leftOuterJoin = false) {
        return parent::loadFilterdCustom($this->getSelectByUserAssocSql($userGuid, '*', $leftOuterJoin));
    }
    
    /**
     * gets the total count of all tasks associated to the user (filtered by the TaskUserAssoc table)
     * if $leftOuterJoin is true, load all tasks, user infos joined only where possible,
     *   if false only the associated tasks
     * @param string $userGuid
     * @param boolean $leftOuterJoin
     * @return number
     */
    public function getTotalCountByUserAssoc(string $userGuid, $leftOuterJoin = false) {
        $s = $this->getSelectByUserAssocSql($userGuid, array('numrows' => 'count(*)'), $leftOuterJoin);
        if(!empty($this->filter)) {
            $this->filter->applyToSelect($s, false);
        }
        return $this->db->fetchRow($s)->numrows;
    }
    
    /**
     * returns the SQL to retrieve the tasks of an user oder of all users joined with the users assoc infos
     * if $leftOuterJoin is true, load all tasks, user infos joined only where possible,
     *   if false only the associated tasks to the user
     * @param string $userGuid
     * @param string $cols column definition
     * @param boolean $leftOuterJoin 
     * @return Zend_Db_Table_Select
     */
    protected function getSelectByUserAssocSql(string $userGuid, $cols = '*', $leftOuterJoin = false) {
        $alias = self::ASSOC_TABLE_ALIAS;
        $s = $this->db->select()
        ->from(array('t' => 'LEK_task'), $cols);
        if($leftOuterJoin) {
            $on = $alias.'.taskGuid = t.taskGuid AND '.$alias.'.userGuid = '.$s->getAdapter()->quote($userGuid);
            $s->joinLeft(array($alias => 'LEK_taskUserAssoc'), $on, array());
        }
        else {
            $s->join(array($alias => 'LEK_taskUserAssoc'), $alias.'.taskGuid = t.taskGuid', array())
            ->where($alias.'.userGuid = ?', $userGuid);
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
     * @param boolean $asJson if true, json is returned, otherwhise assoc-array
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
     * @param boolean $asJson
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
     * @param string $taskGuid
     * @param integer $step
     */
    public function updateWorkflowStep(string $taskGuid, integer $step) {
        $this->db->update(array('workflowStep' => $step), array(
                      'taskGuid = ?' => $taskGuid)
        );
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
     * @param boolean $sessionIndependant optional, default false. If true the lock is session independant, and must therefore revoked manually! 
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
        $nonExclusiveStates = array(self::STATE_OPEN, self::STATE_OPEN);
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
}
