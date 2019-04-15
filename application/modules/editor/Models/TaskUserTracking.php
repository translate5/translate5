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
 * TaskUserTracking Object Instance as needed in the application
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
 * @method string getRole() getRole()
 * @method void setRole() setRole(string $guid)
 * 
 */
class editor_Models_TaskUserTracking extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_TaskUserTracking';
    protected $validatorInstanceClass = 'editor_Models_Validator_TaskUserTracking';
    
    /**
     * loads the TaskUserTracking-entry for the given task and user (= unique)
     * @param string $taskGuid
     * @param string $userGuid
     */
    protected function loadEntry($taskGuid, $userGuid) {
        try {
            $s = $this->db->select()
                ->where('taskGuid = ?', $taskGuid)
                ->where('userGuid = ?', $userGuid);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            // Don't throw a not-found. There might correctly not exist any data for the user; e.g.
            // - if a task has been imported and opened before TaskUserTracking has been implemented
            // - if a user is assigned to a task, but has never opened the task so far
        }
        if ($row) {
            //load implies loading one Row, so use only the first row
            $this->row = $row;
        }
    }
    
    /**
     * returns the taskOpenerNumber of the currently loaded entry or null (we might not have a TaskUserTracking-entry loaded; that's ok).
     * @return NULL|number
     */
    protected function getTaskOpenerNumberForUser(){
        return $this->getTaskOpenerNumber() ?? null;
    }
    
    /**
     * renders an anonymized version using the kind of data that is given:
     * - e.g. "userName-1", "userName-2", ... if tracking-data is available
     * - "-" otherwise
     * @param string $userDataKey
     * @return string
     */
    protected function renderAnonymizeUserdata ($userDataKey) {
        $taskOpenerNumber = $this->getTaskOpenerNumberForUser();
        return (is_null($taskOpenerNumber) ? '-' : $key.'-'.$taskOpenerNumber);
    }
    
    /**
     * anonymizes all user-related data by keys in $data
     * @param string $taskGuid
     * @param array $data
     * @return array
     */
    public function anonymizeUserdata($taskGuid, array $data) {
        $keysToAnonymize = ['firstName','lockingUser','lockingUsername','login','userGuid','userName','surName'];
        array_walk($data, function( &$value, $key) use ($keysToAnonymize, $taskOpenerNumber) {
            if (in_array($key, $keysToAnonymize)) {
                $value = $this->renderAnonymizeUserdata($key);
            }
        });
        return $data;
    }
    
    /**
     * insert or update TaskUserTracking entry. 
     * If  users have already opened the task before, we only keep their data updated.
     * @param string $taskguid
     * @param string $userGuid
     * @param string $role
     */
    public function insertTaskUserTrackingEntry($taskguid, $userGuid, $role) {
        $taskOpenerNumber = $this->getTotalByTaskGuid($taskguid) + 1;
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($userGuid);
        $firstName = $user->getFirstName();
        $surName = $user->getSurName();
        $sql= 'INSERT INTO LEK_taskUserTracking (`taskGuid`,`userGuid`,`taskOpenerNumber`,`firstName`,`surName`,`role`) VALUES (?, ?, ?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE `firstName` = ?,`surName` = ?,`role` = ?';
        $bindings = array(
            $taskguid, $userGuid, $taskOpenerNumber, $firstName, $surName, $role, 
            $firstName, $surName, $role
        );
        try {
            $res = $this->db->getAdapter()->query($sql, $bindings);
        }
        catch(Zend_Db_Statement_Exception $e) {
            $this->handleIntegrityConstraintException($e); // TODO (können ja auch andere Fehler sein)
        }
        
    }
    
    /**
     * How many users have opened the task already?
     */
    protected function getTotalByTaskGuid($taskguid) {
        $s = $this->db->select();
        $s->where('taskGuid = ?', $taskguid);
        return $this->computeTotalCount($s);
        
    }
}