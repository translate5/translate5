<?php

use Doctrine\DBAL\Exception;

/**
 * Class editor_Models_Terms_Attributes
 * Attributes Instance
 *
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getType() getType()
 * @method string setType() setType(string $type)
 * @method string getLanguage() getLanguage()
 * @method string setLanguage() setLanguage(string $language)
 * @method string getElementName() getElementName()
 * @method string setElementName() setElementName(string $elementName)
 * @method string getTarget() getTarget()
 * @method string setTarget() setTarget(string $target)
 * @method string getValue() getValue()
 * @method string setValue() setValue(string $value)
 * @method integer getCollectionId() getCollectionId()
 * @method integer setCollectionId() setCollectionId(integer $collectionId)
 * @method string getEntryId() getEntryId()
 * @method string setEntryId() setEntryId(string $entryId)
 * @method string getTermEntryGuid() getTermEntryGuid()
 * @method string setTermEntryGuid() setTermEntryGuid(string $termEntryGuid)
 * @method string getLangSetGuid() getLangSetGuid()
 * @method string setLangSetGuid() setLangSetGuid(string $langSetGuid)
 * @method string getTermGuid() getTermGuid()
 * @method string setTermGuid() setTermGuid(string $termGuid)
 * @method string getUserName() getUserName()
 * @method string setUserName() setUserName(string $userName)
 * @method string getUserGuid() getUserGuid()
 * @method string setUserGuid() setUserGuid(string $userGuid)
 * @method string getGuid() getGuid()
 * @method string setGuid() setGuid(string $guid)
 */
class editor_Models_Terminology_Models_AttributeModel extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_Attribute';

    /**
     * editor_Models_Terms_Attribute constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    public function getAttributeCollectionByEntryId($collectionId, $entryId): array
    {
        $attributeByKey = [];

        $query = "SELECT * FROM terms_attributes WHERE collectionId = :collectionId AND entryId = :entryId";
        $queryResults = $this->db->getAdapter()->query($query, ['collectionId' => $collectionId, 'entryId' => $entryId]);

        foreach ($queryResults as $key => $attribute) {
            $attributeByKey[$attribute['elementName'].'-'.$attribute['language'].'-'.$attribute['termId']] = $attribute;
        }

        return $attributeByKey;
    }

    public function createAttributes(string $sqlParam, string $sqlFields, array $sqlValue)
    {
        $this->init();
        $insertValues = rtrim($sqlParam, ',');

        $query = "INSERT INTO terms_attributes ($sqlFields) VALUES $insertValues";

        return $this->db->getAdapter()->query($query, $sqlValue);
    }

    /**
     * @param array $attributes
     * @return bool
     */
    public function updateAttributes(array $attributes): bool
    {
        foreach ($attributes as $attribute) {
            $this->db->update($attribute, ['id=?'=> $attribute['id']]);
        }

        return true;
    }
}
