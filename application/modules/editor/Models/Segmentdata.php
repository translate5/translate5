<?php
/**
 * Created by PhpStorm.
 * User: kkolesnikov
 * Date: 2/7/14
 * Time: 4:16 PM
 */

class editor_Models_Segmentdata extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Segmentdata';

    public function loadBytaskGuid($taskGuid) {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->order('id ASC');
        return $this->db->getAdapter()->fetchAll($s);
    }
} 