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
 * Entity Model for segment meta data
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method integer getSegmentId() getSegmentId()
 * @method void setSegmentId() setSegmentId(int $id)
 * @method integer getTransunitId() getTransunitId()
 * @method void setTransunitId() setTransunitId(int $id)
 * @method string getSiblingData() getSiblingData()
 * @method integer getMinWidth() getMinWidth()
 * @method void setMinWidth() setMinWidth(int $width)
 * @method integer getMaxWidth() getMaxWidth()
 * @method void setMaxWidth() setMaxWidth(int $width)
 * @method integer getMaxNumberOfLines() getMaxNumberOfLines()
 * @method void setMaxNumberOfLines() setMaxNumberOfLines(int $maxNumberOfLines)
 * @method string getSizeUnit() getSizeUnit()
 * @method void setSizeUnit() setSizeUnit(string $sizeUnit)
 * @method string getFont() getFont()
 * @method void setFont() setFont(string $font)
 * @method integer getFontSize() getFontSize()
 * @method void setFontSize() setFontSize(int $fontSize)
 * @method integer getAdditionalUnitLength() getAdditionalUnitLength()
 * @method void setAdditionalUnitLength() setAdditionalUnitLength(int $length)
 * @method integer getAdditionalMrkLength() getAdditionalMrkLength()
 * @method void setAdditionalMrkLength() setAdditionalMrkLength(int $length)
 * @method integer getAutopropagated() getAutopropagated()
 * @method void setAutopropagated() setAutopropagated(int $autopropagated)
 * @method integer getLocked() getLocked()
 * @method void setLocked() setLocked(int $locked)
 * @method integer getSourceWordCount() getSourceWordCount()
 * @method void setSourceWordCount() setSourceWordCount(int $count)
 * @method string getPreTransLangResUuid() getPreTranslangResUuid()
 * @method void setPreTransLangResUuid() setPreTranslangResUuid(string $uuid)
 */
class editor_Models_Segment_Meta extends ZfExtended_Models_Entity_MetaAbstract {
    protected $dbInstanceClass = 'editor_Models_Db_SegmentMeta';
    
    public function loadBySegmentId($id) {
        return $this->loadRow('segmentId = ?', $id);
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_MetaAbstract::initEmptyRowset()
     */
    public function initEmptyRowset(){
        //currently not implemented for segment meta, see task meta for usage and what to implement
        // for segments meta add also segment id to initial row set
    }
    
    /**
     * Sets the siblingData field from the given segment instance
     * @param editor_Models_Segment $segment
     */
    public function setSiblingData(editor_Models_Segment $segment) {
        $data = new stdClass();
        $data->nr = $segment->getSegmentNrInTask();
        $data->length = [];
        $editables = $segment->getEditableFieldData();
        foreach($editables as $field => $value){
            //the additional mrk length is added here to each field,
            // so that it is available in the frontend out of the cached siblings without providing an additional data field
            // (the additional unit length is added once to the calculation in the frontend!
            $data->length[$field] = (int)$segment->textLengthByMeta($value, $this, $segment->getFileId()) + (int)$this->getAdditionalMrkLength();
        }
        $this->__call(__FUNCTION__, [json_encode($data)]);
    }
    
    /**
     * Updates the additional unit lengths of a transunit
     * Warning: This does not update the materialized view! (Currently not needed since used only in import before mat view creation)
     * @param string $taskGuid
     * @param string $transunitId
     * @param integer $additionalUnitLength
     */
    public function updateAdditionalUnitLength(string $taskGuid, string $transunitId, int $additionalUnitLength) {
        $this->db->update(['additionalUnitLength' => $additionalUnitLength], [
            'taskGuid = ?' => $taskGuid,
            'transunitId = ?' => $transunitId,
        ]);
    }
    
    /**
     * Return all combinations of font-family and font-size
     * that are used in all the segments of the task.
     * This is only a workaround until we get these infos from the config-data
     * of the taskTemplate (unfortunately not implemented yet).
     * @param string $taskGuid
     * @return array
     */
    public function getAllFontsInTask($taskGuid) {
        $sql = $this->db->select()
                ->from($this->db, array('font','fontSize'))
                ->distinct()
                ->where('taskGuid = ?', $taskGuid);
        $fonts = $this->db->fetchAll($sql);
        return $fonts->toArray();
    }
    
    /**
     * Returns the word count sum of a task as calculated on import
     * @param string $taskGuid
     * @return int
     */
    public function getWordCountSum(string $taskGuid): int {
        $s = $this->db->select()
        ->from($this->db, 'sum(sourceWordCount) as wordCount')
        ->where('`taskGuid` = ?', $taskGuid);
        $result = $this->db->fetchRow($s);
        return $result['wordCount'] ?? 0;
    }
}