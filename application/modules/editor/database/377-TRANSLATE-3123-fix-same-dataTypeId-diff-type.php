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
$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renaming etc...
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '377-TRANSLATE-3123-fix-same-dataTypeId-diff-type.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

/* @var $dataType editor_Models_Terminology_Models_AttributeModel */
$dataType = ZfExtended_Factory::get(editor_Models_Terminology_Models_AttributeDataType::class);

/* @var $db Zend_Db_Adapter_Pdo_Mysql */
$db = $dataType->db->getAdapter();

// Fetch [type => id] pairs for all datatype-records
$byType = $db->query("
    SELECT `type`, `id` FROM `terms_attributes_datatype`
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Get checker
$checker = new \editor_Models_Terminology_DataTypeConsistencyCheck();

// Foreach 
foreach ($checker->checkAttributesAgainstDataTypes() as $problem) {

    // If it's a note-datatype (which have label=note but type='')
    if (!$problem['datatypeType']) {

        // If attribute-record's type is 'note'
        if ($problem['attributeType'] == 'note') {

            // Update attribute-records' elementName and type to be as per dataTypeId
            $db->query("
                UPDATE `terms_attributes` 
                SET `elementName` = '{$problem['datatypeTag']}', `type` = '{$problem['datatypeType']}' 
                WHERE `dataTypeId` = '{$problem['datatypeId']}' AND `collectionId` = '{$problem['collectionId']}'
            ");
        }

    // Else if attribute-record's dataTypeId is NULL
    } else if ($problem['datatypeType'] == 'NOT EXISTENT') {

        // It means $problem['datatypeId'] - is an id of attribute-record, affected by the problem
        $attributeId = $problem['datatypeId'];

        // If there is an existing datatype-record having type same as attribute-record type
        if ($dataTypeId = $byType[$problem['attributeType']] ?? 0) {

            // Load datatype-record by id
            $dataType->load($dataTypeId);

            // Set attribute-record's dataTypeId to be as per datatype-record found attribute-record's type
            $db->query("
                UPDATE `terms_attributes` 
                SET `dataTypeId` = '$dataTypeId', `elementName` = '{$dataType->getLabel()}'  
                WHERE `id` = '$attributeId'
            ");

        // Else if there is no existing datatype-record found by attribute-record's type
        } else {

            // Create new datatype-record
        }

    // Else if attribute-record's type is as per dataTypeId
    } else if ($problem['datatypeType'] == $problem['attributeType']) {

        // Update attribute-records' elementName to be as per dataTypeId as well
        $db->query("
            UPDATE `terms_attributes` 
            SET `elementName` = '{$problem['datatypeTag']}' 
            WHERE `dataTypeId` = '{$problem['datatypeId']}' AND `collectionId` = '{$problem['collectionId']}'
        ");

    // Else
    } else {

        // If there is an existing datatype-record having type same as attribute-record type
        if ($dataTypeId = $byType[$problem['attributeType']] ?? 0) {

            // Load datatype-record by id
            $dataType->load($dataTypeId);

            // Set attribute-record's dataTypeId to be as per datatype-record found attribute-record's type
            $db->query("
                UPDATE `terms_attributes`
                SET `dataTypeId` = '$dataTypeId', `elementName` = '{$dataType->getLabel()}'  
                WHERE 1
                  AND `dataTypeId`   = '{$problem['datatypeId']}'
                  AND `collectionId` = '{$problem['collectionId']}'
                  AND `elementName`  = '{$problem['attributeTag']}'
                  AND `type`         = '{$problem['attributeType']}'
            ");

        // Else
        } else {

            // Init new datatype-record
            $dataType->init([
                'label' => $problem['attributeTag'],
                'type'  => $problem['attributeType'],
            ]);

            // Save it
            $dataType->save();

            // Add to dict to be further available
            $dataTypeId = $byType[$dataType->getType()] = $dataType->getId();

            // Update attribute-records' elementName to be as per dataTypeId as well
            $db->query("
                UPDATE `terms_attributes` 
                SET `dataTypeId` = '$dataTypeId' 
                WHERE 1
                  AND `dataTypeId`   = '{$problem['datatypeId']}'
                  AND `collectionId` = '{$problem['collectionId']}'
                  AND `elementName`  = '{$problem['attributeTag']}'
                  AND `type`         = '{$problem['attributeType']}'
            ");
        }
    }
}

// Foreach dataTypeId having more than 1 type usages
/*foreach ($diff as $dataTypeId => $types) {

    // Load datatype-record by id
    $dataType->load($dataTypeId);

    // Prepare data for new datatype-record
    $dataTypeCtor = $dataType->toArray(); unset($dataTypeCtor['id'], $dataTypeCtor['type']);

    // Get value of type-prop defined for that record
    $correctType = $dataType->getType();

    // Foreach type
    foreach (explode(',', $types) as $type) {

        // If $type is not as per dataTypeId
        if ($type != $correctType) {

            // If such $type is NOT already exist within some another datatype-record
            if (!isset($byType[$type])) {

                // Create new datatype-record based on current one but with different $type
                $dataType_new = clone $dataType;
                $dataType_new->init($dataTypeCtor);
                $dataType_new->setType($type);
                $dataType_new->save();

                // Append that to $byType
                $byType[$type] = $dataType_new->getId();
            }

            // Update usages
            $db->query("
                UPDATE `terms_attributes` 
                SET `dataTypeId` = '{$byType[$type]}' 
                WHERE `dataTypeId` = '{$dataTypeId}' AND `type` = ?
            ", $type);
        }
    }

    // Make sure attribute-record's elementName to be same as per dataTypeId
    $db->query("
        UPDATE `terms_attributes` `ta`, `terms_attributes_datatype` `tad`
        SET `ta`.`elementName` = `tad`.`label`
        WHERE `tad`.`id` = `ta`.`dataTypeId`
    ");

    // Fix cases when attribute-record's dataTypeId is NULL, but type is
    $db->query("
        UPDATE `terms_attributes` `ta`, `terms_attributes_datatype` `tad`
        SET `ta`.`dataTypeId` = `tad`.`id`
        WHERE ISNULL(`ta`.`dataTypeId`) AND `tad`.`type` = `ta`.`type`    
    ");
}*/