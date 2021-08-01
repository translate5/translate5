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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Task Remover - on task deletion several things should happen, this is all encapsulated in this class
 */
class editor_Models_Task_Remover {
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * Sets the task to be removed from system
     * @param editor_Models_Task $task
     */
    public function __construct(editor_Models_Task $task) {
        $this->task = $task;
    }
    
    /**
     * Removes a task completely from translate5 if task is not locked and therefore removable
     */
    public function remove($forced = false) {
        $taskGuid = $this->task->getTaskGuid();
        $projectId = $this->task->getProjectId();
        $isProject = $this->task->isProject();
        if(empty($taskGuid)) {
            return false;
        }
        if(!$isProject){
            $this->removeTask($forced);
        }else{
            $this->removeProject($projectId,$forced,true);
        }

        // on import error project may not be created:
        if(!is_null($projectId)) {
            $this->cleanupProject($projectId);
        }
    }
    
    /**
     * Removes a task from translate5 regardless of its task and locking state
     * @param bool $removeFiles optional, per default true, data directory is removed, if false data directory remains on disk
     */
    public function removeForced($removeFiles = true) {
        $taskGuid = $this->task->getTaskGuid();
        $projectId = $this->task->getProjectId();
        $isProject = $this->task->isProject();
        if(empty($taskGuid)) {
            return false;
        }
        //tries to lock the task, but delete it regardless if could be locked or not.
        $this->task->lock(NOW_ISO);

        if(!$isProject){
            $this->removeTask(true,$removeFiles);
        }else{
            $this->removeProject($projectId,true,$removeFiles);
        }

        // on import error project may not be created:
        if(!is_null($projectId)) {
            $this->cleanupProject($projectId);
        }
    }

    /***
     * Remove the current loaded task. The task data on the disk will be removed by default ($removeFiles). To disable this set $removeFiles to false.
     * @param false $forced
     * @param true $removeFiles: should the task files be removed
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_Conflict
     */
    protected function removeTask(bool $forced = false, bool $removeFiles = true){
        if(!$forced) {
            $this->checkRemovable();
        }
        if($removeFiles){
            $this->removeDataDirectory();
        }
        $this->removeRelatedDbData();
        $this->task->delete();
    }

    /***
     * Remove project and all of his tasks and related data
     *
     * @param int $projectId
     * @param bool $forced
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_Conflict
     */
    protected function removeProject(int $projectId, bool $forced,bool $removeFiles){
        $model=ZfExtended_Factory::get('editor_Models_Task');
        /* @var $model editor_Models_Task */
        $tasks=$model->loadProjectTasks($projectId);
        $tasks = array_reverse($tasks);
        foreach ($tasks as $projectTask){
            $this->task->init($projectTask);
            $this->removeTask($forced,$removeFiles);
        }
    }

    /**
     * Remove the project if there are no tasks in the project
     * @param int $projectId
     */
    protected function cleanupProject(int $projectId) {
        $model=ZfExtended_Factory::get('editor_Models_Task');
        /* @var $model editor_Models_Task */
        $tasks=$model->loadProjectTasks($projectId);
        if(count($tasks)>1 || empty($tasks)){
            return;
        }
        $task=reset($tasks);
        if($task['taskType']!=$model::INITIAL_TASKTYPE_PROJECT){
            return;
        }
        $this->task->load($projectId);
        $this->remove(true);
    }
    
    /**
     * removes the tasks data directory from filesystem
     */
    protected function removeDataDirectory() {
        //also delete files on default delete
        $taskPath = (string)$this->task->getAbsoluteTaskDataPath();
        if(is_dir($taskPath)){
            /* @var $recursivedircleaner ZfExtended_Controller_Helper_Recursivedircleaner */
            $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
                'Recursivedircleaner'
            );
            $recursivedircleaner->delete($taskPath);
        }
    }

    /**
     * internal function with stuff to be excecuted before deleting a task
     */
    protected function checkRemovable() {
        $taskGuid = $this->task->getTaskGuid();
        
        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1042' => 'The task can not be removed due it is used by a user.',
            'E1043' => 'The task can not be removed due it is locked by a user.',
            'E1044' => 'The task can not be locked for deletion.',
        ]);
        
        if($this->task->isUsed($taskGuid)) {
            throw ZfExtended_Models_Entity_Conflict::createResponse('E1042', [
                'Die Aufgabe wird von einem Benutzer benutzt, und kann daher nicht gelöscht werden.'
            ], ['task' => $this->task]);
        }
        
        if($this->task->isLocked($taskGuid) && !$this->task->isErroneous()) {
            throw ZfExtended_Models_Entity_Conflict::createResponse('E1043', [
                'Die Aufgabe ist durch einen Benutzer gesperrt, und kann daher nicht gelöscht werden.'
            ], ['task' => $this->task]);
        }
        
        if(!$this->task->lock(NOW_ISO)) {
            //this should not happen, therefore it is not translated
            throw new ZfExtended_Models_Entity_Conflict('E1044', ['task' => $this->task]);
        }
        return true;
    }
    
    /**
     * drops the tasks Materialized View and deletes several data (segments, terms, file entries)
     * All mentioned data has foreign keys to the task, to reduce locks while deletion this
     * data is deleted directly instead of relying on referential integrity.
     * Also removes the task related term collection
     */
    protected function removeRelatedDbData() {
        //@todo ask marc if logging tables should also be deleted (no constraint is set)
        
        $this->task->dropMaterializedView();
        $taskGuid = $this->task->getTaskGuid();
        
        $segmentTable = ZfExtended_Factory::get('editor_Models_Db_Segments');
        $segmentTable->delete(array('taskGuid = ?' => $taskGuid));
        
        $filesTable = ZfExtended_Factory::get('editor_Models_Db_Files');
        $filesTable->delete(array('taskGuid = ?' => $taskGuid));

        $termcollection=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $termcollection editor_Models_TermCollection_TermCollection  */
        $termcollection->checkAndRemoveTaskImported($this->task->getTaskGuid());
        
    }
}
