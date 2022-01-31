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
 * encapsulates the mapping of configurable attribute states to influence the final term status
 */
class editor_Models_Terminology_TermStatus
{
    const DEFAULT_TAG = 'termNote';
    const DEFAULT_TYPE_ADMINISTRATIVE_STATUS = 'administrativeStatus';
    const DEFAULT_TYPE_NORMATIVE_AUTHORIZATION = 'normativeAuthorization';

    /**
     * Contains the termNote types and values mapped to administrative states:
     * {
     *    "across_userdef_picklist_Verwendung_/_Usage": {
     *        "Verboten / Forbidden": "deprecatedTerm"
     *    }
     * }
     * so that such termNote is mapped to a final administrative state:
     * <termNote type="across_userdef_picklist_Verwendung_/_Usage">Verboten / Forbidden</termNote>
     *
     * @var array
     */
    static protected array $termStatusMap;

    /**
     * Collected term states not listed in statusMap
     * @var array
     */
    protected array $unknownStates = [];

    /** @var Zend_Config */
    protected Zend_Config $config;

    /**
     * editor_Models_Import_TermListParser_TbxFileImport constructor.
     * @throws Zend_Exception
     */
    public function __construct() {
        $this->config = Zend_Registry::get('config');

        //load termNoteMap
        if(empty(self::$termStatusMap)) {
            self::$termStatusMap = [];
            /* @var $db editor_Models_Db_Terminology_TermStatusMap */
            $db = ZfExtended_Factory::get('editor_Models_Db_Terminology_TermStatusMap');
            $loaded = $db->fetchAll()->toArray();
            foreach ($loaded as $map) {
                self::$termStatusMap[$map['tag']][$map['tagAttributeType']][$map['tagValue']] = $map['mappedStatus'];
            }
        }
    }

    /**
     * Reads out the default term status from the configured default administrative status value
     * @throws ZfExtended_Exception
     */
    public function getDefaultTermStatus() {
        $def = $this->config->runtimeOptions->tbx->defaultAdministrativeStatus;
        if(empty(self::$termStatusMap[self::DEFAULT_TAG][self::DEFAULT_TYPE_ADMINISTRATIVE_STATUS][$def])) {
            //this may not happen, therefore just a anonymous exception
            throw new ZfExtended_Exception('value of runtimeOptions->tbx->defaultAdministrativeStatus does not map to a valid value in terms_term_status_map');
        }
        return self::$termStatusMap[self::DEFAULT_TAG][self::DEFAULT_TYPE_ADMINISTRATIVE_STATUS][$def];
    }

    /**
     * return all available administrativeStatus values (the attribute values, not the mapped ones for the term)
     * @return array
     */
    public function getAdministrativeStatusValues(): array {
        return array_keys(self::$termStatusMap[self::DEFAULT_TAG][self::DEFAULT_TYPE_ADMINISTRATIVE_STATUS]);
    }

    /**
     * returns the calculated final term status for the updated attribute
     * @param editor_Models_Terminology_Models_AttributeModel $attribute
     * @param array $others
     * @return string
     * @throws ZfExtended_Exception
     */
    public function getStatusForUpdatedAttribute(editor_Models_Terminology_Models_AttributeModel $attribute, array &$others): string {
        // Get attributes, that may affect term status
        $elementsAndTypes = array_map('array_keys', self::$termStatusMap);

        $attribute->getTermId();
        $isMapped = in_array($attribute->getType(), $elementsAndTypes[$attribute->getElementName()] ?? []);
        $mappedAttributes = $attribute->loadByTerm($attribute->getTermId(), array_keys($elementsAndTypes), array_merge(... array_values($elementsAndTypes)));

        // the changed attribute status is synced to the other status relevant attributes
        //  if it is mapped state, use it, if not consider the default admin state, since it may have been changed
        $sourceType = $isMapped ? $attribute->getType() : $this::DEFAULT_TYPE_ADMINISTRATIVE_STATUS;
        return $this->fromTermNotes($mappedAttributes, $sourceType, $others);
    }

