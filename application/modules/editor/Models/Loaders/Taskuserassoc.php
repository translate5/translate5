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
 * Important: If a TUA can not be loaded (for example if the tua role does not match a workflow step) then such a user proceeds as PM override user!
 */
class editor_Models_Loaders_Taskuserassoc {
    
    /**
     * Loads single assoc for given userGuid and task, forced to the role matching the workflow step
     * The function will throw ZfExtended_Models_Entity_NotFoundException when the taskuserassoc entity does not exist.
     * @param string $userGuid
     * @param editor_Models_Task $task
     * @return editor_Models_TaskUserAssoc
     */
    public static function loadByTaskForceWorkflowRole(string $userGuid, editor_Models_Task $task) {
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        //load the user assoc of the curent available workflow role
        $tua->loadByStep($userGuid, $task->getTaskGuid(), $task->getWorkflowStepName());
        return $tua;
    }
    
    /**
     * Load single assoc for given userGuid and taskGuid, forced to the role matching the workflow step
     * @param string $userGuid
     * @param string $taskGuid
     * @return editor_Models_TaskUserAssoc
     */
    public static function loadByTaskGuidForceWorkflowRole(string $userGuid,string $taskGuid) {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        return self::loadByTaskForceWorkflowRole($userGuid, $task);
    }
    
    /***
     * Return the most appropriate task user assoc result
     * Highest rated result is the user job of the current task workflow step.
     * If no matching workflow job is found, the first next user job for this task will be returned.
     * The result state order(when no workflow job is found) id:
     *   edit
     *   view
     *   unconfirmed
     *   open
     *   waiting
     *   finished
     * @param string $userGuid
     * @param editor_Models_Task $task
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @return editor_Models_TaskUserAssoc
     */
    public static function loadByTask(string $userGuid, editor_Models_Task $task){
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        $tua->loadByStepOrSortedState($userGuid, $task->getTaskGuid(), $task->getWorkflowStepName());
        
        return $tua;
    }
    
    /**
     * Return the most appropriate task user assoc result
     * Highest rated result is the user job of the current task workflow step.
     * If no matching workflow job is found, the first next user job for this task will be returned.
     * The result state order(when no workflow job is found) id:
     *   edit
     *   view
     *   unconfirmed
     *   open
     *   waiting
     *   finished
     * @param string $userGuid
     * @param string $taskGuid
     * @return editor_Models_TaskUserAssoc
     */
    public static function loadByTaskGuid(string $userGuid,string $taskGuid){
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        return self::loadByTask($userGuid, $task);
    }
}
