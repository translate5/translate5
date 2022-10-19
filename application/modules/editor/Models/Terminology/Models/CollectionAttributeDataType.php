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
 * Class editor_Models_Terminology_Models_CollectionAttributeDataType
 *
 * @method integer getCollectionId() getCollectionId()
 * @method integer setCollectionId() setCollectionId(int $collectionId)
 * @method integer getDataTypeId() getDataTypeId()
 * @method integer setDataTypeId() setDataTypeId(int $dataTypeId)
 * @method integer getEnabled() getEnabled()
 * @method integer setEnabled() setEnabled(int $enabled)
 */
class editor_Models_Terminology_Models_CollectionAttributeDataType extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_CollectionAttributeDataType';
    protected $validatorInstanceClass   = 'editor_Models_Validator_Term_CollectionAttributeDataType';

    /***
     * Update the attribute data-type associations for the given term collection.
     * If the attribute data type exist for the collection, no row will be inserted.
     *
     * @param int $collectionId
     */
    public function updateCollectionAttributeAssoc(int $collectionId){
        $this->db->getAdapter()->query("
            INSERT INTO `terms_collection_attribute_datatype` (collectionId,dataTypeId)
            (
            SELECT `collectionId`, `dataTypeId` 
            FROM `terms_attributes` 
            WHERE collectionId = ? AND `dataTypeId` IS NOT NULL 
            GROUP BY collectionId,dataTypeId
            ) 
            ON DUPLICATE KEY UPDATE `exists` = 1
        ", $collectionId);
    }

    /**
     * Load record by given $collectionId and $dataTypeId
     *
     * @param int $collectionId
     * @param int $dataTypeId
     * @return $this
     */
    public function loadBy(int $collectionId, int $dataTypeId) {

        // Fetch row by $collectionId and $dataTypeId
        $this->row = $this->db->fetchRow('`collectionId` = "' . $collectionId . '" AND `dataTypeId` = "' . $dataTypeId . '"');

        // Return model instance itself
        return $this;
    }

    /**
     * Set `exists` flag and return model instance itself
     *
     * @param bool $exists
     * @return $this
     */
    public function setExists(bool $exists) {

        // Call parent
        parent::setExists($exists);

        // Return model instance itself
        return $this;
    }

    /**
     * @param int $collectionId
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function loadAllByCollectionId(int $collectionId) : array {

        // Fetch [dataTypeId => mappingInfo] pairs
        $data = $this->db->getAdapter()->query("
            SELECT `dataTypeId`, JSON_OBJECT('mappingId', `id`, 'enabled', `enabled`, 'exists', `exists`) 
            FROM `terms_collection_attribute_datatype` 
            WHERE `collectionId` = ?"
        , $collectionId)->fetchAll(PDO::FETCH_KEY_PAIR);

        // Json-decode mappingInfo
        return array_map(function($value){
            return json_decode($value);
        }, $data);
    }

    /**
     * @param int $collectionId
     */
    public function onTermCollectionInsert(int $collectionId) : void {
        $this->db->getAdapter()->query("
            INSERT INTO `terms_collection_attribute_datatype` (`collectionId`, `dataTypeId`, `enabled`, `exists`) 
            SELECT ?, `id`, `isTbxBasic`, 0 FROM `terms_attributes_datatype`
        ", $collectionId);
    }

    /**
     * @param int $dataTypeId
     * @param int $collectionId
     */
    public function onCustomDataTypeInsert(int $dataTypeId, int $collectionId) : void {
        $this->db->getAdapter()->query("
            INSERT INTO `terms_collection_attribute_datatype` (`collectionId`, `dataTypeId`, `enabled`, `exists`) 
            SELECT `id`, ?, `id` = ?, 0 FROM `LEK_languageresources` WHERE `resourceType` = 'termcollection';
        ", [$dataTypeId, $collectionId]);
    }
}
