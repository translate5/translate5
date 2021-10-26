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
 * Class editor_Models_Terms_Attributes
 * Attributes Instance
 *
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method integer getCollectionId() getCollectionId()
 * @method integer setCollectionId() setCollectionId(integer $collectionId)
 * @method string getTermEntryId() getTermEntryId()
 * @method string setTermEntryId() setTermEntryId(string $termEntryId)
 * @method string getLanguage() getLanguage()
 * @method string setLanguage() setLanguage(string $language)
 * @method int getTermId() getTermId()
 * @method int setTermId() setTermId(int $termId)
 * @method string getTermTbxId() getTermTbxId()
 * @method string setTermTbxId() setTermTbxId(string $termTbxId)
 * @method string getDataTypeId() getDataTypeId()
 * @method string setDataTypeId() setDataTypeId(int $dataTypeId)
 * @method string getType() getType()
 * @method string setType() setType(string $type)
 * @method string getValue() getValue()
 * @method string setValue() setValue(string $value)
 * @method string getTarget() getTarget()
 * @method string setTarget() setTarget(string $target)
 * @method string getIsCreatedLocally() getIsCreatedLocally()
 * @method void setIsCreatedLocally() setIsCreatedLocally(int $isCreatedLocally)
 * @method string getCreatedBy() getCreatedBy()
 * @method void setCreatedBy() setCreatedBy(int $userId)
 * @method string getCreatedAt() getCreatedAt()
 * @method void setCreatedAt() setCreatedAt(string $createdAt)
 * @method string getUpdatedBy() getUpdatedBy()
 * @method void setUpdatedBy() setUpdatedBy(int $userId)
 * @method string getUpdatedAt() getUpdatedAt()
 * @method void setUpdatedAt() setUpdatedAt(string $updatedAt)
 * @method string getTermEntryGuid() getTermEntryGuid()
 * @method string setTermEntryGuid() setTermEntryGuid(string $termEntryGuid)
 * @method string getLangSetGuid() getLangSetGuid()
 * @method string setLangSetGuid() setLangSetGuid(string $langSetGuid)
 * @method string getTermGuid() getTermGuid()
 * @method string setTermGuid() setTermGuid(string $termGuid)
 * @method string getGuid() getGuid()
 * @method string setGuid() setGuid(string $guid)
 * @method string getElementName() getElementName()
 * @method string setElementName() setElementName(string $elementName)
 * @method string getAttrLang() getAttrLang()
 * @method string setAttrLang() setAttrLang(string $attrLang)
 */
class editor_Models_Terminology_Models_AttributeModel extends editor_Models_Terminology_Models_Abstract
{
    const ATTR_LEVEL_ENTRY = 'termEntry';
    const ATTR_LEVEL_LANGSET = 'langSet';
    const ATTR_LEVEL_TERM = 'term';
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_Attribute';
    protected $validatorInstanceClass = 'editor_Models_Validator_Term_Attribute';