    /**
     * Sets on import the term status in the term out of the collected attributes
     * @param editor_Models_Terminology_TbxObjects_Term $term
     * @param editor_Models_Terminology_BulkOperation_Attribute $bulkAttributes
     * @param bool $admnStatFound
     * @return bool
     * @throws ZfExtended_Exception
     */
    public function setTermStatusOnImport(editor_Models_Terminology_TbxObjects_Term $term, editor_Models_Terminology_BulkOperation_Attribute $bulkAttributes, bool &$admnStatFound = false): bool {

        $importHelper = new editor_Models_Terminology_TermStatus_ImportHelper($this, $term);

        //loop over the collected attributes for the term and search for attributes which may influence the state
        $bulkAttributes->foreachToBeProcessed(function(editor_Models_Terminology_TbxObjects_Attribute $attribute) use ($importHelper) {
            $tag = $attribute->elementName;
            $type = $attribute->type;

            //if the attribute is of a different term (for sure we are in the same termEntry)
            if($attribute->parentTerm !== $importHelper->getTerm()) {
                return;
            }

            //if current termNote type is not allowed to provide a status then we jump over
            if (empty(self::$termStatusMap[$tag]) || !in_array($type, array_keys(self::$termStatusMap[$tag]))) {
                return;
            }

            $importHelper->investigateAttribute($attribute);
        });

        //returns by reference if a administrative status was found or not
        $admnStatFound = $importHelper->isAdmnStatFound();

        //from the following attribute the status should be used
        $attrToBeUsed = $importHelper->getToBeUsedAttribute();

        //if nothing found, use default value
        if(empty($attrToBeUsed)) {
            $term->status = $this->getDefaultTermStatus();
            return false;
        }

        $statusToBeUsed = $this->getStatusFromAttribute($attrToBeUsed->elementName, $attrToBeUsed->type, $attrToBeUsed->value);

        //now sync the to be used status to the other found status relevant attributes
        foreach ($importHelper->getAffectedAttributes() as $attribute) {
            if($attribute === $attrToBeUsed) {
                continue;
            }
            $attribute->value = $this->getAttributeValueFromTermStatus($attribute->elementName, $attribute->type, $statusToBeUsed);
        }

        //set the final value in the term too
        $term->status = $statusToBeUsed;
        return true;
    }

    /**
     * returns the translate5 internal available term status to the one given as termNote in TBX
     * @param array[] $mappedAttributes
     * @param string $sourceType
     * @param array[] $statusPerAttribute
     * @return string
     * @throws ZfExtended_Exception
     */
    protected function fromTermNotes(array $mappedAttributes, string $sourceType, array &$statusPerAttribute) : string
    {
        //first find the used value to a given type, default as fallback
        $usedStatus = $this->getDefaultTermStatus();
        foreach ($mappedAttributes as $attribute) {
            $tag = $attribute['elementName'];
            $type = $attribute['type'];
            //if current attribute tag or type is not allowed to provide a status then we jump over
            if (empty(self::$termStatusMap[$tag]) || !in_array($type, array_keys(self::$termStatusMap[$tag]))) {
                continue;
            }
            //value must be given, on new attributes it may be empty
            if($type == $sourceType && strlen($attribute['value']) > 0) {
                $usedStatus = $this->getStatusFromAttribute($tag, $type, $attribute['value']);
            }
        }

        //then sync this value into the other datatypes, returned as reference
        foreach ($mappedAttributes as $attribute) {
            $type = $attribute['type'];
            $tag = $attribute['elementName'];
            //if current termNote type is not allowed to provide a status then we jump over
            if (empty(self::$termStatusMap[$tag]) || !in_array($type, array_keys(self::$termStatusMap[$tag]))) {
                continue;
            }
            if($type != $sourceType) {
                $statusPerAttribute[$attribute['id']] = [
                    'dataTypeId' => $attribute['dataTypeId'],
                    'status' => $this->getAttributeValueFromTermStatus($tag, $type, $usedStatus)
                ];
            }
        }

        return $usedStatus;
    }

    /**
     * returns the mapped status to given attribute type and value, or null if nothing found
     * @param string $tag
     * @param string $tagAttributeType
     * @param string $tagValue
     * @return string|null
     * @throws ZfExtended_Exception
     */
    protected function getStatusFromAttribute(string $tag, string $tagAttributeType, string $tagValue): ?string {
        //return mapped status from specific configuration
        if (!empty(self::$termStatusMap[$tag][$tagAttributeType][$tagValue])) {
            return self::$termStatusMap[$tag][$tagAttributeType][$tagValue];
        }

        //collect unknown state
        $logValue = $tag.'#'.$tagAttributeType.': '.$tagValue;
        if (!in_array($logValue, $this->unknownStates)) {
            $this->unknownStates[] = $logValue;
        }

        //and return default
        return $this->getDefaultTermStatus();
    }

    /**
     * returns the collected unknown states - if any
     * @return array
     */
    public function getUnknownStates(): array {
        return $this->unknownStates;
    }

    /**
     * Synchronizes the valid term status values into the datatypes picklists
     */
    public function syncStatusToDataTypes() {
        /* @var $db editor_Models_Db_Terminology_TermStatusMap */
        $db = ZfExtended_Factory::get('editor_Models_Db_Terminology_TermStatusMap');
        $db->getAdapter()->query("UPDATE terms_attributes_datatype dt, (
                SELECT tag, tagAttributeType as type, GROUP_CONCAT(tagValue) AS picklistValues
                FROM terms_term_status_map
                GROUP BY tag,tagAttributeType
            ) as s
        SET dt.picklistValues = s.picklistValues, dt.dataType = 'picklist'
        WHERE dt.label = s.tag AND dt.level = 'term' AND dt.type = s.type");
    }

