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

    /**
     * Collection of all data types, 0-indexed
     *
     * @var array
     */
    protected $data = [];

    /**
     * Collection of all data types, `type`-indexed
     *
     * @var array
     */
    protected $byType = [];

    /**
     * Load existing data types to be easy accessible for further use
     *
     * @param bool $reload
     */
    public function loadData(bool $reload = false) {

        // If datatypes are already loaded, and no reload should be done - return
        if (!empty($this->data) && !$reload) {
            return;
        }

        /* @var $dataType editor_Models_Terminology_Models_AttributeDataType */
        $dataType = ZfExtended_Factory::get(editor_Models_Terminology_Models_AttributeDataType::class);

        // Load existing datatypes as 0-indexed array
        $this->data = $dataType->loadAll();

        // As long as we rely on that `type` is unique for each record across whole datatypes-table,
        // here we set up additional `type`-indexed array of datatype-records to quicker check possibility
        foreach ($this->data as $item) {
            $this->byType[ strtolower($item['type']) ] = $item;
        }
    }

    /***
     * Get/calculate the attribute data type for given attribute tbx object.
     *
     * @param editor_Models_Terminology_TbxObjects_Attribute $attribute
     * @return mixed|string
     * @throws ZfExtended_ErrorCodeException
     */
    public function getForAttribute(editor_Models_Terminology_TbxObjects_Attribute $attribute){

        // If node's type-attr is not empty
        if ($type = strtolower($attribute->type)) {

            // If matching datatype is found among existing ones by node's type-attr
            if ($item = $this->byType[$type] ?? 0) {

                // If matching datatype's expected node name does not match actual node name
                if ($item['label'] !== $attribute->elementName) {

                    // Backup node actual name
                    $attribute->wasElementName = $attribute->elementName;

                    // Spoof node actual name with expected one
                    $attribute->elementName = $item['label'];
                }

                // Return dataTypeId
                return $item['id'];
            }
        }

        $labelTypeMatches = [];
        foreach ($this->data as $data) {

            // if the label does not match the element name, continue with the search
            if($data['label'] !== $attribute->elementName){
                continue;
            }

            // if the type is empty or if the type matches the attribute dataType, use this as valid label match
            // there are attributes without type defined (ex: note) and they are valid tbx basic
            // compare with lowercase to ignore case sensitive
            if(ZfExtended_Utils::emptyString($data['type']) || (strtolower($data['type']) === strtolower($attribute->type))){
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
        $this->data = $this->byType = [];
    }
}
