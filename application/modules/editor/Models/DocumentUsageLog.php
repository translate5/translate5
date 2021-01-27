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

class editor_Models_DocumentUsageLog extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = "editor_Models_Db_DocumentUsageLog";
    protected $validatorInstanceClass = "editor_Models_Validator_DocumentUsageLog";
    
    
    /***
     * Update or increse the document count for the current entity.
     * The unique key is: taskType, customerId and yearAndMonth
     */
    public function updateInsertDocumentCount() {
        $sql = "INSERT INTO LEK_documents_usage_log (taskType, sourceLang, targetLang, customerId, yearAndMonth,taskCount)
                VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE taskCount=taskCount+1;";
        $this->db->getAdapter()->query($sql,[
            $this->getTaskType(),
            $this->getSourceLang(),
            $this->getTargetLang(),
            $this->getCustomerId(),
            $this->getYearAndMonth(),
            1
        ]);
    }
}
