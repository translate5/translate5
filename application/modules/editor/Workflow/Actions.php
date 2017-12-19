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
        //FIXME setze die Segmente ebenfalls auf $newTua->getUserGuid als letzten Editor!
        $this->updateAutoStates($this->config->task->getTaskGuid(),'setUntouchedState');
    }
    
    /**
     * sets all segments to initial state - if they were untouched by the user before
     */
    public function segmentsSetInitialState() {
        //FIXME Mit Marc klären, wenn wir oben die $newTua->getUserGuid als letzten Editor setzen, dann auch hier wieder zurücksetzen?
        $this->updateAutoStates($this->config->task->getTaskGuid(),'setInitialStates');
    }
    
    /**
     * Updates the tasks real delivery date to the current timestamp
     */
    public function taskSetRealDeliveryDate() {
        $task = $this->config->task;
        $task->setRealDeliveryDate(date('Y-m-d', $_SERVER['REQUEST_TIME']));
        $task->save();
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
        
        $sourceLang = $task->getSourceLang();
        $targetLang = $task->getTargetLang();
        
        $role = $workflow->getRoleOfStep($stepName);
        if(!$role) {
            return;
        }
        $states = $workflow->getInitialStates();
        $state = $states[$stepName][$role];
        
        $users = $user->loadAllByLanguages($sourceLang, $targetLang);
        foreach($users as $user) {
            $roles = explode(',', $user['roles']);
            $isPm = in_array(ACL_ROLE_PM, $roles);
            $isAdmin = in_array(ACL_ROLE_ADMIN, $roles);
            $isEditor = in_array(ACL_ROLE_EDITOR, $roles);
            if(!$isEditor || $isPm || $isAdmin) {
                continue;
            }
            $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
            /* @var $tua editor_Models_TaskUserAssoc */
            $tua->setRole($role);
            $tua->setState($state);
            $tua->setUserGuid($user['userGuid']);
            $tua->setTaskGuid($task->getTaskGuid());
            //entity version?
            $tua->save();
            $workflow->doUserAssociationAdd($tua);
        }
    }
    
    /**
     * Associates automatically a different PM (The one who starts the import is the default) to the task by the PMs languages
     * @return the new PM user, false if no one found
     */
    public function autoAssociateTaskPm() {
        $task = $this->config->task;
        $workflow = $this->config->workflow;
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
        return;
        $workflow = $this->config->workflow;
        
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        
        $list = $task->loadTasksByPastDeliveryDate();
        
        //TODO: see BEOSPHERE-111
        //re enable it in a better way for beo
        //$acl = ZfExtended_Acl::getInstance();
        //$acl->allow('noRights', 'editorconnect_Models_WsdlWrapper', 'sendToUser');
        
        //affected user:
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        foreach($list as $instance) {
            $task->loadByTaskGuid($instance['taskGuid']);
            //its much easier to load the entity as setting it (INSERT instead UPDATE issue on save, because of internal zend things on initing rows)
            $tua->load($instance['id']);
            $user->loadByGuid($instance['userGuid']);
            $workflow->doWithTask($task, $task); //nothing changed on task directly, but call is needed
            $tuaNew = clone $tua;
            $tuaNew->setState(self::STATE_FINISH);
            $tuaNew->validate();
            $workflow->triggerBeforeEvents($tua, $tuaNew);
            $tuaNew->save();
            $workflow->doWithUserAssoc($tua, $tuaNew);
            editor_Models_LogTask::create($instance['taskGuid'], self::STATE_FINISH, $this->authenticatedUserModel, $user);
        }
    }
    
    
    /**
     * updates all Auto States of this task
     * currently this method supports only updating to REVIEWED_UNTOUCHED and to initial (which is NOT_TRANSLATED and TRANSLATED)
     * @param string $taskGuid
     * @param string $method method to call in editor_Models_Segment_AutoStates
     */
    protected function updateAutoStates(string $taskGuid, string $method) {
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        
        $states = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        /* @var $states editor_Models_Segment_AutoStates */
        
        $states->{$method}($taskGuid, $segment);
    }
}