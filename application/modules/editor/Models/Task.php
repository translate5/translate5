<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
 * @method string getState() getState()
 * @method void setState() setState(string $state)
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
 * @method string getQmSubsegmentFlags() getQmSubsegmentFlags() get Original Flags from DB
 * @method void setQmSubsegmentFlags() setQmSubsegmentFlags(string $flags) set Original Flags in DB
 */
class editor_Models_Task extends ZfExtended_Models_Entity_Abstract {
    const STATE_OPEN = 'open';
    const STATE_END = 'end';
    
    const ASSOC_TABLE_ALIAS = 'tua';
    const TABLE_ALIAS = 't';
    
    protected $dbInstanceClass = 'editor_Models_Db_Task';
    protected $validatorInstanceClass = 'editor_Models_Validator_Task';

    /**
     * Sequenzer for QM SubSegmentFlags
     * @var integer
     */
    protected $qmFlagId = 0;
    
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
     * loads all Entities out of DB associated to the user (filtered by the TaskUserAssoc table)
     * @param string $userGuid
     * @return array
     */
    public function loadListByUserAssoc(string $userGuid) {
        return parent::loadFilterdCustom($this->getSelectByUserAssocSql($userGuid));
    }
    
    /**
     * gets the total count of all tasks associated to the user (filtered by the TaskUserAssoc table)
     * @param string $userGuid
     * @return number
     */
    public function getTotalCountByUserAssoc(string $userGuid) {
        $s = $this->getSelectByUserAssocSql($userGuid, array('numrows' => 'count(*)'));
        if(!empty($this->filter)) {
            $this->filter->applyToSelect($s, false);
        }
        return $this->db->fetchRow($s)->numrows;
    }
    
    /**
     * returns the SQL to retrieve the tasks of an user
     * @param string $userGuid
     * @return Zend_Db_Table_Select
     */
    protected function getSelectByUserAssocSql(string $userGuid, $cols = '*') {
        $alias = self::ASSOC_TABLE_ALIAS;
        $s = $this->db->select()
        ->from(array('t' => 'LEK_task'), $cols)
        ->join(array($alias => 'LEK_taskUserAssoc'), $alias.'.taskGuid = t.taskGuid', array())
        ->where($alias.'.userGuid = ?', $userGuid);
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
        $translate = Zend_Registry::get('Zend_Translate');
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
        $translate = Zend_Registry::get('Zend_Translate');
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
    
    public function delete() {
        //@todo ask marc if logging tables should also be deleted (no constraint is set)
        $taskGuid = $this->getTaskGuid();
        if(empty($taskGuid)) {
            return;
        }
        //An der Segment und Files Tabelle hängen mehrere Abhängigkeiten,
        //daher diese manuell löschen vorher um DB Last durch Table Locks zu verringern.
        $segmentTable = ZfExtended_Factory::get('editor_Models_Db_Segments');
        $segmentTable->delete(array('taskGuid = ?' => $taskGuid));
        
        $termTable = ZfExtended_Factory::get('editor_Models_Db_Terms');
        $termTable->delete(array('taskGuid = ?' => $taskGuid));
        
        $filesTable = ZfExtended_Factory::get('editor_Models_Db_Files');
        $filesTable->delete(array('taskGuid = ?' => $taskGuid));
        
        /* @var $recursivedircleaner ZfExtended_Controller_Helper_Recursivedircleaner */
        $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
        		'Recursivedircleaner'
        );
        $taskPath = (string)$this->getAbsoluteTaskDataPath();
        if(is_dir($taskPath)){
            $recursivedircleaner->delete($taskPath);
        }
        parent::delete();
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
     * unlocks all tasks, where the associated session is invalid
     * @return array an array with tasks which were unlocked
     */
    public function cleanupLockedJobs() {
        $validSessionIds = 'select internalSessionUniqId  from sessionMapInternalUniqId m, session s  where s.modified + lifetime >= UNIX_TIMESTAMP() and s.session_id = m.session_id';
        $where = 'not locked is null and (lockedInternalSessionUniqId not in ('.$validSessionIds.') or lockedInternalSessionUniqId is null)';
        $toUnlock = $this->db->fetchAll($this->db->select()->where($where))->toArray();
        /* @var $tua editor_Models_TaskUserAssoc */
        $this->db->update(array('lockingUser' => null, 'locked' => null, 'lockedInternalSessionUniqId' => null), $where);
        return $toUnlock;
    }
    
    /**
     * locks the task
     * sets a locked-timestamp in LEK_task for the task, if locked column is null
     * 
     * @param string $datetime
     * @return boolean
     * @throws Zend_Exception if something went wrong
     */
    public function lock(string $datetime) {
        $user = new Zend_Session_Namespace('user');
        $rowsUpdated = $this->db->update(array('locked'=>  $datetime), 
                array('taskGuid = ? and locked is null'=>$this->getTaskGuid()));
        if($rowsUpdated===0){
            return !is_null($this->getLocked()) && $this->getLockingUser() == $user->data->userGuid;//already locked by this same user
        }
        if($rowsUpdated===1){
            $session = new Zend_Session_Namespace();
            $this->setLockedInternalSessionUniqId($session->internalSessionUniqId);
            $this->setLockingUser($user->data->userGuid);
            $this->save();
            return true;
        }
        throw new Zend_Exception(
                'More then 1 row updated when setLocked in LEK_task. Number or rows updated for task '.
            $this->getTaskGuid().' : '.$rowsUpdated);
    }
    
    /**
     * unlocks the task
     * unsets a timestamp (sets it to NULL) in LEK_task for the task, if locked column is not null
     * @return boolean false if task had not been locked or does not exist, 
     *          true if task has been unlocked successfully
     * @throws Zend_Exception if something went wrong
     */
    public function unlock() {
        $rowsUpdated = $this->db->update(array('locked'=>  NULL), 
                array('taskGuid = ? and locked is not null'=> $this->getTaskGuid()));
        if($rowsUpdated===0)return false;
        if($rowsUpdated===1){
            $this->setLockedInternalSessionUniqId(null);
            $this->setLockingUser(null);
            $this->save();
            return true;
        }
        throw new Zend_Exception('More then 1 row updated when unlock', 
                'Number or rows updated for task '.
            $this->getTaskGuid().' : '.$rowsUpdated);
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
     * Deep Cloning of the internal data object
     */
    public function __clone() {
        $this->row = clone $this->row;
    }
}
