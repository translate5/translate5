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
 * Entity Model for History Data Entries
 */
class editor_Models_SegmentHistoryData  extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_SegmentsHistoryData';
    
    /**
     * loads the history data entries to one segment, DESC sorted by id (creation)
     *  can be limited with $limit parameter
     *  can be filtered to one datafield with optional parameter $field 
     * @param int $id
     * @param string $field optional, defaults to null which means all fields
     * @param number $limit optional, defaults to 0 which means no limit
     * @return array
     */
    public function loadBySegmentId($id, $field = null, $limit = 0) {
        $s = $this->db->select();
        $s->where('segmentId = ?', $id);
        if(!empty($field)) {
            $s->where('name = ?', $field);
        }
        $s->order('id DESC');
        if($limit > 0) {
            $s->limit($limit);
        }
        return $this->db->fetchAll($s)->toArray();
    }
}