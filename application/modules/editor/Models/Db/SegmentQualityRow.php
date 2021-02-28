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
 * DB Row Model for segment quality data
 * provides the available data fields for convenience and an improved getter/setter for accessing fields
 * 
 * @property int $id
 * @property int $segmentId
 * @property string $taskGuid
 * @property string $fields
 * @property string $type
 * @property string $category
 * @property int $startIndex
 * @property int $endIndex
 * @property int $falsePositive
 * @property int $mqmType
 * @property string $severity
 * @property string $comment
 */
class editor_Models_Db_SegmentQualityRow extends Zend_Db_Table_Row_Abstract {
    
    protected $_tableClass = 'editor_Models_Db_SegmentQuality';
    /**
     *
     * @return string[]
     */
    public function getFields(){
        if(empty($this->get('fields'))){
            return [];
        }
        return explode(',', $this->fields);
    }
    /**
     *
     * @param array $fields
     */
    public function setFields(array $fields){
        $val = (empty($fields)) ? '' : (is_array($fields) ? implode(',', $fields) : strval($fields));
        $this->fields = $val;
    }
    /**
     *
     * @param string $field
     */
    public function setField(string $field){
        $this->fields = empty($field) ? '' : $field;
    }
    /**
     *
     * @param string $field
     */
    public function addField(string $field){
        $fields = $this->getFields();
        $fields[] = $field;
        $this->setFields($fields);
    }
}