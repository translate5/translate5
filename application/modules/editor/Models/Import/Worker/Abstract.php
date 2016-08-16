<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Contains the Import Worker (the scheduling parts)
 * The import process itself is encapsulated in editor_Models_Import_Worker_Import
 */
abstract class editor_Models_Import_Abstract extends ZfExtended_Worker_Abstract {
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
        $wm = $this->workerModel;
        if(isset($wm)){
            $this->workerModel->setState($wm::STATE_DEFUNCT);
            $this->save();
        }
        //if no worker model is set, we don't have to call parent / init a worker model,
        // since we don't even need it in the DB when the task already has errors
        return false;
    }
}