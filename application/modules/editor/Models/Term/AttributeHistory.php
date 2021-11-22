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
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method integer getAttrId() getAttrId()
 * @method void setAttrId() setAttrId(integer $attrId)
 * @method integer getCollectionId() getCollectionId()
 * @method void setCollectionId() setCollectionId(integer $collectionId)
 * @method integer getTermEntryId() getTermEntryId()
 * @method void setTermEntryId() setTermEntryId(integer $termEntryId)
 * @method string getLanguage() getLanguage()
 * @method void setLanguage() setLanguage(string $language)
 * @method integer getTermId() getTermId()
 * @method void setTermId() setTermId(integer $termId)
 * @method integer getDataTypeId() getDataTypeId()
 * @method void setDataTypeId() setDataTypeId(integer $dataTypeId)
 * @method string getType() getType()
 * @method void setType() setType(string $type)
 * @method string getValue() getValue()
 * @method void setValue() setValue(string $value)
 * @method string getTarget() getTarget()
 * @method void setTarget() setTarget(string $target)
 * @method integer getIsCreatedLocally() getIsCreatedLocally()
 * @method void setIsCreatedLocally() setIsCreatedLocally(integer $isCreatedLocally)
 * @method integer getUpdatedBy() getUpdatedBy()
 * @method void setUpdatedBy() setUpdatedBy(integer $userId)
 * @method string getUpdatedAt() getUpdatedAt()
 * @method void setUpdatedAt() setUpdatedAt(string $updatedAt)
 * @method string getTermEntryGuid() getTermEntryGuid()
 * @method void setTermEntryGuid() setTermEntryGuid(string $termEntryGuid)
 * @method string getLangSetGuid() getLangSetGuid()
 * @method void setLangSetGuid() setLangSetGuid(string $langSetGuid)
 * @method string getTermGuid() getTermGuid()
 * @method void setTermGuid() setTermGuid(string $termGuid)
 * @method string getGuid() getGuid()
 * @method void setGuid() setGuid(string $guid)
 * @method string getElementName() getElementName()
 * @method void setElementName() setElementName(string $elementName)
 * @method string getAttrLang() getAttrLang()
 * @method void setAttrLang() setAttrLang(string $attrLang)
 * @method string getDataType() getDataType()
 * @method void setDataType() setDataType(string $dataType)
 */
class editor_Models_Term_AttributeHistory extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Term_AttributeHistory';

    /**
     * Get values, that were set up by tbx import and but are in history now
     *
     * @param array $attrIds
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getImportedByAttrIds(array $attrIds): array {

        // Prepare WHERE clause
        $where = $this->db->getAdapter()
            ->quoteInto('`attrId` IN (?)', $attrIds ?: [0])
            . ' AND `isCreatedLocally` = "0"';

        // Get imported values
        return $this->db->getAdapter()->query('
            SELECT `attrId`, `value`, `target` 
            FROM `terms_attributes_history`
            WHERE '. $where
        )->fetchAll(PDO::FETCH_UNIQUE);
    }
}