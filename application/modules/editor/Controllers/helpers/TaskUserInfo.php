<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
			 https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Helper to fill up a data object containing a task with user infos
 */
class Editor_Controller_Helper_TaskUserInfo extends Zend_Controller_Action_Helper_Abstract {
    /**
     * Cached map of userGuids and taskGuid to userNames
     * @var array
     */
    protected $cachedUserInfo = array();
    
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager;
    
    /**
     * @var editor_Workflow_Abstract
     */
    protected $workflow;
    
    /**
     * The entity instance in the controller
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var editor_Models_TaskUserTracking
     */
    protected $userTracking;
    
    /**
     * @var editor_Workflow_Anonymize
     */
    protected $workflowAnonymize;
    
    /**
     * @var array
     */
    protected $allAssocInfos = [];
    
    /**
     * @var array
     */
    protected $userAssocInfos = [];
    
    public function init() {
        $this->workflowAnonymize = ZfExtended_Factory::get('editor_Workflow_Anonymize');
        $this->userTracking = ZfExtended_Factory::get('editor_Models_TaskUserTracking');
    }
    
    public function initForTask(editor_Workflow_Abstract $workflow, editor_Models_Task $task) {
        $this->task = $task;
        $this->workflow = $workflow;
    }
    
    /**
     * Adds additional user based infos to the given array.
     * If the given taskguid is assigned to a client for anonymizing data, the added user-data is anonymized already.
     * @param array $row gets the row to modify as reference
     */
    public function addUserInfos(array &$row, $isEditAll, $givenUserState = null) {
        $taskguid = $row['taskGuid'];
        //Add actual User Assoc Infos to each Task
        if(isset($this->userAssocInfos[$taskguid])) {
            $row['userRole'] = $this->userAssocInfos[$taskguid]['role'];
            $row['userState'] = $this->userAssocInfos[$taskguid]['state'];
            $row['userStep'] = $this->workflow->getStepOfRole($row['userRole']);
        }
        elseif($isEditAll && !empty($givenUserState)) {
            $row['userState'] = $givenUserState; //returning the given userState for usage in frontend
        }
        
        //Add all User Assoc Infos to each Task
        if(isset($this->allAssocInfos[$taskguid])) {
            $reducer = function($accu, $item) {
                return $accu || !empty($item['usedState']);
            };
            $row['isUsed'] = array_reduce($this->allAssocInfos[$taskguid], $reducer, false);
            $row['users'] = $this->allAssocInfos[$taskguid];
        }
        
        $row['lockingUsername'] = null;
        
        if(!empty($row['lockingUser'])){
            $row['lockingUsername'] = $this->getUsername($this->getUserinfo($row['lockingUser'],$taskguid));
        }
        
        $fields = ZfExtended_Factory::get('editor_Models_SegmentField');
        /* @var $fields editor_Models_SegmentField */
        
        $userPref = ZfExtended_Factory::get('editor_Models_Workflow_Userpref');
        /* @var $userPref editor_Models_Workflow_Userpref */
        
        //we load alls fields, if we are in taskOverview and are allowed to edit all
        // or we have no userStep to filter / search by.
        // No userStep means indirectly that we do not have a TUA (pmCheck)
        if(!$this->task->isRegisteredInSession() && $isEditAll || empty($row['userStep'])) {
            $row['segmentFields'] = $fields->loadByTaskGuid($taskguid);
            //the pm sees all, so fix userprefs
            $userPref->setNotEditContent(false);
            $userPref->setAnonymousCols(false);
            $userPref->setVisibility($userPref::VIS_SHOW);
            $allFields = array_map(function($item) {
                return $item['name'];
            }, $row['segmentFields']);
                $userPref->setFields(join(',', $allFields));
        } else {
            $user = new Zend_Session_Namespace('user');
            $userPref->loadByTaskUserAndStep($taskguid, $this->workflow::WORKFLOW_ID, $user->data->userGuid, $row['userStep']);
            $row['segmentFields'] = $fields->loadByUserPref($userPref);
        }
        
        $row['userPrefs'] = array($userPref->getDataObject());
        $row['notEditContent'] = (bool)$row['userPrefs'][0]->notEditContent;
        
        $config = Zend_Registry::get('config');
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        foreach($row['segmentFields'] as &$field) {
            //TRANSLATE-318: replacing of a subpart of the column name is a client specific feature
            $needle = $config->runtimeOptions->segments->fieldMetaIdentifier;
            if(!empty($needle)) {
                $field['label'] = str_replace($needle, '', $field['label']);
            }
            $field['label'] = $translate->_($field['label']);
        }
        if(empty($this->segmentFieldManager)) {
            $this->segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        }
        //sets the information if this task has default segment field layout or not
        $row['defaultSegmentLayout'] = $this->segmentFieldManager->isDefaultLayout(array_map(function($field){
            return $field['name'];
        }, $row['segmentFields']));
            
        $this->handleUserTracking($row);
    }
    
