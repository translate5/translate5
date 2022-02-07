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
class editor_Models_Terminology_TbxObjects_Attribute extends editor_Models_Terminology_TbxObjects_Abstract{
    CONST ATTRIBUTE_LEVEL_ENTRY = 'entry';
    CONST ATTRIBUTE_LEVEL_LANGUAGE = 'language';
    CONST ATTRIBUTE_LEVEL_TERM = 'term';
    CONST ATTRIBUTE_DEFAULT_DATATYPE = 'plainText';


    const TABLE_FIELDS = [
        'collectionId' => false,
        'termEntryId' => false,
        'language' => true,
        'termId' => false,
        'termTbxId' =>true,
        'dataTypeId' => true,
        'type' => true,
        'value' => true,
        'target' => true,
        // 'isCreatedLocally' => ?
        'createdBy' => false,
        'createdAt' => false,
        'updatedBy' => false,
        // 'updatedAt' => ?
        'termEntryGuid' => true,
        'langSetGuid' => false,
        'termGuid' => false,
        'guid' => false,
        'elementName' => true,
        'attrLang' => false,
        'isDescripGrp' => false
    ];

    public int $collectionId = 0;
    public ?int $termEntryId = null;
    public ?string $language = '';
    public ?int $termId = null;
    public ?string $termTbxId = '';
    public int $dataTypeId = 0;
    public ?string $type = '';
    public ?string $value = '';
    public ?string $target = '';
    //public int $isCreatedLocally = 0;
    public int $createdBy = 0;
    public string $createdAt = '';
    public int $updatedBy = 0;
    //public string $updatedAt = '';
    public ?string $termEntryGuid = null;
    public ?string $langSetGuid = null;
    public ?string $termGuid = null;
    public ?string $guid = null;
    public string $elementName = '';
    public string $attrLang = '';
    public ?int $isDescripGrp = 0;

    /**
     * @return string
     */
    public function getCollectionKey(): string
    {
        return $this->elementName . '-' .$this->type . '-' .$this->target .'-'. $this->termEntryGuid . '-' . $this->language . '-' . $this->termTbxId;
    }

    public function getLevel(): string {
        if(!empty($this->parentLangset) && !empty($this->parentTerm)){
            return self::ATTRIBUTE_LEVEL_TERM;
        }
        if(!empty($this->parentLangset) && empty($this->parentTerm)){
            return self::ATTRIBUTE_LEVEL_LANGUAGE;
        }
        if(!empty($this->parentEntry)){
            return self::ATTRIBUTE_LEVEL_ENTRY;
        }
        // non-existing level. this should never happen, if it does, this is not a valid attribute
        // 'E1357' => 'TBX Import: Could not import due unknown attribute level',
        throw new editor_Models_Terminology_Import_Exception('E1357', [
            'tbxAttribute values' => array_intersect_key(get_object_vars($this), $this::TABLE_FIELDS)
        ]);
    }
}
