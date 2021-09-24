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

/**
 * Class for anonymizing workflows.
 * 
 * Set for each task; see anonymizeUsers() in editor_Models_Task.
 * (The TaskController invokes the tracking everytime a task is opened,
 * no matter if the workflow-users of the task are to be anonymized or not.
 * Hence, the anonymizing of a task can be switched on and off at any time.)
 * 
 * For anonymizing the user-data, the taskUserTracking-data is used.
 * 
 */
class editor_Workflow_Anonymize {
    
    /**
     * @var editor_Models_Comment
     */
    protected $commentHelper;
    
    /**
     * @var string
     */
    protected $sessionUserGuid;
    
    /**
     * @var editor_Models_TaskUserTracking
     */
    protected $taskUserTracking;
    
    /**
     * @var editor_Models_Segment_TrackChangeTag
     */
    protected $trackChangeTagHelper;
    
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    /**
     * @var string
     */
    protected $taskGuid;
    
    /**
     * @var string
     */
    protected $userGuid;
    
    /**
     */
    public function __construct(){
        $this->commentHelper = ZfExtended_Factory::get('editor_Models_Comment');
        $sessionUser = new Zend_Session_Namespace('user');
        $this->sessionUserGuid = $sessionUser->data->userGuid;
        $this->taskUserTracking = ZfExtended_Factory::get('editor_Models_TaskUserTracking');
        $this->trackChangeTagHelper = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
    }
    
    /**
     * anonymizes all user-related data of other workflow users in $data.
     * CAUTION, this means that $data must not contain user-data related to different users!
     * @param string $taskGuid
     * @param string $userGuid
     * @param array $data
     * @param string $currentUserGuid optional, if null use the current authenticated users guid
     * @return array
     */
    public function anonymizeUserdata(string $taskGuid, string $userGuid, array $data, string $currentUserGuid = null) {
        $this->taskGuid = $taskGuid;
        $this->userGuid = $userGuid;
        if ($this->isCurrentUserOrPM($currentUserGuid ?? $this->sessionUserGuid)) {
            return $data;
        }
        $keysToAnonymize = ['comments','email','firstName','lockingUser','lockingUsername','login','userGuid','userName','surName'];
        array_walk($data, function( &$value, $key) use ($keysToAnonymize) {
            if ($value != '' && in_array($key, $keysToAnonymize)) {
                switch ($key) {
                    case 'comments':
                        $value = $this->renderAnonymizedComment($value);
                        break;
                    case 'lockingUser':
                    case 'userGuid':
                        $value = $this->renderAnonymizedUserGuid($value);
                        break;
                    case 'lockingUsername':
                    case 'userName':
                        $value = $this->renderAnonymizedUserName($value);
                        break;
                    default:
                        $value = $this->renderAnonymizedAsEmpty($value);
                        break;
                }
            }
        });
        return $data;
    }
    
    /**
     * anonymize data about OTHER workflow users only
     * and if the other user is NOT the task's PM; 
     * check here:
     * @param string $currentUserGuid the user guid to be used as current user
     * @return boolean
     */
    protected function isCurrentUserOrPM(string $currentUserGuid): bool {
        if ($this->userGuid == $currentUserGuid) {
            return true;
        }
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->taskGuid);
        return $this->userGuid == $task->getPmGuid();
    }
    
    /**
     * renders anonymized value as empty value
     * @param string $value
     * @return string
     */
    protected function renderAnonymizedAsEmpty($value) {
        return '-';
    }
    
    /**
     * renders anonymized comment as markedUp in "comment.phtml"
     * @param string $value
     * @return string
     */
    protected function renderAnonymizedComment($value) {
        // replace author given in <span class="author">xyz</span>
        return $this->commentHelper->renderAnonymizedComment($value);
    }
    
    /**
     * renders an anonymized version of the userguid (= the id of the taskUserTracking-entity or "")
     * @param string $value
     * @return string
     */
    protected function renderAnonymizedUserGuid ($value) {
        $this->taskUserTracking->loadEntry($this->taskGuid, $this->userGuid);
        if(!$this->taskUserTracking->hasEntry()) {
            return "-";
        }
        return $this->taskUserTracking->getId();
    }
    
    /**
     * renders an anonymized version of the username:
     * - "User1", "User2" etc if tracking-data is available
     * - "User" otherwise
     * @param string $value
     * @return string
     */
    protected function renderAnonymizedUserName ($value) {
        $userPrefix = $this->translate->_('Benutzer');
        $this->taskUserTracking->loadEntry($this->taskGuid, $this->userGuid);
        if(!$this->taskUserTracking->hasEntry()) {
            return $userPrefix;
        }
        return $userPrefix . '' . $this->taskUserTracking->getTaskOpenerNumberForUser();
    }
}