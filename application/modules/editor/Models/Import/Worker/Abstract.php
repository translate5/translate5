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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Contains the Import Worker (the scheduling parts)
 * The import process itself is encapsulated in editor_Models_Import_Worker_Import
 */
abstract class editor_Models_Import_Worker_Abstract extends ZfExtended_Worker_Abstract {
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    public function init($taskGuid = NULL, $parameters = array()) {
        $this->task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $ class */
        $this->task->loadByTaskGuid($taskGuid);
        
        if(!$this->task->isErroneous()) {
            return parent::init($taskGuid, $parameters);
        }
        
        //we set the worker to defunct when task has errors
        $wm = $this->workerModel;
        if(isset($wm)){
            $wm->setState($wm::STATE_DEFUNCT);
            $wm->save();
        }
        //if no worker model is set, we don't have to call parent / init a worker model,
        // since we don't even need it in the DB when the task already has errors
        return false;
    }
    
    protected function checkParentDefunc() {
        $parentsOk = parent::checkParentDefunc();
        if(!$parentsOk) {
            $this->task->setErroneous();
        }
        return $parentsOk;
    }
}