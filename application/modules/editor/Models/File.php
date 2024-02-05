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
 * Foldertree Object Instanz wie in der Applikation benötigt
 *
 * @method void setId(int $id)
 * @method void setTaskGuid(string $guid)
 * @method void setFileName(string $name)
 * @method void setFileParser(string $parser)
 * @method void setSourceLang(int $source)
 * @method void setTargetLang(int $target)
 * @method void setRelaisLang(int $target)
 * @method void setFileOrder(int $order)
 * @method void setIsReimportable(int $isReimportable)
 * @method integer getId()
 * @method string getTaskGuid()
 * @method string getFileName()
 * @method string getFileParser()
 * @method integer getSourceLang()
 * @method integer getTargetLang()
 * @method integer getRelaisLang()
 * @method integer getFileOrder()
 * @method integer getIsReimportable()
 */
class editor_Models_File extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Files';


    /***
     * @param string $taskGuid
     * @return array
     */
    public function loadByTaskGuid(string $taskGuid): array
    {
        $s = $this->db->select()->where('taskGuid = ?',$taskGuid);
        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * remove dummy directory entries
     * @param array $idList
     */
    public function cleanupDirectoryIncrements(array $idList): void
    {
        $this->db->delete('`id` in ('.join(',', $idList).')');
    }

    /**
     * Check the list of $taskGuids and keep only the ones related
     * to tasks which were created during terms transfer from termportal
     *
     * @param array $taskGuids
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getTransfersPerTasks(array $taskGuids) {

        // If $taskGuids arg is empty - return empty array
        if (empty($taskGuids)) {
            return [];
        }

        // Enquote and join by comma
        $taskGuids = $this->db->getAdapter()->quoteInto('?', $taskGuids);

        // Get only the ones which were transferred from termportal
        return $this->db->getAdapter()->query('
            SELECT `taskGuid`, 1
            FROM `LEK_files`
            WHERE 1
              AND `taskGuid` IN (' . $taskGuids . ')
              AND `fileName` REGEXP \'^TermCollection_[0-9]+_[0-9]+.tbx$\'
            GROUP BY `taskGuid`
        ')->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * @param array $taskGuids
     * @return array taskGuid => cnt
     */
    public function getFileCountPerTasks(array $taskGuids): array
    {
        if(empty($taskGuids)) {
            return [];
        }
        
        $s = $this->db->select()
        ->from($this->db, ['cnt' => 'count(id)', 'taskGuid'])
        ->where('taskGuid in (?)', $taskGuids)
        ->group('taskGuid');
        
        $keys = [];
        $values = array_map(function($item) use (&$keys){
            $keys[] = $item['taskGuid'];
            return $item['cnt'];
        }, $this->db->fetchAll($s)->toArray());
        return array_combine($keys, $values);
    }
}