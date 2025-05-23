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
 * Term Instance
 *
 * @method string getId()
 * @method void setId(integer $id)
 * @method string getUpdatedBy()
 * @method void setUpdatedBy(int $userId)
 * @method string getUpdatedAt()
 * @method void setUpdatedAt(string $updatedAt)
 * @method string getCollectionId()
 * @method void setCollectionId(integer $collectionId)
 * @method string getTermEntryId()
 * @method void setTermEntryId(integer $termEntryId)
 * @method string getLanguageId()
 * @method void setLanguageId(integer $languageId)
 * @method string getLanguage()
 * @method void setLanguage(string $language)
 * @method string getTerm()
 * @method void setTerm(string $term)
 * @method string getProposal()
 * @method void setProposal(string $proposal)
 * @method string getStatus()
 * @method void setStatus(string $Status)
 * @method string getProcessStatus()
 * @method void setProcessStatus(string $processStatus)
 * @method string getDefinition()
 * @method void setDefinition(string $definition)
 * @method string getTermEntryTbxId()
 * @method void setTermEntryTbxId(string $termEntryTbxId)
 * @method string getTermTbxId()
 * @method void setTermTbxId(string $termTbxId)
 * @method string getTermEntryGuid()
 * @method void setTermEntryGuid(string $entryGuid)
 * @method string getLangSetGuid()
 * @method void setLangSetGuid(string $langSetGuid)
 * @method string getGuid()
 * @method void setGuid(string $guid)
 * @method string getTbxCreatedBy()
 * @method void setTbxCreatedBy(integer $personId)
 * @method string getTbxCreatedAt()
 * @method void setTbxCreatedAt(string $timestamp)
 * @method string getTbxUpdatedBy()
 * @method void setTbxUpdatedBy(integer $personId)
 * @method string getTbxUpdatedAt()
 * @method void setTbxUpdatedAt(string $timestamp)
 */
class editor_Models_Terminology_Models_TermModel extends editor_Models_Terminology_Models_Abstract
{
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_Term';

    protected $validatorInstanceClass = 'editor_Models_Validator_Term_Term';

    public const PROCESS_STATUS_UNPROCESSED = 'unprocessed';

    public const PROCESS_STATUS_PROV_PROCESSED = 'provisionallyProcessed';

    public const PROCESS_STATUS_FINALIZED = 'finalized';

    public const STAT_PREFERRED = 'preferredTerm';

    public const STAT_ADMITTED = 'admittedTerm';

    public const STAT_LEGAL = 'legalTerm';

    public const STAT_REGULATED = 'regulatedTerm';

    public const STAT_STANDARDIZED = 'standardizedTerm';

    public const STAT_DEPRECATED = 'deprecatedTerm';

    public const STAT_SUPERSEDED = 'supersededTerm';

    public const STAT_NOT_FOUND = 'STAT_NOT_FOUND'; //Dieser Status ist nicht im Konzept definiert, sondern wird nur intern verwendet!

    public const TRANSSTAT_FOUND = 'transFound';

    public const TRANSSTAT_NOT_FOUND = 'transNotFound';

    public const TRANSSTAT_NOT_DEFINED = 'transNotDefined';

    public const CSS_TERM_IDENTIFIER = 'term';

    /**
     * The above constants are needed in the application as list, since reflection usage is expensive we cache them
     * here:
     */
    protected static array $statusCache = [];

    protected static array $termEntryTbxIdCache = [];

    protected editor_Models_Segment_TermTag $tagHelper;

    protected array $statOrder = [
        self::STAT_STANDARDIZED => 1,
        self::STAT_PREFERRED => 2,
        self::STAT_LEGAL => 3,
        self::STAT_REGULATED => 3,
        self::STAT_DEPRECATED => 3,
        self::STAT_SUPERSEDED => 3,
        self::STAT_NOT_FOUND => 3,
        self::STAT_ADMITTED => 99,
    ];

    /**
     * If term's `processStatus` was changed to 'rejected',
     * normativeAuthorization-attribute is set to 'deprecatedTerm' (it's created if not existed so far)
     * so this property contains terms_attributes-record's data for that attribute
     */
    protected ?array $normativeAuthorization = null;

    /**
     * Get normativeAuthorization-attribute's model instance if term's processStatus-prop was changed to 'rejected'
     *
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getNormativeAuthorizationIfTermWasRejected(): ?editor_Models_Terminology_Models_AttributeModel
    {
        // If record data for that attribute was previously set
        if ($this->normativeAuthorization) {
            // Load and return model instance
            $na = ZfExtended_Factory::get(editor_Models_Terminology_Models_AttributeModel::class);
            $na->load($this->normativeAuthorization['id']);

            return $na;
        }

        return null;
    }

    public function __construct()
    {
        parent::__construct();
        $this->tagHelper = ZfExtended_Factory::get('editor_Models_Segment_TermTag');
    }

    /**
     * return true if the given term should be considered as empty
     */
    public static function isEmptyTerm(?string $term): bool
    {
        if (is_null($term) || strlen($term) === 0) {
            return true;
        }

        return (bool) preg_match('/^\s*$/', $term);
    }

    public function insert($misc = [])
    {
        // If $misc['userName'] is given
        if (isset($misc['userName'])) {
            // Load or create person
            $person = ZfExtended_Factory
                ::get('editor_Models_Terminology_Models_TransacgrpPersonModel')
                    ->loadOrCreateByName($misc['userName'], $this->getCollectionId());

            // Use person id as tbxCreatedBy and tbxUpdatedBy
            $by = (int) $person->getId();
            $this->setTbxCreatedBy($by);
            $this->setTbxUpdatedBy($by);
        }

        // Set tbx(Created|Updated)At
        $this->setTbxCreatedAt($at = date('Y-m-d H:i:s'));
        $this->setTbxUpdatedAt($at);

        // Save
        $termId = $this->save();

        // Get some needed dataTypeIds
        /* @var $datatype editor_Models_Terminology_Models_AttributeDataType */
        $datatype = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeDataType');
        $dataTypeIds = $datatype->getIdsForTerms(['termNote#processStatus', 'termNote#administrativeStatus', 'note#']);

        // Append to $attrA
        $attrA['processStatus'] = [
            'dataTypeId' => $dataTypeIds['termNote#processStatus'],
            'type' => 'processStatus',
            'value' => $this->getProcessStatus(),
        ];

        // If value for note-attr is given
        if ($misc['note'] ?? false) {
            // Append to attrA
            $attrA['note'] = [
                'dataTypeId' => $dataTypeIds['note#'],
                'type' => 'note',
                'value' => $misc['note'],
            ];
        }

        // If new term's processStatus is 'rejected'
        if ($this->getProcessStatus() == 'rejected') {
            // Append to attrA
            $attrA['administrativeStatus'] = [
                'dataTypeId' => $dataTypeIds['termNote#administrativeStatus'],
                'type' => 'administrativeStatus',
                'value' => 'deprecatedTerm-admn-sts',
            ];
        }

        // If attributes should be copied from other term
        if ($misc['copyAttrsFromTermId'] ?? 0) {
            // Array of dataTypeIds to be ignored while copying attributes from other term
            $except = [$dataTypeIds['termNote#processStatus']];
            if ($misc['note'] ?? 0) {
                $except[] = $dataTypeIds['note#'];
            }
            if ($this->getProcessStatus() == 'rejected') {
                $except[] = $dataTypeIds['termNote#administrativeStatus'];
            }

            // Fetch attributes of existing term, except at least 'processStatus' attribute
            $attrA += $this->db->getAdapter()->query(
                'SELECT `dataTypeId`, `type`, `value`, `target`, `elementName`, `attrLang`  
                FROM `terms_attributes` 
                WHERE `termId` = ? AND `dataTypeId` NOT IN (' . implode(',', $except) . ')',
                $misc['copyAttrsFromTermId']
            )->fetchAll();
        }

        // Foreach attribute to be INSERTed
        foreach ($attrA as $attrI) {
            $this->initAttr($attrI)->save();
        }

        // Check whether there were no terms for this language previously within same termEntryId
        $isTermForNewLanguage = ! $this->db->getAdapter()->query(
            'SELECT `id` 
            FROM `terms_term` 
            WHERE TRUE 
              AND `termEntryId` = :termEntryId
              AND `languageId` = :languageId
              AND `id` != :id
            LIMIT 1',
            [
                ':termEntryId' => $this->getTermEntryId(),
                ':languageId' => $this->getLanguageId(),
                ':id' => $this->getId(),
            ]
        )->fetchColumn();

        // Prepare transacgrp-props relevant for term-level
        $levelA['term'] = [
            'elementName' => 'tig',
            'termId' => $this->getId(),
            'termTbxId' => $this->getTermTbxId(),
            'termGuid' => $this->getGuid(),
        ];

        // Prepare transacgrp-props relevant for language-level
        // No props actually, but this allows us to cycle through $levelA
        if ($isTermForNewLanguage) {
            $levelA['language'] = [
                'elementName' => 'langSet',
            ];
        }

        // Create 'origination' and 'modification' `terms_transacgroup`-entries for term-level (and language-level, if need)
        foreach ($levelA as $byLevel) {
            foreach (['origination', 'modification'] as $type) {
                // Create `terms_transacgrp` model instance
                $t = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');

                // Setup data
                $t->init(
                    $byLevel + [
                        'elementName' => $byLevel['elementName'],
                        'transac' => $type,
                        'date' => date('Y-m-d H:i:s'),
                        'transacNote' => $misc['userName'],
                        'target' => $misc['userGuid'],
                        'transacType' => 'responsibility',
                        'language' => $this->getLanguage(),
                        // 'attrLang' => $this->getLanguage(), // ?
                        'collectionId' => $this->getCollectionId(),
                        'termEntryId' => $this->getTermEntryId(),
                        'termEntryGuid' => $this->getTermEntryGuid(),
                        'guid' => ZfExtended_Utils::uuid(),
                    ]
                );

                // Save `terms_transacgrp` entry
                $t->save();
            }
        }

        // Basic clause for `language`-column to be used in UPDATE query to affect termEntry-level's 'modification'-record
        $language = 'ISNULL(`language`)';

        // If there was at least one term defined for same language, then alter clause for `language`-combo
        // so that not only termEntry-level's 'modification'-record would be affected, but language-level's as well
        if (! $isTermForNewLanguage) {
            $language = '(' . $language . ' OR `language` = "' . $this->getLanguage() . '")';
        }

        // Update 'modification'-record of termEntry-level (and language-level, if need)
        $this->db->getAdapter()->query(
            'UPDATE `terms_transacgrp` 
            SET 
              `date` = :date, 
              `transacNote` = :userName,
              `target` = :userGuid
            WHERE TRUE
              AND `termEntryId` = :termEntryId 
              AND ' . $language . '
              AND `transac` = "modification"',
            [
                ':date' => date('Y-m-d H:i:s'),
                ':userName' => $misc['userName'],
                ':userGuid' => $misc['userGuid'],
                ':termEntryId' => $this->getTermEntryId(),
            ]
        );

        // Update collection languages
        $this->updateCollectionLangs('insert');

        // Return
        return $termId;
    }

