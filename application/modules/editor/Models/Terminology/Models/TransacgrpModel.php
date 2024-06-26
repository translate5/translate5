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
 * TermsTransacgrp Instance
 *
 * @method string getId()
 * @method void setId(integer $id)
 * @method string getTransac()
 * @method void setTransac(string $transac)
 * @method string getTarget()
 * @method void setTarget(string $transac)
 * @method string getDate()
 * @method void setDate(string $admin)
 * @method string getTransacNote()
 * @method void setTransacNote(string $transacNote)
 * @method string getTransacType()
 * @method void setTransacType(string $transacType)
 * @method string getIsDescripGrp()
 * @method void setIsDescripGrp(string $isDescripGrp)
 * @method string getCollectionId()
 * @method void setCollectionId(integer $collectionId)
 * @method string getTermEntryId()
 * @method void setTermEntryId(string $TermEntryId)
 * @method string getLanguage()
 * @method void setLanguage(string $language)
 * @method string getTermId()
 * @method void setTermId(string $termId)
 * @method string getTermTbxId()
 * @method void setTermTbxId(string $termTbxId)
 * @method string getTermEntryGuid()
 * @method void setTermEntryGuid(string $termEntryGuid)
 * @method string getTermGuid()
 * @method void setTermGuid(string $termGuid)
 * @method string getLangSetGuid()
 * @method void setLangSetGuid(string $langSetGuid)
 * @method string getGuid()
 * @method void setGuid(string $guid)
 * @method string getElementName()
 * @method void setElementName(string $elementName)
 */
class editor_Models_Terminology_Models_TransacgrpModel extends editor_Models_Terminology_Models_Abstract
{
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_Transacgrp';

