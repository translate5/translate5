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
 * Default Workflow Class
 */
class editor_Workflow_Default extends editor_Workflow_Abstract {
    /**
     * internal used name of the workflow
     * @var string
     */
    const WORKFLOW_ID = 'default';
    
    public function __construct() {
        parent::__construct();
        $this->events->addIdentifiers(__CLASS__);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleImport()
     */
    protected function handleImport(){
        $this->doDebug(__FUNCTION__);
        $stepName = (bool) $this->newTask->getEmptyTargets() ? self::STEP_TRANSLATION : self::STEP_LECTORING;
        $this->initWorkflowStep($this->newTask, $stepName);
        $this->newTask->load($this->newTask->getId()); //reload task with new workflowStepName and new calculated workflowStepNr
        $this->callActions(__FUNCTION__, $stepName);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Workflow_Abstract::handleBeforeImport()
     */
    protected function handleBeforeImport(){
        $this->doDebug(__FUNCTION__);
        $this->callActions(__FUNCTION__);
    }

    /**
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleAllFinishOfARole()
     */
    protected function handleAllFinishOfARole() {
        $this->doDebug(__FUNCTION__);
        $userGuid = $this->authenticatedUser->userGuid;
        $newTua = $this->newTaskUserAssoc;
        $taskGuid = $newTua->getTaskGuid();
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        $oldStep = $task->getWorkflowStepName();
        
        //this remains as default behaviour
        $nextStep = $this->getNextStep($this->getStepOfRole($newTua->getRole()));
        if($nextStep) {
            //Next step triggert ebenfalls eine callAction → aber irgendwie so, dass der neue Wert verwendet wird! Henne Ei!
            $this->setNextStep($task, $nextStep);
            $nextRole = $this->getRoleOfStep($nextStep);
            if($nextRole) {
                $newTua->setStateForRoleAndTask(self::STATE_OPEN, $nextRole);
            }
        }
        
        //provide here oldStep, since this was the triggering one. The new step is given to handleNextStep trigger
        $this->callActions(__FUNCTION__, $oldStep, $newTua->getRole(), $newTua->getState());
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleFinish()
     */
    protected function handleFinish() {
        $this->doDebug(__FUNCTION__);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleAllFinish()
     */
    protected function handleAllFinish() {
        $this->doDebug(__FUNCTION__);
    }

    /**
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleEnd()
     */
    protected function handleEnd() {
        $this->newTask->dropMaterializedView();
        $this->doDebug(__FUNCTION__);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleFirstFinishOfARole()
     */
    protected function handleFirstFinishOfARole(){
        $this->doDebug(__FUNCTION__);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleFirstFinish()
     */
    protected function handleFirstFinish(){
        $this->doDebug(__FUNCTION__);
    }
    
    /**
     * reopen an ended task (task-specific reopening in contrast to taskassoc-specific unfinish)
     * 
     * @see editor_Workflow_Abstract::handleReopen()
     */
    protected function handleReopen(){
        $this->newTask->createMaterializedView();
        $this->doDebug(__FUNCTION__);
    }
    
    /**
     * unfinish a finished task (taskassoc-specific unfinish in contrast to task-specific reopening)
     * Set all REVIEWED_UNTOUCHED segments to TRANSLATED
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleUnfinish()
     */
    protected function handleUnfinish(){
        $this->doDebug(__FUNCTION__);
        $newTua = $this->newTaskUserAssoc;
        /* @var $actions editor_Workflow_Actions */
        $this->callActions(__FUNCTION__, $this->newTask->getWorkflowStepName(), $newTua->getRole(), $newTua->getState());
    }
    
        
    /**
     * Loads all not finished, lector Assocs where the task is open and the targetDeliveryDate was overdued yesterday or older
     */
    protected function loadTasksByPastDeliveryDate() {
        //select tua.*,t.targetDeliveryDate from LEK_taskUserAssoc tua, LEK_task t where t.taskGuid = tua.taskGuid and role = 'lector' and targetDeliveryDate < CURRENT_DATE;
        //$s = $this->db->getAdapter()->select()
        $db = Zend_Registry::get('db');
        $s = $db->select()
        ->from(array('tua' => 'LEK_taskUserAssoc'))
        ->join(array('t' => 'LEK_task'), 'tua.taskGuid = t.taskGuid', array())
        ->where('tua.role = ?', self::ROLE_LECTOR)
        ->where('tua.state != ?', self::STATE_FINISH)
        ->where('t.state = ?', editor_Models_Task::STATE_OPEN)
        ->where('targetDeliveryDate < CURRENT_DATE');
        return $db->fetchAll($s);
    }
    
    /**
     * checks the delivery dates, if a task is overdue, it'll be finished for all lectors, triggers normal workflow handlers if needed.
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleCronDaily()
     */
    public function doCronDaily() {
        $this->isCron = true;
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        
        $list = $this->loadTasksByPastDeliveryDate();
        
        //FIXME what does this customer specific code here?
        $acl = ZfExtended_Acl::getInstance();
        $acl->allow('noRights', 'editorconnect_Models_WsdlWrapper', 'sendToUser');
        
        //affected user:
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        foreach($list as $instance) {
            $task->loadByTaskGuid($instance['taskGuid']);
            //its much easier to load the entity as setting it (INSERT instead UPDATE issue on save, because of internal zend things on initing rows)
            $tua->load($instance['id']);
            $user->loadByGuid($instance['userGuid']);
            $this->doWithTask($task, $task); //nothing changed on task directly, but call is needed
            $tuaNew = clone $tua;
            $tuaNew->setState(self::STATE_FINISH);
            $tuaNew->validate();
            $this->triggerBeforeEvents($tua, $tuaNew);
            $tuaNew->save();
            $this->doWithUserAssoc($tua, $tuaNew);
            editor_Models_LogTask::create($instance['taskGuid'], self::STATE_FINISH, $this->authenticatedUserModel, $user);
        }
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Workflow_Abstract::handleUserAssociationAdded()
     */
    protected function handleUserAssociationAdded() {
        $this->doDebug(__FUNCTION__);
        $tua = $this->newTaskUserAssoc;
        if(empty($this->newTask)) {
            $this->newTask = ZfExtended_Factory::get('editor_Models_Task');
            $this->newTask->loadByTaskGuid($tua->getTaskGuid());
        }
        $this->callActions(__FUNCTION__, $this->newTask->getWorkflowStepName(), $tua->getRole(), $tua->getState());
    }

}