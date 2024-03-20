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

/***
 * @property editor_Models_Db_SegmentQualityRow $row
 * 
 * @method void setId(int $id)
 * @method string getId()
 * @method void setSegmentId(int $segmentId)
 * @method string getSegmentId()
 * @method void setField(string $field)
 * @method string getField()
 * @method void setTaskGuid(string $taskGuid)
 * @method string getTaskGuid()
 * @method void setType(string $type)
 * @method string getType()
 * @method void setCategory(string $category)
 * @method string getCategory()
 * @method void setStartIndex(int $startIndex)
 * @method string getStartIndex()
 * @method void setEndIndex(int $endIndex)
 * @method string getEndIndex()
 * @method void setFalsePositive(int $falsePositive)
 * @method string getFalsePositive()
 * @method void setCategoryIndex(int $categoryIndex)
 * @method string getCategoryIndex()
 * @method void setSeverity(string $severity)
 * @method string getSeverity()
 * @method void setComment(string $comment)
 * @method string getComment()
*/

class editor_Models_SegmentQuality extends ZfExtended_Models_Entity_Abstract {
    
    protected $dbInstanceClass = 'editor_Models_Db_SegmentQuality';
    protected $validatorInstanceClass = 'editor_Models_Validator_SegmentQuality';
    
    /**
     * 
     * @return stdClass
     */
    public function getAdditionalData() : stdClass {
        return $this->row->getAdditionalData();
    }
    /**
     * 
     * @param stdClass $data
     */
    public function setAdditionalData(stdClass $data){
        $this->row->setAdditionalData($data);
    }

    /**
     * Fetch spell check data for given segments ids
     */
    public function getSpellCheckData(array $segmentIds) {

        // Get spell check data
        $_data = $this->db->getAdapter()->query('
            SELECT 
              `id`,     
              `segmentId`, 
              `field`,
              `falsePositive`,     
              `additionalData`, 
              JSON_EXTRACT(`additionalData`, "$.matchIndex") AS `matchIndex`
            FROM `LEK_segment_quality` 
            WHERE 1
              AND `segmentId` IN (' . join(',', $segmentIds ?: [0]) . ')
              AND `type` = "spellcheck"
            ORDER BY `segmentId`, `field`, `matchIndex`
        ')->fetchAll();

        // Group by `segmentId` and `field`
        foreach ($_data as $_item) {
            $additionalData = json_decode($_item['additionalData']);
            $additionalData->id = (int) $_item['id'];
            $additionalData->falsePositive = (int) $_item['falsePositive'];
            $data[ $_item['segmentId'] ][ $_item['field'] ] []= $additionalData;
        }

        // Foreach given segmentId
        foreach ($segmentIds as $segmentId) {

            // If no spell check data found make sure
            $data[$segmentId] = $data[$segmentId] ?? new stdClass();
        }

        // Return spell check data
        return $data ?? [];
    }

    /**
     * Fetch spell check data for given segments ids
     */
    public function getTermTaggerData(array $segmentIds) : array {

        // Get spell check data
        $_data = $this->db->getAdapter()->query('
            SELECT 
              `id`,     
              `segmentId`, 
              `field`,
              `falsePositive`
            FROM `LEK_segment_quality` 
            WHERE 1
              AND `segmentId` IN (' . join(',', $segmentIds ?: [0]) . ')
              AND `type` = "term"
            ORDER BY `segmentId`, `field`, `startIndex`
        ')->fetchAll();

        // Group by `segmentId` and `field`
        foreach ($_data as $_item) {
            $data[ $_item['segmentId'] ][ $_item['field'] ] []= [
                'id' => (int) $_item['id'],
                'falsePositive' => (int) $_item['falsePositive']
            ];
        }

        // Foreach given segmentId
        foreach ($segmentIds as $segmentId) {

            // If no spell check data found make sure
            $data[$segmentId] = $data[$segmentId] ?? new stdClass();
        }

        // Return spell check data
        return $data ?? [];
    }

    /**
     * Proxy to row's method
     *
     * @return array
     */
    public function spreadFalsePositive() : array {
        return $this->row->spreadFalsePositive();
    }
}
