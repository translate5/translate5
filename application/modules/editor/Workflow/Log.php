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
     * Adds a new log entry, save it to the db, and return the entity instance
     * @param string $taskGuid
     * @param string $userGuid
     * @param string $step
     */
    public function log(string $taskGuid, string $userGuid, string $step) {
        $adapter = $this->db->getAdapter();
        $adapter->beginTransaction();
        $db = $this->db;
        $s = $db->select()
        ->from($db->info($db::NAME), array('maxStep' => 'MAX(stepNr)'))
        ->where('taskGuid = ?', $taskGuid);
        $res = $this->db->fetchRow($s);
        $nextStep = (empty($res->maxStep) ? 1 : ($res->maxStep + 1));
        $this->setTaskGuid($taskGuid);
        $this->setUserGuid($userGuid);
        $this->setStepName($step);
        $this->setStepNr($nextStep);
        //created timestamp automatic by DB
        $this->save();
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->updateWorkflowStep($taskGuid, $nextStep);
        
        $adapter->commit();
    }
}