    public function updateCollectionLangs($event)
    {
        // Query params
        $params = [$this->getCollectionId(), $this->getLanguageId()];

        // If $event is 'delete'
        if ($event == 'delete') {
            // Check whether deleted term was last having it's languageId within it's collectionId
            $wasLast = ! $this->db->getAdapter()->query(
                'SELECT `id` 
                FROM `terms_term` 
                WHERE `collectionId` = ? AND `languageId` = ? 
                LIMIT 1',
                $params
            )->fetchColumn();

            // If it was last term for it's language
            if ($wasLast) {
                // Get info about
                $languageA = $this->db->getAdapter()->query(
                    'SELECT * FROM `LEK_languageresources_languages` WHERE `languageResourceId` = ? LIMIT 3',
                    $params[0]
                )->fetchAll();

                // Remove that language mentions from LEK_languageresources_languages-table
                $this->db->getAdapter()->query(
                    'DELETE FROM `LEK_languageresources_languages` 
                    WHERE `languageResourceId` = ? AND ? IN (`sourceLang`, `targetLang`)',
                    $params
                );

                // If there were only two `LEK_languageresources_languages`-records before DELETE-ion
                if (count($languageA) == 2) {
                    // Get prop
                    $prop = $params[1] == $languageA[0]['sourceLang'] ? 'targetLang' : 'sourceLang';

                    // Insert using existing language as source and new language as target
                    $m = ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
                    $m->init([
                        'sourceLang' => $languageA[0][$prop],
                        'sourceLangCode' => $languageA[0][$prop . 'Code'],
                        'targetLang' => $languageA[0][$prop],
                        'targetLangCode' => $languageA[0][$prop . 'Code'],
                        'languageResourceId' => $params[0],
                    ]);
                    $m->save();
                }
            }
            // Else if $event is  'insert'
        } elseif ($event == 'insert') {
            // Get info
            $info = $this->db->getAdapter()->query(
                'SELECT * 
                FROM `LEK_languageresources_languages` 
                WHERE `languageResourceId` = ? 
                ORDER BY `sourceLang` = ? DESC
                LIMIT 2',
                $params
            )->fetchAll();

            // If $info is an emty array, it means that INSERTed term was the first term in that collection
            if (! $info) {
                // So we insert single `LEK_languageresources_languages`-record having same source and target
                $m = ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
                $m->init([
                    'sourceLang' => $params[1],
                    'sourceLangCode' => $this->getLanguage(),
                    'targetLang' => $params[1],
                    'targetLangCode' => $this->getLanguage(),
                    'languageResourceId' => $params[0],
                ]);
                $m->save();
                // Else if it was not the first term in that collection, but was the first term for that language
            } elseif ($info[0]['sourceLang'] != $this->getLanguage()) {
                // Get existing languages
                $existingA = $this->db->getAdapter()->query(
                    'SELECT DISTINCT `sourceLang`, `sourceLangCode` 
                    FROM `LEK_languageresources_languages`
                    WHERE `languageResourceId` = ?',
                    $params[0]
                )->fetchAll(PDO::FETCH_KEY_PAIR);

                // Foreach of existing languages
                foreach ($existingA as $sourceLang => $sourceLangCode) {
                    // Insert using existing language as source and new language as target
                    $m = ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
                    $m->init([
                        'sourceLang' => $sourceLang,
                        'sourceLangCode' => $sourceLangCode,
                        'targetLang' => $this->getLanguageId(),
                        'targetLangCode' => $this->getLanguage(),
                        'languageResourceId' => $params[0],
                    ]);
                    $m->save();

                    // Flip source and target and insert again
                    $m = ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
                    $m->init([
                        'sourceLang' => $this->getLanguageId(),
                        'sourceLangCode' => $this->getLanguage(),
                        'targetLang' => $sourceLang,
                        'targetLangCode' => $sourceLangCode,
                        'languageResourceId' => $params[0],
                    ]);
                    $m->save();
                }

                // Since we just inserted non-the-first term into collection,
                // we need to remove `LEK_languageresources_languages`-record having same source and target
                if (count($info) === 1) {
                    $this->db->getAdapter()->query(
                        'DELETE FROM `LEK_languageresources_languages` WHERE `id` = ?',
                        $info[0]['id']
                    )->fetchAll(PDO::FETCH_KEY_PAIR);
                }
            }
        }
    }

    /**
     * If $misc arg is given, method expects it's an array containing values under 'userName', and (optionally)
     * 'updateProcessStatusAttr' keys, and if so, this method will run UPDATE query to update `date` and `transacNote`
     * for all involved records of `terms_transacgrp` table for entry-, language- and term-level
     *
     * @param array $misc
     * @return mixed
     */
    public function update(
        $misc = [
            'updateProcessStatusAttr' => true,
        ],
    ) {
        // Get original data
        $orig = $this->row->getCleanData();

        // Call parent
        parent::save();

        // If current data is not equal to original data
        if ($this->toArray() != $orig) {
            // Prepare data for history record
            $init = $orig;
            $init['termId'] = $orig['id'];
            unset($init['id']);

            // Create history instance
            $history = ZfExtended_Factory::get('editor_Models_Term_History');

            // Init with data
            $history->init($init);

            // Save
            $history->save();
        }

        // If term's processStatus-prop was changed, and we should update processStatus-attr as well (yes, by default)
        if ($orig['processStatus'] != $this->getProcessStatus() && $misc['updateProcessStatusAttr']) {
            // If we should update processStatus-attribute, but we don't know it's `id` yet - detect it
            // else just pick that id from $misc['updateProcessStatusAttr']
            $attrId = $misc['updateProcessStatusAttr'] === true
                ? $this->db->getAdapter()->query(
                    'SELECT `id` FROM `terms_attributes` WHERE `termId` = ? AND `type` = "processStatus"',
                    $this->getId()
                )->fetchColumn()
                : $misc['updateProcessStatusAttr'];

            // Update attribute value
            $attr = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
            $attr->load($attrId);
            $attr->setValue($this->getProcessStatus());
            $attr->setUpdatedBy((int) $this->getUpdatedBy());
            $attr->setIsCreatedLocally(1);
            $attr->update();

            // If processStatus became 'rejected'
            if ($orig['processStatus'] != 'rejected' && $this->getProcessStatus() == 'rejected') {
                // Set 'normativeAuthorization' attribute to 'deprecatedTerm'
                // If no such attribute yet exists - it will be created
                $this->normativeAuthorization = $this
                    ->setAttr('normativeAuthorization', 'deprecatedTerm')
                    ->toArray();
            }
        }

        // If $transacgrpData arg is given - update 'modification'-records of all levels
        if (isset($misc['userName'])) {
            $return = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->affectLevels(
                    $misc['userName'],
                    $misc['userGuid'],
                    $this->getTermEntryId(),
                    $this->getLanguage(),
                    $this->getId()
                );
        }

        // Return
        return $return ?? null;
    }

    public function delete()
    {
        // Backup collectionId and languageId
        $collectionId = (int) $this->getCollectionId();
        $languageId = (int) $this->getLanguageId();

        // Backup tbx(Created|Updated)By props
        $personIds = [];
        if ($this->getTbxCreatedBy()) {
            $personIds[$this->getTbxCreatedBy()] = true;
        }
        if ($this->getTbxUpdatedBy()) {
            $personIds[$this->getTbxUpdatedBy()] = true;
        }
        $personIds = array_keys($personIds);

        $termData = $this->toArray();

        // Call parent
        parent::delete();

        $this->logDeletedTerm($termData);

        // Drop terms_transacgrp_person-records if not used anymore
        ZfExtended_Factory
            ::get('editor_Models_Terminology_Models_TransacgrpPersonModel')
                ->dropIfNotUsedAnymore($personIds);

        // Restore collectionId and languageId
        $this->setCollectionId($collectionId);
        $this->setLanguageId($languageId);

        // Update collection languages
        $this->updateCollectionLangs('delete');
    }

    /**
     * Init a new attr with main props given by $data arg and the rest copied from $this
     *
     * @return editor_Models_Terminology_Models_AttributeModel
     */
    public function initAttr($data = [])
    {
        /** @var editor_Models_Terminology_Models_AttributeModel $a */
        $a = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');

        // Do init using $data arg having priority over props copied from $this
        $a->init(
            $data + [
                'collectionId' => $this->getCollectionId(),
                'termEntryId' => $this->getTermEntryId(),
                'language' => $this->getLanguage(),
                'termId' => $this->getId(),
                'termTbxId' => $this->getTermTbxId(),

                // Below four can be provided by $data
                //'dataTypeId' => ,
                //'type' => ,
                //'value' => ,
                //'target' => ,

                'isCreatedLocally' => 1,
                'createdBy' => $this->getUpdatedBy(),
                'createdAt' => date('Y-m-d H:i:s'),
                'updatedBy' => $this->getUpdatedBy(),
                'updatedAt' => date('Y-m-d H:i:s'),
                'termEntryGuid' => $this->getTermEntryGuid(),
                'langSetGuid' => $this->getLangSetGuid(),
                'termGuid' => $this->getGuid(),
                'guid' => ZfExtended_Utils::uuid(),

                // Those three may be defined in $data, and if yes, they won't be overwritten by below
                'elementName' => 'termNote',
                'attrLang' => $this->getLanguage(),
                //'dataType' => null
            ]
        );

        // Return $a
        return $a;
    }

    /**
     * Set term's attribute, found by dataTypeId, identified by $type arg
     * If no such attribute yet exists - it will be created
     *
     * @return editor_Models_Terminology_Models_AttributeModel|void
     * @throws Zend_Db_Statement_Exception
     */
    public function setAttr($type, $value)
    {
        // Get dataTypeId todo: throw exception if not found
        if (! $dataTypeId = $this->db->getAdapter()->query(
            'SELECT `id` FROM `terms_attributes_datatype` WHERE `type` = ? LIMIT 1',
            $type
        )->fetchColumn()) {
            return;
        }

        // Try to find id of existing attribute having such $dataTypeId
        $attrId = (int) $this->db->getAdapter()->query(
            'SELECT `id` FROM `terms_attributes` WHERE `termId` = ? AND `dataTypeId` = ? LIMIT 1',
            [$this->getId(), $dataTypeId]
        )->fetchColumn();

        // If exists
        if ($attrId) {
            /** @var editor_Models_Terminology_Models_AttributeModel $a */
            $a = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');

            // Load
            $a->load($attrId);

            // Set value
            $a->setValue($value);
            $a->setIsCreatedLocally(1);

            // Update. Here we use update method for history record to be created
            $a->update();
            // Else
        } else {
            /** @var editor_Models_Terminology_Models_AttributeModel $a */
            $a = $this->initAttr([
                'dataTypeId' => $dataTypeId,
                'type' => $type,
            ]);

            // Set value
            $a->setValue($value);

            // Insert
            $a->insert();
        }

        // Return attribute
        return $a;
    }

    /**
     * returns a map CONSTNAME => value of all term status
     */
    public static function getAllStatus(): array
    {
        self::initConstStatus();

        return self::$statusCache['status'];
    }

    /**
     * returns an array with groupId and term to a given mid
     * in old table was:
     * termEntryTbxId = groupId
     * termId = mid
     *
     * @return array|Zend_Db_Table_Row_Abstract
     */
    public function getTermAndGroupIdToMid(string $termId, array $collectionIds): ?array
    {
        if (! empty(self::$termEntryTbxIdCache[$termId])) {
            return self::$termEntryTbxIdCache[$termId];
        }

        $select = $this->db->select()
            ->from($this->db, ['termEntryTbxId', 'term'])
            ->where('collectionId IN(?)', $collectionIds)
            ->where('termTbxId = ?', $termId);
        $res = $this->db->fetchRow($select);
        if (empty($res)) {
            return $res;
        }
        self::$termEntryTbxIdCache[$termId] = $res;

        return $res->toArray();
    }

