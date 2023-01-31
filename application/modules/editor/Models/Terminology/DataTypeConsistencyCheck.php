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
class editor_Models_Terminology_DataTypeConsistencyCheck
{
/**
 * reference labels as of 02.2022
 * This values should be at least in the database
 */
protected array $referenceData = [
    [
        "label" => "termNote",
        "type" => "termType",
        "l10nSystem" => "{\"de\":\"Benennungstyp\",\"en\":\"Term type\"}",
        "level" => "term",
        "dataType" => "picklist",
        "picklistValues" => "fullForm,acronym,abbreviation,shortForm,variant,phrase",
        "isTbxBasic" => 1
    ],
    [
        "label" => "descrip",
        "type" => "definition",
        "l10nSystem" => "{\"de\":\"Definition\",\"en\":\"Definition\"}",
        "level" => "entry,language",
        "dataType" => "noteText",
        "picklistValues" => null,
        "isTbxBasic" => 1
    ],
    [
        "label" => "termNote",
        "type" => "abbreviatedFormFor",
        "l10nSystem" => "{\"de\":\"Abkürzung für\",\"en\":\"\"}",
        "level" => "term",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 0
    ],
    [
        "label" => "termNote",
        "type" => "pronunciation",
        "l10nSystem" => "{\"de\":\"Aussprache\",\"en\":\"\"}",
        "level" => "term",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 0
    ],
    [
        "label" => "termNote",
        "type" => "normativeAuthorization",
        "l10nSystem" => "{\"de\": \"Normative Berechtigung\", \"en\": \"Normative Authorization\"}",
        "level" => "term",
        "dataType" => "picklist",
        "picklistValues" => "admitted,admittedTerm,deprecated,deprecatedTerm,legalTerm,preferredTerm,proposed,regulatedTerm,standardizedTerm,supersededTerm",
        "isTbxBasic" => 0
    ],
    [
        "label" => "descrip",
        "type" => "subjectField",
        "l10nSystem" => "{\"de\":\"Sachgebiet\",\"en\":\"Subject field\"}",
        "level" => "entry",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 1
    ],
    [
        "label" => "descrip",
        "type" => "relatedConcept",
        "l10nSystem" => "{\"de\":\"Verwandtes Konzept\",\"en\":\"\"}",
        "level" => "entry,language,term",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 0
    ],
    [
        "label" => "descrip",
        "type" => "relatedConceptBroader",
        "l10nSystem" => "{\"de\":\"Erweitertes verwandtes Konzept\",\"en\":\"\"}",
        "level" => "entry,language,term",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 0
    ],
    [
        "label" => "admin",
        "type" => "productSubset",
        "l10nSystem" => "{\"de\":\"Produkt-Untermenge\",\"en\":\"\"}",
        "level" => "entry,language,term",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 0
    ],
    [
        "label" => "admin",
        "type" => "sourceIdentifier",
        "l10nSystem" => "{\"de\":\"Quellenidentifikator\",\"en\":\"\"}",
        "level" => "entry,language,term",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 0
    ],
    [
        "label" => "termNote",
        "type" => "partOfSpeech",
        "l10nSystem" => "{\"de\":\"Wortart\",\"en\":\"Part of speech\"}",
        "level" => "term",
        "dataType" => "picklist",
        "picklistValues" => "noun,verb,adjective,adverb,properNoun,other",
        "isTbxBasic" => 1
    ],
    [
        "label" => "descrip",
        "type" => "context",
        "l10nSystem" => "{\"de\":\"Kontext\",\"en\":\"Context\"}",
        "level" => "term",
        "dataType" => "noteText",
        "picklistValues" => null,
        "isTbxBasic" => 1
    ],
    [
        "label" => "admin",
        "type" => "businessUnitSubset",
        "l10nSystem" => "{\"de\":\"Teilbereich der Geschäftseinheit\",\"en\":\"\"}",
        "level" => "entry,language,term",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 0
    ],
    [
        "label" => "admin",
        "type" => "projectSubset",
        "l10nSystem" => "{\"de\":\"Projekt\",\"en\":\"Project\"}",
        "level" => "term",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 1
    ],
    [
        "label" => "termNote",
        "type" => "grammaticalGender",
        "l10nSystem" => "{\"de\":\"Genus\",\"en\":\"Gender\"}",
        "level" => "term",
        "dataType" => "picklist",
        "picklistValues" => "masculine,feminine,neuter,other",
        "isTbxBasic" => 1
    ],
    [
        "label" => "note",
        "type" => null,
        "l10nSystem" => "{\"de\":\"Kommentar\",\"en\":\"Comment\"}",
        "level" => "entry,language,term",
        "dataType" => "noteText",
        "picklistValues" => null,
        "isTbxBasic" => 1
    ],
    [
        "label" => "termNote",
        "type" => "administrativeStatus",
        "l10nSystem" => "{\"de\": \"Verwendungsstatus\", \"en\": \"Usage status\"}",
        "level" => "term",
        "dataType" => "picklist",
        "picklistValues" => "admitted,admittedTerm-admn-sts,deprecatedTerm-admn-sts,legalTerm-admn-sts,notRecommended,obsolete,preferred,preferredTerm-admn-sts,regulatedTerm-admn-sts,standardizedTerm-admn-sts,supersededTerm-admn-sts",
        "isTbxBasic" => 1
    ],
    [
        "label" => "termNote",
        "type" => "transferComment",
        "l10nSystem" => "{\"de\":\"Übertragungskommentar\",\"en\":\"\"}",
        "level" => "term",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 0
    ],
    [
        "label" => "admin",
        "type" => "entrySource",
        "l10nSystem" => "{\"de\":\"Quelle des Eintrags\",\"en\":\"\"}",
        "level" => "entry,language,term",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 0
    ],
    [
        "label" => "xref",
        "type" => "xGraphic",
        "l10nSystem" => "{\"de\":\"Abbildung/Multimedia\",\"en\":\"Illustration / Multimedia\"}",
        "level" => "entry",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 1
    ],
    [
        "label" => "admin",
        "type" => "source",
        "l10nSystem" => "{\"de\":\"Quelle\",\"en\":\"Source\"}",
        "level" => "term",
        "dataType" => "noteText",
        "picklistValues" => null,
        "isTbxBasic" => 1
    ],
    [
        "label" => "xref",
        "type" => "externalCrossReference",
        "l10nSystem" => "{\"de\":\"externer Verweis\",\"en\":\"External reference\"}",
        "level" => "entry,term",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 1
    ],
    [
        "label" => "termNote",
        "type" => "geographicalUsage",
        "l10nSystem" => "{\"de\":\"regionale Verwendung\",\"en\":\"Regional use\"}",
        "level" => "term",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 1
    ],
    [
        "label" => "termNote",
        "type" => "termLocation",
        "l10nSystem" => "{\"de\":\"typischer Verwendungsfall\",\"en\":\"Typical use case\"}",
        "level" => "term",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 1
    ],
    [
        "label" => "ref",
        "type" => "crossReference",
        "l10nSystem" => "{\"de\":\"Querverweis\",\"en\":\"Cross reference\"}",
        "level" => "entry,term",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 1
    ],
    [
        "label" => "admin",
        "type" => "customerSubset",
        "l10nSystem" => "{\"de\":\"Kunde\",\"en\":\"TCustomer\"}",
        "level" => "term",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 1
    ],
    [
        "label" => "termNote",
        "type" => "processStatus",
        "l10nSystem" => "{\"de\": \"Prozessstatus\", \"en\": \"Process status\"}",
        "level" => "term",
        "dataType" => "picklist",
        "picklistValues" => "unprocessed,provisionallyProcessed,finalized,rejected",
        "isTbxBasic" => 1
    ],
    [
        "label" => "descrip",
        "type" => "figure",
        "l10nSystem" => "{\"de\":\"Bild\",\"en\":\"Image\"}",
        "level" => "entry,language",
        "dataType" => "plainText",
        "picklistValues" => null,
        "isTbxBasic" => 1
    ]
];

