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
     * Removes a task completly from translate5 if task is not locked and therefore removable
     */
    public function remove() {
        $taskGuid = $this->task->getTaskGuid();
        if(empty($taskGuid)) {
            return false;
        }
        $this->checkRemovable();
        $this->removeDataDirectory();
        $this->removeRelatedDbData();
        $this->task->delete();
    }
    
    /**
     * Removes a task from translate5 regardless of its task and locking state
     * @param boolean $removeFiles optional, per default true, data directory is removed, if false data directory remains on disk
     */
    public function removeForced($removeFiles = true) {
        $taskGuid = $this->task->getTaskGuid();
        if(empty($taskGuid)) {
            return false;
        }
        //tries to lock the task, but delete it regardless if could be locked or not.
        $this->task->lock(NOW_ISO, true);
        
        if($removeFiles) {
            $this->removeDataDirectory();
        }
        $this->removeRelatedDbData();
        $this->task->delete();
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
        
        $e = new ZfExtended_BadMethodCallException();
        $e->setLogging(false);
        
        if($this->task->isUsed($taskGuid)) {
            $e->setMessage("Die Aufgabe wird von einem Benutzer benutzt", true);
            throw $e;
        }
        
        if($this->task->isLocked($taskGuid) && !$this->task->isErroneous()) {
            $e->setMessage("Die Aufgabe ist durch einen Benutzer gesperrt", true);
            throw $e; 
        }
        
        if(!$this->task->lock(NOW_ISO, true)) {
            throw new ZfExtended_Models_Entity_Conflict();
        }
        return true;
    }
    
    /**
     * drops the tasks Materialized View and deletes several data (segments, terms, file entries)
     * All mentioned data has foreign keys to the task, to reduce locks while deletion this 
     * data is deleted directly instead of relying on referential integrity. 
     */
    protected function removeRelatedDbData() {
        //@todo ask marc if logging tables should also be deleted (no constraint is set)
        
        $this->task->dropMaterializedView();
        $taskGuid = $this->task->getTaskGuid();
        
        $segmentTable = ZfExtended_Factory::get('editor_Models_Db_Segments');
        $segmentTable->delete(array('taskGuid = ?' => $taskGuid));
        
        $filesTable = ZfExtended_Factory::get('editor_Models_Db_Files');
        $filesTable->delete(array('taskGuid = ?' => $taskGuid));
    }
}