    /***
     *
     * Load all terms for export and term tagging for given collections, languages and processStatusess
     * @param array $collectionIds
     * @param array $langs
     * @param array $processStatus
     * @return Zend_Db_Table_Rowset_Abstract|null
     */
    public function loadSortedForExport(
        array $collectionIds,
        array $langs = [],
        array $processStatus = [],
    ): ?Zend_Db_Table_Rowset_Abstract {
        $s = $this->db->select()
            ->where('collectionId IN(?)', $collectionIds);

        if (! empty($langs)) {
            $s->where('languageId in (?)', $langs);
        }

        if (! empty($processStatus)) {
            $s->where('processStatus in (?)', $processStatus);
        }

        $s->order('termEntryTbxId ASC')
            ->order('languageId ASC')
            ->order('id ASC');
        $data = $this->db->fetchAll($s);

        if ($data->count() === 0) {
            return null;
        }

        return $data;
    }

    /**
     * Get array of termEntryIds where the given $term exists and have given $languageId and $collectionId,
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function searchTermEntryIdsBy(int $collectionId, string $language, string $term): array
    {
        return $this->db->getAdapter()->query(
            "
            SELECT `termEntryId` 
            FROM `terms_term` 
            WHERE `collectionId` = $collectionId 
              AND `language` = ?
              AND ? IN (`term`, `proposal`)",
            [$language, trim($term)]
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function searchTermByParams(array $params = [], &$total = null)
    {
        /*if (!$params)
        $params = [
            'query' => 'bi*',
            'language' => '5,251,252,367,368,369,370,371,372,373,374,375,376,377',
            'collectionIds' => '8,10,11,12',
            'processStatus' => 'provisionallyProcessed,rejected,finalized,unprocessed',
            'noTermDefinedFor' => '4',
            'limit' => '25,0',
            'attrs' => [
                6 => 'asd',   // 6 is the attribute's dataTypeId
                7 => 'qwe'
            ]
        ];*/

        // If attr-filters are involved in search
        if ($params['attrs']) {
            // Get `id` => `rfc5646` pairs for all languages
            $codeByLangIdA = ZfExtended_Factory::get('editor_Models_Languages')
                ->loadAllKeyValueCustom('id', 'rfc5646', true);

            // Prepare array of language code to be used in WHERE clause
            $codeA = [];
            foreach (explode(',', $params['language']) as $langId) {
                $codeA[] = $codeByLangIdA[$langId];
            }

            // Get text-attributes datatype ids
            $textA = $this->db->getAdapter()
                ->query('SELECT `id`, 1 FROM `terms_attributes_datatype` WHERE `dataType` != "picklist"')
                ->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        // Get the comma-separated list of termEntryIds and/or termIds matching attr-filters
        foreach ($params['attrs'] as $aDataTypeId => $aValue) {
            // Fuzzy search for text-attrs
            if (isset($textA[$aDataTypeId])) {
                $aValue = '*' . trim($aValue, '*?') . '*';
            }

            // If wildcards are used, convert them to the mysql syntax
            $expr = str_replace(['*', '?'], ['%', '_'], $aValue);

            // Prepare query param bindings
            $bind = [
                ':dataTypeId' => $aDataTypeId,
            ];

            // Build WHERE clause
            $attrWHERE = ['`dataTypeId` = :dataTypeId'];

            // Setup WHERE clauses for entry-, language- and term- level attributes
            $attrWHERE[] = '(ISNULL(`language`) OR `language` IN ("' . implode('","', $codeA) . '"))';

            // If filter value is given
            if ($aValue) {
                // Append to WHERE clause
                $attrWHERE[] = '`value`' . ($expr == $aValue ? ' = ' : ' LIKE ') . ':value';

                // Add bindings
                $bind += [
                    ':value' => $expr,
                ];
            }

            // Mind previous query results to apply intersection
            if ($matched ?? 0) {
                $or = [];
                if ($matched['termEntryId'] ?? 0) {
                    $or[] = '`termEntryId` IN (' . join(',', $matched['termEntryId']) . ')';
                }
                if ($matched['termId'] ?? 0) {
                    $or[] = '`termId` IN (' . join(',', $matched['termId']) . ')';
                }
                $attrWHERE[] = '(' . join(' OR ', $or) . ')';
            }

            // Get termEntryIds and/or termIds of matched attributes
            $matched = $this->db->getAdapter()->query(
                'SELECT DISTINCT 
                  IF(`termId`, "termId", "termEntryId") AS `prop`, 
                  IF(`termId`, `termId`, `termEntryId`) AS `value`
                FROM `terms_attributes` 
                WHERE ' . implode(' AND ', $attrWHERE),
                $bind
            )->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);

            // If nothing found
            if (! $matched) {
                // Setup &$total variable by reference, as 0
                $total = 0;

                // Return empty data
                return [];
            }
        }

        // If wildcards are used, convert them to the mysql syntax
        $keyword = str_replace(['*', '?'], ['%', '_'], $params['query']);

        // Flag, indicating whether we're in allExcept-mode
        $allExcept = array_key_exists('except', $params);

        // If we're not going to count $total -
        // it means we're in autocomplete mode,
        // so append wildcard if not already added
        if ($total === false && ! $allExcept) {
            $keyword = rtrim($keyword, '%') . '%';
        }

        // Shared WHERE clause, that will be used for querying both terms and proposals tables
        $where = [
            '`t`.`languageId` IN (' . $params['language'] . ')',
            '`t`.`collectionId` IN (' . ($params['collectionIds'] ?: 0) . ')',
        ];

        // Append clause for prosessStatus
        if ($params['processStatus']) {
            $where[] = '`t`.`processStatus` IN ("' . str_replace(',', '","', $params['processStatus']) . '")';
            if (preg_match('~unprocessed~', $params['processStatus'])) {
                $where[] = '(' . array_pop($where) . ' OR `t`.`proposal` != "")';
            }
        }

        // Mind attr-filters in WHERE clause
        if ($matched ?? 0) {
            $or = [];
            if ($matched['termEntryId'] ?? 0) {
                $or[] = '`t`.`termEntryId` IN (' . join(',', $matched['termEntryId']) . ')';
            }
            if ($matched['termId'] ?? 0) {
                $or[] = '`t`.`id` IN (' . join(',', $matched['termId']) . ')';
            }
            array_unshift($where, '(' . join(' OR ', $or) . ')');
        }

        // If 'noTermDefinedFor' param is given
        if ($_ = (int) $params['noTermDefinedFor']) {
            // Respect it in FROM clause
            $noTermDefinedFor = sprintf(
                ' LEFT JOIN `terms_term` AS `t2` ON (
                `t`.`termEntryId` = `t2`.`termEntryId` AND `t2`.`languageId` = "%s"
            )',
                $_
            );