    /**
     * returns a list of datatypes where the elementName and type of the datatype does not match the corresponding values in the attributes table
     * or where the datatypeid does not exist in the datatypes list
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function checkAttributesAgainstDataTypes(): array {
        /** @var editor_Models_Terminology_Models_AttributeDataType $model */
        $model = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeDataType');
        $q = $model->db->getAdapter()->query('select tad.id datatypeId, tad.label datatypeTag, tad.type datatypeType, ta.collectionId, ta.elementName attributeTag, ta.`type` attributeType
from terms_attributes_datatype tad 
    JOIN terms_attributes ta on tad.id = ta.dataTypeId and (tad.label != ta.elementName or tad.`type` != ta.`type`) 
group by tad.id, tad.label, tad.type, ta.collectionId, ta.elementName, ta.`type`
union
select ta.id datatypeid, ": attribute ID" datatypeTag, "NOT EXISTENT" datatypeType, ta.collectionId, ta.elementName attributeTag, ta.`type` attributeType
from terms_attributes ta
LEFT JOIN terms_attributes_datatype tad ON ta.dataTypeId = tad.id
where tad.id IS NULL;
');
        return $q->fetchAll(Zend_Db::FETCH_ASSOC);
    }

    /**
     * @return array[]
     */
    public function checkDataTypesAgainstDefault(): array {
        /** @var editor_Models_Terminology_Models_AttributeDataType $model */
        $model = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeDataType');
        $all = $model->loadAll();
        //rebuild the data to a tree
        $tree = [];
        foreach($all as $datatype) {
            //we use empty string instead null as key
            $tree[$datatype['label'] ?? ''][$datatype['type'] ?? ''] = $datatype;
        }

        $notFound = [];
        $differentContent = [];
        foreach($this->referenceData as $datatype) {
            if(empty($tree[$datatype['label'] ?? ''][$datatype['type'] ?? ''])) {
                $notFound[] = $datatype;
                continue;
            }
            $found = $tree[$datatype['label'] ?? ''][$datatype['type'] ?? ''];
            $diff = false;
            foreach ($datatype as $k => $v) {
                if($k === 'picklistValues') {
                    continue;
                }
                if($k === 'isTbxBasic') { //fix int vs string values
                    $v = (int) $v;
                    $found[$k] = (int) $found[$k] ?? null;
                }
                if($v !== ($found[$k] ?? null)) {
                    $diff = true;
                    $found[$k.'_orig'] = $v;
                }
            }
            if($diff) {
                $differentContent[] = $found;
            }
        }

        return [
            'notFound' => $notFound,
            'differentContent' => $differentContent,
        ];
    }

