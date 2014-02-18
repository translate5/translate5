<?php
/**
 * Created by PhpStorm.
 * User: kkolesnikov
 * Date: 2/7/14
 * Time: 4:16 PM
 */

class editor_Models_SegmentData extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_SegmentData';

    public function loadBytaskGuid($taskGuid) {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->order('id ASC');
        $data = $this->db->getAdapter()->fetchAll($s);
        foreach($data as $value){
            $dataAssoc[$value['name']] = $value;
        }
        return $dataAssoc;
    }
} 