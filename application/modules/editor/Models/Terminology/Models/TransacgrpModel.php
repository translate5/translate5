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
 * Class editor_Models_Terminology_Models_Transacgrp
 * TermsTransacgrp Instance
 *
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getTransac() getTransac()
 * @method string setTransac() setTransac(string $transac)
 * @method string getTarget() getTarget()
 * @method string setTarget() setTarget(string $transac)
 * @method string getDate() getDate()
 * @method string setDate() setDate(string $admin)
 * @method string getTransacNote() getTransacNote()
 * @method string setTransacNote() setTransacNote(string $transacNote)
 * @method string getTransacType() getTransacType()
 * @method string setTransacType() setTransacType(string $transacType)
 * @method string getIsDescripGrp() getIsDescripGrp()
 * @method string setIsDescripGrp() setIsDescripGrp(string $isDescripGrp)
 * @method integer getCollectionId() getCollectionId()
 * @method integer setCollectionId() setCollectionId(integer $collectionId)
 * @method string getTermEntryId() getTermEntryId()
 * @method string setTermEntryId() setTermEntryId(string $TermEntryId)
 * @method string getLanguage() getLanguage()
 * @method string setLanguage() setLanguage(string $language)
 * @method string getTermId() getTermId()
 * @method string setTermId() setTermId(string $termId)
 * @method string getTermTbxId() getTermTbxId()
 * @method string setTermTbxId() setTermTbxId(string $termTbxId)
 * @method string getTermEntryGuid() getTermEntryGuid()
 * @method string setTermEntryGuid() setTermEntryGuid(string $termEntryGuid)
 * @method string getTermGuid() getTermGuid()
 * @method string setTermGuid() setTermGuid(string $termGuid)
 * @method string getLangSetGuid() getLangSetGuid()
 * @method string setLangSetGuid() setLangSetGuid(string $langSetGuid)
 * @method string getGuid() getGuid()
 * @method string setGuid() setGuid(string $guid)
 * @method string getElementName() getElementName()
 * @method string setElementName() setElementName(string $elementName)
 */
