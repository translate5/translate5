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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/
/***
 * @property editor_Models_Db_SegmentQualityRow $row
 * @method void setId() setId(int $id)
 * @method int getId() getId()
 * @method void setSegmentId() setSegmentId(int $segmentId)
 * @method int getSegmentId() getSegmentId()
 * @method void setTaskGuid() setTaskGuid(string $taskGuid)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setType() setType(string $type)
 * @method string getType() getType()
 * @method void setCategory() setCategory(string $category)
 * @method string getCategory() getCategory()
 * @method void setStartIndex() setStartIndex(int $startIndex)
 * @method int getStartIndex() getStartIndex()
 * @method void setEndIndex() setEndIndex(int $endIndex)
 * @method int getEndIndex() getEndIndex()
 * @method void setFalsePositive() setFalsePositive(int $falsePositive)
 * @method int getFalsePositive() getFalsePositive()
 * @method void setQmtype() setQmtype(int $qmtype)
 * @method int getQmtype() getQmtype()
 * @method void setSeverity() setSeverity(string $severity)
 * @method string getSeverity() getSeverity()
 * @method void setComment() setComment(string $comment)
 * @method string getComment() getComment()
*/

class editor_Models_SegmentQuality extends ZfExtended_Models_Entity_Abstract {
    
    protected $dbInstanceClass = 'editor_Models_Db_SegmentQuality';
    protected $validatorInstanceClass = 'editor_Models_Validator_SegmentQuality';
    
    /**
     * 
     * @return string[]
     */
    public function getFields(){
        return $this->row->getFields();
    }
    /**
     * 
     * @param array $fields
     */
    public function setFields(array $fields){
        $this->row->setFields($fields);
    }
    /**
     * 
     * @param string $field
     */
    public function setField(string $field){
        $this->row->fields = empty($field) ? '' : $field;
    }
    /**
     * 
     * @param string $field
     */
    public function addField(string $field){
        $this->row->addField($field);
    }
}
