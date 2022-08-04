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
     * @param stdClass $data
     */
    public function setAdditionalData(?stdClass $data){
        if(is_object($data)){
            $jsonString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->additionalData = (empty($jsonString)) ? NULL : $jsonString;
        } else {
            $this->additionalData = NULL;
        }
    }
    /**
     * Compares our additionalData with the passed additional data if they are equal
     * @param stdClass $data
     * @return boolean
     */
    public function isAdditionalDataEqual(stdClass $data=NULL) : bool {
        if($data == NULL){
            return $this->additionalData === NULL;
        }
        $ours = (array) $this->getAdditionalData();
        $theirs = (array) $data;
        foreach($ours as $key => $val){
            if(!array_key_exists($key, $theirs) || $theirs[$key] !== $val){
                return false;
            }
        }
        return (count($ours) == count($theirs));
    }
    /** can be used for debugging
    public function save(){
        error_log('SAVED QUALITY '.$this->id.': type: '.$this->type.' category: '.$this->category.' falsePositive: '.$this->falsePositive);
        parent::save();
    }
    //*/
}