    /**
     * @param string $userName
     * @param string $userGuid
     * @param string|int $termEntryId
     * @param ?string $language
     * @param null|int|string $termId
     * @return string
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function affectLevels($userName, $userGuid, $termEntryId, $language = null, $termId = null)
    {
        // Detect level that levels should be affected from and up to top
        if ($termEntryId && $language && $termId) {
            $level = 'term';
        } elseif ($termEntryId && $language) {
            $level = 'language';
        } else {
            $level = 'entry';
        }

        // Build WHERE clause for each level and all it's parent levels to be affected
        $where = [
            'term' => '(ISNULL(`language`) OR (`language` = :language AND (ISNULL(`termId`) OR `termId` = :termId)))',
            'language' => '(ISNULL(`language`) OR (`language` = :language AND  ISNULL(`termId`)                       ))',
            'entry' => ' ISNULL(`language`)',
        ];

        // Build param bindings
        $bind = [
            ':date' => date('Y-m-d H:i:s', $time = time()),
            ':userName' => $userName,
            ':userGuid' => $userGuid,
            ':termEntryId' => $termEntryId,
        ];
        if ($level == 'language' || $level == 'term') {
            $bind[':language'] = strtolower($language);
        }
        if ($level == 'term') {
            $bind[':termId'] = $termId;
        }

        // Run query
        $affectedFact = $this->db->getAdapter()->query(
            '
            UPDATE `terms_transacgrp` 
            SET 
              `date` = :date, 
              `transacNote` = :userName,
              `target` = :userGuid
            WHERE TRUE
              AND `termEntryId` = :termEntryId 
              AND `transac` = \'modification\'
              AND ' . $where[$level],
            $bind
        )->rowCount();

        // How many row should be affected
        $affectedPlan = [
            'term' => 3,
            'language' => 2,
            'entry' => 1,
        ];

        // Define variables
        $termUpdate = $person = $source = null;

        // If $level is 'term'
        if ($level == 'term') {
            // Get source
            $source = $this->db->getAdapter()->query('SELECT * FROM `terms_term` WHERE `id` = ?', $termId)->fetch();

            // Load or create person
            $person = ZfExtended_Factory
                ::get('editor_Models_Terminology_Models_TransacgrpPersonModel')
                    ->loadOrCreateByName($userName, $source['collectionId']);

            // Prepare data for term to be updated with
            $termUpdate = [
                'tbxUpdatedBy' => $person->getId(),
                'tbxUpdatedAt' => $bind[':date'],
            ];
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
            $levelA['exist'] = $this->db->getAdapter()->query($sql, $bind)->fetchAll(PDO::FETCH_COLUMN);

            // Define which level should exist
            $levelA['should'] = [
                'term' => ['term', 'language', 'entry'],
                'language' => ['language', 'entry'],
                'entry' => ['entry'],
            ];

            // Get levels, that transacgrp-records are missing for
            $levelA['missing'] = array_diff($levelA['should'][$level], $levelA['exist']);

            // If $level is 'term' - fetch terms_term-record by $termId arg
            if ($level == 'term') {
                // $source = $this->db->getAdapter()->query('SELECT * FROM `terms_term` WHERE `id` = ?', $termId)->fetch();
                $info['termEntryGuid'] = $source['termEntryGuid'];
                $info['termTbxId'] = $source['termTbxId'];
                $info['termGuid'] = $source['guid'];

                // Else fetch terms_term_entry-record by $termEntryId arg
            } else {
                $source = $this->db->getAdapter()->query('SELECT * FROM `terms_term_entry` WHERE `id` = ?', $termEntryId)->fetch();
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
                if ($mlevel == 'term' || $mlevel == 'language') {
                    $byLevel += [
                        'language' => $language,
                    ];
                }

                // Props, applicable for term-level only
                if ($mlevel == 'term') {
                    $byLevel += [
                        'termId' => $termId,
                        'termTbxId' => $info['termTbxId'],
                        'termGuid' => $info['termGuid'],
                    ];
                }

                // Setup 'elementName'
                if ($mlevel == 'term') {
                    $byLevel += [
                        'elementName' => 'tig',
                    ];
                } elseif ($mlevel == 'language') {
                    $byLevel += [
                        'elementName' => 'langSet',
                    ];
                } elseif ($mlevel == 'entry') {
                    $byLevel += [
                        'elementName' => 'termEntry',
                    ];
                }

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
                    if ($level == 'term' && $mlevel == 'term' && $type == 'origination') {
                        // Append tbxCreatedBy and tbxCreatedAt to the data for term to be updated with
                        $termUpdate += [
                            'tbxCreatedBy' => $person->getId(),
                            'tbxCreatedAt' => $termUpdate['tbxUpdatedAt'],
                        ];
                    }
                }
            }
        }

        // If $level is 'term'
        if ($level == 'term') {
            // Prepare col => value pairs as sql
            $cols = [];
            foreach ($termUpdate as $prop => $value) {
                $cols[] = '`' . $prop . '` = "' . $value . '"';
            }

            // Update terms_term-record
            $this->db->getAdapter()->query('UPDATE `terms_term` SET ' . join(', ', $cols) . ' WHERE `id` = ?', $termId);
        }

        // Return affection info, appending a whitespace at the ending to indicate that
        // transacgrp-records at least for one level were missing, but as long as we created
        // them we should pass that to client-side app, so it can detect the whitespace and
        // update not only 'updated' viewModel's prop, but 'created' prop as well where it was missing
        return $userName . ', ' . date('d.m.Y H:i:s', $time) . editor_Utils::rif($missing, ' ');
    }

    /**
     * Get data for tbx-export
     *
     * @param string $termEntryIds Comma-separated list of ids
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getExportData($termEntryIds)
    {
        return array_group_by($this->db->getAdapter()->query('
            SELECT `termEntryId`, `language`, `termId`, `elementName`, `transac`, `date`, `transacNote`, `transacType`, `isDescripGrp`, `target`  
            FROM `terms_transacgrp`
            WHERE `termEntryId` IN (' . $termEntryIds . ')
        ')->fetchAll(), 'termEntryId', 'language', 'termId');
    }

    /**
     * This method retrieves attributes grouped by level.
     * It is used internally by TermModel->terminfo() and ->siblinginfo()
     * and should not be called directly
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function loadGroupedByLevel($levelColumnToBeGroupedBy, $where, $bind)
    {
        return $this->db->getAdapter()->query(
            '
            SELECT 
              ' . $levelColumnToBeGroupedBy . ',                   
              `transac`,
              CONCAT(`transacNote`, ", ", DATE_FORMAT(`date`, "%d.%m.%Y %H:%i:%s")) AS `whowhen`
            FROM `terms_transacgrp`
            WHERE TRUE # TRUE here is just to beautify WHERE clause
              AND ' . $where . ' 
              AND `transacType` = "responsibility" 
              AND `transac` IN ("modification", "origination")',
            $bind
        )->fetchAll(PDO::FETCH_GROUP);
    }
}
