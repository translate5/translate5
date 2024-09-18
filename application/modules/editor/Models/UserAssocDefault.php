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

/***
* @method void setId(int $id)
* @method string getId()
* @method void setCustomerId(int $customerId)
* @method string getCustomerId()
* @method void setSourceLang(int $sourceLang)
* @method string getSourceLang()
* @method void setTargetLang(int $targetLang)
* @method string getTargetLang()
* @method void setUserGuid(string $userGuid)
* @method string getUserGuid()
* @method void setWorkflowStepName(string $workflowStepName)
* @method string getWorkflowStepName()
* @method void setWorkflow(string $workflow)
* @method string getWorkflow()
* @method void setDeadlineDate(double $deadlineDate)
* @method string getDeadlineDate()
* @method string getTrackchangesShow()
* @method void setTrackchangesShow(int $isAllowed)
* @method string getTrackchangesShowAll()
* @method void setTrackchangesShowAll(int $isAllowed)
* @method string getTrackchangesAcceptReject()
* @method void setTrackchangesAcceptReject(int $isAllowed)
*
*/

class editor_Models_UserAssocDefault extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = editor_Models_Db_UserAssocDefault::class;

    protected $validatorInstanceClass = editor_Models_Validator_UserAssocDefault::class;

    /***
     * Load all default assocs for given task. The rows are filtered for workflow,customerId, sourceLang and targetLang
     * @param editor_Models_Task $task
     * @return array|null
     */
    public function loadDefaultsForTask(editor_Models_Task $task)
    {
        $s = $this->db->select()
            ->where('customerId = ?', $task->getCustomerId())
            ->where('sourceLang = ?', $task->getSourceLang())
            ->where('targetLang = ?', $task->getTargetLang());

        return $this->db->getAdapter()->fetchAll($s);
    }
}