class editor_Models_Terminology_Models_TransacgrpModel extends editor_Models_Terminology_Models_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_Transacgrp';

    /**
     * @param $user
     * @param $termEntryId
     * @param null $language
     * @param null $termId
     */
    public function affectLevels($userName, $userGuid, $termEntryId, $language = null, $termId = null) {

        // Detect level that levels should be affected from and up to top
        if ($termEntryId && $language && $termId) $level = 'term';
        else if ($termEntryId && $language) $level = 'language';
        else $level = 'entry';

        // Build WHERE clause for each level and all it's parent levels to be affected
        $where = [
            'term'     => '(ISNULL(`language`) OR (`language` = :language AND (ISNULL(`termId`) OR `termId` = :termId)))',
            'language' => '(ISNULL(`language`) OR (`language` = :language AND  ISNULL(`termId`)                       ))',
            'entry'    => ' ISNULL(`language`)',
        ];

        // Build param bindings
        $bind = [
            ':date' => date('Y-m-d H:i:s', $time = time()),
            ':userName' => $userName,
            ':userGuid' => $userGuid,
            ':termEntryId' => $termEntryId
        ];
        if ($level == 'language' || $level == 'term') $bind[':language'] = strtolower($language);
        if ($level == 'term') $bind[':termId'] = $termId;

        // Run query
        $affectedFact = editor_Utils::db()->query('
            UPDATE `terms_transacgrp` 
            SET 
              `date` = :date, 
              `transacNote` = :userName,
              `target` = :userGuid
            WHERE TRUE
              AND `termEntryId` = :termEntryId 
              AND `transac` = \'modification\'
              AND ' . $where[$level],
        $bind)->rowCount();

        // How many row should be affected
        $affectedPlan = [
            'term'     => 3,
            'language' => 2,
            'entry'    => 1
        ];

        // If $level is 'term'
        if ($level == 'term') {

            // Get source
            $source = editor_Utils::db()->query('SELECT * FROM `terms_term` WHERE `id` = ?', $termId)->fetch();

            // Load or create person
            $person = ZfExtended_Factory
                ::get('editor_Models_Terminology_Models_TransacgrpPersonModel')
                ->loadOrCreateByName($userName, $source['collectionId']);

            // Prepare data for term to be updated with
            $termUpdate = ['tbxUpdatedBy' => $person->getId(), 'tbxUpdatedAt' => $bind[':date']];
        }

        // If number of affected rows is less than it should be
        // it mean that terms_transacgrp-records for at least one level are missing
        if ($missing = $affectedFact < $affectedPlan[$level]) {

            // Setup definition for level-column
            $levelColumnToBeGroupedBy['term'] = '
              IF (`termEntryId` = :termEntryId AND ISNULL(`language`) AND ISNULL(`termId`), "entry", 
                IF (`termEntryId` = :termEntryId AND `language` = :language AND ISNULL(`termId`), "language", 
                  IF (`termId` = :termId, "term", 
                    "other"))) AS `level`';

            // Setup definition for level-column
            $levelColumnToBeGroupedBy['language'] = '
              IF (`termEntryId` = :termEntryId AND ISNULL(`language`) AND ISNULL(`termId`), "entry", 
                IF (`termEntryId` = :termEntryId AND `language` = :language AND ISNULL(`termId`), "language", 
                  "other")) AS `level`';

            // Setup definition for level-column
            $levelColumnToBeGroupedBy['entry'] = '
              IF (`termEntryId` = :termEntryId AND ISNULL(`language`) AND ISNULL(`termId`), "entry", 
                  "other") AS `level`';

            //
            unset($bind[':date'], $bind[':userName'], $bind[':userGuid']);

            $sql = '
                SELECT 
                  ' . $levelColumnToBeGroupedBy[$level] . '                   
                FROM `terms_transacgrp`
                WHERE TRUE # TRUE here is just to beautify WHERE clause
                  AND `termEntryId` = :termEntryId 
                  AND `transac` = "modification"
                  AND ' . $where[$level];

            // Get levels, that terms_transacgrp-records are exist for
            $levelA['exist'] = editor_Utils::db()->query($sql, $bind)->fetchAll(PDO::FETCH_COLUMN);

            // Define which level should exist
            $levelA['should'] = [
                'term'     => ['term', 'language', 'entry'],
                'language' => [        'language', 'entry'],
                'entry'    => [                    'entry'],
            ];

            // Get levels, that transacgrp-records are missing for
            $levelA['missing'] = array_diff($levelA['should'][$level], $levelA['exist']);

            // If $level is 'term' - fetch terms_term-record by $termId arg
            if ($level == 'term') {
                // $source = editor_Utils::db()->query('SELECT * FROM `terms_term` WHERE `id` = ?', $termId)->fetch();
                $info['termEntryGuid'] = $source['termEntryGuid'];
                $info['termTbxId'] = $source['termTbxId'];
                $info['termGuid'] = $source['guid'];

            // Else fetch terms_term_entry-record by $termEntryId arg
            } else {
                $source = editor_Utils::db()->query('SELECT * FROM `terms_term_entry` WHERE `id` = ?', $termEntryId)->fetch();
                $info['termEntryGuid'] = $source['entryGuid'];
            }

            // Setup 'collectionId'
            $info['collectionId'] = $source['collectionId'];

            // Create missing terms_transacgrp-records
            foreach ($levelA['missing'] as $mlevel) {

                // Props applicable for all levels
                $byLevel = [
                    'collectionId' => $info['collectionId'],
                    'termEntryId' => $termEntryId,
                    'termEntryGuid' => $info['termEntryGuid'],
                ];

                // Props applicable for term- and language-levels
                if ($mlevel == 'term' || $mlevel == 'language') $byLevel += [
                    'language' => $language
                ];

                // Props, applicable for term-level only
                if ($mlevel == 'term') $byLevel += [
                    'termId' => $termId,
                    'termTbxId' => $info['termTbxId'],
                    'termGuid' => $info['termGuid'],
                ];

                // Setup 'elementName'
                if ($mlevel == 'term')          $byLevel += ['elementName' => 'tig'];
                else if ($mlevel == 'language') $byLevel += ['elementName' => 'langSet'];
                else if ($mlevel == 'entry')    $byLevel += ['elementName' => 'termEntry'];

                // Create `terms_transacgrp`-records
                foreach (['origination', 'modification'] as $type) {

                    // Create `terms_transacgrp` model instance
                    $t = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');

                    // Setup remaining props
                    $t->init($byLevel + [
                        'transac' => $type,
                        'date' => date('Y-m-d H:i:s'),
                        'transacNote' => $userName,
                        'target' => $userGuid,
                        'transacType' => 'responsibility',
                        'guid' => ZfExtended_Utils::uuid(),
                    ]);

                    // Save `terms_transacgrp` entry
                    $t->save();

                    // If level is 'term' but origination-transacgrp-record is missing
                    if ($level == 'term' && $mlevel == 'term' && $type == 'origination')

                        // Append tbxCreatedBy and tbxCreatedAt to the data for term to be updated with
                        $termUpdate += ['tbxCreatedBy' => $person->getId(), 'tbxCreatedAt' => $termUpdate['tbxUpdatedAt']];
                }
            }
        }

        // If $level is 'term'
        if ($level == 'term') {

            // Prepare col => value pairs as sql
            $cols = [];
            foreach ($termUpdate as $prop => $value)
                $cols []= '`' . $prop . '` = "' . $value . '"';

            // Update terms_term-record
            $this->db->getAdapter()->query('UPDATE `terms_term` SET ' . join(', ', $cols) . ' WHERE `id` = ?', $termId);
        }

        // Return affection info, appending a whitespace at the ending to indicate that
        // transacgrp-records at least for one level were missing, but as long as we created
        // them we should pass that to client-side app, so it can detect the whitespace and
        // update not only 'updated' viewModel's prop, but 'created' prop as well where it was missing
        return $userName . ', ' . date('d.m.Y H:i:s', $time) . editor_Utils::rif($missing, ' ');
    }

    public function getTransacGrpCollectionByEntryId($collectionId, $termEntryId): array
    {
        $transacGrpByKey = [];

        $query = "SELECT * FROM terms_transacgrp WHERE collectionId = :collectionId AND termEntryId = :termEntryId";
        $queryResults = $this->db->getAdapter()->query($query, ['collectionId' => $collectionId, 'termEntryId' => $termEntryId]);

        foreach ($queryResults as $key => $transacGrp) {
            $transacGrpByKey[$transacGrp['elementName'].'-'.$transacGrp['transac'].'-'.$transacGrp['isDescripGrp'].'-'.$transacGrp['termId']] = $transacGrp;
        }

        return $transacGrpByKey;
    }

    /***
     * Handle transac attributes group. If no transac group attributes exist for the entity, new one will be created.
     *
     * @param editor_Models_Terminology_Models_TermModel|editor_Models_Terminology_Models_TermEntryModel $entity
     * @return bool
     */
    public function handleTransacGroup($entity): bool
    {
        if ($entity->getId() === null) {
            return false;
        }
        $ret = $this->getTransacGroup($entity);
        //if the transac group exist, do nothing
        if (!empty($ret)) {
            return false;
        }

        return true;
    }

    /***
     * Get transac attribute for the entity and type
     *
     * @param editor_Models_Terminology_Models_TermModel|editor_Models_Terminology_Models_TermEntryModel $entity
     * @param array $types
     * @return array
     */
    public function getTransacGroup($entity): array
    {
        $s = $this->db->select();
        if ($entity instanceof editor_Models_Terminology_Models_TermModel){
            $s->where('termId=?', $entity->getTermId());
        }

        if ($entity instanceof editor_Models_Terminology_Models_TermEntryModel){
            $s->where('id=?', $entity->getId());
        }

        $s->where('collectionId=?', $entity->getTermId());

        $result = $this->db->fetchAll($s)->toArray();

        return $result;
    }
    /***
     * Create transac group attributes with its values. The type can be origination or modification
     * Depending on what kind of entity is passed, the appropriate attribute will be created(term attribute or term entry attribute)
     *
     * @param editor_Models_Terminology_Models_TermModel|editor_Models_Terminology_Models_TermEntryModel $entity
     * @param string $type
     * @return bool
     */
    public function createTransacGroup($entity, string $type): bool
    {

        return true;
    }

    /**
     * Get data for tbx-export
     *
     * @param $termEntryIds Comma-separated list of ids
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getExportData($termEntryIds) {
        return array_group_by($this->db->getAdapter()->query('
            SELECT `termEntryId`, `language`, `termId`, `elementName`, `transac`, `date`, `transacNote`, `transacType`, `isDescripGrp`, `target`  
            FROM `terms_transacgrp`
            WHERE `termEntryId` IN (' . $termEntryIds . ')
        ')->fetchAll(), 'termEntryId', 'language', 'termId');
    }
}
