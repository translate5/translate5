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
 * TaskUserAssoc Object Instance as needed in the application
 * @method integer getId() getId()
 * @method string getTaskGuid() getTaskGuid()
 * @method string getUserGuid() getUserGuid()
 * @method string getState() getState()
 * @method string getRole() getRole()
 * @method string getUsedState() getUsedState()
 * @method string getUsedInternalSessionUniqId() getUsedInternalSessionUniqId()
 * @method string getIsPmOverride() getIsPmOverride()
 * @method void setId() setId(integer $id)
 * @method void setTaskGuid() setTaskGuid(string $taskGuid)
 * @method void setUserGuid() setUserGuid(string $userGuid)
 * @method void setState() setState(string $state)
 * @method void setRole() setRole(string $role)
 * @method void setUsedState() setUsedState(string $state)
 * @method void setUsedInternalSessionUniqId() setUsedInternalSessionUniqId(string $sessionId)
 * @method void setIsPmOverride() setIsPmOverride(boolean $isPmOverride)
 */
class editor_Models_TaskUserAssoc extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_TaskUserAssoc';
    protected $validatorInstanceClass = 'editor_Models_Validator_TaskUserAssoc';

    
    /**
     * returns all users to the taskGuid and role of the given TaskUserAssoc
     * @param mixed $role string or null as a value
     * @param string $taskGuid
     * @return [array] list with user arrays
     */
    public function getUsersOfRoleOfTask($role,$taskGuid){
        if (is_null($role))
            return array();
        /* @var $tua editor_Models_TaskUserAssoc */
        $this->setRole($role);
        $this->setTaskGuid($taskGuid);
        return $this->loadAllUsers();
    }
    
    /**
     * loads all tasks to the given user guid
     * @param guid $userGuid
     * @return array|null
     */
    public function loadByUserGuid(string $userGuid){
        try {
            $s = $this->db->select()->where('userGuid = ?', $userGuid);
            return $this->db->fetchAll($s)->toArray();
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        return null;
    }
    
    /**
     * loads the assocs regardless isPmOverride is set or not
     * @param array $list
     * @return array
     */
    public function loadByTaskGuidList(array $list) {
        try {
            if(count($list)===0)
                return array();
            $s = $this->db->select()->where('taskGuid in (?)', $list);
            return $this->db->fetchAll($s)->toArray();
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        return null;
    }
    
    /**
     * loads one TaskUserAssoc Instance by given params. If params taskGuid or role are
     * null, task is loaded regardless of taskGuid or role
     * this method loads the assoc regardless isPmOverride is set or not
     * 
     * @param string $userGuid
     * @param string $taskGuid
     * @param string $role | null
     * @param string $state | null
     * @return array
     */
    public function loadByParams(string $userGuid, $taskGuid = null,
            $role = null,$state = null) {
        try {
            $s = $this->db->select()
                ->where('userGuid = ?', $userGuid);
            if(!is_null($taskGuid)) $s->where('taskGuid = ?', $taskGuid);
            if(!is_null($role)) $s->where('role= ?', $role);
            if(!is_null($state)) $s->where('state= ?', $state);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if (!$row) {
            $this->notFound(__CLASS__ . '#taskGuid + userGuid', $taskGuid.' + '.$userGuid);
        }
        //load implies loading one Row, so use only the first row
        $this->row = $row;
        return $this->row->toArray();
    }
    
    /**
     * Updates the stored user states of an given taskGuid 
     * @param string $state
     * @param string $role
     * @param string $taskGuid
     */
    public function setStateForRoleAndTask(string $state, string $role, string $taskGuid) {
        $this->db->update(array('state' => $state), array(
            'role = ?' => $role,
            'taskGuid = ?' => $taskGuid,
        ));
    }
    
    /**
     * returns a matrix with the usage counts for all state, 
     * role combinations of the actually loaded assoc's task (exclude pmOverrides)
     * @return array
     */
    public function getUsageStat() {
        $sql = 'select state, role, count(userGuid) cnt from LEK_taskUserAssoc where taskGuid = ? and isPmOverride = 0 group by state, role;';
        $res = $this->db->getAdapter()->query($sql, array($this->getTaskGuid()));
        return $res->fetchAll();
    }
    
    /**
     * returns a list with users to the actually loaded taskGuid and role
     * loads only assocs where isPmOverride not set
     * @param string $taskGuid
     * @param string $role
     * @return array
     */
    public function loadAllUsers() {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $db = $this->db;
        $s = $user->db->select()
        ->from(array('u' => $user->db->info($db::NAME)))
        ->join(array('tua' => $db->info($db::NAME)), 'tua.userGuid = u.userGuid', array())
        ->where('tua.isPmOverride = 0')
        ->where('tua.role = ?', $this->getRole())
        ->where('tua.taskGuid = ?', $this->getTaskGuid());
        return $user->db->fetchAll($s)->toArray();
    }
    
    /**
     * loads the TaskUserAssoc Content joined with userinfos (currently only login)
     * loads only assocs where isPmOverride not set
     * @return array
     */
    public function loadAllWithUserInfo() {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $db = $this->db;
        $s = $db->select()
        ->setIntegrityCheck(false)
        ->from(array('tua' => $db->info($db::NAME)))
        ->join(array('u' => $user->db->info($db::NAME)), 'tua.userGuid = u.userGuid', array('login', 'surName', 'firstName'))
        ->where('tua.isPmOverride = 0');
        //->where('tua.taskGuid = ?', $this->getTaskGuid()); kommt per filter aktuell!
        
        //default sort: 
        if(!$this->filter->hasSort()) {
            $this->filter->addSort('surName');
            $this->filter->addSort('firstName');
            $this->filter->addSort('login');
        }
        return $this->loadFilterdCustom($s);
    }
    

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::save()
     */
    public function save() {
        $taskGuid = $this->get('taskGuid');
        $result = parent::save();
        $this->updateTask($taskGuid);
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::delete()
     */
    public function delete() {
        $taskGuid = $this->get('taskGuid');
        $task = ZfExtended_Factory::get('editor_Models_Task');

        $e = new ZfExtended_BadMethodCallException();
        $e->setLogging(false);
        
        if($this->isUsed()) {
            $e->setMessage("Die Aufgabe wird von einem Benutzer benutzt", true);
            throw $e;
        }
        
        /* @var $task editor_Models_Task */
        if($task->isLocked($taskGuid, $this->getUserGuid())) {
            $e->setMessage("Die Aufgabe ist durch einen Benutzer gesperrt", true);
            throw $e;
        }

        $result = parent::delete();
        $this->updateTask($taskGuid);
        return $result;
    }
    
    /**
     * deletes the actual loaded assoc if it is a pmOverride assoc
     */
    public function deletePmOverride() {
        $this->db->delete(array(
            'id = ?' => $this->getId(),
            'taskGuid = ?' => $this->getTaskGuid(),
            'userGuid = ?' => $this->getUserGuid(),
            'isPmOverride = 1',
        ));
        $this->init();
    }

    /**
     * deletes all assoc entries for this userGuid, and updates the users counter in the Task Entity
     * @param string $userGuid
     */
    public function deleteByUserguid($userGuid) {
        $list = $this->loadByUserGuid($userGuid);
        foreach($list as $assoc) {
            $this->init($assoc);
            $this->delete();
        }
    }
    
    /**
     * updates the task table count field
     * @todo this method is a perfect example for the usage in events!
     */
    protected function updateTask($taskGuid) {
        $sql = 'update `LEK_task` t, (select count(*) cnt, ? taskGuid from `LEK_taskUserAssoc` where taskGuid = ? and isPmOverride = 0) tua 
            set t.userCount = tua.cnt where t.taskGuid = tua.taskGuid';
        $db = $this->db->getAdapter();
        $sql = $db->quoteInto($sql, $taskGuid, 'string', 2);
        $db->query($sql);
    }
    
    /**
     * set all associations of the given taskGuid (or for all tasks if null) to unused where the session is expired
     * sets also the state to open where allowed
     * @param string $taskGuid optional, if omitted cleanup all taskUserAssocs
     * @param string $forced optional, default false. if true cleanup also taskUserAssocs with validSessionsIds, only usable with given taskGuid!
     */
    public function cleanupLocked($taskGuid = null, $forced = false) {
        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive();
        /* @var $workflow editor_Workflow_Abstract */
        
        $validSessionIds = ZfExtended_Models_Db_Session::GET_VALID_SESSIONS_SQL;
        $where = array('not usedState is null and (usedInternalSessionUniqId not in ('.$validSessionIds.') or usedInternalSessionUniqId is null)');
        if(!empty($taskGuid)) {
            if($forced) {
                //since with force = true we throw out all users we allow this only with a given taskguid
                $where = array();
            }
            $where['taskGuid = ?'] = $taskGuid;
        }

        if(!empty($workflow)) {
            //updates the workflow state back to open if allowed
            $where2 = $where;
            $where2['state in (?)'] = $workflow->getAllowedTransitionStates($workflow::STATE_OPEN);
            $this->db->update(array('state' => $workflow::STATE_OPEN), $where2);
        }
        
        //delete all pmEditAll fake entries
        $where3 = $where;
        $where3[] = 'isPmOverride = 1';
        $this->db->delete($where3);
        
        //unuse the associations where the using sessionId was expired, this update must be performed after the other!
        $this->db->update(array('usedState' => null,'usedInternalSessionUniqId' => null), $where);
    }
    
    /**
     * returns true if user of the currently loaded taskUserAssoc uses the associated task
     * @return boolean
     */
    public function isUsed() {
        $validSessionIds = ZfExtended_Models_Db_Session::GET_VALID_SESSIONS_SQL;
        $validSessionIds .= ' AND internalSessionUniqId = ?';
        $res = $this->db->getAdapter()->query($validSessionIds, array($this->getUsedInternalSessionUniqId()));
        $validSessions = $res->fetchAll();
        //if usedInternalSessionUniqId not exists in the session table reset it, 
        //  also the usedState value and return false
        if(empty($validSessions)){
            $this->db->update(array('usedState' => null, 'usedInternalSessionUniqId' => null), 'id = '.(int)$this->getId());
            return false;
        }
        $usedState = $this->getUsedState();
        // if usedState is set and sessionId is valid return true
        return !empty($usedState);
    }
    
    /**
     * loads and returns the currently used associations of the given taskGuid
     * @param string $taskGuid
     * @return array
     */
    public function loadUsed(string $taskGuid) {
        $this->cleanupLocked($taskGuid);
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->where('not usedState is null')
            ->where('not usedInternalSessionUniqId is null');
        return $this->db->fetchAll($s)->toArray();
    }
    
}