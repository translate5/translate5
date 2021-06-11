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
class editor_Models_Terminology_TbxObjects_DataType {


    /***
     * Collection of all data types with unique key from label-type-name
     * @var array
     */
    protected $data = [];

    /***
     * Load all attribute data types in data array.
     * Each row will be unique key from label-type-name and the row value will be the dataTypeId
     * @param bool $reload
     */
    public function loadData(bool $reload = false){
        if(!empty($this->data && !$reload)){
            return;
        }
        $dataType = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeDataType');
        /* @var $dataType editor_Models_Terminology_Models_AttributeDataType */
        $this->data = $dataType->loadAllTranslated();
    }

    /***
     * Get/calculate the attribute data type for given attribute tbx object.
     *
     * @param editor_Models_Terminology_TbxObjects_Attribute $attribute
     * @return mixed|string
     * @throws ZfExtended_ErrorCodeException
     */
    public function getForAttribute(editor_Models_Terminology_TbxObjects_Attribute $attribute){
        $labelTypeMatches = [];
        foreach ($this->data as $data) {
            if($data['label'] === $attribute->getElementName() && $data['type'] === $attribute->getType()){
                $labelTypeMatches[] = $data;
            }
        }

        if(empty($labelTypeMatches)){
            return '';
        }

        foreach ($labelTypeMatches as $match){
            if(in_array($attribute->getLevel(),explode(',',$match['level']))){
                return $match['id'];
            }
        }

        return $labelTypeMatches[0]['id'];
    }

    /***
     * Reset the current dataType collection array
     */
    public function resetData(){
        $this->data =  [];
    }
}
