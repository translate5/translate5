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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 *
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
// $this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renaming etc...
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '376-TRANSLATE-3123-delete-attrs-duplicates.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

/* @var $model editor_Models_Terminology_Models_AttributeModel */
$db = ZfExtended_Factory::get(editor_Models_Terminology_Models_AttributeModel::class)->db->getAdapter();

// Get ids of attribute datatypes, that are allowed to have multiple occurrences on their level
$allowedMulti = $db->query("
    SELECT `id` 
    FROM `terms_attributes_datatype` 
    WHERE `type` IN ('xGraphic', 'crossReference', 'externalCrossReference', 'figure')
")->fetchAll(PDO::FETCH_COLUMN);

// Get NOT IN (...) clause
$dataTypeId_NOT_IN = $db->quoteInto('`dataTypeId` NOT IN (?)', $allowedMulti);

// Get ids of attribute datatypes, that are picklist-datatypes
$picklistA = $db->query("
    SELECT `id`, 1 FROM `terms_attributes_datatype` WHERE `dataType` = 'picklist'
")->fetchAll(PDO::FETCH_KEY_PAIR);

/**
 * @param array $attrA
 * @param $db Zend_Db_Adapter_Abstract
 * @param array $picklistA
 */
function cleanupAttrA(array $attrA, $db, array $picklistA) {

    // Foreach attr having duplicates
    foreach ($attrA as $attrI) {

        // Delete all records of this attribute except the newer one
        $db->query("DELETE FROM `terms_attributes` WHERE `id` IN ({$attrI['older']})");

        // If this attribute is not a picklist
        if (!isset($picklistA[$attrI['dataTypeId']])) {

            // Set value of newer one to be distinct concatenated values across all records (newer + older)
            $db->query(
                "UPDATE `terms_attributes` SET `value` = ? WHERE `id` = {$attrI['newer']}", $attrI['values']
            );
        }
    }
}

// Prepare SELECT FROM
$selectFrom = "
  SELECT
    SUBSTRING_INDEX(GROUP_CONCAT(`id` ORDER BY `updatedAt` DESC, `id` DESC), ',', 1) AS `newer`,
    `dataTypeId`,
    REPLACE (
      GROUP_CONCAT(`id` ORDER BY `updatedAt` DESC, `id` DESC),
      CONCAT(SUBSTRING_INDEX(GROUP_CONCAT(`id` ORDER BY `updatedAt` DESC, `id` DESC), ',', 1), ','),
      ''
    ) AS `older`,
    GROUP_CONCAT(DISTINCT TRIM(`value`) ORDER BY `updatedAt` DESC, `id` DESC SEPARATOR ', ') AS `values`,
    COUNT(`id`) AS `qty`
  FROM `terms_attributes`
";

// Term-level: get duplicates
$attrA = $db->query("$selectFrom
  WHERE NOT ISNULL(`termId`) AND $dataTypeId_NOT_IN
  GROUP BY CONCAT(`termId`, '-', `dataTypeId`, '-', `type`)
  HAVING COUNT(`id`) > 1
")->fetchAll();

// Do cleanup
cleanupAttrA($attrA, $db, $picklistA);

// Set termTbxId for attributes where it shouldn't be but NULL
$db->query('
  UPDATE 
   `terms_attributes` `ta`
   LEFT JOIN `terms_term` `tt` ON (`ta`.`termId` = `tt`.`id`)
  SET `ta`.`termTbxId` = `tt`.`termTbxId`
  WHERE ISNULL(`ta`.`termTbxId`)
');

// TermEntry-level: get duplicates
$attrA = $db->query("$selectFrom
  WHERE ISNULL(`language`) AND $dataTypeId_NOT_IN
  GROUP BY CONCAT(`termEntryId`, '-', `dataTypeId`, '-', `type`)
  HAVING COUNT(`id`) > 1
")->fetchAll();

// Do cleanup
cleanupAttrA($attrA, $db, $picklistA);

// Language-level: get duplicates
$attrA = $db->query("$selectFrom
  WHERE NOT ISNULL(`language`) AND ISNULL(`termId`) AND $dataTypeId_NOT_IN
  GROUP BY LOWER(CONCAT(`termEntryId`, '-', `language`, '-', `dataTypeId`, '-', `type`))
  HAVING COUNT(`id`) > 1
")->fetchAll();

// Do cleanup
cleanupAttrA($attrA, $db, $picklistA);

// Get checker
$checker = new \editor_Models_Terminology_DataTypeConsistencyCheck();

// Foreach sameTypeDiffElementName-case found
foreach ($checker->sameTypeDiffElementName() as $case) {

    // Get correct dataTypeId and elementName
    list ($correct['dataTypeId'], $correct['elementName']) = explode('-', $case['correct-dataTypeId-elementName']);

    // Foreach mistake
    foreach (explode(',', $case['mistake-list']) as $item) {

        // Extract mistake's dataTypeId and elementName
        list ($mistake['dataTypeId'], $mistake['elementName']) = explode('-', $item);

        // Fix db data
        $db->query("
            UPDATE `terms_attributes` 
            SET `dataTypeId` = '{$correct['dataTypeId']}', `elementName` = '{$correct['elementName']}'
            WHERE 1
              AND `dataTypeId` = '{$mistake['dataTypeId']}'
              AND `elementName` = '{$mistake['elementName']}'
              AND `type` = '{$case['type']}'
        ");
    }
}

// Foreach sameTypeDiffLabel-case found
foreach ($checker->sameTypeDiffLabel() as $case) {

    // Get correct id and label
    list ($correct['id'], $correct['label']) = explode('-', $case['correct-id-label']);

    // Foreach mistake
    foreach (explode(',', $case['mistake-list']) as $item) {

        // Extract mistake's id and label
        list ($mistake['id'], $mistake['label']) = explode('-', $item);

        // Delete datatype-record created by mistake
        $db->query("DELETE FROM `terms_attributes_datatype` WHERE `id` = '{$mistake['id']}'");
    }
}