    /**
     * Fetch duplicated attrs separately for each level
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function checkAttributeDuplicates() {

        /** @var editor_Models_Terminology_Models_AttributeModel $model */
        $model = ZfExtended_Factory::get(editor_Models_Terminology_Models_AttributeModel::class);
        $db = $model->db->getAdapter();

        // Get attribute-duplicates on term-level
        $term = $db->query("
            SELECT
              COUNT(`ta`.`id`) AS `qty`,
              SUBSTRING_INDEX(GROUP_CONCAT(`ta`.`id` ORDER BY `updatedAt` DESC, `ta`.`id` DESC), ',', 1) AS `newestId`,
              REPLACE (
                GROUP_CONCAT(`ta`.`id` ORDER BY `updatedAt` DESC, `ta`.`id` DESC),
                CONCAT(SUBSTRING_INDEX(GROUP_CONCAT(`ta`.`id` ORDER BY `updatedAt` DESC, `ta`.`id` DESC), ',', 1), ','),
                ''
              ) AS `olderIds`,
              CONCAT (`type`, '-', `termId`, '-', `dataTypeId`) AS `type-termId-dataTypeId`,
              MAX(`createdAt`),
              MAX(`updatedAt`)
            FROM `terms_attributes` `ta`
            WHERE NOT ISNULL (`termId`) AND `type` NOT IN ('xGraphic', 'crossReference', 'externalCrossReference', 'figure')
            GROUP BY `type-termId-dataTypeId`
            HAVING `qty` > 1
            ORDER BY `type-termId-dataTypeId` LIKE 'processStatus%' DESC, `qty` DESC, `newestId` DESC
            LIMIT 10
        ")->fetchAll();

        // Get attribute-duplicates on language-level
        $language = $db->query("
            SELECT
              COUNT(`ta`.`id`) AS `qty`,
              SUBSTRING_INDEX(GROUP_CONCAT(`ta`.`id` ORDER BY `updatedAt` DESC, `ta`.`id` DESC), ',', 1) AS `newestId`,
              REPLACE (
                GROUP_CONCAT(`ta`.`id` ORDER BY `updatedAt` DESC, `ta`.`id` DESC),
                CONCAT(SUBSTRING_INDEX(GROUP_CONCAT(`ta`.`id` ORDER BY `updatedAt` DESC, `ta`.`id` DESC), ',', 1), ','),
                ''
              ) AS `olderIds`,
              CONCAT(`type`, '-', `termEntryId`, '-', `language`, '-', `dataTypeId`) AS `type-termEntryId-language-dataTypeId`,
              MAX(`createdAt`),
              MAX(`updatedAt`)
            FROM `terms_attributes` `ta`
            WHERE NOT ISNULL(`language`) AND ISNULL(`termId`) AND `type` NOT IN ('xGraphic', 'crossReference', 'externalCrossReference', 'figure')
            GROUP BY `type-termEntryId-language-dataTypeId`
            HAVING `qty` > 1
            ORDER BY `qty` DESC, `newestId` DESC
            LIMIT 10
        ")->fetchAll();

        // Get attribute-duplicates on termEntry-level
        $termEntry = $db->query("
            SELECT
              COUNT(`ta`.`id`) AS `qty`,
              SUBSTRING_INDEX(GROUP_CONCAT(`ta`.`id` ORDER BY `updatedAt` DESC, `ta`.`id` DESC), ',', 1) AS `newestId`,
              REPLACE (
                GROUP_CONCAT(`ta`.`id` ORDER BY `updatedAt` DESC, `ta`.`id` DESC),
                CONCAT(SUBSTRING_INDEX(GROUP_CONCAT(`ta`.`id` ORDER BY `updatedAt` DESC, `ta`.`id` DESC), ',', 1), ','),
                ''
              ) AS `olderIds`,
              CONCAT(`type`, '-', `termEntryId`, '-', `dataTypeId`) AS `type-termEntryId-dataTypeId`,
              MAX(`createdAt`),
              MAX(`updatedAt`)
            FROM `terms_attributes` `ta`
            WHERE ISNULL(`language`) AND `type` NOT IN ('xGraphic', 'crossReference', 'externalCrossReference', 'figure')
            GROUP BY `type-termEntryId-dataTypeId`
            HAVING `qty` > 1
            ORDER BY `qty` DESC, `newestId` DESC
            LIMIT 10
        ")->fetchAll();

        // Return duplicates info by level
        return compact('term', 'language', 'termEntry');
    }

    /**
     * Get first 10 term-level attributes having no termTbxId
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function noTermTbxId() {
        return ZfExtended_Factory::get(editor_Models_Terminology_Models_AttributeModel::class)
            ->db->getAdapter()->query("
                SELECT `id`, `termId`, `dataTypeId`, `type` 
                FROM `terms_attributes` 
                WHERE NOT ISNULL(`termId`) AND ISNULL(`termTbxId`)
                LIMIT 10
            ")->fetchAll();
    }

    /**
     * Get all cases when attributes have same dataTypeId but different type
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function sameDataTypeIdDiffType() {
        return ZfExtended_Factory::get(editor_Models_Terminology_Models_AttributeModel::class)
            ->db->getAdapter()->query("
                SELECT `dataTypeId`, COUNT(DISTINCT `type`) AS `type-qty`, GROUP_CONCAT(DISTINCT `type`) AS `type-list`
                FROM `terms_attributes`
                GROUP BY `dataTypeId`
                HAVING `type-qty` > 1
            ")->fetchAll();
    }

    /**
     * Get all cases when attributes have same type but different elementName
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function sameTypeDiffElementName() {
        return ZfExtended_Factory::get(editor_Models_Terminology_Models_AttributeModel::class)
            ->db->getAdapter()->query("
                SELECT 
                  `type`,  
                  COUNT(DISTINCT `elementName`) AS `qty`,
                  SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT CONCAT(`dataTypeId`, '-', `elementName`) ORDER BY `dataTypeId` ASC), ',', 1) AS `correct-dataTypeId-elementName`,
                  REPLACE (
                    GROUP_CONCAT(DISTINCT CONCAT(`dataTypeId`, '-', `elementName`) ORDER BY `dataTypeId` ASC),
                    CONCAT(SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT CONCAT(`dataTypeId`, '-', `elementName`) ORDER BY `dataTypeId` ASC), ',', 1), ','),
                    ''
                  ) AS `mistake-list`
                FROM `terms_attributes`
                GROUP BY `type`
                HAVING `qty` > 1
            ")->fetchAll();
    }

    /**
     * Get all cases when attributes exist on unexpected levels
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function sameTypeUnexpectedLevel() {
        return ZfExtended_Factory::get(editor_Models_Terminology_Models_AttributeDataType::class)
            ->db->getAdapter()->query("
                WITH `data` AS (
                  SELECT
                    `dataTypeId`, 
                    `type`, 
                    GROUP_CONCAT(DISTINCT IF(ISNULL(`language`), 'entry', IF(ISNULL(`termId`), 'language', 'term'))) AS `actual-levels`
                  FROM `terms_attributes` 
                  GROUP BY `dataTypeId`
                )
                SELECT `d`.*, `m`.`level` AS `expected-levels`
                FROM `data` `d` 
                  JOIN `terms_attributes_datatype` `m` ON (`d`.`dataTypeId` = `m`.`id`)
                WHERE (`actual-levels` LIKE '%term%'     AND `m`.`level` NOT LIKE '%term%')
                   OR (`actual-levels` LIKE '%language%' AND `m`.`level` NOT LIKE '%language%')
                   OR (`actual-levels` LIKE '%entry%'    AND `m`.`level` NOT LIKE '%entry%')
            ")->fetchAll();
    }

    /**
     * Get all cases when datatypes have same type but different label
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function sameTypeDiffLabelOrLevel() {
        return ZfExtended_Factory::get(editor_Models_Terminology_Models_AttributeDataType::class)
            ->db->getAdapter()->query("
                SELECT 
                  `type`,  
                  COUNT(DISTINCT CONCAT(`label`, '-', `level`)) AS `qty`,
                  SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT CONCAT(`id`, '-', `label`, '-', `level`) ORDER BY `id` ASC SEPARATOR ';'), ';', 1) AS `correct-id-label-level`,
                  REPLACE (
                    GROUP_CONCAT(DISTINCT CONCAT(`id`, '-', `label`, '-', `level`) ORDER BY `id` ASC SEPARATOR ';'),
                    CONCAT(SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT CONCAT(`id`, '-', `label`, '-', `level`) ORDER BY `id` ASC SEPARATOR ';'), ';', 1), ';'),
                    ''
                  ) AS `mistake-list`
                FROM `terms_attributes_datatype`
                GROUP BY `type`
                HAVING `qty` > 1
            ")->fetchAll();
    }
}