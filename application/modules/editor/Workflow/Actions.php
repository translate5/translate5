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

use MittagQI\Translate5\Workflow\ArchiveTaskActions;

/**
 * Encapsulates the Default Actions triggered by the Workflow.
 * Warning: the here listed public methods are called as configured in LEK_workflow_action table!
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
     * ends the task
     */
    public function endTask() {
        $task = $this->config->task;
        
        if($task->isErroneous() || $task->isExclusiveState() && $task->isLocked($task->getTaskGuid())) {
            throw new ZfExtended_Exception('The attached task can not be set to state "end": '.print_r($task->getDataObject(),1));
        }
        $oldTask = clone $task;
        $task->setState($task::STATE_END);

        try {
            $this->config->workflow->hookin()->doWithTask($oldTask, $task);
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
            $tua =editor_Models_Loaders_Taskuserassoc::loadByTask($userGuid,$task);
        }
        else {
            $tua = $this->config->newTua;
        }
        $tua->setStateForStepAndTask($this->config->workflow::STATE_OPEN, $tua->getWorkflowStepName());
    }
    
    /**
     * removes all competitive users in competitive mode
     * @throws ZfExtended_Models_Entity_Conflict
     */
    public function removeCompetitiveUsers() {
        $task = $this->config->task;
        if($task->getUsageMode() !== $task::USAGE_MODE_COMPETITIVE) {
            return;
        }
        
        $userGuid = $this->currentUser()->getUserGuid();
        if(empty($this->config->newTua)) {
            $tua =editor_Models_Loaders_Taskuserassoc::loadByTask($userGuid,$task);
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
    
    /***
     * Set the default deadline date from config for given task user assoc
     * @param editor_Models_TaskUserAssoc $tua
     * @param string $workflowStep
     */
    protected function setDefaultDeadlineDate(editor_Models_TaskUserAssoc &$tua, string $workflowStep) {
        $task = $this->config->task;
        /* @var $task editor_Models_Task */
        
        // check if the order date is set. With empty order data, no deadline date from config is posible
        if(empty($task->getOrderdate()) || is_null($task->getOrderdate())){
            return;
        }
        
        // get the config for the task workflow and the user assoc role workflow step
        $configValue = $task->getConfig()->runtimeOptions->workflow->{$task->getWorkflow()}->{$workflowStep}->defaultDeadlineDate ?? 0;
        if($configValue<=0){
            return;
        }
        $tua->setDeadlineDate(editor_Utils::addBusinessDays($task->getOrderdate(),$configValue));
    }
    
    /***
     * Checks the deadine dates of a task assoc, if it is overdued, it'll be finished for all lectors, triggers normal workflow handlers if needed.
     */
    public function finishOverduedTaskUserAssoc(){
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
        ->where('tua.deadlineDate < CURRENT_DATE');
        $taskRows = $db->fetchAll($s);
        
        foreach($taskRows as $row) {
            $task->loadByTaskGuid($row['taskGuid']);
            //its much easier to load the entity as setting it (INSERT instead UPDATE issue on save, because of internal zend things on initing rows)
            $tua->load($row['id']);
            $workflow->hookin()->doWithTask($task, $task); //nothing changed on task directly, but call is needed
            $tuaNew = clone $tua;
            $tuaNew->setState($workflow::STATE_FINISH);
            $tuaNew->validate();
            $workflow->hookin()->doWithUserAssoc($tua, $tuaNew, function() use ($tuaNew){
                $tuaNew->save();
            });
        }
        $log = ZfExtended_Factory::get('editor_Logger_Workflow', [$task]);
        $log->debug('E1013', 'finish overdued task via workflow action');
    }

    /**
     * Delete all tasks where the task status is 'end',
     * and the last modified date for this task is older than x days (where x is zf_config variable)
     *
     * Parameters docu for the LEK_worker_action table:
     *      - if no filesystem and targetPath configuration is given, task is just deleted without backup!
     *      - A backup configuration may look like (as JSON string the DB then):
     *       {
     *          "filesystem": "local|sftp",
     *              → One of the implemented Adapters in MittagQI\Translate5\Tools\FlysystemFactory
     *          "targetPath": "/target/dir/task-{taskName}.zip"
     *              → the target filename for the task, may contain any field from the task meta json in the exported
     *                file + the magic filed {time} which gives the current time stamp
     *          "other options for filesystem": "just place here too"
     *              → See again FlysystemFactory for the needed options, just to be placed directly in the options object
     *       }
     */
    public function deleteOldEndedTasks(){
        /** @var ArchiveTaskActions $taskActions */
        $taskActions = ZfExtended_Factory::get('\MittagQI\Translate5\Workflow\ArchiveTaskActions', [
            $this->config
        ]);
        $params = $this->config->parameters;
        if(empty($params->filesystem) && empty($params->targetPath)) {
            $taskActions->removeOldTasks();
        }
        else {
            $taskActions->backupThenRemove();
        }
    }
    
    /***
     * Remove old connector usage logs. How old the logs should be is defined in system configuration
     */
    public function removeOldConnectorUsageLog() {
        $log = ZfExtended_Factory::get('editor_Models_LanguageResources_UsageLogger');
        /* @var $log editor_Models_LanguageResources_UsageLogger */
        $log->removeOldLogs();
    }

    /***
     * Send post request to the configured url alongside with task,task user assoc and additional contend as
     * json data
     * @return void
     * @throws Zend_Http_Client_Exception|Zend_Exception
     */
    public function triggerCallbackAction(): void
    {
        $triggerConfig = $this->config->parameters;
        $url = $triggerConfig->url ?? '';
        // set the data parameters from the trigger config if exist
        // this can be used for api authentication
        $data = $triggerConfig->params ?? new stdClass();
        if( empty($url) ){
            return;
        }

        $task = $this->config->task;

        /** @var editor_Models_TaskUserAssoc $assoc */
        $assoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');

        $tua = $assoc->loadAllOfATask($task->getTaskGuid());

        /** @var Zend_Http_Client $http */
        $http = ZfExtended_Factory::get('Zend_Http_Client');
        $http->setUri($url);
        $http->setMethod($http::POST);
        $http->setHeaders('Accept-charset', 'UTF-8');
        $http->setHeaders('Accept', 'application/json; charset=utf-8');

        if( !empty($task)){
            $data->task = $task->getDataObject();
            unset($data->task->lockedInternalSessionUniqId);
            unset($data->task->qmSubsegmentFlags);
        }

        if( !empty($tua)){
            foreach ($tua as &$item) {
                unset($item['staticAuthHash']);
                unset($item['usedInternalSessionUniqId']);
            }
            $data->tua = $tua;

        }

        if(isset($data)){
            $http->setRawData(json_encode($data, JSON_PRETTY_PRINT));
        }
        $response = $http->request();

        //we consider all non 200 status values as invalid and log that!
        if($response->getStatus() !== 200) {
            $task->logger()->warn('E1394', 'All finish of a role callback HTTP status code is {code} instead 200.', [
                'code' => $response->getStatus(),
                'result' => $response->getBody(),
            ]);
        }
    }
}