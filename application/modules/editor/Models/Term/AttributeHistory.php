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
 * @method string getId()
 * @method void setId(integer $id)
 * @method string getAttrId()
 * @method void setAttrId(integer $attrId)
 * @method string getCollectionId()
 * @method void setCollectionId(integer $collectionId)
 * @method string getTermEntryId()
 * @method void setTermEntryId(integer $termEntryId)
 * @method string getLanguage()
 * @method void setLanguage(string $language)
 * @method string getTermId()
 * @method void setTermId(integer $termId)
 * @method string getDataTypeId()
 * @method void setDataTypeId(integer $dataTypeId)
 * @method string getType()
 * @method void setType(string $type)
 * @method string getValue()
 * @method void setValue(string $value)
 * @method string getTarget()
 * @method void setTarget(string $target)
 * @method string getIsCreatedLocally()
 * @method void setIsCreatedLocally(integer $isCreatedLocally)
 * @method string getUpdatedBy()
 * @method void setUpdatedBy(integer $userId)
 * @method string getUpdatedAt()
 * @method void setUpdatedAt(string $updatedAt)
 * @method string getTermEntryGuid()
 * @method void setTermEntryGuid(string $termEntryGuid)
 * @method string getLangSetGuid()
 * @method void setLangSetGuid(string $langSetGuid)
 * @method string getTermGuid()
 * @method void setTermGuid(string $termGuid)
 * @method string getGuid()
 * @method void setGuid(string $guid)
 * @method string getElementName()
 * @method void setElementName(string $elementName)
 * @method string getAttrLang()
 * @method void setAttrLang(string $attrLang)
 * @method string getDataType()
 * @method void setDataType(string $dataType)
 */
use editor_Models_Terminology_Models_AttributeModel as AttributeModel;

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

    /**
     * Get array of history-records for a given attribute id
     *
     * @param int $attrId
     * @param string|null $mainLang This is applicable only when we're fetching history for termEntry-level ref-attribute
     *                              (e.g. having type='crossReference'), and if so - then we try to find the termEntry
     *                              that this ref-attribute is pointing to, so that $mainLang is used to choose the term
     *                              to be used as representation of that destination-termEntry, but keep in mind that
     *                              there is quite complex precedence logic for this, see AttributeModel::refTarget() for details
     *
     * @return array
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getByAttrId(int $attrId, ?string $mainLang = null): array {

        // Fetch history
        $history = $this->db->getAdapter()->query('
            SELECT 
              `h`.`id`, 
              `h`.`value`,
              `h`.`target`, 
              `h`.`type`,
              `h`.`updatedAt`, 
              CONCAT(`u`.`firstName`, " ", `u`.`surName`) AS `updatedBy`
            FROM 
              `terms_attributes_history` `h` 
              LEFT JOIN `Zf_users` `u` ON `h`.`updatedBy` = `u`.`id`
            WHERE `h`.`attrId` = ?
            ORDER BY `h`.`updatedAt` DESC
        ', $attrId)->fetchAll();

        // Load attribute model instance
        $attr = ZfExtended_Factory::get(AttributeModel::class);
        $attr->load($attrId);

        // Get user who last updated that attribute
        try {
            $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            $user->load($attr->getUpdatedBy());
            $updatedBy = $user->getFirstName() . ' ' . $user->getSurName();
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $updatedBy = null;
        }

        // Prepend current state as a record into the history
        array_unshift($history, [
            'id' => 0,
            'value' => $attr->getValue(),
            'target' => $attr->getTarget(),
            'type' => $attr->getType(),
            'updatedAt' => $attr->getUpdatedAt(),
            'updatedBy' => $updatedBy,
        ]);

        // Get elementName
        $elementName = $attr->getElementName();

        // If it's a ref-attribute
        if ($elementName === 'ref') {

            // Get level
            $level = $attr->getTermId() ? 'term': 'termEntry';

            // Get languages
            $termLang = $attr->getLanguage();
            $mainLang = $mainLang ?? $termLang;

            // Prepare targets
            foreach ($history as $record) {

                // Push attr into refs array
                $refA[$level][$record['id']] = $record;

                // If `target` prop is not empty - collect 'target'
                // for all ref-attributes, to be able to query for ref-targets just once
                if ($record['target']) $refTargetIdA[$record['target']] = [$level, $record['id']];
            }

            // Get ref data by targets and priority language
            AttributeModel::refTarget($refA, $refTargetIdA ?? [], [$termLang, $mainLang], $level);
        }

        // Foreach history-record
        foreach ($history as &$record) {

            // Setup an isValidUrl-flag
            if ($elementName === 'xref') {
                $record['isValidUrl'] = preg_match('~ href="([^"]+)"~', editor_Utils::url2a($record['target']));

            // Setup reference data, including isValidTbx-flag
            } else if ($elementName === 'ref') {
                $record = $refA[$level][$record['id']];
            }
        }

        // Return history, including current state
        return $history;
    }
}