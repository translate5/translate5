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

class editor_Models_Terminology_Models_AttributeDataType extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_AttributeDatatype';

    protected $validatorInstanceClass = 'editor_Models_Validator_Term_AttributeDatatype';

    /**
     * Load the label with given name,type and level, if it does not exist, the label will be created
     */
    public function loadOrCreate(string $labelName, string $labelType = '', array $level = [
        editor_Models_Terminology_TbxObjects_Attribute::ATTRIBUTE_LEVEL_ENTRY,
        editor_Models_Terminology_TbxObjects_Attribute::ATTRIBUTE_LEVEL_LANGUAGE,
        editor_Models_Terminology_TbxObjects_Attribute::ATTRIBUTE_LEVEL_TERM])
    {
        $s = $this->db->select()
            ->from($this->db)
            ->where('label = ?', $labelName);
        if (ZfExtended_Utils::emptyString($labelType)) {
            $s->where('(type = "" or type is null)');
        } else {
            $s->where('type = ?', $labelType);
        }
        $levelSql = [];
        // for each level, add like search
        foreach ($level as $l) {
            $levelSql[] = 'level LIKE "%' . $l . '%"';
        }
        $s->where(implode(' OR ', $levelSql));
        $row = $this->db->fetchRow($s);
        if ($row) {
            $this->row = $row;

            return;
        }
        $this->init();
        $this->setType(ZfExtended_Utils::emptyString($labelType) ? null : $labelType);
        $this->setLabel($labelName);
        $this->setDataType(editor_Models_Terminology_TbxObjects_Attribute::ATTRIBUTE_DEFAULT_DATATYPE);
        $this->setLevel(implode(',', $level));
        $this->save();
    }

    /***
     * Load all data type attributes translated for the current user locale.
     *
     * @param string $locale
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function loadAllWithTranslations(string $locale): array
    {
        return $this->db->getAdapter()->query('
            SELECT
              `id`,
              IF (
                  JSON_UNQUOTE(JSON_EXTRACT(`l10nCustom`, :lang)) != "",
                  JSON_UNQUOTE(JSON_EXTRACT(`l10nCustom`, :lang)),
                  IF (
                    JSON_UNQUOTE(JSON_EXTRACT(`l10nSystem`, :lang)) != "",
                    JSON_UNQUOTE(JSON_EXTRACT(`l10nSystem`, :lang)),
                    `type`
                  )
              ) AS `title`
            FROM `terms_attributes_datatype`
        ', [
            ':lang' => '$.' . $locale,
        ])->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Get comma-separated list of ids of tbx-basic attributes
     *
     * @return string
     */
    public function getTbxBasicIds()
    {
        return implode(',', $this->db->getAdapter()->query('
            SELECT `id` FROM `terms_attributes_datatype` WHERE `isTbxBasic` = 1
        ')->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * provides a list of term level label and type tupels (as one string separated with #) and returns their datatype IDs
     * example "termNote#footype", "note#" returns for example the IDs for label termNote with type footype on level term and the id for label note with type null on level term
     * Only type may be empty (null)!
     */
    public function getIdsForTerms(array $labelTypeList): array
    {
        //we load all datatypes for the given labels / elementNames and filter them then on PHP level
        $s = $this->db->select()
            ->from($this->db, ['id', 'label', 'type']);

        foreach ($labelTypeList as $key) {
            $parts = explode('#', $key);
            $s->orWhere('(label = ?', $parts[0]);
            if (empty($parts[1])) {
                $s->where('type is null');
            } else {
                $s->where('type = ?', $parts[1]);
            }
            $s->where('FIND_IN_SET( "term" ,level)>0 )');
        }

        $dbResult = $this->db->fetchAll($s)->toArray();
        $result = [];
        foreach ($dbResult as $row) {
            $result[$row['label'] . '#' . $row['type']] = $row['id'];
        }

        return $result;
    }

    /**
     * Get dataTypeId by $type
     *
     * @return string
     * @throws Zend_Db_Statement_Exception
     */
    public function getIdByType(string $type)
    {
        return $this->db->getAdapter()->query(
            'SELECT `id` FROM `terms_attributes_datatype` WHERE `type` = ?',
            $type
        )->fetchColumn();
    }

    /**
     * Get dataTypeId by for note-attribute
     *
     * @return string
     * @throws Zend_Db_Statement_Exception
     */
    public function getNoteId()
    {
        return $this->db->getAdapter()->query(
            'SELECT `id` FROM `terms_attributes_datatype` WHERE `label` = "note" AND `type` IS NULL'
        )->fetchColumn();
    }

    /**
     * Get array of terms_attributes.dataTypeId => terms_attributes.id pairs for a level, identified by $termEntryId,
     * $language and $termId args. Currently this is used to prevent creating more than 1 attributes having same dataTypeId
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getAlreadyExistingFor(int $termEntryId, string $language = null, int $termId = null)
    {
        // Detect level
        if ($termEntryId && $language && $termId) {
            $level = 'term';
        } elseif ($termEntryId && $language) {
            $level = 'language';
        } else {
            $level = 'entry';
        }

        // Setup WHERE clauses for entry-, language- and term-level attributes
        $levelWHERE = [
            'entry' => '`termEntryId` = :termEntryId AND ISNULL(`language`) AND ISNULL(`termId`)',
            'language' => '`termEntryId` = :termEntryId AND `language` = :language AND ISNULL(`termId`)',
            'term' => '`termId` = :termId',
        ];

        // Params for binding to the existing attribute-fetching query
        $bind = [
            'entry' => [
                ':termEntryId' => $termEntryId,
            ],
            'language' => [
                ':termEntryId' => $termEntryId,
                ':language' => $language,
            ],
            'term' => [
                ':termId' => $termId,
            ],
        ];

        // Return existing attributes datatype ids
        return $this->db->getAdapter()->query('
            SELECT `dataTypeId`, `id` 
            FROM `terms_attributes`
            WHERE ' . $levelWHERE[$level], $bind[$level])->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Get array of collection ids that are enabled to create attribute with current data type in
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getEnabledCollectionIds()
    {
        return $this->db->getAdapter()->query('
            SELECT `collectionId` 
            FROM `terms_collection_attribute_datatype` 
            WHERE `dataTypeId` = ? AND `enabled` = "1"', $this->getId())->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get array of attribute datatypes in a format, compatible with TermPortal client app
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function getLocalized(string $locale, array $collectionIds): array
    {
        if (empty($collectionIds)) {
            return [];
        }

        // Fetch from database
        $attributes = $this->db->getAdapter()->query('
            SELECT 
              `a`.`id`,
              `a`.`id`,
              IF (
                  JSON_UNQUOTE(JSON_EXTRACT(`l10nCustom`, :lang)) != "",
                  JSON_UNQUOTE(JSON_EXTRACT(`l10nCustom`, :lang)), 
                  IF (
                    JSON_UNQUOTE(JSON_EXTRACT(`l10nSystem`, :lang)) != "", 
                    JSON_UNQUOTE(JSON_EXTRACT(`l10nSystem`, :lang)), 
                    `type`
                  )
              ) AS `title`,
              IF (JSON_UNQUOTE(JSON_EXTRACT(`l10nSystem`, :lang)) != "",
                  JSON_UNQUOTE(JSON_EXTRACT(`l10nSystem`, :lang)),
                  `type`) AS `system`,     
              CONCAT("attr-", `a`.`id`) AS `alias`,
              IF(`a`.`label` = "note", `a`.`label`, `a`.`dataType`) AS `dataType`,
              `a`.`picklistValues`,
              `a`.`level`,
              `a`.`isTbxBasic`,
              `a`.`type`
            FROM `terms_attributes_datatype` `a` 
            GROUP BY `a`.`id`
            ORDER BY `title`
        ', [
            ':lang' => '$.' . $locale,
        ])->fetchAll(PDO::FETCH_UNIQUE);

        // Make sure isTbxBasic to be integer in javascript
        array_walk($attributes, fn (&$a) => $a['isTbxBasic'] += 0);

        // For each of those props
        foreach (['enabled', 'exists'] as $column) {
            // Get array of [dataTypeId => termcollectionIds] pairs having $column-prop = 1
            $colections = $this->db->getAdapter()->query('
                SELECT `dataTypeId`, GROUP_CONCAT(`collectionId`)
                FROM `terms_collection_attribute_datatype` 
                WHERE `' . $column . '` = "1" AND `collectionId` IN (' . join(',', $collectionIds) . ')
                GROUP BY `dataTypeId`
            ')->fetchAll(PDO::FETCH_KEY_PAIR);

            // Apply to $attributes
            foreach (array_keys($attributes) as $dataTypeId) {
                $attributes[$dataTypeId][$column . 'In'] = $colections[$dataTypeId] ?? '';
            }
        }

        // Foreach attribute datatype - check:
        /*foreach ($attributes as $dataTypeId => $dataType) {

            // If it's not a TBX Basic datatype, and it's not enabled for any of accessible term collections
            if (!$dataType['isTbxBasic'] && !$dataType['enabledIn']) {

                // Unset it
                unset($attributes[$dataTypeId]);
            }
        }*/

        // Return attributes
        return $attributes;
    }

    /**
     * @return mixed
     * @throws Zend_Db_Statement_Exception
     */
    public function getUsageForLevelsByCollectionId($collectionId, string $locale = 'en')
    {
        // Get localized attrs
        $localized = $this->getLocalized($locale, [$collectionId]);

        // Get dataTypeIds of attrs, that:
        // 1.can appear multiple times at the same level
        // 2.require double-column for being excel-exported
        $double = $multi = [];
        foreach (['crossReference', 'figure', 'xGraphic', 'externalCrossReference'] as $type) {
            foreach ($localized as $dataTypeId => $dataType) {
                if ($dataType['type'] == $type) {
                    $multi[$dataTypeId] = $dataType['type'];
                    if (preg_match('~^(xGraphic|externalCrossReference)$~', $dataType['type'])) {
                        $double[$dataTypeId] = $dataType['type'];
                    }
                }
            }
        }

        // Get comma separated list of dataTypeIds in the proper order
        $list = join(',', array_keys($multi));

        // Get entry-level dataTypeIds usages
        $entry = $this->db->getAdapter()->query('
            SELECT DISTINCT `dataTypeId`
            FROM `terms_attributes`
            WHERE 1
              AND `collectionId` = ?
              AND `termEntryId` IS NOT NULL 
              AND `language` IS NULL
              AND `termId` IS NULL
            ORDER BY FIND_IN_SET(`dataTypeId`, ?) ASC 
        ', [$collectionId, $list])->fetchAll(PDO::FETCH_COLUMN);

        // Get language-level dataTypeIds usages
        $language = $this->db->getAdapter()->query('
            SELECT DISTINCT `dataTypeId`
            FROM `terms_attributes`
            WHERE 1
              AND `collectionId` = ? 
              AND `termEntryId` IS NOT NULL 
              AND `language` IS NOT NULL
              AND `termId` IS NULL
            ORDER BY FIND_IN_SET(`dataTypeId`, ?) ASC 
        ', [$collectionId, $list])->fetchAll(PDO::FETCH_COLUMN);

        // Get term-level dataTypeIds usages
        $term = $this->db->getAdapter()->query('
            SELECT DISTINCT `dataTypeId`
            FROM `terms_attributes`
            WHERE 1
              AND `collectionId` = ? 
              AND `termEntryId` IS NOT NULL 
              AND `language` IS NOT NULL
              AND `termId` IS NOT NULL
            ORDER BY FIND_IN_SET(`dataTypeId`, ?) ASC 
        ', [$collectionId, $list])->fetchAll(PDO::FETCH_COLUMN);

        // Collect usage info, so that for each level we have arrays of [dataTypeId => title] pairs
        foreach (compact('entry', 'language', 'term') as $level => $dataTypeIdA) {
            $usage[$level] = [];
            foreach ($dataTypeIdA as $dataTypeId) {
                $usage[$level][$dataTypeId] = $localized[$dataTypeId]['title'];
            }
        }

        // Return usage
        return (object) compact('usage', 'double', 'multi');
    }
}
