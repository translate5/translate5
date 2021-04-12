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
* @method void setTaskType() setTaskType(string $taskType)
* @method string getTaskType() getTaskType()
* @method void setSourceLang() setSourceLang(int $sourceLang)
* @method int getSourceLang() getSourceLang()
* @method void setTargetLang() setTargetLang(int $targetLang)
* @method int getTargetLang() getTargetLang()
* @method void setCustomerId() setCustomerId(int $customerId)
* @method int getCustomerId() getCustomerId()
* @method void setYearAndMonth() setYearAndMonth(string $yearAndMonth)
* @method string getYearAndMonth() getYearAndMonth()
* @method void setTaskCount() setTaskCount(int $taskCount)
* @method int getTaskCount() getTaskCount()
*/

class editor_Models_TaskUsageLog extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = "editor_Models_Db_TaskUsageLog";
    protected $validatorInstanceClass = "editor_Models_Validator_TaskUsageLog";
    
    /***
     * Load task usage by taskType and customer.
     * 
     * @param int $customerId
     * @param array $taskTypes
     * @return array
     */
    public function loadByTypeAndCustomer(int $customerId = null,array $taskTypes = []){
        $s=$this->db->select()
        ->from('LEK_task_usage_log',['customerId','sourceLang','targetLang','yearAndMonth','taskCount']);
        if(!empty($customerId)){
            $s->where('customerId = ?',$customerId);
        }
        if(!empty($taskTypes)){
            $s->where('taskType IN(?)',$taskTypes);
        }
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Update or insert task count for the current entity.
     * If the unique key is duplicated(taskType,customerId,sourceLang,targetLang ans yearAndMonth), 
     * the row taskCount will be incremented by $taskCount
     * @param float $taskCount
     */
    public function updateInsertTaskCount(float $taskCount = 1) {
        $sql = "INSERT INTO LEK_task_usage_log (taskType, sourceLang, targetLang, customerId, yearAndMonth,taskCount)
                VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE taskCount=taskCount+?;";
        $this->db->getAdapter()->query($sql,[
            $this->getTaskType(),
            $this->getSourceLang(),
            $this->getTargetLang(),
            $this->getCustomerId(),
            $this->getYearAndMonth(),
            $taskCount,
            $taskCount
        ]);
    }
}
