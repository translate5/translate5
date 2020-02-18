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
 * Encapsulates the Default Actions triggered by the Workflow
 */
class editor_Workflow_Actions extends editor_Workflow_Actions_Abstract {
    /**
     * sets all segments to untouched state - if they are untouched by the user
     */
    public function segmentsSetUntouchedState() {
        $user = $this->currentUser();
        $states = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        /* @var $states editor_Models_Segment_AutoStates */
        $states->setUntouchedState($this->config->task->getTaskGuid(), $user);
    }
    
    /**
     * sets all segments to initial state - if they were untouched by the user before
     */
    public function segmentsSetInitialState() {
        $states = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        /* @var $states editor_Models_Segment_AutoStates */
        $states->setInitialStates($this->config->task->getTaskGuid());
    }
    
    /**
     * Updates the finishDate to the current timestamp for all reviewers
     */
    public function setReviewersFinishDate() {
        $task = $this->config->task;
        $taskAssoc=ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $taskAssoc editor_Models_TaskUserAssoc */
        $taskAssoc->updateReviewersFinishDate($task->getTaskGuid(),NOW_ISO);
    }
    
    /**
     * ends the task
     */
    public function endTask() {
        $task = $this->config->task;
        
        if($task->isErroneous() || $task->isExclusiveState() && $task->isLocked($task->getTaskGuid())) {
            throw new ZfExtended_Exception('The attached task can not be set to state "end": '.print_r($task->getDataObject(),1));
        }
        $oldTask = clone $task;
        $task->setState('end');

        try {
            $this->config->workflow->doWithTask($oldTask, $task);
        }
        catch (ZfExtended_Models_Entity_NoAccessException $e) {
            //ignore no access here. Access may by declined by the called workflow. But this may not block the end via workflow action.
        }
        
        if($oldTask->getState() != $task->getState()) {
            $log = ZfExtended_Factory::get('editor_Logger_Workflow', [$task]);
            $log->debug('E1013', 'task ended via workflow action');
        }
        
        $task->save();
    }
    
    /**
     * Enables the other unconfirmed users in cooperative mode
     */
    public function confirmCooperativeUsers() {
        $task = $this->config->task;
        if($task->getUsageMode() !== $task::USAGE_MODE_COOPERATIVE) {
            return;
        }
        $userGuid = $this->currentUser()->getUserGuid();
        if(empty($this->config->newTua)) {
            $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
            /* @var $tua editor_Models_TaskUserAssoc */
            $tua->loadByParams($userGuid, $task->getTaskGuid());
        }
        else {
            $tua = $this->config->newTua;
        }
        $tua->setStateForRoleAndTask($this->config->workflow::STATE_OPEN, $tua->getRole());
    }
    
