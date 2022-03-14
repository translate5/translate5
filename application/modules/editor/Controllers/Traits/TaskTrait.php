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
 * provides functionality to fetch the task either via param "taskGuid" first and then as fallback via session
 */
trait editor_Controllers_Traits_TaskTrait {

    /**
     * Retrieves the relevant task either by request param "taskGuid" or session
     * @return editor_Models_Task
     * @throws ZfExtended_NotAuthenticatedException
     */
    protected function fetchTask() : editor_Models_Task {
        $taskGuid = $this->fetchTaskGuid();
        $task = editor_ModelInstances::taskByGuid($taskGuid);
        return $task;
    }
    /**
     * Retrieves the relevant taskGuid either by request param "taskGuid" or session
     * @return string
     * @throws ZfExtended_NotAuthenticatedException
     */
    protected function fetchTaskGuid() : string {
        $taskGuid = $this->getRequest()->getParam('taskGuid'); // enable possiblity to use the API outside of  the current task-context
        if(is_null($taskGuid)){
            $session = new Zend_Session_Namespace();
            $taskGuid = $session->taskGuid;
        }
        if(empty($taskGuid)){
            throw new ZfExtended_NotAuthenticatedException();
        }
        return $taskGuid;
    }
}