            // Respect it in WHERE clause
            $where[] = 'ISNULL(`t2`.`term`)';
        }

        // Data columns, that would be fetched by search SQL query
        $termQueryCol = $allExcept ? '`t`.`' . ($params['column'] ?? 'id') . '`' : '
          `t`.`id`, 
          `t`.`collectionId`, 
          `t`.`termEntryId`, 
          `t`.`languageId`, 
          `t`.`term`, 
          `t`.`proposal`, 
          `t`.`processStatus`, 
          `t`.`status`, 
          `t`.`definition`, 
          `t`.`termEntryTbxId` 
        ';

        // Assume limit arg can be comma-separated string containing '<LIMIT>,<OFFSET>'
        if (! $allExcept) {
            list($limit, $offset) = explode(',', $params['limit']);
        }

        // Cols that we're going to search in by default
        $cols = ['`t`.`term`', '`t`.`proposal`'];

        // If we should only search for `term`-column (e.g. `proposal`-column won't be involved)
        if ($params['processStatus']
            && ! in_array(self::PROCESS_STATUS_UNPROCESSED, editor_Utils::ar($params['processStatus']))) {
            // Drop proposal-col from $cols, so it won't be mentioned in $keywordWHERE
            array_pop($cols);
        }

        // Keyword WHERE clauses using LIKE
        foreach ($cols as $col) {
            $keywordWHERE[] = sprintf('LOWER(%s) LIKE LOWER(:keyword) COLLATE utf8mb4_bin', $col);
        }

        // Render keyword WHERE string
        $keywordWHERE = '(' . implode(' OR ', $keywordWHERE) . ')';

        // Keep letters, numbers and underscores only
        $against = trim(preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $params['query'])) . '*';

        // Build match-against WHERE
        $againstWHERE = [
            'words' => 'MATCH(' . implode(', ', $cols) . ') AGAINST(:against IN BOOLEAN MODE)',
        ];

        // If $against contains spaces
        if (preg_match('~ ~', $against)) {
            // Prepare whole search phrase
            $phrase = '"' . trim($against, '*') . '"';

            // Build match-against WHERE clause for the whole phrase
            $againstWHERE['phrase'] = 'MATCH(' . implode(', ', $cols) . ') AGAINST(:phrase IN BOOLEAN MODE)';

            // Add special search syntax
            $against = '+' . preg_replace('~ ~', ' +', $against);
        }

        // Prepare params array
        $bindParam = [];

        // Mind termEntryTbxId and termTbxId params
        foreach (['termEntryTbxId', 'termTbxId'] as $prop) {
            if (isset($params[$prop]) && $params[$prop]) {
                $token = ':' . $prop;
                $where[] = '`t`.`' . $prop . '` LIKE CONCAT("%%", ' . $token . ', "%%")';
                $bindParam[$token] = $params[$prop];
            }
        }

        // Mind tbxCreatedBy and tbxUpdatedBy params
        foreach (['tbxCreatedBy', 'tbxUpdatedBy'] as $prop) {
            if (isset($params[$prop]) && $params[$prop]) {
                $where[] = '`t`.`' . $prop . '` IN (' . $params[$prop] . ')';
            }
        }

        // Respect tbx(Created|Updated)(At|Gt|Lt) params
        foreach (['tbxCreated', 'tbxUpdated'] as $prop) {
            $since = $params[$prop . 'Gt'] ?? false;
            $until = $params[$prop . 'Lt'] ?? false;
            $at = $params[$prop . 'At'] ?? false;
            if ($at || $since || $until) {
                if ($at) {
                    $cond = " = '$at'";
                } elseif ($since && $until) {
                    $cond = "BETWEEN '$since' AND '$until'";
                } elseif ($since) {
                    $cond = ">= '$since'";
                } else {
                    $cond = "<= '$until'";
                }
                $where[] = 'DATE(`t`.`' . $prop . "At`) $cond";
            }
        }

        // If it's a non '*'-query (e.g. non 'any'-query)
        if (! preg_match('~^\*+$~', $against)) {
            // Append :keyword param
            $bindParam[':keyword'] = $keyword;

            // Prepend $where with $keywordWHERE
            array_unshift($where, $keywordWHERE);

            // Check if wildcard prefix is NOT going to be used in LIKE(:keyword), and if so
            if (! preg_match('~[%_][^\s]~', $keyword)) {
                // Add bindings
                $bindParam[':against'] = $against;
                if (count($againstWHERE) == 2) {
                    $bindParam[':phrase'] = $phrase;
                }

                // Stringify againstWHERE clause(s)
                $againstWHERE = '(' . join(' OR ', $againstWHERE) . ')';

                // Prepend $where with $againstWHERE, because
                // FULLTEXT-search does not support wildcard prefixes
                array_unshift($where, $againstWHERE);
            }
        }

        // Make sure some termIds will be excluded
        if ($allExcept && $params['except'] ?? 0) {
            $where[] = '`t`.`' . ($params['column'] ?? 'id') . '` NOT IN (' . $params['except'] . ')';
        }

        // Term query template
        $termQueryTpl = '
            SELECT SQL_NO_CACHE %s 
            FROM `terms_term` `t` %s 
            WHERE ' . implode(' AND ', $where) . ' 
            ORDER BY `t`.`term` ASC';

        // If we have to calculate total
        if ($total === true) {
            // Render query for getting total count of results in terms-table
            $totalQuery = sprintf($termQueryTpl, 'COUNT(*)', $noTermDefinedFor ?? '', $keywordWHERE);

            // Setup &$total variable by reference
            $total = (int) $this->db->getAdapter()->query($totalQuery, $bindParam)->fetchColumn();
        }

        // Render query for getting actual results from terms table
        $termQuery = sprintf($termQueryTpl, $termQueryCol, $noTermDefinedFor ?? '', $keywordWHERE);

        // If we're not in allExcept-mode - append LIMIT clause
        if (! $allExcept) {
            $termQuery .= ' LIMIT ' . (int) $offset . ',' . (int) $limit;
        }

        // If we're on allExcept-mode - make sure ids will be fetched
        $fetchMode = $allExcept ? PDO::FETCH_COLUMN : null;

        // Return results
        return $this->db->getAdapter()->query($termQuery, $bindParam)->fetchAll($fetchMode);
    }

    /**
     * returns a map CONSTNAME => value of all term process-status
     */
    public static function getAllProcessStatus(): array
    {
        self::initConstStatus();

        return self::$statusCache['processStatus'];
    }

    /**
     * creates a internal list of the status constants
     */
    protected static function initConstStatus()
    {
        if (! empty(self::$statusCache)) {
            return;
        }

        self::$statusCache = [
            'status' => [],
            'translation' => [],
            'processStatus' => [],
        ];

        $reflection = new ReflectionClass(__CLASS__);
        $constants = $reflection->getConstants();
        foreach ($constants as $key => $val) {
            if (strpos($key, 'STAT_') === 0) {
                self::$statusCache['status'][$key] = $val;
            }
            if (strpos($key, 'TRANSSTAT_') === 0) {
                self::$statusCache['translation'][$key] = $val;
            }
            if (strpos($key, 'PROCESS_STATUS_') === 0) {
                self::$statusCache['processStatus'][$key] = $val;
            }
        }
    }

    /***
     * Returns all terms of the given $searchTerms that don't exist in
     * any of the given collections.
     * @param array $searchTerms with objects {'text':'abc', 'id':123}
     * @param array $collectionIds
     * @param array $language
     * @return array $nonExistingTerms with objects {'text':'abc', 'id':123}
     */
    public function getNonExistingTermsInAnyCollection(array $searchTerms, array $collectionIds, array $language): array
    {
        $nonExistingTerms = [];
        if (empty($searchTerms) || empty($collectionIds) || empty($language)) {
            return $nonExistingTerms;
        }
        foreach ($searchTerms as $term) {
            $s = $this->db->select()
                ->where('term = ?', $term->text)
                ->where('collectionId IN(?)', $collectionIds)
                ->where('languageId IN (?)', $language);
            $terms = $this->db->fetchAll($s);

            if ($terms->count() === 0) {
                $nonExistingTerms[] = $term;
            }
        }

        return $nonExistingTerms;
    }

    /**
     * Returns the configured mapping of term-statuses
     * (= which statuses are allowed etc).
     */
    public static function getTermStatusMap(): array
    {
        $config = Zend_Registry::get('config');

        return $config->runtimeOptions->tbx->termLabelMap->toArray();
    }

    /***
     * Export term and term attribute proposals in excel file.
     * When no path is provided, redirect the output to a client's web browser (Excel)
     *
     * @param array $rows
     * @param string|null $path : the path where the excel document will be saved
     */
    public function exportProposals(array $rows, string $path = null)
    {
        $excel = ZfExtended_Factory::get('ZfExtended_Models_Entity_ExcelExport');
        /* @var $excel ZfExtended_Models_Entity_ExcelExport */

        // set property for export-filename
        $excel->setProperty('filename', 'Term and term attributes proposals');

        $t = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $t ZfExtended_Zendoverwrites_Translate */

        // sample label-translations
        $excel->setLabel('termEntryId', $t->_('Eintrag'));
        $excel->setLabel('definition', $t->_('Definition'));
        $excel->setLabel('language', $t->_('Sprache'));
        $excel->setLabel('termTbxId', $t->_('Term-Id'));
        $excel->setLabel('term', $t->_('Term'));
        $excel->setLabel('termProposal', $t->_('Änderung zu bestehendem Term'));
        $excel->setLabel('processStatus', $t->_('Prozess-Status'));
        $excel->setLabel('attributeName', $t->_('Attributs-Schlüssel'));
        $excel->setLabel('attribute', $t->_('Attributs-Wert'));
        $excel->setLabel('attributeProposal', $t->_('Änderung zu bestehendem Attributs-Wert'));
        $excel->setLabel('lastEditor', $t->_('Letzter Bearbeiter'));
        $excel->setLabel('lastEditedDate', $t->_('Bearbeitungsdatum'));

        $autosizeCells = function ($phpExcel) use ($excel) {
            foreach ($phpExcel->getWorksheetIterator() as $worksheet) {
                $phpExcel->setActiveSheetIndex($phpExcel->getIndex($worksheet));
                $sheet = $phpExcel->getActiveSheet();

                //the highes column based on the current row columns
                $highestColumn = 'M';
                foreach (range('A', $highestColumn) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                $highestColumnIndex = $excel->columnIndexFromString($highestColumn);

                // expects same number of row records for all columns
                $highestRow = $worksheet->getHighestRow();

                for ($col = 0; $col < $highestColumnIndex; $col++) {
                    // if you do not expect same number of row records for all columns
                    // get highest row index for each column
                    // $highestRow = $worksheet->getHighestRow();
                    for ($row = 1; $row <= $highestRow; $row++) {
                        $cell = $worksheet->getCellByColumnAndRow($col, $row);
                        if (strpos($cell->getValue(), '<changemycolortag>') !== false) {
                            $cell->setValue(str_replace('<changemycolortag>', '', $cell->getValue()));
                            $sheet->getStyle($cell->getCoordinate())->getFill()->setFillType('solid')->getStartColor(
                            )->setRGB('f9f25c');
                        }
                    }
                }
            }
        };

        //if the path is provided, save the excel into the given path location
        if (! empty($path)) {
            $excel->loadArrayData($rows);
            $autosizeCells($excel->getSpreadsheet());
            $excel->saveToDisc($path);

            return;
        }

        //send the excel to browser download
        $excel->simpleArrayToExcel($rows, $autosizeCells);
    }

    /***
     * Update language assoc for given collections. The langages are merged from exsisting terms per collection.
     * @param array|null $collectionIds
     */
    public function updateAssocLanguages(array $collectionIds = null)
    {
        $s = $this->db->select()
            ->from(
                [
                    't' => 'terms_term',
                ],
                ['t.languageId', 't.collectionId']
            )
            ->join(
                [
                    'l' => 'LEK_languages',
                ],
                't.languageId = l.id',
                ['rfc5646', 'langName']
            );

        if (! empty($collectionIds)) {
            $s->where('t.collectionId IN(?)', $collectionIds);
        }

        $s->group('t.collectionId')->group('t.languageId')->setIntegrityCheck(false);
        $ret = $this->db->fetchAll($s)->toArray();

        $data = [];
        foreach ($ret as $lng) {
            if (! isset($data[$lng['collectionId']])) {
                $data[$lng['collectionId']] = [];
            }
            array_push($data[$lng['collectionId']], $lng);
        }

        foreach ($data as $key => $value) {
            $alreadyProcessed = [];
            //the term collection contains terms with only one language
            $isSingleCombination = count($value) == 1;
            foreach ($value as $x) {
                foreach ($value as $y) {
                    //keep track of what is already processed
                    $combination = [$x['languageId'], $y['languageId']];

                    //it is not the same number or single language combination and thay are not already processed
                    if (($x['languageId'] === $y['languageId'] && ! $isSingleCombination) || in_array(
                        $combination,
                        $alreadyProcessed
                    )) {
                        continue;
                    }
                    //Add it to the list of what you've already processed
                    $alreadyProcessed[] = $combination;

                    //save the language combination
                    $model = ZfExtended_Factory::get(editor_Models_LanguageResources_Languages::class);

                    $model->setSourceLang($x['languageId']);
                    $model->setSourceLangCode($x['rfc5646']);
                    $model->setSourceLangName($x['langName']);

                    $model->setTargetLang($y['languageId']);
                    $model->setTargetLangCode($y['rfc5646']);
                    $model->setTargetLangName($y['langName']);

                    $model->setLanguageResourceId($key);
                    $model->save();
                }
            }
        }
    }

    /**
     * exports all terms of all termCollections associated to the task in the task's languages.
     * @throws editor_Models_Term_TbxCreationException
     */
    public function exportForTagging(editor_Models_Task $task): string
    {
        $languageModel = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languageModel editor_Models_Languages */

        $assoc = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $assoc editor_Models_TermCollection_TermCollection */
        $collectionIds = $assoc->getCollectionsForTask($task->getTaskGuid());

        if (empty($collectionIds)) {
            //No term collection assigned to task although tasks terminology flag is true.
            // This is normally not possible, since the terminology flag in the task is maintained on TC task assoc changes via API
            throw new editor_Models_Term_TbxCreationException('E1113', [
                'task' => $task,
            ]);
        }

        //get source and target language fuzzies
        $langs = [
            $languageModel->getFuzzyLanguages($task->getSourceLang(), includeMajor: true),
            $languageModel->getFuzzyLanguages($task->getTargetLang(), includeMajor: true),
        ];
        if ($task->getRelaisLang() > 0) {
            $langs[] = $languageModel->getFuzzyLanguages((int) $task->getRelaisLang(), includeMajor: true);
        }
        $langs = array_unique(array_merge(...$langs));

        $statuses = $task->getConfig()->runtimeOptions->terminology->usedTermProcessStatus->toArray();
        if (empty($statuses)) {
            $statuses = [];
        }
        $data = $this->loadSortedForExport($collectionIds, $langs, $statuses);
        if (! $data) {
            //The associated collections don't contain terms in the languages of the task.
            // Should not be, should be checked already on assignment of collection to task.
            // Colud happen when all terms of a language are removed from a TermCollection via term import after associating that term collection to a task.
            throw new editor_Models_Term_TbxCreationException('E1114', [
                'task' => $task,
                'collectionIds' => $collectionIds,
                'languageIds' => $langs,
            ]);
        }

        $exporteur = ZfExtended_Factory::get('editor_Models_Export_Terminology_Tbx');
        /* @var $exporteur editor_Models_Export_Terminology_Tbx */
        $exporteur->setData($data);
        $result = $exporteur->export();
        if (empty($result)) {
            //collected terms could not be converted to XML.
            throw new editor_Models_Term_TbxCreationException('E1115', [
                'task' => $task,
                'collectionIds' => $collectionIds,
                'languageIds' => $langs,
            ]);
        }

        return $result;
    }

    /**
     * Sortiert die Terme innerhalb der Termgruppen:
     */
    public function sortTerms(array $termGroups): array
    {
        foreach ($termGroups as $groupId => $group) {
            usort($group, [$this, 'compareTerms']);
            $termGroups[$groupId] = $group;
        }

        return $termGroups;
    }

    /**
     * Bewertet die Terme nach den folgenden Kriterien (siehe auch http://php.net/usort/)
     *  -- 1. Kriterium: Vorzugsbenennung vor erlaubter Benennung vor verbotener Benennung
     *  -- 2. Kriterium: In Quelle vorhanden
     *  -- 3. Kriterium: In Ziel vorhanden (damit ist die Original-Ãœbersetzung gemeint, nicht die editierte Variante)
     *  -- 4. Kriterium: Alphanumerische Sortierung
     *  Zusammenhang Parameter und Return Values siehe usort $cmp_function
     */
    protected function compareTerms($term1, $term2): int
    {
        // return > 0 => t1 > t2
        // return = 0 => t1 = t2
        // return < 0 => t1 < t2
        $term1 = is_array($term1) ? (object) $term1 : $term1;
        $term2 = is_array($term2) ? (object) $term2 : $term2;
        $status = $this->compareTermStatus($term1->status, $term2->status);

        if ($status !== 0) {
            return $status;
        }

        $isSource = 0;
        if (isset($term1->isSource)) {
            $isSource = $this->compareTermLangUsage($term1->isSource, $term2->isSource);
        }

        if ($isSource !== 0) {
            return $isSource;
        }

        //Kriterium 4 - alphanumerische Sortierung:
        return strcmp(mb_strtolower($term1->term), mb_strtolower($term2->term));
    }

    /**
     * Vergleicht die Term Status
     */
    protected function compareTermStatus(string $status1, string $status2): int
    {
        //wenn beide stati gleich, dann wird kein weiterer Vergleich benoetigt
        if ($status1 === $status2) {
            return 0;
        }
        if (empty($this->statOrder[$status1])) {
            $status1 = self::STAT_NOT_FOUND;
        }
        if (empty($this->statOrder[$status2])) {
            $status2 = self::STAT_NOT_FOUND;
        }

        //je kleiner der statOrder, desto hÃ¶herwertiger ist der Status!
        //Da Hoeherwertig aber bedeutet, dass es in der Sortierung weiter oben erscheinen soll,
        //ist der Hoeherwertige Status im numerischen Wert kleiner!
        if ($this->statOrder[$status1] < $this->statOrder[$status2]) {
            return -1; //status1 ist hoeherwertiger, da der statOrdner kleiner ist
        }

        return 1; //status2 ist hoeherwertiger
    }

    /**
     * Vergleicht die Term auf Verwendung in Quell oder Zielspalte
     */
    protected function compareTermLangUsage(string $isSource1, string $isSource2): int
    {
        //Verwendung in Quelle ist hoeherwertiger als in Ziel (Kriterium 2 und 3)
        if ($isSource1 === $isSource2) {
            return 0;
        }
        if ($isSource1) {
            return 1;
        }

        return -1;
    }

    /***
     * Remove terms where the updated date is older than the given one.
     * TODO: Import performance bottleneck. Optimize this if possible!
     * @param array $collectionIds
     * @param string $olderThan
     * @return boolean
     */
    public function removeOldTerms(array $collectionIds, $olderThan): bool
    {
        //get all terms in the collection older than the date
        $s = $this->db->select()
            ->setIntegrityCheck(false)
            ->from([
                't' => 'terms_term',
            ], ['t.id', 't.proposal'])
            //->joinLeft(['p'=>'terms_proposal'],'p.termId=t.id ',['p.term','p.created','p.userGuid','p.userName'])
            ->where('t.updatedAt < ?', $olderThan)
            ->where('t.collectionId in (?)', $collectionIds)
            ->where('t.processStatus NOT IN (?)', self::PROCESS_STATUS_UNPROCESSED);
        $result = $this->db->fetchAll($s)->toArray();

        if (empty($result)) {
            return false;
        }
        $term = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $term editor_Models_Terminology_Models_TermModel */
        //$transacGrp = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');
        /* @var $transacGrp editor_Models_Terminology_Models_TransacgrpModel */
        $session = (new Zend_Session_Namespace('user'))->data;

        //$deleteProposals = [];
        //for each of the terms with the proposals, use the proposal value as the
        //new term value in the original term, after the original term is updated, remove
        //the proposal
        foreach ($result as $key => $res) {
            /*if (empty($res['term'])) {*/
            if (empty($res['proposal'])) {
                continue;
            }
            /*$proposal = ZfExtended_Factory::get('editor_Models_Term_Proposal');
            /* @var $proposal editor_Models_Term_Proposal */
            /*$proposal->init([
                'created'=>$res['created'],
                'userGuid'=>$res['userGuid'],
                'userName'=>$res['userName'],
            ]);*/

            $term->load($res['id']);
            /*$term->setTerm($res['term']);
            $term->setCreated($res['created']);
            $term->setUpdated(NOW_ISO);
            $term->setUserGuid($res['userGuid']);
            $term->setUserName($res['userName']);*/

            $term->setTerm($res['proposal']);
            $term->setProposal('');
            $term->setUpdatedBy($session->id);
            //$term->setUpdatedAt(NOW_ISO);
            $term->setProcessStatus(self::PROCESS_STATUS_UNPROCESSED);
            //TODO: with the next termportal step(add new attribute and so)
            //update/merge those new proposal attributes to
            //now only the transac group should be modefied
            // $transacGrp->updateTermProcessStatus($term, $term::PROCESS_STATUS_UNPROCESSED);
            //$term->save();
            $term->update([
                'userName' => $session->userName, // transacGrp will be updated
                'userGuid' => $session->userGuid, // transacGrp will be updated
                'updateProcessStatusAttr' => true, // processStatus-attr will be updated
            ]);
            //$deleteProposals[] = $res['id'];
            unset($result[$key]);
        }
        /*//remove the collected proposals
        if (!empty($deleteProposals)) {
            $proposal = ZfExtended_Factory::get('editor_Models_Term_Proposal');
            /* @var $proposal editor_Models_Term_Proposal * /
            $proposal->db->delete([
                'termId IN(?)' => $deleteProposals
            ]);
        }*/

        $result = array_column($result, 'id');
        if (empty($result)) {
            return false;
        }

        //delete the collected old terms
        return $this->db->delete([
            'id IN(?)' => $result,
        ]) > 0;
    }

    /***
     * Load all term and attribute proposals, or if second parameter is given load only proposals younger as $youngerAs
     * date within the given collection(s)
     * @param array $collectionIds
     * @param string $youngerAs optional, if omitted all proposals are loaded
     */
    public function loadProposalExportData(array $collectionIds, string $youngerAs = '')
    {
        if (empty($collectionIds)) {
            return [];
        }
        $adapter = $this->db->getAdapter();
        $bindParams = [];
        $termYoungerSql = $attrYoungerSql = '';
        if (! empty($youngerAs)) {
            $bindParams[] = $youngerAs;
            //$bindParams[] = $youngerAs;
            $termYoungerSql = ' AND t.updatedAt >=?';
            $attrYoungerSql = ' AND ta.createdAt >=?';
        }

        /*return [[
            'termEntryId' => 'zxc111',
            'definition' => 'zxc211',
            'language' => 'zxc2',
            'termTbxId' => 'zxc2',
            'term' => 'zxc2',
            'termProposal' => 'zxc2',
            'processStatus' => 'zxc2',
            'attributeName' => 'zxc2',
            'attribute' => 'zxc2',
            'attributeProposal' => 'zxc2',
            'lastEditor' => 'zxc2',
            'lastEditedDate' => 'zxc2',
        ]];*/

        //Info: why collection ids is not in bindParams
        //binding multiple values to single param is not posible with $adapter->query . For more info see PDOStatement::execute
        $termSql = "SELECT
                    t.termEntryId as 'term-termEntryId',
                    t.definition as 'term-definition',
                    l.langName as 'term-language',
                    t.id as 'term-Id',
                    t.term as 'term-term',
                    t.processStatus as 'term-processStatus',
                    t.updatedBy as 'term-lastEditor',
                    t.updatedAt as 'term-lastEditedDate',
                    t.id as 'termproposal-id',
                    t.proposal as 'termproposal-term',
                    t.updatedAt as 'termproposal-lastEditedDate',
                    t.updatedBy as 'termproposal-lastEditor',
                    null as 'attribute-id',
                    null as 'attribute-name',
                    null as 'attribute-value',
                    null as 'attribute-lastEditedDate',
                    null as 'attribute-lastEditor',
                    null as 'attributeproposal-id',
                    null as 'attributeproposal-value',
                    null as 'attributeproposal-lastEditedDate',
                    null as 'attributeproposal-lastEditor'
                    FROM terms_term t
                    INNER JOIN LEK_languages l ON t.language = l.rfc5646
                WHERE " . $adapter->quoteInto('t.collectionId IN(?)', $collectionIds)
            . $termYoungerSql . "
                AND (t.proposal IS NOT NULL or t.processStatus = 'unprocessed')
                ORDER BY t.termEntryTbxId, t.term";

        $termResult = $adapter->query($termSql, $bindParams)->fetchAll();

        $attributeSql = "SELECT
                        ta.id as 'attribute-id',
                        ta.termId as 'term-Id',
                        ta.termEntryId as 'attribute-termEntryId',
                        ta.elementName as 'attribute-name',
                        IF(ISNULL(tah.id), ta.value, tah.value) as 'attribute-value',
                        ta.updatedAt as 'attribute-lastEditedDate',
                        ta.updatedBy as 'attribute-lastEditor',
                        ta.isCreatedLocally as 'attribute-isCreatedLocally',
                        l.langName as 'term-language',
                        tah.id as 'attributeproposal-id',
                        IF(ISNULL(tah.id), tah.value, ta.value) as 'attributeproposal-value',
                        tah.updatedAt as 'attributeproposal-lastEditedDate',
                        tah.updatedBy as 'attributeproposal-lastEditor',
                        t.termEntryId as 'term-termEntryId',
                        t.definition as 'term-definition',
                        t.id as 'term-Id',
                        t.term as 'term-term',
                        t.processStatus as 'term-processStatus',
                        t.updatedBy as 'term-lastEditor',
                        t.updatedAt as 'term-lastEditedDate',
                        t.id as 'termproposal-id',
                        t.proposal as 'termproposal-term',
                        t.updatedAt as 'termproposal-lastEditedDate',
                        t.updatedBy as 'termproposal-lastEditor'
                    FROM terms_attributes ta
                        LEFT OUTER JOIN terms_attributes_history tah ON (tah.attrId = ta.id AND tah.isCreatedLocally = 0)
                        LEFT OUTER JOIN terms_term t on ta.termId = t.id
                        LEFT OUTER JOIN LEK_languages l ON t.language = l.rfc5646
                    WHERE " . $adapter->quoteInto('ta.collectionId IN(?)', $collectionIds) .
            $attrYoungerSql . "
                    AND ta.isCreatedLocally = 1
                    ORDER BY ta.termEntryId, ta.termId";

        $attributeResult = $adapter->query($attributeSql, $bindParams)->fetchAll();

        //merge term proposals with term attributes and term entry attributes proposals
        $resultArray = array_merge($termResult, $attributeResult);

        // Collect distinct editor-user ids
        $userIdA = [];
        foreach ($resultArray as $idx => $item) {
            foreach ($item as $prop => $value) {
                if (preg_match('~-lastEditor$~', $prop)) {
                    if ($userId = $resultArray[$idx][$prop]) {
                        $userIdA[$userId] = true;
                    }
                }
            }
        }

        // Get user names by ids array
        $userNameA = $adapter->query(
            'SELECT `id`, CONCAT(`firstName`, " ", `surName`) 
            FROM `Zf_users` 
            WHERE `id` IN (' . (implode(',', array_keys($userIdA)) ?: 0) . ')'
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        // Spoof user ids with user names
        foreach ($resultArray as $idx => $item) {
            foreach ($item as $prop => $value) {
                if (preg_match('~-lastEditor$~', $prop) && $value) {
                    $resultArray[$idx][$prop] = $userNameA[$value];
                }
            }
        }

        if (empty($resultArray)) {
            return [];
        }

        return $this->groupProposalExportData($resultArray);
    }

    /***
     * Group the term and attribute proposal data for the export
     * @param array $data
     * @return array
     */
    protected function groupProposalExportData(array $data): array
    {
        usort($data, function ($a, $b) {
            $retval = $a['term-Id'] <=> $b['term-Id'];
            if ($retval == 0) {
                $retval = $b['term-term'] <=> $a['term-term'];
            }

            return $retval;
        });

        $returnResult = [];
        $tmpTerm = [];

        //clange cell color by value on the excel export callback
        $changeMyCollorTag = '<changemycolortag>';
        foreach ($data as $row) {
            $tmpTerm['termEntryId'] = $row['term-termEntryId'];
            //if it is empty it is termEntryAttribute
            if (empty($tmpTerm['termEntryId']) && ! empty($row['attribute-termEntryId'])) {
                $tmpTerm['termEntryId'] = $row['attribute-termEntryId'];
            }
            $tmpTerm['definition'] = $row['term-definition'];
            $tmpTerm['language'] = $row['term-language'];
            $tmpTerm['termId'] = $row['term-Id'];
            $tmpTerm['term'] = $changeMyCollorTag . $row['term-term'];
            $tmpTerm['termProposal'] = '';
            $tmpTerm['processStatus'] = $row['term-processStatus'];
            $tmpTerm['attributeName'] = $row['attribute-name'];
            $tmpTerm['attribute'] = $row['attribute-value'];
            $tmpTerm['attributeProposal'] = '';
            $tmpTerm['lastEditor'] = $changeMyCollorTag . $row['term-lastEditor'];
            $tmpTerm['lastEditedDate'] = $changeMyCollorTag . $row['term-lastEditedDate'];

            //if the proposal exist, set the change color and last editor for the proposal
            if (! empty($row['termproposal-term'])) {
                $tmpTerm['term'] = str_replace($changeMyCollorTag, '', $row['term-term']);
                $tmpTerm['termProposal'] = $changeMyCollorTag . $row['termproposal-term'];
                $tmpTerm['lastEditor'] = $changeMyCollorTag . $row['termproposal-lastEditor'];
                $tmpTerm['lastEditedDate'] = $changeMyCollorTag . $row['termproposal-lastEditedDate'];
            }

            if (isset($row['attribute-isCreatedLocally']) && $row['attribute-isCreatedLocally'] == 1) {
                $tmpTerm['attribute'] = $changeMyCollorTag . $row['attribute-value'];
                $tmpTerm['lastEditor'] = $changeMyCollorTag . $row['attribute-lastEditor'];
                $tmpTerm['lastEditedDate'] = $changeMyCollorTag . $row['attribute-lastEditedDate'];
                $tmpTerm['term'] = str_replace($changeMyCollorTag, '', $row['term-term'] ?? '');
                $tmpTerm['termProposal'] = str_replace($changeMyCollorTag, '', $row['termproposal-term'] ?? '');
            }

            //if the attribute proposal is set, set the change color and last editor for the attribute proposal
            if (! empty($row['attributeproposal-value'])) {
                $tmpTerm['term'] = str_replace($changeMyCollorTag, '', $row['term-term']);
                $tmpTerm['termProposal'] = str_replace($changeMyCollorTag, '', $row['termproposal-term']);
                $tmpTerm['attribute'] = $row['attribute-value'];
                $tmpTerm['attributeProposal'] = $changeMyCollorTag . $row['attributeproposal-value'];
                $tmpTerm['lastEditor'] = $changeMyCollorTag . $row['attributeproposal-lastEditor'];
                $tmpTerm['lastEditedDate'] = $changeMyCollorTag . $row['attributeproposal-lastEditedDate'];
            }
            $returnResult[] = $tmpTerm;
            $tmpTerm = [];
        }

        return $returnResult;
    }

    /***
     * Get all definitions in the given entryIds. The end results will be grouped by $entryIds as a key.
     * @param array $entryIds
     * @return array
     */
    public function getDeffinitionsByEntryIds(array $entryIds): array
    {
        if (empty($entryIds)) {
            return [];
        }
        $s = $this->db->select()
            ->where('termEntryId IN(?)', $entryIds);
        $return = $this->db->fetchAll($s)->toArray();

        if (empty($return)) {
            return [];
        }

        //group the definitions by termEntryId as a key
        $result = [];
        foreach ($return as $r) {
            if (! isset($result[$r['termEntryId']])) {
                $result[$r['termEntryId']] = [];
            }

            if (! in_array($r['definition'], $result[$r['termEntryId']]) && ! empty($r['definition'])) {
                $result[$r['termEntryId']][] = $r['definition'];
            }
        }

        return $result;
    }

    /**
     * Is the term a "preferred" term according to the given status?
     * @return boolean
     */
    public static function isPreferredTerm(string $termStatus): bool
    {
        $termStatusMap = self::getTermStatusMap();
        if (! array_key_exists($termStatus, $termStatusMap)) {
            return false;
        }

        return $termStatusMap[$termStatus] == 'preferred';
    }

    /**
     * Is the term a "permitted" term according to the given status?
     * @return boolean
     */
    public static function isPermittedTerm(string $termStatus): bool
    {
        $termStatusMap = self::getTermStatusMap();
        if (! array_key_exists($termStatus, $termStatusMap)) {
            return false;
        }

        return $termStatusMap[$termStatus] == 'permitted';
    }

    /**
     * Check if given $termStatus is considered as a match
     *
     * @return boolean
     */
    public static function isMatch(string $termStatus): bool
    {
        $termStatusMap = self::getTermStatusMap();
        if (! array_key_exists($termStatus, $termStatusMap)) {
            return false;
        }

        return ! in_array($termStatusMap[$termStatus], ['supersededTerm', 'deprecatedTerm']);
    }

    public function loadByMid(string $termId, array $collectionIds): ?Zend_Db_Table_Row_Abstract
    {
        $s = $this->db->select(false);
        $s->from($this->db);
        $s->where('collectionId IN(?)', $collectionIds)->where('termTbxId = ?', $termId);

        $this->row = $this->db->fetchRow($s);
        if (empty($this->row)) {
            $this->notFound('#select', $s->assemble());
        }

        return $this->row;
    }

    /**
     * returns all term mids from given segment content (allows and returns also duplicated mids)
     * @return array values are the mids of the terms in the string
     */
    public function getTermMidsFromSegment(string $seg): array
    {
        return array_map(function ($item) {
            return $item['termId'];
        }, $this->getTermInfosFromSegment($seg));
    }

    /**
     * Returns term-informations for a given group id
     *
     * @param array $languageIds 1-dim array with languageIds|default empty array;
     *          if passed only terms with the passed languageIds are returned
     * @return array  2-dim array (get term of first row like return[0]['term'])
     */
    public function getAllTermsOfGroup(array $collectionIds, string $termEntryTbxId, $languageIds = []): array
    {
        $db = $this->db;
        $s = $db->select()
            ->where('collectionId IN(?)', $collectionIds)
            ->where('termEntryTbxId = ?', $termEntryTbxId);
        if (! empty($languageIds)) {
            $s->where('languageId in (?)', $languageIds);
        }

        return $db->fetchAll($s)->toArray();
    }

    /**
     * Load [termTbxId => termEntryTbxId] pairs for terms having termTbxId given by $termTbxIds arg
     *
     * @return array
     */
    public function loadTermEntryTbxIdsByTermTbxIds(array $termTbxIds)
    {
        return $this->db->getAdapter()->query(
            'SELECT `termTbxId`, `termEntryTbxId` 
            FROM `terms_term` 
            WHERE `termTbxId` IN ("' . join('","', $termTbxIds) . '")'
        )->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Find first homonym for the given $term, stored under any of termEntries
     * identified by $termEntryTbxIds arg, and having language from $languageIds list
     */
    public function findHomonym(string $term, array $termEntryTbxIds, array $languageIds)
    {
        return $this->db->getAdapter()->query(
            'SELECT `termTbxId` 
            FROM `terms_term` 
            WHERE 1
              AND `termEntryTbxId` IN ("' . join('","', $termEntryTbxIds) . '") 
              AND `term` = ?
              AND `languageId` IN (' . join(',', $languageIds) . ')',
            $term
        )->fetchColumn();
    }

    /**
     * Load distinct terms themselves, by their tbx ids
     */
    public function loadDistinctByTbxIds(array $termTbxIds): array
    {
        return $this->db->getAdapter()->query(
            'SELECT DISTINCT term 
            FROM `terms_term` 
            WHERE `termTbxId` IN ("' . join('","', $termTbxIds) . '")'
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * returns mids and term flags (css classes) found in a string
     * @return array 2D Array, first level are found terms, second level has key mid and key classes
     */
    public function getTermInfosFromSegment(string $seg): array
    {
        return $this->tagHelper->getInfos($seg);
    }

    /***
     * Remove old term proposals by given date.
     *
     * @param array $collectionIds
     * @param string $olderThan
     * @return boolean
     */
    public function removeProposalsOlderThan(array $collectionIds, string $olderThan)
    {
        // Delete entries having processStatus=unprocessed
        $rowsCount = $this->db->delete([
            'updatedAt < ?' => $olderThan,
            'collectionId in (?)' => $collectionIds,
            'processStatus = ?' => self::PROCESS_STATUS_UNPROCESSED,
        ]);

        // Setup `proposal` column to be empty string todo: history tables to be involved ?
        return ($this->db->update([
            'proposal' => '',
        ], [
            'updatedAt < ?' => $olderThan,
            'collectionId in (?)' => $collectionIds,
            'LENGTH(`proposal`) > ?' => 0,
        ]) + $rowsCount) > 0;
        /*return ($this->db->delete([
            'created < ?' => $olderThan,
            'collectionId in (?)' => $collectionIds,
        ]) + $rowsCount) > 0;*/
    }

    /**
     * Get data for tbx-export
     *
     * @param array $ids Comma-separated list of ids, or array of ids
     * @param string $idsProp Name of ids-prop, 'termEntryId' by default
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getExportData($ids, $idsProp = 'termEntryId')
    {
        // Build clause for termIds
        $where = $this->db->getAdapter()->quoteInto('`' . $idsProp . '` IN (?)', editor_Utils::ar($ids));

        // Fetch and return terms, grouped by termEntryId and language props
        return array_group_by(
            $this->db->getAdapter()->query(
                'SELECT `termEntryId`, `id`, `term`, `language`, `termTbxId`, `processStatus`, `termTbxId` 
                FROM `terms_term`
                WHERE ' . $where
            )->fetchAll(),
            'termEntryId',
            'language'
        );
    }

    /**
     * Check whether current term is the last one having it's termEntryId or / and languageId
     *
     * @return bool|string
     * @throws Zend_Db_Statement_Exception
     */
    public function isLast()
    {
        // Get data, that will help to detect whether this term is the last in it's termEntry or language
        $isLast_data = $this->db->getAdapter()->query(
            'SELECT `language`, COUNT(`id`) AS `termQty` 
            FROM `terms_term` 
            WHERE `termEntryId` = ? 
            GROUP BY `language` 
            ORDER BY `language` = ? DESC 
            LIMIT 2',
            [$this->getTermEntryId(), $this->getLanguage()]
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        // Return false, if not last, or 'language' or 'entry' if is last within language or entry
        return $isLast_data[$this->getLanguage()] < 2
            ? (count($isLast_data) < 2 ? 'entry' : 'language')
            : false;
    }

    /**
     * Delete language-level things:
     * 1. `terms_images`-records
     * 2. image-files
     * 3.`terms_transacgrp`-records
     * 4.`terms_attributes`-records
     */
    public function preDeleteIfLast4Language()
    {
        // Delete `terms_images`-records and image-files found by those records
        editor_Models_Terminology_Models_AttributeModel
            ::deleteImages((int) $this->getCollectionId(), (int) $this->getTermEntryId(), $this->getLanguage());

        // Delete `terms_transacgrp`- and `terms_attributes`- records for language-level and term-level
        $where = '`termEntryId` = ? AND `language` = ? AND `termId` IS NULL';
        $bind = [$this->getTermEntryId(), $this->getLanguage()];
        $this->db->getAdapter()->query('DELETE FROM `terms_transacgrp` WHERE ' . $where, $bind);
        $this->db->getAdapter()->query('DELETE FROM `terms_attributes` WHERE ' . $where, $bind);
    }

    /**
     * Delete termEntry- levels things:
     * 1.`terms_images`-records (both on termEntry- and language- levels)
     * 2.`terms_term_entry`-record. Note: current terms_term-record will be deleted by ON DELETE CASCADE
     * @throws Zend_Db_Statement_Exception
     */
    public function preDeleteIfLast4Entry()
    {
        // Delete `terms_images`-records and image-files found by those records
        editor_Models_Terminology_Models_AttributeModel
            ::deleteImages((int) $this->getCollectionId(), (int) $this->getTermEntryId());

        /** @var editor_Models_Terminology_Models_TermEntryModel $termEntry */
        $termEntry = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        $termEntry->load((int) $this->getTermEntryId());
        $termEntry->delete();
    }

    /**
     * Detach current term proposal into new term, with attributes replication,
     * and return newly created term data in a format, compatible with TermPortal siblings-panel
     */
    public function detachProposal(
        string &$processStatus,
        int $userId,
        string $userName,
        string $userGuid,
        int $processStatusAttrId = null,
    ): array {
        // Prepare the data to be used for init a new term based on current term's proposal
        $init = $this->toArray();
        unset($init['id'], $init['proposal']);
        $init['processStatus'] = $processStatus;
        $init['term'] = $this->getProposal();
        $init['guid'] = ZfExtended_Utils::uuid();
        $init['termTbxId'] = 'id' . ZfExtended_Utils::uuid();
        $init['updatedBy'] = $userId;

        // Move existing term's proposal to the new term, with attributes replicated
        $p = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        $p->init($init);
        $p->insert([
            'userName' => $userName,
            'userGuid' => $userGuid,
            'copyAttrsFromTermId' => $this->getId(),
        ]);

        // If processStatus is 'rejected', it means that proposal for existing term was rejected,
        // so that we spoof $params['value'] with processStatus of existing term,
        // as it will be flushed within json response
        if ($processStatus == 'rejected') {
            $processStatus = $this->getProcessStatus();
        }

        // Else if existing term's proposal is accepted, e.g. is 'provisionallyProcessed'
        // or 'finalized' - then, for existing term, setup `processStatus` = 'rejected'
        // Also, spoof $params['value'] for it to be 'rejected', as it will be flushed within json response
        else {
            $this->setProcessStatus($processStatus = 'rejected');
        }

        // Remove proposal from existing term
        $this->setProposal('');
        $this->setUpdatedBy($userId);
        $this->update([
            'updateProcessStatusAttr' => $processStatusAttrId ?? true,
        ]);

        // Return new term data in format, compatible with TermPortal's siblings-panel store
        return [
            'id' => $p->getId(),
            'tbx' => $p->getTermTbxId(),
            'languageId' => $p->getLanguageId(),
            'language' => $p->getLanguage(),
            'term' => $p->getTerm(),
            'proposal' => $p->getProposal(),
            'status' => $p->getStatus(),
            'processStatus' => $p->getProcessStatus(),
            'termEntryTbxId' => $p->getTermEntryTbxId(),

            // For store's new record, images array will be copied from source record
            'images' => [],
        ];
    }

    /**
     * Do process status change, with (if need)
     *  - detaching the proposal
     *  - creating/updating normativeAuthorization-attr
     *  - updating collections stats
     *
     * @return array
     */
    public function doProcessStatusChange(
        string $processStatus,
        string|int $userId,
        string $userName,
        string $userGuid,
        editor_Models_Terminology_Models_AttributeModel $processStatusAttr = null,
    ) {
        // Return value
        $data = [];

        // If term, that we're going to change processStatus for - has a proposal
        if ($this->getProposal()) {
            // If new processStatus is 'rejected', 'provisionallyProcessed' or 'finalized'
            // - cut proposal from existing into separate term
            if ($processStatus != 'unprocessed') {
                // Make sure newly created term's data to be flushed within json response,
                // so it'll be possible to add record into siblings-panel grid's store
                $data['inserted'] = $this->detachProposal(
                    $processStatus,
                    $userId,
                    $userName,
                    $userGuid,
                    (int) $processStatusAttr->getId()
                );

                // If $this->normativeAuthorization was set by the above $this->detachProposal() -> $this->update() call
                // it means that term processStatus was changed to 'rejected', so 'normativeAuthorization'
                // attribute was set to 'deprecatedTerm', and in case if there was no such attribute previously
                // we need to pass attr info to client side for new attr-field to be added into the attr-panel
                if ($this->normativeAuthorization ?? 0) {
                    $data['normativeAuthorization'] = $this->_normativeAuthorization($userName);
                }

                // Update collection stats
                ZfExtended_Factory
                    ::get('editor_Models_TermCollection_TermCollection')
                        ->updateStats((int) $this->getCollectionId(), [
                            'termEntry' => 0,
                            'term' => 1,
                        ]);
            }
            // Else
        } else {
            // Update `processStatus` on `terms_term`-record
            $this->setProcessStatus($processStatus);
            $this->setUpdatedBy($userId);
            $this->update([
                'updateProcessStatusAttr' => $processStatusAttr->getId(),
            ]);

            // If $this->normativeAuthorization was set by the above $this->update() call
            // it means that term processStatus was changed to 'rejected', so 'normativeAuthorization'
            // attribute was set to 'deprecatedTerm', and in case if there was no such attribute previously
            // we need to pass attr info to client side for attr-field to be added into the attr-panel
            if ($this->normativeAuthorization ?? 0) {
                $data['normativeAuthorization'] = $this->_normativeAuthorization($userName);

                // Increment collection stats 'attribute'-prop only
                ZfExtended_Factory
                    ::get('editor_Models_TermCollection_TermCollection')
                        ->updateStats((int) $this->getCollectionId(), [
                            'termEntry' => 0,
                            'term' => 0,
                            'attribute' => 1,
                        ]);
            }
        }

        // Append processStatus-attr info into return value
        $data['processStatus'] = [
            'id' => $processStatusAttr->getId(),
            'value' => $processStatus,
            'type' => 'processStatus',
            'dataTypeId' => $processStatusAttr->getDataTypeId(),
            'created' => $userName . ', ' . date('d.m.Y H:i:s', strtotime($processStatusAttr->getCreatedAt())),
            'updated' => $userName . ', ' . date('d.m.Y H:i:s'),
        ];

        // Return data
        return $data;
    }

    /**
     * @return array|void
     */
    protected function _normativeAuthorization($userName)
    {
        // If no 'normativeAuthorization' prop is set - return
        if (! $na = $this->normativeAuthorization ?? 0) {
            return;
        }

        // Else return attribute-info compatible with TermPortal {xtype: 'attrpanel'}
        return [
            'id' => $na['id'],
            'value' => $na['value'],
            'type' => $na['type'],
            'dataTypeId' => $na['dataTypeId'],
            'created' => $userName . ', ' . date('d.m.Y H:i:s', strtotime($na['createdAt'])),
            'updated' => $userName . ', ' . date('d.m.Y H:i:s', strtotime($na['updatedAt'])),
        ];
    }

    /**
     * Fetch attributes and transacgrps data for TermPortal right panels,
     * and image/figure-attributes for center panel
     */
    public function terminfo(): array
    {
        // Setup different `language`-column clauses to be used
        // for fetching attributes-data and fetching transacgrp-data
        // because we need to fetch image-attributes not only for right-panels
        // but also for center panel
        $cond = [
            'transacgrp' => '`language` = :language',
            'attribute' => '(`language` = :language OR (`type` = "figure" AND NOT ISNULL(`language`)))',
        ];

        // Setup definition for level-column
        $levelColumnToBeGroupedBy = '
          IF ((`termEntryId` = :termEntryId AND ISNULL(`language`) AND ISNULL(`termId`)), "entry", 
            IF ((`termEntryId` = :termEntryId AND %s AND ISNULL(`termId`)), "language", 
              IF (`termId` = :termId, "term", "other"))) AS `level`';

        // Setup WHERE clauses for entry-, language- and term-level attributes/transacgrp
        $levelWHERE = implode(' AND ', [
            '`termEntryId` = :termEntryId',
            '(ISNULL(`language`) OR %s)',
            '(ISNULL(`termId`)   OR `termId` = :termId)',
        ]);

        // Params for binding to the attribute/transacgrp-fetching query
        $bind = [
            ':termEntryId' => $this->getTermEntryId(),
            ':language' => $this->getLanguage(),
            ':termId' => $this->getId(),
        ];

        // Get attributes grouped by level
        $attributeA = ZfExtended_Factory
            ::get('editor_Models_Terminology_Models_AttributeModel')
                ->loadGroupedByLevel(
                    sprintf($levelColumnToBeGroupedBy, $cond['attribute']),
                    sprintf($levelWHERE, $cond['attribute']),
                    $bind
                );

        // Get `transacgrp` data grouped by level
        $transacgrpA = ZfExtended_Factory
            ::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->loadGroupedByLevel(
                    sprintf($levelColumnToBeGroupedBy, $cond['transacgrp']),
                    sprintf($levelWHERE, $cond['transacgrp']),
                    $bind
                );

        // Convert transacgrp-data
        foreach (['entry', 'language', 'term'] as $level) {
            $transacgrpA[$level]
                = array_column($transacgrpA[$level] ?? [], 'whowhen', 'transac');
        }

        // Return attributes and transacgrps
        return [$attributeA, $transacgrpA];
    }

    /**
     * Fetch attributes and transacgrps data for TermPortal right panels
     */
    public function siblinginfo(): array
    {
        // Setup different entry-level clauses to be used
        // for fetching attributes-data and fetching transacgrp-data
        // because we need to fetch ref-attributes not only for
        // language- and term- levels, but for entry-level as well
        $cond = [
            'attribute' => '`termEntryId` = :termEntryId AND ISNULL(`language`) AND ISNULL(`termId`) AND `elementName` = "ref"',
            'transacgrp' => 'FALSE',
        ];

        // Setup definition for level-column
        $levelColumnToBeGroupedBy = '
          IF ((`termEntryId` = :termEntryId AND ISNULL(`language`) AND ISNULL(`termId`)), "entry", 
            IF ((`termEntryId` = :termEntryId AND `language` = :language AND ISNULL(`termId`)), "language", 
              IF (`termId` = :termId, "term", "other"))) AS `level`';

        // Setup WHERE clauses for entry-, language- and term-level attributes
        $levelWHERE = '`termEntryId` = :termEntryId AND ((' . implode(') OR (', [
            'entry' => '%s',
            'language' => '`termEntryId` = :termEntryId AND `language` = :language AND ISNULL(`termId`)',
            'term' => '`termId` = :termId',
        ]) . '))';

        // Params for binding to the attribute/transacgrp-fetching query
        $bind = [
            ':termEntryId' => $this->getTermEntryId(),
            ':language' => $this->getLanguage(),
            ':termId' => $this->getId(),
        ];

        // Get attributes grouped by level
        $attributeA = ZfExtended_Factory
            ::get('editor_Models_Terminology_Models_AttributeModel')
                ->loadGroupedByLevel(
                    $levelColumnToBeGroupedBy,
                    sprintf($levelWHERE, $cond['attribute']),
                    $bind
                );

        // Get transacgrps grouped by level
        $transacgrpA = ZfExtended_Factory
            ::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->loadGroupedByLevel(
                    $levelColumnToBeGroupedBy,
                    sprintf($levelWHERE, $cond['transacgrp']),
                    $bind
                );

        // Convert transacgrp-data
        foreach (['entry', 'language', 'term'] as $level) {
            $transacgrpA[$level]
                = array_column($transacgrpA[$level] ?? [], 'whowhen', 'transac');
        }

        // Return attributes and transacgrps
        return [$attributeA, $transacgrpA];
    }

    /**
     * Get client name
     *
     * @return string
     * @throws Zend_Db_Statement_Exception
     */
    public function getClientName()
    {
        // Get `clientId` of a term collection
        $clientId = $this->db->getAdapter()->query(
            'SELECT `customerId` FROM `LEK_languageresources_customerassoc` WHERE `languageResourceId` = ?',
            $this->getCollectionId()
        )->fetchColumn();

        // Use that `clientId` to get the client name
        return $this->db->getAdapter()->query(
            'SELECT `name` FROM `LEK_customer` WHERE `id` = ?',
            $clientId
        )->fetchColumn();
    }

    /**
     * Get all terms having same termEntryId as current term has, orderded:
     *   by current search language(s), and then
     *   by termportal languages
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function getSiblingsOrderedBy(string $searchLangIds = '', string $termPortalLangRfcs = ''): array
    {
        // Bind params
        $bind = [
            ':termEntryId' => $this->getTermEntryId(),
        ];

        // Order clauses
        $order = [];

        // If we have current search term languages ids given
        if ($searchLangIds) {
            // Make sure this languages terms will be at the top
            $order[] = 'FIND_IN_SET(`languageId`, :searchLangIds) DESC';

            // Append binding
            $bind[':searchLangIds'] = join(',', array_reverse(explode(',', $searchLangIds)));
        }

        // If we have termportal languages given
        if ($termPortalLangRfcs) {
            // Setup secondary order clause
            $order[] = 'FIND_IN_SET(`language`, :termPortalLangRfcs)';

            // Append binding
            $bind[':termPortalLangRfcs'] = $termPortalLangRfcs;
        }

        // Join ORDER clauses
        $order = join(', ', $order) ?: '`id`';

        // Get raw siblings
        $siblings = $this->db->getAdapter()->query(
            $sql = '
            SELECT 
              `id`, 
              `id`, 
              `termTbxId` AS `tbx`, 
              `languageId`, 
              LOWER(`language`) AS `language`, 
              `term`,
              `proposal`,
              `collectionId`,
              `status`,
              `processStatus`,
              `termEntryId`,
              `termEntryTbxId`,
              `updatedBy` as `createdBy`
            FROM `terms_term` 
            WHERE `termEntryId` = :termEntryId
            ORDER BY ' . $order,
            $bind
        )->fetchAll(PDO::FETCH_UNIQUE);

        // Get users who created for each term, from history
        $createdBy = $this->db->getAdapter()->query(
            'SELECT `termId`, MIN(CONCAT(`updatedAt`, "--", `updatedBy`)) 
            FROM `terms_term_history` 
            WHERE `termId` IN (' . join(',', array_keys($siblings)) . ') AND NOT ISNULL(`updatedBy`) 
            GROUP BY `termId`'
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        // For each term having history - spoof value of createdBy with the value found in history
        foreach ($createdBy as $termId => $info) {
            $siblings[$termId]['createdBy'] = explode('--', $info)[1];
        }

        // Return siblings having 'createdBy'-prop, containing either
        // value of `updateBy` prop of a first `terms_term_history`-record, if exists,
        // or value of `terms_term`.`updatedBy` prop
        return $siblings;
    }

    /**
     * Get id of a user who created this term, either from the oldest history record (if exists), or from current record
     *
     * @return string
     * @throws Zend_Db_Statement_Exception
     */
    public function getCreatedBy()
    {
        // Try to find oldest value of `updatedBy`-prop in current term's history
        $oldest_updatedBy = $this->db->getAdapter()->query(
            'SELECT `updatedBy` 
            FROM `terms_term_history` 
            WHERE `termId` = ? 
            ORDER BY `updatedAt` ASC 
            LIMIT 1',
            $this->getId()
        )->fetchColumn();

        // If found - return it, else return current value `updatedBy`-prop
        return $oldest_updatedBy ?: $this->getUpdatedBy();
    }

    /**
     * Get ids of all terms having given $termEntryId
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getIdsByTermEntryId($termEntryId)
    {
        return $this->db->getAdapter()->query(
            'SELECT `id` 
            FROM `terms_term`
            WHERE `termEntryId` = ?',
            $termEntryId
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get distinct languages of all terms having given $termEntryId
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getLanguagesByTermEntryId($termEntryId, $certain = false)
    {
        // Query param binding
        $bind = [
            ':termEntryId' => $termEntryId,
        ];

        // If $certain arg is given
        if ($certain) {
            // Append binding
            $bind[':certain'] = $certain;

            // Append WHERE clause
            $certain = ' AND FIND_IN_SET(`language`, :certain)';
        }

        // Return distinc languages
        return $this->db->getAdapter()->query(
            'SELECT DISTINCT `language` 
            FROM `terms_term`
            WHERE `termEntryId` = :termEntryId' . $certain,
            $bind
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Exclude values from $termIds, having $language-translations,
     * so the only ones which have no translation for given $language will be kept and returned
     *
     * @param string $languageId
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function havingNoTranslation(array $termIds, int $languageId)
    {
        // Build termIds-where clause
        $termIds = $this->db->getAdapter()->quoteInto('`t`.`id` IN (?)', $termIds);

        // Run query and fetch results
        return $this->db->getAdapter()->query(
            'SELECT `t`.`collectionId`, `t`.`id` 
            FROM 
              `terms_term` `t` 
              LEFT JOIN `terms_term` AS `t2` ON (
                `t`.`termEntryId` = `t2`.`termEntryId` AND `t2`.`languageId` = ?
              )
            WHERE ' . $termIds . ' AND ISNULL(`t2`.`id`)',
            $languageId
        )->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);
    }

    /**
     * Get distinct values for columns, specified by $cols arg, for terms, identified by $termIds arg
     *
     * @param string $cols Comma-separated column names
     */
    public function distinctColsForTermIds(string $cols, array $termIds)
    {
        // Build termIds-where clause
        $termIds = $this->db->getAdapter()->quoteInto('`id` IN (?)', $termIds);

        // Fetch data
        $data = $this->db->getAdapter()->query('SELECT ' . $cols . ' FROM `terms_term` WHERE ' . $termIds)->fetchAll();

        // Collect distinct values for given $cols
        foreach (explode(',', $cols) as $col) {
            $distinct[$col] = array_unique(array_column($data, $col));
        }

        // Return collected
        return $distinct;
    }

    /**
     * Remove items from $termIdByAttrIdA, for which no proposals were detected,
     * so only the ones for which they were detected would be kept and returned
     */
    public function detectProposals(array $termIdByAttrIdA)
    {
        // If first arg is empty array - return empty array
        if (! $termIdByAttrIdA) {
            return [];
        }

        // Build where clause for `id`-column
        $idWHERE = $this->db->getAdapter()->quoteInto('`id` IN (?)', array_unique($termIdByAttrIdA));

        // Get termIds (as keys), for which proposals are detected
        $detected = $this->db->getAdapter()->query(
            'SELECT `id`, 1 
            FROM `terms_term` 
            WHERE 1
              AND ' . $idWHERE . '
              AND (`processStatus` = "unprocessed" OR `proposal` != "")'
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        // Unset those items from $termIdByAttrIdA for which no proposals were detected
        foreach ($termIdByAttrIdA as $attrId => $termId) {
            if (! isset($detected[$termId])) {
                unset($termIdByAttrIdA[$attrId]);
            }
        }

        // Return
        return $termIdByAttrIdA;
    }

    /**
     * Get the array of allowed values for processStatus-attribute according to current user's rights
     *
     * @param string $current Current status that we should base on
     * @param bool $includeCurrent Include current status to the array of returned. This is useful when
     *                             we need to prepare data for processStatus-combobox store
     */
    public static function getAllowedProcessStatuses(
        string $current = 'unprocessed',
        bool $includeCurrent = true,
    ): array {
        // Define which old values can be changed to which new values
        $allow = false;
        $allowByRight = [
            'review' => [
                'unprocessed' => [
                    'provisionallyProcessed' => true,
                    'rejected' => true,
                ],
            ],
            'finalize' => [
                'provisionallyProcessed' => [
                    'finalized' => true,
                    'rejected' => true,
                ],
            ],
            'propose' => [],
            'anyStatus' => true, // any change allowed
        ];

        // Get auth
        $auth = ZfExtended_Authentication::getInstance();

        // Merge allowed
        foreach ($allowByRight as $right => $info) {
            if ($auth->isUserAllowed('editor_term', $right)) {
                $allow = is_bool($info) || is_bool($allow)
                    ? $info
                    : $info + $allow;
            }
        }

        // Prepare list of allowed values
        $allowed = [];

        // Include current status to the list
        if ($includeCurrent) {
            $allowed[$current] = $current;
        }

        // Append other allowed values
        foreach (explode(',', 'unprocessed,provisionallyProcessed,finalized,rejected') as $possible) {
            if ($allow === true || (is_array($allow[$current] ?? 0) && ($allow[$current][$possible] ?? 0))) {
                $allowed[$possible] = $possible;
            }
        }

        // Return as ordinary array rather than associative
        return array_values($allowed);
    }

    /**
     * Log deleted terms in the language resources log
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    private function logDeletedTerm(array $termData = [])
    {
        if (empty($termData) || empty($termData['collectionId'])) {
            return;
        }

        $collection = ZfExtended_Factory::get(editor_Models_TermCollection_TermCollection::class);
        $collection->load($termData['collectionId']);

        $logger = Zend_Registry::get('logger')->cloneMe('editor.languageresource');

        $logger->info('E1592', 'Deleted term: {name}', [
            'name' => $termData['term'] ?? '',
            'data' => $termData,
            'languageResource' => $collection,
        ]);
    }
}
