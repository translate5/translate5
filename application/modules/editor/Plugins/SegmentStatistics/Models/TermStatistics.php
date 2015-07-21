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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Default Model for Plugin SegmentStatistics
 * 
 * @method void setId() setId(integer $id)
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method void setMid() setmid(integer $Mid)
 * @method void setTerm() setTerm(string $term)
 * @method void setFoundCount() setFoundCount(integer $count)
 * @method void setNotFoundCount() setNotFoundCount(integer $count)
 * 
 * @method integer getId() getId()
 * @method string getTaskGuid() getTaskGuid()
 * @method integer getMid() getMid()
 * @method integer getTerm() getTerm()
 * @method integer getFoundCount() getFoundCount()
 * @method integer getNotFoundCount() getNotFoundCount()
 */
class editor_Plugins_SegmentStatistics_Models_TermStatistics extends ZfExtended_Models_Entity_Abstract {
    const COUNT_FOUND = 'foundCount';
    const COUNT_NOT_FOUND = 'notFoundCount';
    
    protected $dbInstanceClass = 'editor_Plugins_SegmentStatistics_Models_Db_TermStatistics';
    
    /**
     * Increases or initializes the found counter of the given term
     * @param string $taskGuid
     * @param integer $mid
     * @param string $term
     */
    public function incTermFound($taskGuid, $mid, $term) {
        $this->incTerm(func_get_args(), self::COUNT_FOUND);
    }
    
    /**
     * Increases or initializes the notFound counter of the given term
     * @param string $taskGuid
     * @param integer $mid
     * @param string $term
     */
    public function incTermNotFound($taskGuid, $mid, $term) {
        $this->incTerm(func_get_args(), self::COUNT_NOT_FOUND);
    }

    /**
     * increases the given termCounter
     * @param array $params
     * @param string $colToInc
     */
    protected function incTerm(array $params, $colToInc) {
        $db = $this->db;
        $sql = 'INSERT INTO '.$db->info($db::NAME).' (taskGuid, mid, term, '.$colToInc.') VALUES (?, ?, ?, 1) ';
        $sql .= ' ON DUPLICATE KEY UPDATE '.$colToInc.' = '.$colToInc.'+1';
        $db->getAdapter()->query($sql, $data);
    }
    
    /**
     * Loads the term stats for one task, ordered by foundCount
     * @param string $taskGuid
     * @return multitype:
     */
    public function loadByTaskGuid($taskGuid) {
        $s = $this->db->select(false);
        $db = $this->db;
        $cols = $this->db->info($db::COLS);

        $s->from($this->db, $cols);
        $s->where('taskGuid = ?', $taskGuid);
        $s->order('foundCount DESC');
        return parent::loadFilterdCustom($s);
    }
    
    /**
     * deletes the statistics to the given taskGuid
     * @param string $taskGuid
     */
    public function deleteByTask($taskGuid) {
        $this->db->delete(array('taskGuid = ?' => $taskGuid));
    }
}