    /**
     * loads attributes of a term, optionally filtered by element and type
     * @param int $termId
     * @param array $element
     * @param array $type
     * @return array
     */
    public function loadByTerm(int $termId, array $element = [], array $type = []): array {
        $s = $this->db->select()
            ->where('termId = ?', $termId);
        if(!empty($element)) {
            $s->where('elementName in (?)', $element);
        }
        if(!empty($type)) {
            $s->where('type in (?)', $type);
        }
        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Is the user allowed for attribute proposal
     * @return boolean
     */
    public function isProposableAllowed(): bool
    {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */

        return $user->hasRole('termProposer');
    }

    /***
     * Update term modification attribute group with the term proposal values.
     * The modification group date and editor will be set as term proposal date an term proposal editor.
     *
     * @param array $attributes
     * @param array $termProposal
     * @return array
     */
    public function updateModificationGroupDate(array $attributes, array $termProposal): array
    {
//        if (empty($attributes) || empty($termProposal) || empty($termProposal['created']) || empty($termProposal['userName'])) {
//            return $attributes;
//        }

        //foreach term attribute check, find the transac modification attribute
        foreach ($attributes as &$attribute) {

            if (empty($attribute['children'])) {
                continue;
            }

            //ignore non modification tags
//            if ($attribute['name'] != 'transac' || $attribute['attrType'] != 'modification') {
//                continue;
//            }

//            foreach ($attribute['children'] as &$child) {
//                if ($child['name'] === 'tig' || $child['name'] === 'langSet' || $child['name'] === 'transac') {
//                    //convert the date to unix timestamp
//                    $child['attrValue'] = strtotime($termProposal['created']);
//                }
//                if ($child['name'] === 'transacNote' && $this->isResponsablePersonAttribute($child['attrType'])) {
//                    $child['attrValue'] = $termProposal['userName'];
//                }
//            }
        }

        return $attributes;
    }

    /**
    * Check if given AttrType is for responsable person
    * Info: the responsable person type is saved in with different values in some tbx files
    * @param string|null $type
    * @return boolean
    */
    public function isResponsablePersonAttribute(string $type = null): bool
    {
        if (!empty($type)) {
            return $type == 'responsiblePerson' || $type == 'responsibility';
        }
        if ($this->getType() != null) {
            return $this->getType() == 'responsiblePerson' || $this->getType() == 'responsibility';
        }

        return false;
    }

    /***
     * Group the attributes by parent-child
     *
     * @param array $list
     * @param int $parentId
     * @return array
     */
    public function createChildTree(array $list, $parentId = 0): array
    {
        $transacGrpModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');
        /* @var $transacGrpModel editor_Models_Terminology_Models_TransacgrpModel */

        $attCols = [
            'id AS attributeId',
            'elementName AS name',
            'transacType AS attrType',
            'guid AS attrId',
            'transac AS attrValue',
            'date AS date',
            'adminType AS attrDataType',
            new Zend_Db_Expr('"termAttribute" as attributeOriginType')//this is needed as fixed value
        ];

        $cols = [
            'id AS transacGrpId',
            'elementName AS name',
            'transac AS transacType',
            'transacNote AS attrValue',
            'date AS date',
            'transacType AS attrType'
            ];

        $tree = [];
        foreach ($list as $element) {
            if ($element['transacGrpId']) {
//                $select = $transacGrpModel->db->select()
//                    ->from($transacGrpModel->db, $attCols)
//                    ->where('terms_transacgrp.id = ?', $element['transacGrpId'])
//                    ->group('terms_transacgrp.id');
//                $rows = $this->db->fetchAll($select)->toArray();
                $rows = $transacGrpModel->db->find($element['transacGrpId'])->toArray();

                if ($rows) {
                    $element['children'] = $this->prepareTransacGrpChild($rows);
                } else {
                    $element['children'] = [];
                }

                $tree[] = $element;
            }
        }

        return $tree;
    }

    private function prepareTransacGrpChild(array $transacGrp): array
    {
        $transacGrpChild = [];

        foreach ($transacGrp as $tGrp) {
            $transacGrpChild['id'] = $tGrp['id'];
//            $transacGrpChild['name'] = $tGrp['elementName'];
            $transacGrpChild['attributeId'] = $tGrp['transac'];
            $transacGrpChild['dataTypeId'] = $tGrp['date'];
            $transacGrpChild['termEntryId'] = $tGrp['adminType'];
            $transacGrpChild['internalCount'] = $tGrp['adminValue'];
            $transacGrpChild['language'] = $tGrp['transacNote'];
            $transacGrpChild['name'] = $tGrp['transacType'];
            $transacGrpChild['attrType'] = $tGrp['isDescripGrp'];
            $transacGrpChild['attrDataType'] = $tGrp['collectionId'];
            $transacGrpChild['attrTarget'] = $tGrp['termEntryId'];
            $transacGrpChild['attrId'] = $tGrp['termId'];
            $transacGrpChild['attrValue'] = $tGrp['termEntryGuid'];
            $transacGrpChild['attrCreated'] = $tGrp['langSetGuid'];
            $transacGrpChild['attrUpdated'] = $tGrp['guid'];
        }

        return $transacGrpChild;
    }
    /**
     * returns true if the attribute is proposable according to its values.
     * Additional ACL checks must be done outside (in the controllers)
     * @param string $name optional, if both parameters are empty the values from $this are used
     * @param string $type optional, if both parameters are empty the values from $this are used
     */
    public function isProposable($name = null, $type = null) {
        if(empty($name) && empty($type)) {
            $name = $this->getElementName();
            $type = $this->getType();
        }
        return !($name == 'date'
            || $name=='termNote' && $type=='processStatus'
            || $name=='transacNote' && ($this->isResponsablePersonAttribute($type))
            || $name=='transac' && ($type=='origination' || $type=='origination')
            || $name=='transac' && $type=='modification');
    }
    /***
     * Get all attributes for the given term entry (groupId)
     * @param string $termEntryId
     * @param array $collectionIds
     * @return array|NULL
     */
    public function getAttributesForTermEntry(string $termEntryId, array $collectionIds): ?array
    {
        $cols = [
            'terms_attributes.id AS attributeId',
            'terms_attributes.dataTypeId as dataTypeId',
            'terms_attributes.termEntryId AS termEntryId',
            //'terms_attributes.internalCount AS internalCount',
            'terms_attributes.language AS language',
            'terms_attributes.elementName AS name',
            'terms_attributes.type AS attrType',
            'terms_attributes.target AS attrTarget',
            'terms_attributes.guid AS attrId',
            'terms_attributes.value AS attrValue',
            'terms_attributes.created AS attrCreated',
            'terms_attributes.updatedAt AS attrUpdated',
            'terms_term_entry.collectionId AS collectionId',
            new Zend_Db_Expr('"termEntryAttribute" as attributeOriginType')//this is needed as fixed value
        ];

        $s = $this->db->select()
            ->from($this->db,[])
            ->join('terms_term_entry', 'terms_term_entry.id = terms_attributes.termEntryId', $cols)
            ->joinLeft('terms_attributes_datatype', 'terms_attributes_datatype.id = terms_attributes.dataTypeId', ['terms_attributes_datatype.labelText as headerText']);

        $s->join('terms_transacgrp', 'terms_transacgrp.termEntryId = terms_term_entry.id',[
            'terms_transacgrp.transac as transac',
            'terms_transacgrp.transacNote as transacNote',
            'terms_transacgrp.transacType as transacType',
        ]);

        if ($this->isProposableAllowed()) {
            $s->joinLeft('terms_attributes_proposal', 'terms_attributes_proposal.attributeId = terms_attributes.id',[
                'terms_attributes_proposal.value as proposalAttributeValue',
                'terms_attributes_proposal.id as proposalAttributelId',
            ]);
        }

        $s->where('terms_attributes.termId is null OR terms_attributes.termId = ""')
            ->where('terms_term_entry.id = ?', $termEntryId)
            ->where('terms_term_entry.collectionId IN(?)', $collectionIds)
            ->group('terms_term_entry.id');
        $s->setIntegrityCheck(false);

        $rows = $this->db->fetchAll($s)->toArray();

        if (empty($rows)) {
            return null;
        }

        $mapProposal = function($item) {
            $item['proposable'] = $this->isProposable($item['name'],$item['attrType']);
            $item['proposal'] = null;

            if (!isset($item['proposalAttributelId'])) {
                unset($item['proposalAttributelId']);

                if (!isset($item['proposalAttributeValue'])) {
                    unset($item['proposalAttributeValue']);
                }
                return $item;
            }
            $obj = new stdClass();
            $obj->id = $item['proposalAttributelId'];
            $obj->value = $item['proposalAttributeValue'];
            $item['proposal'] = $obj;

            return $item;
        };
        $rows = array_map($mapProposal, $rows);

        return $rows;
    }

    /**
     * @return mixed
     */
    public function update($misc = []) {

        $orig = $this->row->getCleanData();

        // Set up `isCreatedLocally` flag to 1 if not explicitly given
        if (!$this->isModified('isCreatedLocally')) $this->setIsCreatedLocally(1);

        // Call parent
        $return = parent::save();

        // If current data is not equal to original data
        if ($this->toArray() != $orig) {

            // Prepare data for history record
            $init = $orig; $init['attrId'] = $orig['id']; unset($init['id'], $init['createdBy'], $init['createdAt']);

            // Create history instance
            $history = ZfExtended_Factory::get('editor_Models_Term_AttributeHistory');

            // Init with data
            $history->init($init);

            // Save
            $history->save();
        }

        // Affect transacgrp-records and return modification string, e.g. '<user name>, <date in d.m.Y H:i:s format>'
        if (isset($misc['userName']))
            $return = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->affectLevels($misc['userName'], $misc['userGuid'], $this->getTermEntryId(), $this->getLanguage(), $this->getTermId());

        // Return
        return $return;
    }

    /**
     * @return mixed
     */
    public function insert($misc = []) {

        // Call parent
        $return = parent::save();

        // Affect transacgrp-records
        if ($misc['userName'] ?? 0)
            $return = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->affectLevels($misc['userName'], $misc['userGuid'], $this->getTermEntryId(), $this->getLanguage(), $this->getTermId());

        // Return
        return $return;
    }

    /**
     * @return mixed
     */
    public function delete($misc = []) {

        // If attribute's `type` is 'figure' and `target` is not empty
        if ($this->getType() == 'figure' && $this->getTarget()) {

            // Setup terms_images model
            $i = ZfExtended_Factory::get('editor_Models_Terminology_Models_ImagesModel');

            // If `terms_images` record found by attr's target - delete that record
            if ($i->loadByTargetId($this->getCollectionId(), $this->getTarget())){
                $i->delete();
            }
        }

        // If attribute's `type` is 'definition' - check whether we should update `terms_term`.`definition`
        // and if yes, what should be the new value and what terms should be affected
        if ($this->getType() == 'definition')
            $return['definition'] = $this->replicateDefinition('deleted');

        // Affect transacgrp-records and return modification string, e.g. '<user name>, <date in d.m.Y H:i:s format>'
        if ($misc['userName'])
            $return['updated'] = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->affectLevels($misc['userName'], $misc['userGuid'], $this->getTermEntryId(), $this->getLanguage(), $this->getTermId());

        // Call parent
        parent::delete();

        // Return
        return $return;
    }

    /***
     * Remove old attribute proposals from the collection by given date.
     *
     * @param array $collectionIds
     * @param string $olderThan
     * @return boolean
     * @throws Zend_Db_Statement_Exception
     */
    public function removeProposalsOlderThan(array $collectionIds,string $olderThan): bool
    {
        // Get ids of attrs, that were created or updated after tbx-import
        $attrIdA = $this->db->getAdapter()->query('
            SELECT `id` 
            FROM `terms_attributes` 
            WHERE TRUE
              AND `isCreatedLocally` = "1" 
              AND `collectionId` IN (' . implode(',', $collectionIds) . ')
        ')->fetchAll(PDO::FETCH_COLUMN);

        if (empty($attrIdA)){
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
        if (!empty($attrIdA_created)) {
            //this speeds incredibly up the SQL since no cast must be done in SQL then
            $affectedQty += $this->db->delete([
                'createdAt < ?' => $olderThan,
                'collectionId in (?)' => $collectionIds,
                'id in (?)' => $attrIdA_created,
            ]);
        }

        // Overwrite $attrIdA_updated array for it to keep only ids of attributes, that were last updated before $olderThan arg
        if ($attrIdA_updated) $attrIdA_updated = $this->db->getAdapter()->query($sql = '
            SELECT `id` 
            FROM `terms_attributes` 
            WHERE TRUE
              AND `id` IN (' . implode(',', $attrIdA_updated) . ')
              AND `updatedAt` < ? 
        ', $olderThan)->fetchAll(PDO::FETCH_COLUMN);

        // Revert updated attrs' `value` and `target` props to tbx-imported values
        foreach ($attrIdA_updated as $attrId) {
            $this->load($attrId);
            $this->setValue($tbxA[$attrId]['value']);
            $this->setTarget($tbxA[$attrId]['target']);
            $this->setIsCreatedLocally(0);
            $this->save();

            // Increase counter
            $affectedQty ++;
        }

        // Delete history-records for $attrIdA_updated attrs
        if ($attrIdA_updated) ZfExtended_Factory::get('editor_Models_Term_AttributeHistory')->db->delete([
            'attrId in (?)' => $attrIdA_updated,
        ]);

        // Return
        return $affectedQty > 0;
    }

    /**
     * @param array $refA
     * @param $refTargetIdA
     * @param array $prefLangA
     * @param string $level
     * @throws Zend_Db_Statement_Exception
     */
    public static function refTarget(array &$refA, array $refTargetIdA, array $prefLangA, $level = null) {

        // If no ref-attributes having non-empty target-prop found - return
        if (!$refTargetIdA) return;

        // Shortcut to arg passed to IN (?)
        $in = '"' . implode('","', array_keys($refTargetIdA)) . '"';

        // Which tbx column to use
        $tbxCol = $level
            ? '`' . ($level == 'term' ? 'termTbxId' : 'termEntryTbxId') . '`'
            : 'IF(`termTbxId` IN (' . $in .'), `termTbxId`, `termEntryTbxId`)';

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
        foreach ($prefLangA as $prefLang)
            $prefLangGroupA[substr($prefLang, 0, 2)] = true;

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
            if (count($prefLangGroupA) == 1)
                foreach ($prefLangA as $prefLang)
                    if ($value = $refData['json'][$prefLang] ?? null) {
                        $refData['language'] = $prefLang;
                        list (
                            $refData['termId'],
                            $refData['languageId'],
                            $refData['value'],
                            $refData['processStatus'],
                            $refData['status']
                            ) = explode(',', $value);

                        // Jump to next $refData
                        continue 2;
                    }

            // Foreach preferred language
            foreach ($prefLangA as $prefLang) {

                // If term exists for the preferred language
                if (count($prefLangGroupA) >= 1 && $value = $refData['json'][$prefLang] ?? null) {
                    $refData['language'] = $prefLang;
                    list (
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
                            list (
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
            if (!isset($refData['language'])) {
                $refData['language'] = array_keys($refData['json'])[0];
                list (
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
            $_ += ['isValidTbx' => !!$_];

            // Merge into attribute, with a priority
            $refA[$level][$attributeId] = $_ + $refA[$level][$attributeId];
        }
    }

    /**
     * @param int $collectionId
     * @param int $termEntryId
     * @param null $language
     * @throws Zend_Db_Statement_Exception
     */
    public static function deleteImages(int $collectionId, int $termEntryId, $language = null) {

        // Setup query param bindings
        $bind[':collectionId'] = $collectionId;
        $bind[':termEntryId'] = $termEntryId;
        if ($language) {
            $bind[':language'] = $language;
        }

        // Build WHERE clause using bindings
        $where = []; foreach ($bind as $key => $value) $where []= '`' . ltrim($key, ':') . '` = ' . $key;

        // Get image-attribute targets
        $targetIdA = editor_Utils::db()->query('
            SELECT `target`, `id` FROM `terms_attributes` WHERE ' . implode(' AND ', $where) . ' AND `type` = "figure" 
        ', $bind)->fetchAll(PDO::FETCH_KEY_PAIR);

        // If nothing found - return
        if (!$targetIdA) return;

        /* @var $i editor_Models_Terminology_Models_ImagesModel */
        $i = ZfExtended_Factory::get('editor_Models_Terminology_Models_ImagesModel');

        // Delete the images then
        $images = $i->loadByTargetIdList($collectionId, array_keys($targetIdA));
        foreach($images as $image) {
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
    public function getExportData($termEntryIds, $tbxBasicOnly = false) {
        return array_group_by($this->db->getAdapter()->query('
            SELECT `termEntryId`, `language`, `termId`, `elementName`, `type`, `value`, `target`, `isDescripGrp` 
            FROM `terms_attributes`
            WHERE `termEntryId` IN (' . $termEntryIds . ')' . editor_Utils::rif($tbxBasicOnly, ' AND `dataTypeId` IN ($1)')
        )->fetchAll(), 'termEntryId', 'language', 'termId');
    }

    /**
     * Replicate new value of definition attribute to `terms_term`.`definition` where needed
     * and return array containing new value and ids of affected `terms_term` records for
     * being able to apply that on client side
     *
     * Accepts a definition text as a first arg and spread it across
     * `terms_term`.`definition` where need according to the agreed logic
     *
     * @param $definition
     */
    public function replicateDefinition($event) {

        // If $event is 'deleted'
        if ($event == 'deleted') {

            // If it's a language-level definition-attribute is going to be deleted
            // get termEntry-level definition-attribute to be used as a replacement
            // or just use null
            $value = $this->getLanguage() ? $this->_entryLevelDef() : null;

        // Else if $event is 'updated'
        } else if ($event == 'updated') {

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
        $bind []= $this->getLanguage()
            ? $this->getLanguage()
            : join(',', $this->_getLanguagesWithNoOrEmptyDefinition());

        i($bind, 'a');
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
            $affected []= $termId;
        }

        // Return
        return ['value' => $value, 'affected' => $affected];
    }

    /**
     * Get the value of termEntry-level definition attribute
     *
     * @return string
     * @throws Zend_Db_Statement_Exception
     */
    protected function _entryLevelDef() {
        return $this->db->getAdapter()->query('
            SELECT `value` 
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
    protected function _getLanguagesWithNoOrEmptyDefinition() {
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
}
