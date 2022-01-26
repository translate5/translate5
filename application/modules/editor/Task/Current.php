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

/**
 * The instance of the currently opened task by this request
 */
class editor_Task_Current extends editor_Models_Task {

    static protected editor_Task_Current $instance;

    public static function getInstance(): self {
        if(empty($instance)) {
            $task = new self;
            $task->_loadInternal(editor_Controllers_Plugins_LoadCurrentTask::getTaskId());
//FIXME error handling if task not given / found
//FIXME security / task access: currently accessibility of a task is checked at the place where it is set into the session.
// Now this must be checked here, either on each request or still on open by putting the opened tasks in a list, and here is just checked against the list
            $task->row->setReadOnly(true);
            self::$instance = $task;
        }
        return self::$instance;
    }

    public function load($id)
    {
        throw new Exception("Current Task can not be loaded manually, there may be only the one defined by the URL.");
    }

    protected function _loadInternal(int $id) {
        return parent::load($id);
    }
}
