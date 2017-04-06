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
 * This class provides a general Worker which can be configured with a callback method which.
 * This class is designed for simple workers which dont need a own full blown worker class.
 * 
 * The following parameters are needed: 
 * class → the class which should be instanced on work. Classes with Constructor Parameters are currently not supported!
 * method → the class method which is called on work. The method receives the taskguid and the whole parameters array.
 * 
 * Be careful: This class can not be used in worker_dependencies !
 */
class editor_Models_Import_Worker_SetTaskToOpen extends editor_Models_Import_Worker_Abstract {
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        return empty($parameters);
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->taskGuid);
        
        if ($task->getState() != $task::STATE_IMPORT) {
            return false;
        }
        $task->setState($task::STATE_OPEN);
        $task->save();
        $task->unlock();
        
        $eventManager = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        $eventManager->trigger('importCompleted', $this, array('task' => $task));
        return true;
    }
}