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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Entity Model for excel export data
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method string getUserGuid() getUserGuid()
 * @method void setUserGuid() setUserGuid(string $guid)
 * @method string getExported() getExported()
 * @method void setExported() setExported(string $timestamp)
 */
class editor_Models_Task_ExcelExport extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_TaskExcelExport';
    
    /**
     * How often has a task been exported?
     * @param string $taskGuid
     * @return int
     */
    public function getNumberOfExportsByTaskGuid($taskGuid) : int {
        $s = $this->db->select()
        ->where('taskGuid = ?', $taskGuid);
        return $this->db->fetchAll($s)->count();
    }
    
    /**
     * Has a task ever been exported at least once?
     * @param string $taskGuid
     * @return bool
     */
    public function isExported($taskGuid) : bool {
        return $this->getNumberOfExportsByTaskGuid($taskGuid) > 0;
    }
    
}