<?php
/**
 * Created by PhpStorm.
 * User: kkolesnikov
 * Date: 2/10/14
 * Time: 10:16 AM
 */

class editor_Models_Segmentfield extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Segmentfield';

    public function loadBytaskGuid($taskGuid) {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->order('id ASC');
        return $this->db->getAdapter()->fetchAll($s);
    }
} 