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
 * LogWorkflow Entity Objekt, used mainly in the workflow classes
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $taskGuid)
 * @method string getUserGuid() getUserGuid()
 * @method void setUserGuid() setUserGuid(string $userGuid)
 * @method string getStepName() getStepName()
 * @method void setStepName() setStepName(string $stepName)
 * @method string getStepNr() getStepNr()
 * @method void setStepNr() setStepNr(string $stepNr)
 * @method string getCreated() getCreated()
 */
class editor_Workflow_Log extends ZfExtended_Models_Entity_Abstract {
    
    /**
     * @var string
     */
    protected $dbInstanceClass = 'editor_Models_Db_LogWorkflow';

    /**
     * Adds a new log entry, save it to the db
     * @param editor_Models_Task $task
     * @param string $userGuid
     */
    public function log(editor_Models_Task $task, string $userGuid) {
        $this->setTaskGuid($task->getTaskGuid());
        $this->setUserGuid($userGuid);
        $this->setStepName($task->getWorkflowStepName()); //empty step name comes from import!
        $this->setStepNr($task->getWorkflowStep());
        //created timestamp automatic by DB
        $this->save();
    }
}