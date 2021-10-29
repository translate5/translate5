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

class editor_Models_Terminology_Models_AttributeDataType extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_AttributeDatatype';
    protected $validatorInstanceClass   = 'editor_Models_Validator_Term_AttributeDatatype';


    /**
     * Load the label with given name,type and level, if it does not exist, the label will be created
     * @param string $labelName
     * @param string $labelType
     */
    public function loadOrCreate(string $labelName, string $labelType = '',array $level = [
        editor_Models_Terminology_TbxObjects_Attribute::ATTRIBUTE_LEVEL_ENTRY,
        editor_Models_Terminology_TbxObjects_Attribute::ATTRIBUTE_LEVEL_LANGUAGE,
        editor_Models_Terminology_TbxObjects_Attribute::ATTRIBUTE_LEVEL_TERM])
    {
        $s = $this->db->select()
        ->from($this->db)
        ->where('label = ?', $labelName);
        if (!empty($labelType)) {
            $this->setType($labelType);
            $s->where('type = ?', $labelType);
        }
        $levelSql = [];
        // for each level, add like search
        foreach ($level as $l) {
            $levelSql[] = 'level LIKE "%'.$l.'%"';
        }
        $s->where(implode(' OR ',$levelSql));
        $row = $this->db->fetchRow($s);
        if ($row) {
            $this->row = $row;
            return;
        }
        $this->setLabel($labelName);
        $this->setDataType(editor_Models_Terminology_TbxObjects_Attribute::ATTRIBUTE_DEFAULT_DATATYPE);
        $this->setLevel(implode(',',$level));
        $this->save();
    }

    /***
     * Return all labels with translated labelText
     * @return array
     */
    public function loadAllTranslated(bool $addUniqueKey = false): array
    {
        $labels = $this->loadAll();
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        foreach ($labels as &$label){
            if (empty($label['labelText'])) {
                continue;
            }
            $label['labelText'] = $translate->_($label['labelText']);
        }

        return $labels;
    }

    /**
     * Get comma-separated list of ids of tbx-basic attributes
     *
     * @return string
     */
    public function getTbxBasicIds() {
        return implode(',', $this->db->getAdapter()->query('
            SELECT `id` FROM `terms_attributes_datatype` WHERE `isTbxBasic` = 1
        ')->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * provides a list of term level label and type tupels (as one string separated with #) and returns their datatype IDs
     * example "termNote#footype", "note#" returns for example the IDs for label termNote with type footype on level term and the id for label note with type null on level term
     * Only type may be empty (null)!
     *
     * @param array $labelTypeList
     * @return array
     */
    public function getIdsForTerms(array $labelTypeList): array {
        //we load all datatypes for the given labels / elementNames and filter them then on PHP level
        $s = $this->db->select()
            ->from($this->db, ['id', 'label', 'type']);

        foreach($labelTypeList as $key) {
            $parts = explode('#', $key);
            $s->orWhere('(label = ?', $parts[0]);
            if(empty($parts[1])) {
                $s->where('type is null');
            }
            else {
                $s->where('type = ?', $parts[1]);
            }
            $s->where('FIND_IN_SET( "term" ,level)>0 )');
        }

        $dbResult = $this->db->fetchAll($s)->toArray();
        $result = [];
        foreach($dbResult as $row) {
            $result[$row['label'].'#'.$row['type']] = $row['id'];
        }
        return $result;
    }

    /**
     * Get dataTypeId by $type
     *
     * @param string $type
     * @return string
     * @throws Zend_Db_Statement_Exception
     */
    public function getIdByType(string $type) {
        return $this->db->getAdapter()->query(
            'SELECT `id` FROM `terms_attributes_datatype` WHERE `type` = ?', $type
        )->fetchColumn();
    }

    /**
     * Get dataTypeId by for note-attribute
     *
     * @param string $type
     * @return string
     * @throws Zend_Db_Statement_Exception
     */
    public function getNoteId() {
        return $this->db->getAdapter()->query(
            'SELECT `id` FROM `terms_attributes_datatype` WHERE `label` = "note" AND `type` IS NULL'
        )->fetchColumn();
    }


    /**
     * Get array of terms_attributes.dataTypeId for a level, identified by $termEntryId, $language and $termId args
     * Currently this is used to prevent creating more than 1 attributes having same dataTypeId
     *
     * @param int $termEntryId
     * @param string|null $language
     * @param int|null $termId
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getAlreadyExistingFor(int $termEntryId, string $language = null, int $termId = null) {

        // Detect level
        if ($termEntryId && $language && $termId) $level = 'term';
        else if ($termEntryId && $language) $level = 'language';
        else $level = 'entry';

        // Setup WHERE clauses for entry-, language- and term-level attributes
        $levelWHERE = [
            'entry'    => '`termEntryId` = :termEntryId AND ISNULL(`language`) AND ISNULL(`termId`)',
            'language' => '`termEntryId` = :termEntryId AND `language` = :language AND ISNULL(`termId`)',
            'term'     => '`termId` = :termId'
        ];

        // Params for binding to the existing attribute-fetching query
        $bind = [
            'entry'    => [':termEntryId' => $termEntryId],
            'language' => [':termEntryId' => $termEntryId, ':language' => $language],
            'term'     => [':termId' => $termId]
        ];

        // Return existing attributes datatype ids
        return $this->db->getAdapter()->query('
            SELECT `dataTypeId` 
            FROM `terms_attributes`
            WHERE ' . $levelWHERE[$level]
        , $bind[$level])->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get array of collection ids that are allowed to create attribute with current data type in
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getAllowedCollectionIds() {
        return $this->db->getAdapter()->query('
            SELECT `collectionId` 
            FROM `terms_collection_attribute_datatype` 
            WHERE `dataTypeId` = ?'
        , $this->getId())->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get array of attribute datatypes in a format, compatible with TermPortal client app
     *
     * @param string $locale
     * @param array $collectionIds
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getLocalized(string $locale, array $collectionIds): array {

        // Fetch from database
        $attributes = $this->db->getAdapter()->query('
            SELECT 
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
              CONCAT("attr-", `a`.`id`) AS `alias`,
              IF(`a`.`label` = "note", `a`.`label`, `a`.`dataType`) AS `dataType`,
              `a`.`picklistValues`,
              `a`.`level`,
              `a`.`isTbxBasic`,
              `a`.`type`,
              GROUP_CONCAT(`ac`.`collectionId`) AS `collections`
            FROM 
              `terms_attributes_datatype` `a` 
              LEFT JOIN `terms_collection_attribute_datatype` `ac` ON (`ac`.`dataTypeId` = `a`.`id`)
            WHERE `ac`.`collectionId` IN (' . join(',',$collectionIds) . ') OR ISNULL(`ac`.`collectionId`)
            GROUP BY `a`.`id`
            ORDER BY `title`
        ', [':lang' => '$.' . $locale])->fetchAll();

        // Make sure isTbxBasic to be integer in javascript
        array_walk($attributes, fn(&$a) => $a['isTbxBasic'] += 0);

        // Return attributes
        return $attributes;
    }
}