    /**
     * Applies the user anonymising rules to the data where it is needed
     * @param array $row
     * @param string $taskGuid
     */
    protected function handleUserTracking(array &$row) {
        $taskGuid = $row['taskGuid'];
        /* @var $userTracking editor_Models_TaskUserTracking */
        $row['userTracking'] = $this->userTracking->getByTaskGuid($taskGuid);
        
        // anonymize userinfo?
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        if($this->task->getTaskGuid() != $taskGuid){
            $this->task->init($row);
        }
        
        if (!$this->task->anonymizeUsers()) {
            return;
        }
        
        /* @var $workflowAnonymize editor_Workflow_Anonymize */
        if(!empty($row['lockingUser'])) {
            $row = $this->workflowAnonymize->anonymizeUserdata($taskGuid, $row['lockingUser'], $row);
        }
        if(!empty($row['userTracking'])) {
            foreach ($row['userTracking'] as &$rowTrack) {
                $rowTrack = $this->workflowAnonymize->anonymizeUserdata($taskGuid, $rowTrack['userGuid'], $rowTrack);
            }
        }
        if(!empty($row['users'])) {
            foreach ($row['users'] as &$rowUser) {
                $rowUser = $this->workflowAnonymize->anonymizeUserdata($taskGuid, $rowUser['userGuid'], $rowUser);
            }
        }
    }
    
    /**
     * Fetch an array with Task User Assoc Data for the currently logged in User.
     * Returns an array with an entry for each task, key is the taskGuid
     * @return array returns the assoc infos to the current user
     */
    public function initUserAssocInfos(array $taskRawObjects) {
        $taskGuids = array_column($taskRawObjects, 'taskGuid');
        $currentWorkflowRoles = $this->getTasksCurrentWorkflowRoles($taskRawObjects);
        $this->userAssocInfos = []; //collects the assoc infos to the current user
        $userAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $userAssoc editor_Models_TaskUserAssoc */
        $user = new Zend_Session_Namespace('user');
        $userGuid = $user->data->userGuid;
        $assocs = $userAssoc->loadByTaskGuidList($taskGuids);
        $this->allAssocInfos = [];
        foreach($assocs as $assoc) {
            if(!isset($this->allAssocInfos[$assoc['taskGuid']])) {
                $this->allAssocInfos[$assoc['taskGuid']] = array();
            }
            //since a user can be assigned multiple times to a task,
            // the role has also to be checked to determine the current user
            $role = $currentWorkflowRoles[$assoc['taskGuid']] ?? '';
            
            //we need an info about the current user in any case, so we init the userAssocInfos with the first assoc of the current user
            // but we override the already stored userAssocInfo if a later assoc has the matching role
            $firstCurrentUserAssoc = empty($this->userAssocInfos[$assoc['taskGuid']]);
            if($userGuid == $assoc['userGuid'] && ($firstCurrentUserAssoc || $role == $assoc['role'])) {
                $this->userAssocInfos[$assoc['taskGuid']] = $assoc;
            }
            $userInfo = $this->getUserinfo($assoc['userGuid'], $assoc['taskGuid']);
            $assoc['userName'] = $userInfo['surName'].', '.$userInfo['firstName'];
            $assoc['login'] = $userInfo['login'];
            //set only not pmOverrides
            if(empty($assoc['isPmOverride'])) {
                $this->allAssocInfos[$assoc['taskGuid']][] = $assoc;
            }
        }
        $userSorter = function($first, $second){
            if($first['userName'] > $second['userName']) {
                return 1;
            }
            if($first['userName'] < $second['userName']) {
                return -1;
            }
            return 0;
        };
        foreach($this->allAssocInfos as $taskGuid => $taskUsers) {
            usort($taskUsers, $userSorter);
            $this->allAssocInfos[$taskGuid] = $taskUsers;
        }
        return $this->userAssocInfos;
    }
    
    /**
     * returns a mapping between task and the workflow role to the tasks workflow step
     * @return array
     */
    protected function getTasksCurrentWorkflowRoles($taskRawObjects): array {
        $result = [];
        $wfRoleCache = [];
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wfm editor_Workflow_Manager */
        foreach($taskRawObjects as $taskObj) {
            $taskObj = (object) $taskObj;
            $key = $taskObj->workflow.'#'.$taskObj->workflowStepName;
            if(empty($wfRoleCache[$key])) {
                $workflow = $wfm->getCached($taskObj->workflow);
                $wfRoleCache[$key] = $workflow->getRoleOfStep($taskObj->workflowStepName);
            }
            $result[$taskObj->taskGuid] = $wfRoleCache[$key];
        }
        return $result;
    }
    
    /**
     * returns the username for the given userGuid.
     * Doing this on client side would be possible, but then it must be ensured that UsersStore is always available and loaded before TaskStore.
     * @param string $userGuid
     * @param string $taskGuid
     * @return array
     */
    protected function getUserinfo($userGuid, $taskGuid) {
        $notfound = array(); //should not be, but can occur after migration of old data!
        if(empty($userGuid)) {
            return $notfound;
        }
        if(isset($this->cachedUserInfo[$userGuid])) {
            // cache for user
            return $this->cachedUserInfo[$userGuid];
        }
        if(empty($this->tmpUserDb)) {
            $this->tmpUserDb = ZfExtended_Factory::get('ZfExtended_Models_Db_User');
            /* @var $this->tmpUserDb ZfExtended_Models_Db_User */
        }
        $s = $this->tmpUserDb->select()->where('userGuid = ?', $userGuid);
        $row = $this->tmpUserDb->fetchRow($s);
        if(!$row) {
            return $notfound;
        }
        $userInfo = $row->toArray();
        
        $this->cachedUserInfo[$userGuid] = $userInfo;
        return $userInfo;
    }
    
    /**
     * returns the commonly used username: Firstname Lastname (login)
     * @param array $userinfo
     */
    protected function getUsername(array $userinfo) {
        if(empty($userinfo)) {
            return '- not found -'; //should not be, but can occur e.g. after migration of old data or for lockingUsername
        }
        return $userinfo['firstName'].' '.$userinfo['surName'].' ('.$userinfo['login'].')';
    }
}
