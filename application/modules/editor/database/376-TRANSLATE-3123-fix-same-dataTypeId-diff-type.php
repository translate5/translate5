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
$SCRIPT_IDENTIFIER = '377-TRANSLATE-3123-fix-same-dataTypeId-diff-type.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

/* @var $dataType editor_Models_Terminology_Models_AttributeDataType */
$dataType = ZfExtended_Factory::get(editor_Models_Terminology_Models_AttributeDataType::class);

/* @var $db Zend_Db_Adapter_Pdo_Mysql */
$db = $dataType->db->getAdapter();

// Fetch [type => id] pairs for all datatype-records
$byType = $db->query("
    SELECT `type`, `id` FROM `terms_attributes_datatype`
")->fetchAll(PDO::FETCH_KEY_PAIR);

/**
 * Find datatype-record by attribute-record's type (e.g $problem['attributeType']),
 * or create new datatype-record based on attribute-record's type and elementName (e.g. $problem['attributeTag'])
 *
 * @param editor_Models_Terminology_Models_AttributeDataType $dataType
 * @param array $problem
 * @param array $byType
 * @throws Zend_Db_Statement_Exception
 * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
 * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
 * @throws ZfExtended_Models_Entity_NotFoundException
 */
function findOrCreateDataType(editor_Models_Terminology_Models_AttributeDataType $dataType, $problem, &$byType) {

    // If there is an existing datatype-record having type same as attribute-record type
    if ($dataTypeId = $byType[$problem['attributeType']] ?? 0) {

        // Load datatype-record by id
        $dataType->load($dataTypeId);

    // Else
    } else {

        // Init new datatype-record
        $dataType->init([
            'label' => $problem['attributeTag'],
            'type'  => $problem['attributeType']
        ]);

        // Save it
        $dataType->save();

        // Add to dict to be further available
        $byType[$dataType->getType()] = $dataType->getId();
    }
}

/**
 * Update attributes at the step where we surely know the correct values for `dataTypeId`, `type` and `elementName`
 *
 * @param editor_Models_Terminology_Models_AttributeDataType $dataType
 * @param $problem
 * @param string $whereColumn
 */
function updateAttributes(editor_Models_Terminology_Models_AttributeDataType $dataType, $problem, $whereColumn = 'dataTypeId') {

    // Set attribute-record's dataTypeId to be as per datatype-record found attribute-record's type
    $dataType->db->getAdapter()->query("
        UPDATE `terms_attributes` 
        SET 
            `dataTypeId` = '{$dataType->getId()}', 
            `elementName` = '{$dataType->getLabel()}',
            `type` = '{$dataType->getType()}'
        WHERE 1
          AND `$whereColumn` = '{$problem['datatypeId']}'
          AND `collectionId` = '{$problem['collectionId']}'
          AND `elementName`  = '{$problem['attributeTag']}'
          AND `type`         = '{$problem['attributeType']}'
    ");
}

// Get checker
$checker = new \editor_Models_Terminology_DataTypeConsistencyCheck();

// Foreach 
foreach ($checker->checkAttributesAgainstDataTypes() as $problem) {

    // If it's a note-datatype (e.g. have label='note' and type='')
    if (!$problem['datatypeType']) {

        // If attribute-record's type is 'note', it means we just need to load note-datatype-record
        // into $dataType model instance for further processing, but for doing that we just need to
        // make sure datatype-record's id to be picked from $byType[''] in findOrCreateDataType() call
        $_problem = $problem['attributeType'] == 'note'
            ? ['attributeType' => '']
            : $problem;

        // Load correct datatype-record into $dataType model instance (such record is created, if need)
        findOrCreateDataType($dataType, $_problem, $byType);

        // Update attributes
        updateAttributes($dataType, $problem);

    // Else
    } else {

        // If attribute-record's dataTypeId is NULL then value of $problem['datatypeId']
        // is an id of attribute-record rather than id of datatype-record
        $whereColumn = $problem['datatypeType'] == 'NOT EXISTENT' ? 'id' : 'dataTypeId';

        // Load correct datatype-record into $dataType model instance (such record is created, if need)
        findOrCreateDataType($dataType, $problem, $byType);

        // Update attributes
        updateAttributes($dataType, $problem, $whereColumn);
    }
}