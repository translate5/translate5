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

/**
 * Class for anonymizing workflows.
 * Set for each task; see anonymizeUsers() in editor_Models_Task.
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
     * anonymizes all user-related data of other workflow users in $data
     * @param string $taskGuid
     * @param array $data
     * @return array
     */
    public function anonymizeUserdata($taskGuid, array $data) {
        // anonymize data about OTHER workflow users only: must be checked on the level of the values
        // (eg user-data in TrackChange-Tags is completely independent from anything given here)
        $userGuid = $data['userGuid'] ?? '';
        $lockingUser = $data['lockingUser'] ?? '';
        $keysToAnonymize = ['comments','firstName','lockingUser','lockingUsername','login','userGuid','userName','surName','targetEdit'];
        array_walk($data, function( &$value, $key) use ($keysToAnonymize, $lockingUser, $taskGuid, $userGuid) {
            if ($value != '' && in_array($key, $keysToAnonymize)) {
                switch ($key) {
                    case 'comments':
                        $value = $this->renderAnonymizedComment($value, $userGuid);
                        break;
                    case 'lockingUsername':
                        $value = $this->renderAnonymizedUserName($value, $lockingUser, $taskGuid);
                        break;
                    case 'targetEdit':
                        $value = $this->renderAnonymizedTargetEdit($value, $taskGuid);
                        break;
                    case 'userName':
                        $value = $this->renderAnonymizedUserName($value, $userGuid, $taskGuid);
                        break;
                    default:
                        $value = $this->renderAnonymizedAsEmpty($value, $userGuid);
                        break;
                }
            }
        });
        return $data;
    }
    
    /**
     * anonymize data about OTHER workflow users only; check here:
     * @param string $userGuid
     * @return boolean
     */
    public function isOtherWorkflowUser($userGuid) {
        return $userGuid !== $this->sessionUserGuid;
    }
    
    /**
     * renders anonymized value as empty value
     * (anonymizes data about OTHER workflow users only)
     * @param string $value
     * @param string $userGuid
     * @return string
     */
    protected function renderAnonymizedAsEmpty($value, $userGuid) {
        if (!$this->isOtherWorkflowUser($userGuid)) {
            return $value;
        }
        return '';
    }
    
    /**
     * renders anonymized comment as markedUp in "comment.phtml"
     * (anonymizes data about OTHER workflow users only)
     * @param string $value
     * @param string $userGuid
     * @return string
     */
    protected function renderAnonymizedComment($value, $userGuid) {
        if (!$this->isOtherWorkflowUser($userGuid)) {
            return $value;
        }
        // replace author given in <span class="author">xyz</span>
        return $this->commentHelper->renderAnonymizedComment($value);
    }
    
    /**
     * renders an anonymized version of the username:
     * - "User1", "User2" etc if tracking-data is available
     * - "User" otherwise
     * (anonymizes data about OTHER workflow users only)
     * @param string $value
     * @param string $userGuid
     * @param string $taskGuid
     * @return string
     */
    public function renderAnonymizedUserName ($value, $userGuid, $taskGuid) {
        if (!$this->isOtherWorkflowUser($userGuid)) {
            return $value;
        }
        $userPrefix = $this->translate->_('Benutzer');
        $this->taskUserTracking->loadEntry($taskGuid, $userGuid);
        if(is_null($this->taskUserTracking->getTaskOpenerNumberForUser())) {
            return $userPrefix;
        }
        return $userPrefix . '' . $this->taskUserTracking->getTaskOpenerNumberForUser();
    }
    
    /**
     * renders the real username of the given anonymized username (= as anonymized by renderAnonymizedUserName()!)
     * @param string $taskGuid
     * @param string $userNameAnon
     * @param string $taskGuid
     * @return string
     */
    public function renderUnanonymizedUserName($value, $userNameAnon, $taskGuid) {
        $userPrefix = $this->translate->_('Benutzer');
        $taskOpenerNumber = substr($userNameAnon, strlen($userPrefix));
        if(!filter_var($taskOpenerNumber, FILTER_VALIDATE_INT)){
            return $userNameAnon;
        }
        $this->taskUserTracking->loadEntryByTaskOpenerNumber($taskGuid, $taskOpenerNumber);
        if(is_null($this->taskUserTracking->getUsernameForUser())) {
            return $userNameAnon;
        }
        return $this->taskUserTracking->getUsernameForUser();
    }
    
    /**
     * renders anonymized target
     * @param string $value
     * @param string $taskGuid
     * @return string
     */
    protected function renderAnonymizedTargetEdit($value, $taskGuid) {
        // replace data-userguid und data-username in TrackChanges:
        return $this->trackChangeTagHelper->renderAnonymizedTrackChangeData($value, $taskGuid);
    }
}