<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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