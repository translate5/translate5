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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/
/***
* @method void setId() setId(int $id)
* @method int getId() getId()
* @method void setCustomerId() setCustomerId(int $customerId)
* @method int getCustomerId() getCustomerId()
* @method void setSourceLang() setSourceLang(int $sourceLang)
* @method int getSourceLang() getSourceLang()
* @method void setTargetLang() setTargetLang(int $targetLang)
* @method int getTargetLang() getTargetLang()
* @method void setUserGuid() setUserGuid(string $userGuid)
* @method string getUserGuid() getUserGuid()
* @method void setWorkflowStepName() setWorkflowStepName(string $workflowStepName)
* @method string getWorkflowStepName() getWorkflowStepName()
* @method void setWorkflow() setWorkflow(string $workflow)
* @method string getWorkflow() getWorkflow()
* @method void setDeadlineDate() setDeadlineDate(double $deadlineDate)
* @method double getDeadlineDate() getDeadlineDate()
*
*/

class editor_Models_UserAssocDefault extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = "editor_Models_Db_UserAssocDefault";
    protected $validatorInstanceClass = "editor_Models_Validator_UserAssocDefault";

    /***
     * Load all default assocs for given task. The rows are filtered for workflow,customerId, sourceLang and targetLang
     * @param editor_Models_Task $task
     * @return array|null
     */
    public function loadDefaultsForTask(editor_Models_Task $task){
        $s = $this->db->select()
            ->where('customerId = ?', $task->getCustomerId())
            ->where('sourceLang = ?',$task->getSourceLang())
            ->where('targetLang = ?',$task->getTargetLang())
            ->where('workflow = ?',$task->getWorkflow());
        return $this->db->getAdapter()->fetchAll($s);
    }
}
