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
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    /**
     * @var editor_Models_Comment
     */
    protected $commentHelper;
    
    /**
     * @var editor_Models_Segment_TrackChangeTag
     */
    protected $trackChangeTagHelper;
    
    
    
    /**
     */
    public function __construct(){
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $this->commentHelper = ZfExtended_Factory::get('editor_Models_Comment');
        $this->trackChangeTagHelper = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        parent::__construct();
    }
    
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
        } else {
            $this->row = null;
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
    
    // -------------------------------------------------------------------------
    // ANONYMIZING (could be refactored into a class on it's own)
    // -------------------------------------------------------------------------
    
    /**
     * anonymizes all user-related data of other workflow users
     * @param string $taskGuid
     * @param array $data
     * @return array
     */
    public function anonymizeOtherUserdata($taskGuid, array $data) {
        $userGuid = $data['userGuid'];
        // anonymize data about OTHER workflow users only
        $sessionUser = new Zend_Session_Namespace('user');
        if ($userGuid === $sessionUser->data->userGuid) {
            return $data;
        }
        return $this->anonymizeUserdata($taskGuid, $data);
    }
    
    /**
     * anonymizes all user-related data in $data
     * @param string $taskGuid
     * @param array $data
     * @return array
     */
    public function anonymizeUserdata($taskGuid, array $data) {
        $userGuid = $data['userGuid'];
        $keysToAnonymize = ['comments','firstName','lockingUser','lockingUsername','login','userGuid','userName','surName','targetEdit'];
        array_walk($data, function( &$value, $key) use ($taskGuid, $userGuid, $keysToAnonymize) {
            if ($value != '' && in_array($key, $keysToAnonymize)) {
                switch ($key) {
                    case 'comments':
                        $value = $this->renderAnonymizedComment($value);
                        break;
                    case 'targetEdit':
                        $value = $this->renderAnonymizedTargetEdit($value);
                        break;
                    case 'userName':
                        $value = $this->renderAnonymizedUserName($taskGuid, $userGuid);
                        break;
                    default:
                        $value = '';
                        break;
                }
            }
        });
        return $data;
    }
    
    /**
     * renders anonymized comment as markedUp in "comment.phtml"
     * @return string
     */
    protected function renderAnonymizedComment($value) {
        // replace author given in <span class="author">xyz</span>
        return $this->commentHelper->renderAnonymizedComment($value);
    }
    
    /**
     * renders an anonymized version of the username:
     * - "User1", "User2" etc if tracking-data is available
     * - "User" otherwise
     * @return string
     */
    protected function renderAnonymizedUserName ($taskGuid, $userGuid) {
        $this->loadEntry($taskGuid, $userGuid);
        if(is_null($this->row)) {
            return $this->translate->_('Benutzer');
        }
        return $this->translate->_('Benutzer') . '' . $this->getTaskOpenerNumberForUser();
    }
    
    /**
     * renders anonymized target
     * @return string
     */
    protected function renderAnonymizedTargetEdit($value) {
        // replace data-userguid und data-username in TrackChanges:
        return $this->trackChangeTagHelper->renderAnonymizedTrackChangeData($value);
    }
}