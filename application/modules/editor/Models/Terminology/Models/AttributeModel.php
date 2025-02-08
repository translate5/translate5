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
 * Attributes Instance
 *
 * @method string getId()
 * @method void setId(integer $id)
 * @method string getCollectionId()
 * @method void setCollectionId(integer $collectionId)
 * @method string getTermEntryId()
 * @method void setTermEntryId(string $termEntryId)
 * @method string getLanguage()
 * @method void setLanguage(string $language)
 * @method string getTermId()
 * @method void setTermId(int $termId)
 * @method string getTermTbxId()
 * @method void setTermTbxId(string $termTbxId)
 * @method string getDataTypeId()
 * @method void setDataTypeId(int $dataTypeId)
 * @method string getType()
 * @method void setType(string $type)
 * @method string getValue()
 * @method void setValue(string $value)
 * @method string getTarget()
 * @method void setTarget(string $target)
 * @method string getIsCreatedLocally()
 * @method void setIsCreatedLocally(int $isCreatedLocally)
 * @method string getCreatedBy()
 * @method void setCreatedBy(int $userId)
 * @method string getCreatedAt()
 * @method void setCreatedAt(string $createdAt)
 * @method string getUpdatedBy()
 * @method void setUpdatedBy(int $userId)
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
 */
class editor_Models_Terminology_Models_AttributeModel extends editor_Models_Terminology_Models_Abstract
{
    public const ATTR_LEVEL_ENTRY = 'termEntry';

    public const ATTR_LEVEL_LANGSET = 'langSet';

    public const ATTR_LEVEL_TERM = 'term';

    protected $dbInstanceClass = 'editor_Models_Db_Terminology_Attribute';

    protected $validatorInstanceClass = 'editor_Models_Validator_Term_Attribute';

