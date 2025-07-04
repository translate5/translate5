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
 * Entity Model for segment meta data
 * @method string getId()
 * @method void setId(int $id)
 * @method string getTaskGuid()
 * @method void setTaskGuid(string $guid)
 * @method string getSegmentId()
 * @method void setSegmentId(int $id)
 * @method string getTransunitId()
 * @method void setTransunitId(int $id)
 * @method string getTransunitHash()
 * @method void setTransunitHash(string $transunitHash)
 * @method string getSiblingData()
 * @method string getMinWidth()
 * @method void setMinWidth(int $width)
 * @method string getMaxWidth()
 * @method void setMaxWidth(int $width)
 * @method string getMaxNumberOfLines()
 * @method void setMaxNumberOfLines(int $maxNumberOfLines)
 * @method string getSizeUnit()
 * @method void setSizeUnit(string $sizeUnit)
 * @method string getFont()
 * @method void setFont(string $font)
 * @method string getFontSize()
 * @method void setFontSize(int $fontSize)
 * @method string getAdditionalUnitLength()
 * @method void setAdditionalUnitLength(int $length)
 * @method string getAdditionalMrkLength()
 * @method void setAdditionalMrkLength(int $length) DEPRECATED!
 * @method string getAutopropagated()
 * @method void setAutopropagated(bool $autopropagated)
 * @method string getLocked()
 * @method void setLocked(bool $locked)
 * @method string getSourceWordCount()
 * @method void setSourceWordCount(int $count)
 * @method string getSourceCharacterCount()
 * @method void setSourceCharacterCount(int $count)
 * @method string getPreTransLangResUuid()
 * @method void setPreTransLangResUuid(string $uuid)
 * @method string getMrkMid()
 * @method void setMrkMid(string $mrkMid)
 * @method string getSourceFileId()
 * @method void setSourceFileId(string $sourceFileId)
 * @method string getSegmentDescriptor()
 * @method void setSegmentDescriptor(string $descriptor)
 */
class editor_Models_Segment_Meta extends ZfExtended_Models_Entity_MetaAbstract
{
    protected $dbInstanceClass = 'editor_Models_Db_SegmentMeta';

    public function loadBySegmentId($id)
    {
        return $this->loadRow('segmentId = ?', $id);
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_MetaAbstract::initEmptyRowset()
     */
    public function initEmptyRowset()
    {
        //currently not implemented for segment meta, see task meta for usage and what to implement
        // for segments meta add also segment id to initial row set
    }

    /**
     * Sets the siblingData field from the given segment instance
     */
    public function setSiblingData(editor_Models_Segment $segment)
    {
        $data = new stdClass();
        $data->nr = $segment->getSegmentNrInTask();
        $data->length = [];
        $editables = $segment->getEditableFieldData();
        foreach ($editables as $field => $value) {
            //the additional mrk length is added here to each field,
            // so that it is available in the frontend out of the cached siblings without providing an additional data field
            // (the additional unit length is added once to the calculation in the frontend!
            $data->length[$field] = (int) $segment->textLengthByMeta(
                $value,
                $this,
                (int) $segment->getFileId(),
                str_contains($field, editor_Models_SegmentField::TYPE_SOURCE)
            ) + (int) $this->getAdditionalMrkLength();
        }
        $this->__call(__FUNCTION__, [json_encode($data)]);
    }

    /**
     * Updates the additional unit lengths of a transunit
     * Warning: This does not update the materialized view! (Currently not needed since used only in import before mat view creation)
     * @param integer $additionalUnitLength
     */
    public function updateAdditionalUnitLength(string $taskGuid, string $transunitHash, int $additionalUnitLength)
    {
        $this->db->update([
            'additionalUnitLength' => $additionalUnitLength,
        ], [
            'taskGuid = ?' => $taskGuid,
            'transunitHash = ?' => $transunitHash,
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
    public function getAllFontsInTask($taskGuid)
    {
        $sql = $this->db->select()
            ->from($this->db, ['font', 'fontSize'])
            ->distinct()
            ->where('taskGuid = ?', $taskGuid);
        $fonts = $this->db->fetchAll($sql);

        return $fonts->toArray();
    }

    /**
     * Returns the word count sum of a task as calculated on import
     */
    public function getWordCountSum(editor_Models_Task $task): int
    {
        $s = $this->db->select()
            ->from($this->db, 'sum(LEK_segments_meta.sourceWordCount) as wordCount');

        // Blocked segments should not be counted in the task total words sum
        $s->setIntegrityCheck(false)
            ->join('LEK_segments', 'LEK_segments.id = LEK_segments_meta.segmentId', [])
            ->where('LEK_segments.autoStateId!=?', editor_Models_Segment_AutoStates::BLOCKED);

        // If edit 100 matches is disabled, filter out locked segments from the total sum as well
        if (! $task->getEdit100PercentMatch()) {
            $s->where('LEK_segments.autoStateId!=?', editor_Models_Segment_AutoStates::LOCKED);
        }
        $s->where('LEK_segments_meta.taskGuid = ?', $task->getTaskGuid());
        $result = $this->db->fetchRow($s);

        return $result['wordCount'] ?? 0;
    }

    /***
     * Get progress of the segments for given taskguid.
     * Progress is calculated based on amount of meta records with particular states values
     * in provided column name in LEK_segments_meta table
     * The return value will be between 0 and 1
     *
     * @param string $taskGuid
     * @param array $states
     * @param string $columnName
     *
     * @return float
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function calculateSegmentProgressByStatesAndColumn(string $taskGuid, array $states, string $columnName): float
    {
        $adapter = $this->db->getAdapter();
        $sql = "SELECT (SELECT COUNT(*) FROM LEK_segments_meta WHERE " . $adapter->quoteInto($columnName . ' IN(?)', $states) . " AND taskGuid = ?) / COUNT(*) AS 'progress'
                FROM LEK_segments_meta
                WHERE taskGuid = ?";
        $statement = $this->db->getAdapter()->query($sql, [$taskGuid, $taskGuid]);
        $result = $statement->fetch();

        return $result['progress'] ?? 0;
    }
}
