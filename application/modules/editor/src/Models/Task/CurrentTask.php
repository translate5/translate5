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

namespace MittagQI\Translate5\Models\Task;

/**
 * The instance of the currently opened task by this request
 */
class CurrentTask extends \editor_Models_Task {

    static protected CurrentTask $instance;

    /**
     * returns true if a current task is provided in the URI and if it is accessible
     * @return bool
     */
    public static function isProvided(): bool {
        //FIXME also check access as defined in getInstance
        return !is_null(\editor_Controllers_Plugins_LoadCurrentTask::getTaskId());
    }

    public static function getInstance(): self {
        if(empty($instance)) {
            $task = new self;
            $task->_loadInternal(\editor_Controllers_Plugins_LoadCurrentTask::getTaskId());
//FIXME error handling if task not given / found
//FIXME security / task access: currently accessibility of a task is checked at the place where it is set into the session.
// Now this must be checked here, either on each request or still on open by putting the opened tasks in a list, and here is just checked against the list
// if it is not in the list, do the security / access check
// FIXME FIXME since access check is on controller level, it should remain there, so better put the access check into the LoadCurrentTask Plugin instead here into the model?

//IDEA: to fix that easily: we store the correctly opened taskIds in a list in the session.
// So we know this session has opened that task (if we do not do that, we have instead to check on each request the access!)
// If the requested task is not in the list, we must return a value so that the load against taskController is triggered from UI

//FIXME if a task is access via URL, it must be opened if not opened for that user already
// depending on the show back to overview config, the user should either remain on the page and get a task not accessible info or if go back is provided a openAdministration should be peformed.
// must be implemented in the app.js
            $task->row->setReadOnly(true);
            self::$instance = $task;
        }
        return self::$instance;
    }

    /**
     * @throws \Exception
     */
    public function load($id)
    {
        throw new \Exception("Current Task can not be loaded manually, there may be only the one defined by the URL.");
    }

    protected function _loadInternal(int $id): ?\Zend_Db_Table_Row_Abstract
    {
        return parent::load($id);
    }
}
