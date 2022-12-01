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

/* @var $attr editor_Models_Terminology_Models_AttributeModel */
$attr = ZfExtended_Factory::get(editor_Models_Terminology_Models_AttributeModel::class);

// Get ids of attribute datatypes, that are allowed to have multiple occurrences on their level
$allowedMulti = $attr->db->getAdapter()->query('
    SELECT `id` 
    FROM `terms_attributes_datatype` 
    WHERE `type` IN ("xGraphic", "crossReference", "externalCrossReference", "figure")
')->fetchAll(PDO::FETCH_COLUMN);

// Get NOT IN (...) clause
$dataTypeId_NOT_IN = $attr->db->getAdapter()->quoteInto('`dataTypeId` NOT IN (?)', $allowedMulti);

// Term-level: Get ids of all duplicated occurrences of attributes except the most recent occurrence
$olderDuplicates = $attr->db->getAdapter()->query('
  SELECT 
    REPLACE(
       GROUP_CONCAT(`id` ORDER BY `updatedAt` DESC, `id` DESC),
       CONCAT(SUBSTRING_INDEX(
        GROUP_CONCAT(
          `id` ORDER BY `updatedAt` DESC, `id` DESC
        ), 
        ",", 1
      ), ","),
      ""
    ) AS `older`
  FROM `terms_attributes`
  WHERE NOT ISNULL(`termId`) AND ' . $dataTypeId_NOT_IN . '
  GROUP BY CONCAT(`termId`, "-", `dataTypeId`, "-", `type`)
  HAVING COUNT(`id`) > 1
')->fetchAll(PDO::FETCH_COLUMN);

// Delete older duplicates
foreach ($olderDuplicates as $list) {
    $attr->db->getAdapter()->query('DELETE FROM `terms_attributes` WHERE `id` IN (' . $list . ')');
}

// Set termTbxId for attributes where it shouldn't be but NULL
$attr->db->getAdapter()->query('
  UPDATE 
   `terms_attributes` `ta`
   LEFT JOIN `terms_term` `tt` ON (`ta`.`termId` = `tt`.`id`)
  SET `ta`.`termTbxId` = `tt`.`termTbxId`
  WHERE ISNULL(`ta`.`termTbxId`)
');

// TermEntry-level: Get ids of all duplicated occurrences of attributes except the most recent occurrence
$olderDuplicates = $attr->db->getAdapter()->query('
  SELECT 
    REPLACE(
       GROUP_CONCAT(`id` ORDER BY `updatedAt` DESC, `id` DESC),
       CONCAT(SUBSTRING_INDEX(
        GROUP_CONCAT(
          `id` ORDER BY `updatedAt` DESC, `id` DESC
        ), 
        ",", 1
      ), ","),
      ""
    ) AS `older`
  FROM `terms_attributes`
  WHERE ISNULL(`language`) AND ' . $dataTypeId_NOT_IN . '
  GROUP BY CONCAT(`termEntryId`, "-", `dataTypeId`, "-", `type`)
  HAVING COUNT(`id`) > 1
')->fetchAll(PDO::FETCH_COLUMN);

// Delete older duplicates
foreach ($olderDuplicates as $list) {
    $attr->db->getAdapter()->query('DELETE FROM `terms_attributes` WHERE `id` IN (' . $list . ')');
}

// Language-level: Get ids of all duplicated occurrences of attributes except the most recent occurrence
$olderDuplicates = $attr->db->getAdapter()->query('
  SELECT 
    REPLACE(
       GROUP_CONCAT(`id` ORDER BY `updatedAt` DESC, `id` DESC),
       CONCAT(SUBSTRING_INDEX(
        GROUP_CONCAT(
          `id` ORDER BY `updatedAt` DESC, `id` DESC
        ), 
        ",", 1
      ), ","),
      ""
    ) AS `older`
  FROM `terms_attributes`
  WHERE NOT ISNULL(`language`) AND ISNULL(`termId`) AND ' . $dataTypeId_NOT_IN . '
  GROUP BY LOWER(CONCAT(`termEntryId`, "-", `language`, "-", `dataTypeId`, "-", `type`))
  HAVING COUNT(`id`) > 1
')->fetchAll(PDO::FETCH_COLUMN);

// Delete older duplicates
foreach ($olderDuplicates as $list) {
    $attr->db->getAdapter()->query('DELETE FROM `terms_attributes` WHERE `id` IN (' . $list . ')');
}
