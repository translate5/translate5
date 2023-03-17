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

/**#@+ 
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 * 
 */
/**
 * Klasse zum Zugriff auf die Tabelle mit Namen des Klassennamens (in lower case)
 * 
 *
 */
class editor_Models_Db_Segments extends Zend_Db_Table_Abstract {
    protected $_name;
    public $_primary = 'id';

    /**
     * @param array $config see Zend_Db_Table_Abstract::__construct
     * @param string $name the default table name, can be overwritten for dynamic view usage
     */
    public function __construct(array $config = array(), $tableName = 'LEK_segments')
    {
        $this->_name = $tableName;
        parent::__construct($config);
    }
    
    /**
     * @return string
     */
    public function __toString() {
        return $this->_name;
    }

    /**
     * Retrieves all segment-id's for a task
     * @param string $taskGuid
     * @param bool $forUpdate
     * @param array $additionalConditions: can be used to add additional restrictions, format is the Zend-typical 'column = ?' => 'columnValue'
     * @return int[]
     */
    public function getAllIdsForTask(string $taskGuid, bool $forUpdate = false, array $additionalConditions = []): array
    {
        $where = $this->select()
            ->from($this->_name, ['id'])
            ->where('taskGuid = ?', $taskGuid)
            ->order('id');
        if(!empty($additionalConditions)){
            foreach($additionalConditions as $condition => $value){
                $where->where($condition, $value);
            }
        }
        if ($forUpdate) {
            $where->forUpdate(true);
        }
        $rows = $this->fetchAll($where)->toArray();
        return array_column($rows, 'id');
    }
    
    /**
     * Is entity a materialized view
     * 
     * @return boolean
     */
    public function isView() {
        return $this->_name !== 'LEK_segments';
    }
}