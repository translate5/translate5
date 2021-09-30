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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * TaskUserTracking Object Instance as needed in the application.
 *
 * The TaskController invokes the tracking everytime a task is opened,
 * no matter if the workflow-users of the task are to be anonymized or not.
 * Hence, the anonymizing of a task can be switched on and off at any time.
 *
 * However, there might correctly not exist any data for a user of a task; e.g.
 * - if a task has been imported and opened before TaskUserTracking has been implemented
 * - if a user is assigned to a task, but has never opened the task so far
 *
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method string getUserGuid() getUserGuid()
 * @method void setUserGuid() setUserGuid(string $guid)
 * @method integer getTaskOpenerNumber() getTaskOpenerNumber()
 * @method void setTaskOpenerNumber() setTaskOpenerNumber(int $id)
 * @method string getFirstName() getFirstName()
 * @method void setFirstName() setFirstName(string $guid)
 * @method string getSurName() getSurName()
 * @method void setSurName() setSurName(string $guid)
 * @method string getUserName() getUserName()
 * @method void setUserName() setUserName(string $guid)
 * @method string getRole() getRole()
 * @method void setRole() setRole(string $guid)
 *
 */
class editor_Models_TaskUserTracking extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_TaskUserTracking';
    protected $validatorInstanceClass = 'editor_Models_Validator_TaskUserTracking';
    
    /**
     * has a row been loaded at all?
     * We correctly might not have any data for a user of a task; e.g.
     * - if a task has been imported and opened before TaskUserTracking has been implemented
     * - if a user is assigned to a task, but has never opened the task so far
     * @return boolean
     */
    public function hasEntry(): bool {
        return (!is_null($this->row));
    }
    
    /**
     * loads the TaskUserTracking-entry for the given task and userGuid (= unique)
     * @param string $taskGuid
     * @param string $userGuid
     */
    public function loadEntry($taskGuid, $userGuid) {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->where('userGuid = ?', $userGuid);
        $this->row = $this->db->fetchRow($s);
    }
    
    /**
     * loads the TaskUserTracking-entry for the given task and taskOpenerNumber (= unique)
     * @param string $taskGuid
     * @param string $userGuid
     */
    public function loadEntryByTaskOpenerNumber($taskGuid, $taskOpenerNumber) {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->where('taskOpenerNumber = ?', $taskOpenerNumber);
        $this->row = $this->db->fetchRow($s);
    }
    
    /**
     * gets all TaskUserTracking-data for the given taskGuid
     * @param string $taskGuid
     * @return array
     */
    public function getByTaskGuid($taskGuid) {
        try {
            $s = $this->db->select()->where('taskGuid = ?', $taskGuid);
            return $this->db->fetchAll($s)->toArray();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * gets all TaskUserTracking-data for the given taskGuid
     * @param array $taskGuidList
     * @return array
     */
    public function loadGroupedByTaskGuid(array $taskGuidList) {
        try {
            $result = array_fill_keys($taskGuidList, []);
            $s = $this->db->select()->where('taskGuid in (?)', $taskGuidList);
            $res = $this->db->fetchAll($s)->toArray();
            foreach($res as $row) {
                $result[$row['taskGuid']][] = $row;
            }
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * returns the taskOpenerNumber of the currently loaded entry or null (we might not have a TaskUserTracking-entry loaded; that's ok).
     * @return NULL|number
     */
    public function getTaskOpenerNumberForUser(){
        if (!$this->hasEntry()){
            return null;
        }
        return $this->getTaskOpenerNumber();
    }
    
    /**
     * returns the (real, as tracked) username of the currently loaded entry or null (we might not have a TaskUserTracking-entry loaded; that's ok).
     * @return NULL|string
     */
    public function getUsernameForUser(){
        if (!$this->hasEntry()){
            return null;
        }
        return $this->getUserName();
    }
    
    /**
     * insert or update TaskUserTracking entry.
     * If users have already opened the task before, we only keep their data updated.
     * If it's a user who hasn't opened the task before, the user will get the next taskOpenerNumber for this task.
     * @param string $taskGuid
     * @param string $userGuid
     * @param string $role
     */
    public function insertTaskUserTrackingEntry($taskGuid, $userGuid, $role) {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($userGuid);
        $firstName = $user->getFirstName();
        $surName = $user->getSurName();
        $userName = $user->getUserName();
        // TODO: is this SQL-statement safe regarding  race conditions? (see https://stackoverflow.com/a/5360154)
        $sql= 'INSERT INTO LEK_taskUserTracking (`taskGuid`, `userGuid`, `taskOpenerNumber`, `firstName`, `surName`, `userName`, `role`)
               VALUES (?, ?,
                       (SELECT coalesce(MAX(`taskOpenerNumber`), 0) FROM LEK_taskUserTracking t2 WHERE t2.taskGuid = ?) + 1,
                       ?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE `firstName` = ?,`surName` = ?,`userName` = ?,`role` = ?';
        $bindings = array(
            $taskGuid, $userGuid, $taskGuid, $firstName, $surName, $userName, $role,
            $firstName, $surName, $userName, $role
        );
        $stmt = $this->db->getAdapter()->query($sql, $bindings);

        if($stmt->rowCount() > 0) {
            //we trigger the event only, if really something was changed.
            $this->events->trigger('afterUserTrackingInsert', $this, [
                'taskGuid' => $taskGuid,
                'userGuid' => $userGuid,
                'role' => $role
            ]);
        }
    }
}