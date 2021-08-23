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
 * Default Model for Plugin SegmentStatistics
 * 
 * @method void setId() setId(int $id)
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method void setMid() setmid(string $Mid)
 * @method void setSegmentId() setSegmentId(int $segmentId)
 * @method void setField() setField(int $field)
 * @method void setFieldName() setFieldName(string $fieldName)
 * @method void setFieldType() setFieldType(string $fieldType)
 * @method void setTerm() setTerm(string $term)
 * @method void setNotFoundCount() setNotFoundCount(int $count)
 * @method void setFoundCount() setFoundCount(int $count)
 * @method void setType() setType(string $type)
 * 
 * @method integer getId() getId()
 * @method string getTaskGuid() getTaskGuid()
 * @method string getMid() getMid()
 * @method integer getSegmentId() getSegmentId()
 * @method integer getField() getField()
 * @method string getFieldName() getFieldName()
 * @method string getFieldType() getFieldType()
 * @method string getTerm() getTerm()
 * @method integer getNotFoundCount() getNotFoundCount()
 * @method integer getFoundCount() getFoundCount()
 * @method string getType() getType()
 */
class editor_Plugins_SegmentStatistics_Models_TermStatistics extends ZfExtended_Models_Entity_Abstract {
    const COUNT_FOUND = 'foundCount';
    const COUNT_NOT_FOUND = 'notFoundCount';
    
    protected $dbInstanceClass = 'editor_Plugins_SegmentStatistics_Models_Db_TermStatistics';
    
    /**
     * Loads the term stats for one task, ordered by foundCount and filterd by SegmentMetaJoin
     * @param string $taskGuid
     * @return multitype:
     */
    public function loadTermSums($taskGuid, $fieldName, $type) {
        $s = $this->db->select(false);
        $db = $this->db;

        $cols = array(
            'ts.term',
            'ts.mid',
            'ts.fileId',
            'foundSum' => 'sum(ts.foundCount)',
            'notFoundSum' => 'sum(ts.notFoundCount)',
        );
        $s->from(array('ts' => $db->info($db::NAME)), $cols)
        ->where('ts.taskGuid = ?', $taskGuid)
        ->where('ts.fieldName = ?', $fieldName)
        ->where('ts.type = ?', $type)
        ->group('ts.mid')
        ->group('ts.fileId')
        ->order('ts.fileId ASC')
        ->order('foundSum DESC');
        
        $meta = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_SegmentMetaJoin');
        /* @var $meta editor_Plugins_SegmentStatistics_Models_SegmentMetaJoin */
        $meta->setTarget('ts');
        $s = $meta->segmentsMetaJoin($s, $taskGuid);
        return $db->fetchAll($s)->toArray();
    }
    
    /**
     * deletes the statistics to the given taskGuid and type
     * @param string $taskGuid
     * @param string $type
     */
    public function deleteType($taskGuid, $type) {
        $this->db->delete(array('taskGuid = ?' => $taskGuid, 'type = ?' => $type));
    }
}