    /**
     * loads attributes of a term, optionally filtered by element and type
     */
    public function loadByTerm(int $termId, array $element = [], array $type = []): array
    {
        $s = $this->db->select()
            ->where('termId = ?', $termId);
        if (! empty($element)) {
            $s->where('elementName in (?)', $element);
        }
        if (! empty($type)) {
            $s->where('type in (?)', $type);
        }

        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * Check if given AttrType is for responsable person
     * Info: the responsable person type is saved in with different values in some tbx files
     * @return boolean
     */
    public function isResponsablePersonAttribute(string $type = null): bool
    {
        if (! empty($type)) {
            return $type == 'responsiblePerson' || $type == 'responsibility';
        }
        if ($this->getType() != null) {
            return $this->getType() == 'responsiblePerson' || $this->getType() == 'responsibility';
        }

        return false;
    }

    /**
     * returns true if the attribute is proposable according to its values.
     * Additional ACL checks must be done outside (in the controllers)
     * @param string $name optional, if both parameters are empty the values from $this are used
     * @param string $type optional, if both parameters are empty the values from $this are used
     */
    public function isProposable($name = null, $type = null)
    {
        if (empty($name) && empty($type)) {
            $name = $this->getElementName();
            $type = $this->getType();
        }

        return ! ($name == 'date'
            || $name == 'termNote' && $type == 'processStatus'
            || $name == 'transacNote' && ($this->isResponsablePersonAttribute($type))
            || $name == 'transac' && ($type == 'origination' || $type == 'origination')
            || $name == 'transac' && $type == 'modification');
    }

    /**
     * @param array $misc
     * @return mixed
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function update($misc = [])
    {
        $orig = $this->row->getCleanData();

        // Set up `isCreatedLocally` flag to 1 if not explicitly given
        if (! $this->isModified('isCreatedLocally')) {
            $this->setIsCreatedLocally(1);
        }

        // Call parent
        $return = parent::save();

        // If current data is not equal to original data
        if ($this->toArray() != $orig) {
            // Prepare data for history record
            $init = $orig;
            $init['attrId'] = $orig['id'];
            unset($init['id'], $init['createdBy'], $init['createdAt']);

            // Create history instance
            $history = ZfExtended_Factory::get('editor_Models_Term_AttributeHistory');

            // Init with data
            $history->init($init);

            // Save
            $history->save();
        }

        // Affect transacgrp-records and return modification string, e.g. '<user name>, <date in d.m.Y H:i:s format>'
        if (isset($misc['userName'])) {
            $return = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->affectLevels($misc['userName'], $misc['userGuid'], $this->getTermEntryId(), $this->getLanguage(), $this->getTermId());
        }

        // Return
        return $return;
    }

    /**
     * @param array $misc
     * @return mixed|string
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function insert($misc = [])
    {
        // Call parent
        $return = parent::save();

        // Affect transacgrp-records
        if ($misc['userName'] ?? 0) {
            $return = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->affectLevels($misc['userName'], $misc['userGuid'], $this->getTermEntryId(), $this->getLanguage(), $this->getTermId());
        }

        // Load mapping-record
        $mapping = ZfExtended_Factory
            ::get('editor_Models_Terminology_Models_CollectionAttributeDataType')
                ->loadBy($this->getCollectionId(), $this->getDataTypeId());

        // If mapping-record's `exists` flag is false, e.g there are no other attributes with such dataTypeId in same TermCollection
        if (! $mapping->getExists()) {
            // Set `exists` flag to true for mapping-record
            $mapping->setExists(true)->save();
        }

        // Return
        return $return;
    }

    /**
     * @param array $misc
     * @return mixed
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function delete($misc = [])
    {
        // If attribute's `type` is 'figure' and `target` is not empty
        if ($this->getType() == 'figure' && $this->getTarget()) {
            // Setup terms_images model
            $i = ZfExtended_Factory::get('editor_Models_Terminology_Models_ImagesModel');

            // If `terms_images` record found by attr's target - delete that record
            if ($i->loadByTargetId($this->getCollectionId(), $this->getTarget())) {
                $i->delete();
            }
        }

        // If attribute's `type` is 'definition' - check whether we should update `terms_term`.`definition`
        // and if yes, what should be the new value and what terms should be affected
        if ($this->getType() == 'definition') {
            $return['definition'] = $this->replicateDefinition('deleted');
        }

        // Affect transacgrp-records and return modification string, e.g. '<user name>, <date in d.m.Y H:i:s format>'
        if ($misc['userName'] ?? 0) {
            $return['updated'] = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->affectLevels($misc['userName'], $misc['userGuid'], $this->getTermEntryId(), $this->getLanguage(), $this->getTermId());
        }

        // Set isLast-check-skip flag
        $skipCheckIsLast = $misc['skipCheckIsLast'] ?? false;

        // If there are no other attributes with such dataTypeId in same TermCollection
        if (! $skipCheckIsLast && $this->isLastOfDataTypeInCollection()) {
            // Remove mapping
            ZfExtended_Factory
                ::get('editor_Models_Terminology_Models_CollectionAttributeDataType')
                    ->loadBy($this->getCollectionId(), $this->getDataTypeId())
                    ->setExists(false)
                    ->save();
        }

        // Call parent
        parent::delete();

        // Return
        return $return ?? null;
    }

    /**
     * Check whether current attribute is the last having it's dataTypeId within it's collectionId
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function isLastOfDataTypeInCollection(): bool
    {
        return ! $this->db->getAdapter()->query('
            SELECT `id` 
            FROM `terms_attributes` 
            WHERE 1
              AND `collectionId` = ? 
              AND `dataTypeId` = ? 
              AND `id` != ? 
            LIMIT 1
        ', [$this->getCollectionId(), $this->getDataTypeId(), $this->getId()])->fetchColumn();
    }

    /***
     * Remove old attribute proposals from the collection by given date.
     *
     * @param array $collectionIds
     * @param string $olderThan
     * @return boolean
     * @throws Zend_Db_Statement_Exception
     */
    public function removeProposalsOlderThan(array $collectionIds, string $olderThan): bool
    {
        // Get ids of attrs, that were created or updated after tbx-import
        $attrIdA = $this->db->getAdapter()->query('
            SELECT `id` 
            FROM `terms_attributes` 
            WHERE TRUE
              AND `isCreatedLocally` = "1" 
              AND `collectionId` IN (' . implode(',', $collectionIds) . ')
        ')->fetchAll(PDO::FETCH_COLUMN);

        if (empty($attrIdA)) {
            return false;
        }

        // Get tbx-imported values for `value` and `target` props, that now have changed values in attributes-table
        $tbxA = $this->db->getAdapter()->query('
            SELECT `attrId`, `value`, `target` 
            FROM `terms_attributes_history`
            WHERE TRUE
              AND `isCreatedLocally` = "0"
              AND `attrId` IN (' . implode(',', $attrIdA) . ')
        ')->fetchAll(PDO::FETCH_UNIQUE);

        // Distinct between created and updated
        $attrIdA_updated = array_keys($tbxA);
        $attrIdA_created = array_diff($attrIdA, $attrIdA_updated);

        // Affected counter
        $affectedQty = 0;

        // Delete created attrs, that are older than $olderThan
        if (! empty($attrIdA_created)) {
            //this speeds incredibly up the SQL since no cast must be done in SQL then
            $affectedQty += $this->db->delete([
                'createdAt < ?' => $olderThan,
                'collectionId in (?)' => $collectionIds,
                'id in (?)' => $attrIdA_created,
            ]);
        }

        // Overwrite $attrIdA_updated array for it to keep only ids of attributes, that were last updated before $olderThan arg
        if ($attrIdA_updated) {
            $attrIdA_updated = $this->db->getAdapter()->query($sql = '
            SELECT `id` 
            FROM `terms_attributes` 
            WHERE TRUE
              AND `id` IN (' . implode(',', $attrIdA_updated) . ')
              AND `updatedAt` < ? 
        ', $olderThan)->fetchAll(PDO::FETCH_COLUMN);
        }

        // Revert updated attrs' `value` and `target` props to tbx-imported values
        foreach ($attrIdA_updated as $attrId) {
            $this->load($attrId);
            $this->setValue($tbxA[$attrId]['value']);
            $this->setTarget($tbxA[$attrId]['target']);
            $this->setIsCreatedLocally(0);
            $this->save();

            // Increase counter
            $affectedQty++;
        }

        // Delete history-records for $attrIdA_updated attrs
        if ($attrIdA_updated) {
            ZfExtended_Factory::get('editor_Models_Term_AttributeHistory')->db->delete([
                'attrId in (?)' => $attrIdA_updated,
            ]);
        }

        // Return
        return $affectedQty > 0;
    }

    /**
     * @param string $level
     * @throws Zend_Db_Statement_Exception
     */
    public static function refTarget(array &$refA, array $refTargetIdA, array $prefLangA, $level = null)
    {
        // If no ref-attributes having non-empty target-prop found - return
        if (! $refTargetIdA) {
            return;
        }

        // Shortcut to arg passed to IN (?)
        $in = '"' . implode('","', array_keys($refTargetIdA)) . '"';

        // Which tbx column to use
        $tbxCol = $level
            ? '`' . ($level == 'term' ? 'termTbxId' : 'termEntryTbxId') . '`'
            : 'IF(`termTbxId` IN (' . $in . '), `termTbxId`, `termEntryTbxId`)';

        // Build WHERE clause
        $where = $level
            ? '`' . ($level == 'term' ? 'termTbxId' : 'termEntryTbxId') . '` IN (' . $in . ')'
            : '`termTbxId` IN (' . $in . ') OR `termEntryTbxId` IN (' . $in . ')';

        // Get data by ref targets
        $dataByRefTargetIdA = editor_Utils::db()->query($_ = '
            SELECT
              ' . $tbxCol . ' AS `tbx`,
              `termEntryId`,
              `collectionId`,
              JSON_OBJECTAGG(
                `language`,
                CONCAT(`id`, ",", `languageId`, ",", `term`, ",", `processStatus`, ",", `status`)
              ) AS `json`
            FROM `terms_term`
            WHERE ' . $where . '
            GROUP BY `tbx`            
        ')->fetchAll(PDO::FETCH_UNIQUE);

        // Simulate situation when current search-term language is 'en-us', but refData contains term only for 'en-gb'
        /*$dataByRefTargetIdA['626a86ec-4979-43e5-8293-6bb6532b7cf5']['json']
            = str_replace('de-de', 'fr-fr', $dataByRefTargetIdA['626a86ec-4979-43e5-8293-6bb6532b7cf5']['json']);*/

        // Get preferred languages groups
        $prefLangGroupA = [];
        foreach ($prefLangA as $prefLang) {
            $prefLangGroupA[substr($prefLang, 0, 2)] = true;
        }

        // Foreach data item
        foreach ($dataByRefTargetIdA as $tbxId => &$refData) {
            // Decode json
            $refData['json'] = json_decode($refData['json'], true);

            // If preferred languages belong to same group, try to find such terms as a priority choice
            // For example we have:
            // 1. $prefLangA = ['en-gb', 'en-us'], where
            //   'en-us' is a priority 1 (center-panel clicked term's language), and
            //   'en-gb' is a priority 2 (main term-search language)
            // 2. reference-terms found for 'en-gb' and 'en-au' languages
            // So, 'en-au' may be chosen if it appears first before 'en-gb' in the database,
            // despite 'en-gb' is more preferable, so below we're preventing that
            if (count($prefLangGroupA) == 1) {
                foreach ($prefLangA as $prefLang) {
                    if ($value = $refData['json'][$prefLang] ?? null) {
                        $refData['language'] = $prefLang;
                        list(
                            $refData['termId'],
                            $refData['languageId'],
                            $refData['value'],
                            $refData['processStatus'],
                            $refData['status']
                        ) = explode(',', $value);

                        // Jump to next $refData
                        continue 2;
                    }
                }
            }

            // Foreach preferred language
            foreach ($prefLangA as $prefLang) {
                // If term exists for the preferred language
                if (count($prefLangGroupA) >= 1 && $value = $refData['json'][$prefLang] ?? null) {
                    $refData['language'] = $prefLang;
                    list(
                        $refData['termId'],
                        $refData['languageId'],
                        $refData['value'],
                        $refData['processStatus'],
                        $refData['status']
                    ) = explode(',', $value);

                    //
                    break;

                    // Else if language of clicked left result is like 'xx-yy',
                    // e.g. belongs to a group 'xx', or is like 'xx'
                } else {
                    // Try to find term for the language within that group
                    foreach ($refData['json'] as $lang => $value) {
                        if (substr($lang, 0, 2) == substr($prefLang, 0, 2)) {
                            $refData['language'] = $lang;
                            list(
                                $refData['termId'],
                                $refData['languageId'],
                                $refData['value'],
                                $refData['processStatus'],
                                $refData['status']
                            ) = explode(',', $value);

                            break 2;
                        }
                    }
                }
            }

            // If term for preferred languages is still not found - just use first
            if (! isset($refData['language'])) {
                $refData['language'] = array_keys($refData['json'])[0];
                list(
                    $refData['termId'],
                    $refData['languageId'],
                    $refData['value'],
                    $refData['processStatus'],
                    $refData['status']
                ) = explode(',', $refData['json'][$refData['language']]);
            }

            // Unset data for other languages
            unset($refData['json']);
        }

        // Apply ref data. Actually, the following props will be:
        // 1. Overwritten: language, value
        // 2. Added: termEntryId
        foreach ($refTargetIdA as $refTargetId => $info) {
            // Pick level and attributeId
            list($level, $attributeId) = $info;

            // Setup a shortcut
            $_ = $dataByRefTargetIdA[$refTargetId] ?? [];

            // Add isValidTbx flag
            $_ += [
                'isValidTbx' => ! ! $_,
            ];

            // Merge into attribute, with a priority
            $refA[$level][$attributeId] = $_ + $refA[$level][$attributeId];
        }
    }

    /**
     * @param null $language
     * @throws Zend_Db_Statement_Exception
     */
    public static function deleteImages(int $collectionId, int $termEntryId, $language = null)
    {
        // Setup query param bindings
        $bind[':collectionId'] = $collectionId;
        $bind[':termEntryId'] = $termEntryId;
        if ($language) {
            $bind[':language'] = $language;
        }

        // Build WHERE clause using bindings
        $where = [];
        foreach ($bind as $key => $value) {
            $where[] = '`' . ltrim($key, ':') . '` = ' . $key;
        }

        // Get image-attribute targets
        $targetIdA = editor_Utils::db()->query('
            SELECT `target`, `id` FROM `terms_attributes` WHERE ' . implode(' AND ', $where) . ' AND `type` = "figure" 
        ', $bind)->fetchAll(PDO::FETCH_KEY_PAIR);

        // If nothing found - return
        if (! $targetIdA) {
            return;
        }

        /* @var $i editor_Models_Terminology_Models_ImagesModel */
        $i = ZfExtended_Factory::get('editor_Models_Terminology_Models_ImagesModel');

        // Delete the images then
        $images = $i->loadByTargetIdList($collectionId, array_keys($targetIdA));
        foreach ($images as $image) {
            $i->init($image);
            $i->delete();
        }
    }

    /**
     * Get data for tbx-export
     *
     * @param string $termEntryIds Comma-separated list of ids
     * @param bool $tbxBasicOnly
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getExportData($termEntryIds, $tbxBasicOnly = false)
    {
        return array_group_by($this->db->getAdapter()->query(
            '
            SELECT `termEntryId`, `language`, `termId`, `elementName`, `type`, `value`, `target`, `isDescripGrp`, `dataTypeId` 
            FROM `terms_attributes`
            WHERE `termEntryId` IN (' . $termEntryIds . ')' . editor_Utils::rif($tbxBasicOnly, ' AND `dataTypeId` IN ($1)')
        )->fetchAll(), 'termEntryId', 'language', 'termId');
    }

    /**
     * Replicate new value of definition attribute to `terms_term`.`definition` where needed
     * and return array containing new value and ids of affected `terms_term` records for
     * being able to apply that on client side
     */
    public function replicateDefinition($event)
    {
        // If $event is 'deleted'
        if ($event == 'deleted') {
            // If it's a language-level definition-attribute is going to be deleted
            // get termEntry-level definition-attribute to be used as a replacement
            // or just use empty string
            $value = $this->getLanguage() ? $this->_entryLevelDef() : '';

            // Else if $event is 'updated'
        } elseif ($event == 'updated') {
            // If we updated the language-level definition-attribute
            $value = $this->getLanguage()

                // Use new value if not empty, or termEntry-level one otherwise
                ? ($this->getValue() ?: $this->_entryLevelDef())

                // Else if we updated termEntry-level one - use the value we have
                : $this->getValue();
        }

        // Prepare query bindings
        $bind = [$this->getTermEntryId()];

        // Bind the language-param to the query
        // If it's a lanuguage-level definition, $this's language is just used,
        // otherwise we need to replicate definition to all terms, that have
        // no definition-attribute on their language-level, or have but it's empty,
        // so we find the languages matching that criteria within current termEntry
        $bind[] = $this->getLanguage()
            ? $this->getLanguage()
            : join(',', $this->_getLanguagesWithNoOrEmptyDefinition());

        // Get ids of terms, that will be affected
        $termIdA = $this->db->getAdapter()->query('
            SELECT `id` 
            FROM `terms_term` 
            WHERE `termEntryId` = ? AND FIND_IN_SET(`language`, ?)
        ', $bind)->fetchAll(PDO::FETCH_COLUMN);

        // Affected term ids array
        $affected = [];

        // Get term model
        $termM = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');

        // Foreach termId
        foreach ($termIdA as $termId) {
            // Load term and update definition, involving history-record creation
            $termM->load($termId);
            $termM->setDefinition($value);
            $termM->update();

            //
            $affected[] = $termId;
        }

        // Return
        return [
            'value' => $value,
            'affected' => $affected,
        ];
    }

    /**
     * Get the value of termEntry-level definition attribute
     *
     * @return string
     * @throws Zend_Db_Statement_Exception
     */
    protected function _entryLevelDef()
    {
        return $this->db->getAdapter()->query('
            SELECT IFNULL(`value`, "") 
            FROM `terms_attributes` 
            WHERE 1
              AND `termEntryId` = ? 
              AND ISNULL(`language`) 
              AND `type` = "definition"
        ', $this->getTermEntryId())->fetchColumn();
    }

    /**
     * Get array of languages (within current termEntryId) that have no definition-attribute,
     * or have but it's empty. This is an internal-purpose method, that is used to build the
     * WHERE clause to identify terms_term-records that termEntry-level definition-attribute
     * can be replicated across as a value of `definition` column
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    protected function _getLanguagesWithNoOrEmptyDefinition()
    {
        return $this->db->getAdapter()->query('
            SELECT
              `t`.`language`,
              COUNT(`ta`.`id`) AS `qty`,
              MAX(IFNULL(`ta`.`TYPE` = "definition", 0)) AS `hasDef`,
              MAX(IFNULL(`ta`.`TYPE` = "definition", 0) AND IFNULL(`ta`.`value`, "") = "") AS `butEmpty`
            FROM 
              `terms_term` `t`
              LEFT JOIN `terms_attributes` `ta` ON (
                    `ta`.`termEntryId` = `t`.`termEntryId` 
                AND `t`.`language` = `ta`.`language` 
                AND ISNULL(`ta`.`termId`)
              )
            WHERE `t`.`termEntryId` = ?
            GROUP BY `t`.`language`
            HAVING `hasDef` = 0 OR `butEmpty` = 1
        ', $this->getTermEntryId())->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * This method retrieves attributes grouped by level.
     * It is used internally by TermModel->terminfo() and ->siblinginfo()
     * and should not be called directly
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function loadGroupedByLevel($levelColumnToBeGroupedBy, $where, $bind)
    {
        return $this->db->getAdapter()->query(
            '
            SELECT 
              ' . $levelColumnToBeGroupedBy . ', 
              `id`, 
              `elementName`,
              `value`,
              `type`,
              `dataTypeId`,
              `language`,
              `target`,
              IFNULL(`createdBy`, 0) AS `createdBy`, DATE_FORMAT(`createdAt`, "%d.%m.%Y %H:%i:%s") AS `createdAt`,
              IFNULL(`updatedBy`, 0) AS `updatedBy`, DATE_FORMAT(`updatedAt`, "%d.%m.%Y %H:%i:%s") AS `updatedAt`
            FROM `terms_attributes` 
            WHERE ' . $where . ' AND `isDraft` = 0
            ORDER BY `type` = "processStatus" DESC, `id` DESC',
            $bind
        )->fetchAll(PDO::FETCH_GROUP);
    }

    /**
     * Set up `isDraft` = 0 for records identified by comma-separated ids given by $ids arg
     * Return ids of special attrs among those (e.g. processStatus- and definition-attrs) if any
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function undraftByIds($ids)
    {
        // Set up `isDraft` = 0 for records identified by comma-separated ids given by $ids arg
        $this->db->getAdapter()->query('
            UPDATE `terms_attributes` SET `isDraft` = "0" WHERE `id` IN (' . $ids . ')
        ');

        // Return ids of special attrs among those
        return $this->db->getAdapter()->query('
            SELECT `id` FROM `terms_attributes` WHERE `id` IN (' . $ids . ') AND FIND_IN_SET(`type`, "processStatus,definition,administrativeStatus") 
        ')->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Delete attributes having isDraft=1
     * This is currently used by cron
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function deleteDrafts()
    {
        // Get ids array of draft attributes
        $attrIdA_isDraft = $this->db->getAdapter()->query('
            SELECT `id` FROM `terms_attributes` WHERE `isDraft` = "1"
        ')->fetchAll(PDO::FETCH_COLUMN);

        // Foreach id
        foreach ($attrIdA_isDraft as $attrId) {
            // Load model instance
            $this->load($attrId);

            // Call delete method
            $this->delete();
        }
    }

    /**
     * @throws Zend_Db_Statement_Exception
     */
    public function getReadonlyByIds(array $attrIds, $createdBy, array $rights): array
    {
        // Shortcuts to bool flags indicating whether or not current user has certain rights
        $canReview = in_array('review', $rights);
        $canPropose = in_array('propose', $rights);
        $canFinalize = in_array('finalize', $rights);

        // Get termIds for those of given attrIds that belong to term-level
        $termIds = $attrIds ? $this->db->getAdapter()->query('
            SELECT `termId` 
            FROM `terms_attributes` 
            WHERE `id` IN (' . join(',', $attrIds) . ') AND NOT ISNULL(`termId`) 
        ')->fetchAll(PDO::FETCH_COLUMN) : [];

        // Get [termId => processStatus] pairs
        $processStatusA = $termIds ? $this->db->getAdapter()->query('
            SELECT
              `id`,
              IF (`proposal` != "", "unprocessed", `processStatus`) AS `processStatus`
            FROM `terms_term`
            WHERE `id` IN (' . join(',', $termIds) . ')
        ')->fetchAll(PDO::FETCH_KEY_PAIR) : [];

        // Get attrs info
        $_attrA = $attrIds ? $this->db->getAdapter()->query('
            SELECT 
               `id`, 
               `termEntryId`, 
               `language`, 
               `termId`, 
               `createdBy`,
               `isDraft`,    
               IF (`termId`, "term", IF (`language` != "", "language", "entry")) AS `level`
            FROM `terms_attributes` 
            WHERE `id` IN (' . join(',', $attrIds) . ') 
        ')->fetchAll(PDO::FETCH_UNIQUE) : [];

        // Group by termEntryId, preserving attrId as keys
        $attrA = [];
        foreach ($_attrA as $attrId => $attr) {
            $attrA[$attr['termEntryId']][$attrId] = $attr;
        }

        // As long as editing and deletion of language- and entry-level attributes is only allowed for cases when
        //  - ALL terms within given language, or
        //  - ALL terms within given entry
        // are having same processStatus we need to collect lists of distinct processStatus-values grouped by languages
        $infoByTermEntryIdA = $attrA ? $this->db->getAdapter()->query(
            '
            SELECT
              `termEntryId`, 
              `language`,
              GROUP_CONCAT(DISTINCT IF(`proposal` != "", "unprocessed", `processStatus`)) AS `distinct`
            FROM `terms_term` 
            WHERE `termEntryId` IN (' . join(',', array_keys($attrA)) . ')
            GROUP BY CONCAT(`termEntryId`, "-", `language`)'
        )->fetchAll(PDO::FETCH_GROUP) : [];

        // Readonly info represented as [attrId => boolean] pairs
        $readonly = [];

        // Foreach involved termEntryId and it's distinct info
        foreach ($infoByTermEntryIdA as $termEntryId => $distinct) {
            // Convert $distinct from
            // [['language' => 'en', 'distinct' => 'status1,status2'], ...]
            // to
            // ['en' => 'status1,status2', ...]
            $distinct = array_combine(
                array_column($distinct, 'language'),
                array_column($distinct, 'distinct')
            );

            // Wrap that info into a more handy format
            $distinct = [
                'entry' => join(',', array_unique($distinct)),
                'language' => $distinct,
            ];

            // Foreach attr within current $termEntryId
            foreach ($attrA[$termEntryId] as $attrId => $attr) {
                // Set up readonly flag to be true by default
                $readonly[$attrId] = true;

                // If it is a draft attribute
                if ($attr['isDraft']) {
                    // Setup readonly flag to be false
                    $readonly[$attrId] = false;

                    // Goto next
                    continue;
                }

                // Shortcuts
                $level = $attr['level'];
                $language = $attr['language'];
                $termId = $attr['termId'];

                // Get list of distinct processStatus-values depending on level
                if ($level == 'term') {
                    $_distinct = $processStatusA[$termId];
                } elseif ($level == 'language') {
                    $_distinct = $distinct[$level][$language];
                } elseif ($level == 'entry') {
                    $_distinct = $distinct[$level];
                }

                // If distinct processStatus list consists of only 1 value, and it's 'unprocessed'
                if ($_distinct == 'unprocessed') {
                    // If current user has propose-right, but has no review-right
                    if ($canPropose && ! $canReview) {
                        // If current user can delete own attrs, and current user is current attr creator
                        if ($createdBy && $attr['createdBy'] == $createdBy) {
                            $readonly[$attrId] = false;
                        }

                        // Else if current user has review-right
                    } elseif ($canReview) {
                        $readonly[$attrId] = false;
                    }

                    // Else if distinct processStatus list consists of only 1 value, and it's 'provisionallyProcessed'
                } elseif ($_distinct == 'provisionallyProcessed') {
                    // If current user has finalize-right
                    if ($canFinalize) {
                        $readonly[$attrId] = false;
                    }
                }
            }
        }

        // Return
        return $readonly;
    }

    /**
     * Get quantity of existing attributes having given $collectionId and $dataTypeId
     *
     * @return string
     * @throws Zend_Db_Statement_Exception
     */
    public function qtyBy(int $collectionId, int $dataTypeId)
    {
        return $this->db->getAdapter()->query('
            SELECT COUNT(`id`) FROM `terms_attributes` WHERE `collectionId` = ? AND `dataTypeId` = ?
        ', [$collectionId, $dataTypeId])->fetchColumn();
    }

    /**
     * Delete existing attributes having given $collectionId and $dataTypeId
     *
     * @return string
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function deleteBy(int $collectionId, int $dataTypeId)
    {
        // Get ids
        $idA = $this->db->getAdapter()->query('
            SELECT `id` FROM `terms_attributes` WHERE `collectionId` = ? AND `dataTypeId` = ?
        ', [$collectionId, $dataTypeId])->fetchAll(PDO::FETCH_COLUMN);

        // Foreach
        foreach ($idA as $id) {
            // Load
            $this->load($id);

            // Delete
            $this->delete([
                'skipCheckIsLast' => true,
            ]);
        }
    }
}
