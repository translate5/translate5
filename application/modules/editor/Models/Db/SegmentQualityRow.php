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
 * DB Row Model for segment quality data
 * provides the available data fields for convenience and an improved getter/setter for accessing fields
 * 
 * @property int $id
 * @property string $taskGuid
 * @property int $segmentId
 * @property string $field
 * @property string $type
 * @property string $category
 * @property int $startIndex
 * @property int $endIndex
 * @property int $falsePositive
 * @property string $additionalData
 * @property int $categoryIndex
 * @property string $severity
 * @property string $comment
 */
class editor_Models_Db_SegmentQualityRow extends Zend_Db_Table_Row_Abstract {
    
    protected $_tableClass = 'editor_Models_Db_SegmentQuality';
    /**
     * Used in editor_Segment_Qualities to process the qualities
     * @var string
     */
    public $processingState;
    
    /**
     * Retrieves the additionalData as decoded stdClass
     * @return stdClass
     */
    public function getAdditionalData() : stdClass {
        $data = (empty($this->additionalData)) ? NULL : json_decode($this->additionalData);
        if(is_object($data)){
            return $data;
        }
        return new stdClass();
    }

    /**
     * Sets the additionalData, which can only be an stdClass Object, as encoded JSON
     * An empy or missing Object will lead to NULL as column value
     * @param stdClass|array|null $data
     */
    public function setAdditionalData(stdClass|array|null $data): void
    {
        if(is_object($data) || is_array($data)){
            $jsonString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->additionalData = (empty($jsonString)) ? NULL : $jsonString;
        } else {
            $this->additionalData = NULL;
        }
    }

    /**
     * Compares our additionalData with the passed additional data if they are equal
     * @param stdClass|array|null $data
     * @return boolean
     */
    public function isAdditionalDataEqual(stdClass|array|null $data = null) : bool {
        if($data === null){
            return $this->additionalData === null;
        }

        $ours = (array) $this->getAdditionalData();
        $theirs = (array) $data;

        foreach($ours as $key => $val){
            if(!array_key_exists($key, $theirs) || $theirs[$key] !== $val){
                return false;
            }
        }

        return (count($ours) === count($theirs));
    }
    /** can be used for debugging
    public function save(){
        error_log('SAVED QUALITY '.$this->id.': type: '.$this->type.' category: '.$this->category.' falsePositive: '.$this->falsePositive);
        parent::save();
    }
    //*/

    /**
     * Try to get json_decode($this->additionalData)->content
     * False is returned if it is not possible to get, or it is empty
     *
     * @return bool|mixed
     */
    public function getContent() : bool|string {

        // If no additional data exists for this quality - return
        if (!$data = $this->additionalData) {
            return false;
        }

        // Else if additional data exists, but is not json-decodable - return
        if (!$data = json_decode($data)) {
            return false;
        }

        // Else if it's json_decodable, but contains no content-prop or it's empty - return
        if (!strlen($data->content ?? '')) {
            return false;
        }

        // Return content
        return $data->content;
    }

    /**
     * Get quantity of similar qualities triggered by same content
     *
     * @return int|string
     * @throws Zend_Db_Statement_Exception
     */
    /**
     * @param string $mode
     * @return PDOStatement
     */
    private function getSimilarQualityStmt($mode = 'qty') {

        // Shortcut
        $db = $this->getTable()->getAdapter();

        // Get mysql expression to be used in SELECT clause
        $select = ['qty' => 'COUNT(`id`)', 'ids' => '`id`'];

        // Prepare and return statement
        return $db->query('
            SELECT ' . $select[$mode] . ' FROM `LEK_segment_quality` WHERE `taskGuid` = ?
              AND `id` != ?
              AND `type` = ?
              AND `category` = ?
              AND `field` = ?
              AND NOT ISNULL(`additionalData`) 
              AND JSON_EXTRACT(`additionalData`, "$.content") = ?
        ', [
            $this->taskGuid,
            $this->id,
            $this->type,
            $this->category,
            $this->field,
            $this->getContent(),
        ]);
    }

    /**
     * Spread current value of falsePositive-flag for all other occurrences of such [quality - content] pair found in this task
     *
     * @return array
     */
    public function spreadFalsePositive() : array {

        // Get content
        $content = $this->getContent();

        // If no content - return empty array
        if ($content === false) {
            return [];
        }

        // Get ids of similar qualities
        $ids = $this->getSimilarQualityIds();

        // Update similar qualities' falsePositive flag
        if ($ids) {

            // Prepare comma-separated list of ids
            $in = join(',', $ids);

            // Do spread
            $this->getTable()->getAdapter()->query("
                UPDATE `LEK_segment_quality` 
                SET `falsePositive` = ? 
                WHERE `taskGuid` = ? AND `id` IN ($in)
            ", [
                $this->falsePositive,
                $this->taskGuid
            ]);
        }

        // Return ids of similar qualities
        return $ids;
    }

    /**
     * Get array of ids of all other qualities similar to current quality across this task
     *
     * @return int
     */
    public function getSimilarQualityIds(): array {
        return $this->getContent() === false
            ? []
            : $this->getSimilarQualityStmt('ids')->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get quantity of all other qualities similar to current quality across this task
     *
     * @return int
     */
    public function getSimilarQualityQty() : int {
        return $this->getContent() === false
            ? 0
            : (int) $this->getSimilarQualityStmt('qty')->fetchColumn();
    }
}