    /**
     * returns the attribute administrativeStatus value from a given term status (returns the first matching), returns the first one too as fallback
     * @param string $termStatus
     * @return string
     */
    public function getAdmnStatusFromTermStatus(string $termStatus): string {
        return $this->getAttributeValueFromTermStatus(self::DEFAULT_TAG, self::DEFAULT_TYPE_ADMINISTRATIVE_STATUS, $termStatus) ?? reset(self::$termStatusMap[self::DEFAULT_TYPE_ADMINISTRATIVE_STATUS]);
    }

    /**
     * returns the attributes value (by tag and type) from a given term status (returns the first matching), returns the first one too as fallback
     * @param string $tag
     * @param string $termStatus
     * @param string $type
     * @return string|null
     */
    protected function getAttributeValueFromTermStatus(string $tag, string $type, string $termStatus): ?string {
        $result = array_search($termStatus, self::$termStatusMap[$tag][$type]);
        if($result === false) {
            return null;
        }
        return $result;
    }
}

class editor_Models_Terminology_TermStatus_ImportHelper {
    private bool $admnStatFound = false;

    /* @var $foundByPrecedenceType editor_Models_Terminology_TbxObjects_Attribute[] */
    private array $foundByPrecedenceType = [];

    /* @var $affectedAttributes editor_Models_Terminology_TbxObjects_Attribute[] */
    private array $affectedAttributes = [];

    private editor_Models_Terminology_TbxObjects_Term $term;

    private editor_Models_Terminology_TermStatus $statusInstance;

    public function __construct(editor_Models_Terminology_TermStatus $statusInstance, editor_Models_Terminology_TbxObjects_Term $term) {
        $this->term = $term;
        $this->statusInstance = $statusInstance;
    }

    /**
     * Investigates the given attribute and collects data for further processing about it
     * @param editor_Models_Terminology_TbxObjects_Attribute $attribute
     */
    public function investigateAttribute(editor_Models_Terminology_TbxObjects_Attribute $attribute)
    {
        $type = $attribute->type;

        //we collect all attributes, which are affected by the status map for later updating
        $this->affectedAttributes[] = $attribute;

        if($type == $this->statusInstance::DEFAULT_TYPE_ADMINISTRATIVE_STATUS) {
            $this->admnStatFound = true;
        }

        if($type != $this->statusInstance::DEFAULT_TYPE_ADMINISTRATIVE_STATUS && $type != $this->statusInstance::DEFAULT_TYPE_NORMATIVE_AUTHORIZATION) {
            $type = 'custom';
        }

        //if multiple, then we collect only the first one
        if(isset($this->foundByPrecedenceType[$type])) {
            return;
        }

        //collect all results for the different types
        $this->foundByPrecedenceType[$type] = $attribute;
    }

    /**
     * returns the attribute from which the status should be used, keeps a defined precedence
     * @return editor_Models_Terminology_TbxObjects_Attribute|null
     */
    public function getToBeUsedAttribute(): ?editor_Models_Terminology_TbxObjects_Attribute
    {
        /* @var $attrToBeUsed editor_Models_Terminology_TbxObjects_Attribute */
        // precedence by $termNote->type: administrative before normative before custom states if given multiple on import
        if(!empty($this->foundByPrecedenceType[$this->statusInstance::DEFAULT_TYPE_ADMINISTRATIVE_STATUS])) {
            return $this->foundByPrecedenceType[$this->statusInstance::DEFAULT_TYPE_ADMINISTRATIVE_STATUS];
        }
        if(!empty($this->foundByPrecedenceType[$this->statusInstance::DEFAULT_TYPE_NORMATIVE_AUTHORIZATION])) {
            return $this->foundByPrecedenceType[$this->statusInstance::DEFAULT_TYPE_NORMATIVE_AUTHORIZATION];
        }
        if(!empty($this->foundByPrecedenceType['custom'])) {
            return $this->foundByPrecedenceType['custom'];
        }
        return null;
    }

    /**
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function getTerm(): editor_Models_Terminology_TbxObjects_Term
    {
        return $this->term;
    }

    /**
     * @return editor_Models_Terminology_TbxObjects_Attribute[]
     */
    public function getAffectedAttributes(): array
    {
        return $this->affectedAttributes;
    }

    /**
     * @return bool
     */
    public function isAdmnStatFound(): bool
    {
        return $this->admnStatFound;
    }
}