    /**
     * @throws ZfExtended_Models_Entity_Conflict
     */
    public function removeCompetitiveUsers() {
        $task = $this->config->task;
        if($task->getUsageMode() !== $task::USAGE_MODE_COMPETITIVE) {
            return;
        }
        
        $userGuid = $this->currentUser()->getUserGuid();
        if(empty($this->config->newTua)) {
            $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
            /* @var $tua editor_Models_TaskUserAssoc */
            $tua->loadByParams($userGuid, $task->getTaskGuid());
        }
        else {
            $tua = $this->config->newTua;
        }
        $deleted = $tua->deleteOtherUsers($task->getTaskGuid(), $userGuid, $tua->getRole());
        if($deleted !== false) {
            $notifier = ZfExtended_Factory::get('editor_Workflow_Notification');
            /* @var $notifier editor_Workflow_Notification */
            $notifier->init($this->config);
            $notifier->notifyCompetitiveDeleted(['deleted' => $deleted, 'currentUser' => $this->currentUser()->getDataObject()]);
            return;
        }
        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1160' => 'The competitive users can not be removed, probably some other user was faster and you are not assigned anymore to that task.'
        ]);
        throw ZfExtended_Models_Entity_Conflict::createResponse('E1160', [
            'noField' => 'Die anderen Benutzer können nicht aus der Aufgabe entfernt werden, eventuell war ein anderer Benutzer schneller und hat Sie aus der Aufgabe entfernt.'
        ]);
    }
    
    /**
     * Associates automatically editor users to the task by users languages
     */
    public function autoAssociateEditorUsers() {
        $task = $this->config->task;
        $workflow = $this->config->workflow;
        $stepName = $task->getWorkflowStepName();
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        
        $user->loadByGuid($task->getPmGuid());
        $pmId = $user->getId();
        $aclInstance = ZfExtended_Acl::getInstance();
        $roles = $user->getRoles();
        $pmSeeAll = !empty($roles) && $aclInstance->isInAllowedRoles($roles, 'backend', 'seeAllUsers');
        
        $sourceLang = $task->getSourceLang();
        $targetLang = $task->getTargetLang();
        
        $role = $workflow->getRoleOfStep($stepName);
        if(!$role) {
            return;
        }
        $states = $workflow->getInitialStates();
        $state = $states[$stepName][$role];
        
        $users = $user->loadAllByLanguages($sourceLang, $targetLang);
        
        
        foreach($users as $data) {
            $roles = explode(',', $data['roles']);
            $isPm = in_array(ACL_ROLE_PM, $roles);
            $isAdmin = in_array(ACL_ROLE_ADMIN, $roles);
            $isEditor = in_array(ACL_ROLE_EDITOR, $roles);
            //the user to be added must be a editor and it must be visible for the pm of the task
            $isVisible = $pmSeeAll || $user->hasParent($pmId, $data['parentIds']);
            if(!$isEditor || $isPm || $isAdmin || !$isVisible) {
                continue;
            }
            $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
            /* @var $tua editor_Models_TaskUserAssoc */
            $tua->setRole($role);
            $tua->setState($state);
            $tua->setUserGuid($data['userGuid']);
            $tua->setTaskGuid($task->getTaskGuid());
            //entity version?
            $tua->save();
            $workflow->doUserAssociationAdd($tua);
        }
    }
    
    /**
     * Associates automatically a different PM (The one who starts the import is the default) to the task by the PMs languages
     * @return ZfExtended_Models_User|false the new PM user, false if no one found
     */
    public function autoAssociateTaskPm() {
        $task = $this->config->task;
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        
        $sourceLang = $task->getSourceLang();
        $targetLang = $task->getTargetLang();
        $users = $user->loadAllByLanguages($sourceLang, $targetLang);
        
        foreach($users as $userData) {
            $roles = explode(',', $userData['roles']);
            if(!in_array(ACL_ROLE_PM, $roles)) {
                continue;
            }
            $user->init($userData);
            $task->setPmGuid($user->getUserGuid());
            $task->setPmName($user->getUsernameLong());
            $task->save();
            return $user;
        }
        return false;
    } 
    
    /***
     * Checks the delivery dates, if a task is overdue, it'll be finished for all lectors, triggers normal workflow handlers if needed.
     */
    public function finishOverduedTasks(){
        $workflow = $this->config->workflow;
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        
        $db = Zend_Registry::get('db');
        
        $s = $db->select()
        ->from(array('tua' => 'LEK_taskUserAssoc'))
        ->join(array('t' => 'LEK_task'), 'tua.taskGuid = t.taskGuid', array())
        ->where('tua.role = ?', $workflow::ROLE_REVIEWER)
        ->where('tua.state != ?', $workflow::STATE_FINISH)
        ->where('t.state = ?', $workflow::STATE_OPEN)
        ->where('targetDeliveryDate < CURRENT_DATE');
        $taskRows = $db->fetchAll($s);
        
        foreach($taskRows as $row) {
            $task->loadByTaskGuid($row['taskGuid']);
            //its much easier to load the entity as setting it (INSERT instead UPDATE issue on save, because of internal zend things on initing rows)
            $tua->load($row['id']);
            $workflow->doWithTask($task, $task); //nothing changed on task directly, but call is needed
            $tuaNew = clone $tua;
            $tuaNew->setState($workflow::STATE_FINISH);
            $tuaNew->validate();
            $workflow->triggerBeforeEvents($tua, $tuaNew);
            $tuaNew->save();
            $workflow->doWithUserAssoc($tua, $tuaNew);
        }
        $log = ZfExtended_Factory::get('editor_Logger_Workflow', [$task]);
        $log->debug('E1013', 'finish overdued task via workflow action');
    }
    
    /***
     * Delete all tasks where the task status is 'end',
     * and the last modified date for this task is older than x days (where x is zf_config variable)
     */
    public function deleteOldEndedTasks(){
        $taskModel=ZfExtended_Factory::get('editor_Models_Task');
        /* @var $taskModel editor_Models_Task */
        $taskModel->removeOldTasks();
    }
}