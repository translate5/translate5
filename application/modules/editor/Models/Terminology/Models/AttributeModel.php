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
     * editor_Models_Terms_Attribute constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    public function getAttributeCollectionByEntryId($collectionId, $termEntryId): array
    {
        $attributeByKey = [];

        $query = "SELECT * FROM terms_attributes WHERE collectionId = :collectionId AND termEntryId = :termEntryId";
        $queryResults = $this->db->getAdapter()->query($query, ['collectionId' => $collectionId, 'termEntryId' => $termEntryId]);

        foreach ($queryResults as $key => $attribute) {
            $attributeByKey[$attribute['elementName'].'-'.$attribute['type'].'-'.$attribute['termEntryId'].'-'.$attribute['language'].'-'.$attribute['termTbxId']] = $attribute;
        }

        return $attributeByKey;
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
            $transacGrpChild['attrType'] = $tGrp['ifDescripgrp'];
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
            || $name=='transac' && ($type=='creation' || $type=='origination')
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

    /***
     * Check if for the current term there is a processStatus attribute. When there is no one, create it.
     * @param int $termId
     */
    /*public function checkOrCreateProcessStatus(int $termId)
    {
        $s=$this->db->select()
            ->where('termId=?',$termId)
            ->where('elementName="termNote"')
            ->where('type="processStatus"');

        $result=$this->db->fetchAll($s)->toArray();

        if (count($result) > 0) {
            return;
        }

        $term = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $term editor_Models_Terminology_Models_TermModel * /
        $term->load($termId);

        $language = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $language editor_Models_Languages * /

        $language->loadById($term->getLanguage());

        $this->setCollectionId($term->getCollectionId());
        $this->setTermId($term->getTermId());
        $this->setGuid(ZfExtended_Utils::guid());
        $this->setTermEntryId($term->getTermEntryId());
        $this->setLangSetGuid($term->getLangSetGuid());
        $this->setLanguage($language->getRfc5646());
        $this->setElementName('termNote');
        $this->setType('processStatus');

        $label = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeDataType');
        /* @var $label editor_Models_Terminology_Models_AttributeDataType * /
        $label->loadOrCreate('termNote', 'processStatus',editor_Models_Terminology_TbxObjects_Attribute::ATTRIBUTE_LEVEL_TERM);
        $this->setDataTypeId($label->getId());

//        $this->setAttrLang($language->getRfc5646());

        $this->setUserGuid($term->getUserGuid());
        $this->setUserName($term->getUserName());
        $this->setProcessStatus($term->getProcessStatus());
        $this->setValue($term->getProcessStatus());

        $this->save();
    }*/

    /**
     * Loads an attribute for the given term
     * @param editor_Models_Terminology_Models_TermModel $term
     * @param string $name
     * @param string $level
     */
    public function loadByTermAndName(editor_Models_Terminology_Models_TermModel $term, string $name, string $level = self::ATTR_LEVEL_TERM) {
        $s = $this->db->select()->where('collectionId = ?', $term->getCollectionId());
        $s->where('termEntryId = ?', $term->getTermEntryId());
        if ($level == self::ATTR_LEVEL_LANGSET || $level == self::ATTR_LEVEL_TERM) {
            $lang = ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $lang editor_Models_Languages */
            $lang->loadById($term->getLanguageId());
            $s->where('language = ?', strtolower($lang->getRfc5646()));
        } else {
            $s->where('language is null OR language = "none"');
        }

        if ($level === self::ATTR_LEVEL_TERM) {
            $s->where('termId = ?', $term->getTermId());
        } else {
            $s->where('termId is null OR termId = ""');
        }
        $s->where('elementName = ?', $name);

        $row = $this->db->fetchRow($s);
        if (!$row) {
            $this->notFound(__CLASS__ . '#termAndName', $term->getId().'; '.$name);
        }
        //load implies loading one Row, so use only the first row
        $this->row = $row;
    }
    /***
     * Add comment attribute for given term
     * @param int $termId
     * @param string $termText
     * @return editor_Models_Terminology_Models_AttributeModel
     */
    /*public function addTermComment(int $termId,string $termText): self
    {
        $term = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $term editor_Models_Terminology_Models_TermModel * /
        $term->load($termId);

        $lang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lang editor_Models_Languages * /
        $lang->loadById($term->getLanguageId());

        $label = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeDataType');
        /* @var $label editor_Models_Terminology_Models_AttributeDataType * /
        $label->loadOrCreate('note');

        $this->init([
            'elementName' => 'note',
            'created' => NOW_ISO,
          //'internalCount' => 1,
            'collectionId' => $term->getCollectionId(),
            'termId' => $term->getTermId(),
            'termEntryId' => $term->getTermEntryId(),
            'termEntryGuid' => $term->getTermEntryGuid(),
            'langSetGuid' => $term->getLangSetGuid(),
            'guid' => ZfExtended_Utils::guid(),
            'language' => strtolower($lang->getRfc5646()),
            'dataTypeId' => $label->getId(),
            'isCreatedLocally' => 1
        ]);
        $this->setValue(trim($termText));
        $sessionUser = new Zend_Session_Namespace('user');
        $this->setUserGuid($sessionUser->data->userGuid);
        $this->setUserName($sessionUser->data->userName);
        $this->hasField('updatedAt') && $this->setUpdatedAt(NOW_ISO);
        $this->save();

        return $this;
    }*/

    /**
     * creates a new, unsaved term attribute history entity
     * @return editor_Models_Term_AttributeHistory
     */
    /*public function getNewHistoryEntity(): editor_Models_Term_AttributeHistory
    {
        $history = ZfExtended_Factory::get('editor_Models_Term_AttributeHistory');
        /* @var $history editor_Models_Term_AttributeHistory * /
        $history->setAttributeId($this->getId());
        $history->setHistoryCreated(NOW_ISO);

        $fields = $history->getFieldsToUpdate();
        foreach ($fields as $field) {
            $history->__call('set' . ucfirst($field), array($this->get($field)));
        }

        return $history;
    }*/

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
        if ($misc['userName'])
            $return = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->affectLevels($misc['userName'], $this->getTermEntryId(), $this->getLanguage(), $this->getTermId());

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
        if ($misc['userName'])
            ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->affectLevels($misc['userName'], $this->getTermEntryId(), $this->getLanguage());

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

        // Affect transacgrp-records and return modification string, e.g. '<user name>, <date in d.m.Y H:i:s format>'
        if ($misc['userName'])
            $return = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->affectLevels($misc['userName'], $this->getTermEntryId(), $this->getLanguage());

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
     */
    public function removeProposalsOlderThan(array $collectionIds,string $olderThan): bool
    {
        // Get ids of attrs, that were created or updated after tbx-import
        if (!$attrIdA = editor_Utils::db()->query('
            SELECT `id` 
            FROM `terms_attributes` 
            WHERE TRUE
              AND `isCreatedLocally` = "1" 
              AND `collectionId` IN (' . implode(',', $collectionIds) . ')
        ')->fetchAll(PDO::FETCH_COLUMN)) return false;

        // Get tbx-imported values for `value` and `target` props, that now have changed values in attributes-table
        $tbxA = editor_Utils::db()->query('
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
        if ($attrIdA_created) $affectedQty += $this->db->delete([
            'createdAt < ?' => $olderThan,
            'id in (?)' => $attrIdA_created,
        ]);

        // Overwrite $attrIdA_updated array for it to keep only ids of attributes, that were last updated before $olderThan arg
        if ($attrIdA_updated) $attrIdA_updated = editor_Utils::db()->query($sql = '
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

        // If no ref-attributes found - return
        if (!$refA) return;

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
              ' . $tbxCol . ',
              `termEntryId`,
              `collectionId`,
              JSON_OBJECTAGG(
                `language`,
                CONCAT(`id`, ",", `languageId`, ",", `term`, ",", `processStatus`, ",", `status`)
              ) AS `json`
            FROM `terms_term`
            WHERE ' . $where . '
            GROUP BY `termEntryId`            
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
                    if ($value = $refData['json'][$prefLang]) {
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
                if (count($prefLangGroupA) >= 1 && $value = $refData['json'][$prefLang]) {
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
            if (!$refData['language']) {
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
            $_ = $dataByRefTargetIdA[$refTargetId] ?: [];

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

        /* @var $i editor_Models_Terminology_Models_ImagesModel */
        $i = ZfExtended_Factory::get('editor_Models_Terminology_Models_ImagesModel');

        //delete the images then
        $images = $i->loadByTargetIdList($collectionId, array_keys($targetIdA));
        foreach($images as $image) {
            $i->init($image);
            $i->delete();
        }
    }
}
