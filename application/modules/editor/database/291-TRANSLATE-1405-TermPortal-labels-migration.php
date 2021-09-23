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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/***
 * Removes duplicates data type labels. Data type will be removed from terms_attributes_datatype
 * only if the duplicate data type (label,type) is not assigned to any attribute, it is not tbx basic and there is no labelText for it.
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

/* @var $this ZfExtended_Models_Installer_DbUpdater */

$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = Zend_Db_Table::getDefaultAdapter();




// load all duplicated attribute data types.
$stm = $db->query('select group_concat(terms_attributes_datatype.id) as duplicates,count(*) as groupCount from terms_attributes_datatype group by label,type having groupCount>1;');
try {
    $result = $stm->fetchAll();
    if (!empty($result)) {
        foreach ($result as $res) {
            $ids = '"'.str_replace(',','","',$res['duplicates']).'"';

            error_log("Migration for duplicates datatypes:".$ids);

            $dataTypes = $db->query('SELECT * FROM terms_attributes_datatype WHERE id IN('.$ids.')')->fetchAll();

            $toUseType = [];
            $toUseLabelText = null;

            // find which data type should be used for this duplicate group
            foreach ($dataTypes as $type){
                // always the tbx basic from duplicate group should be used
                if((int)$type['isTbxBasic'] === 1){
                    $toUseType = $type;
                }
                // check if there is already translated labelText in one of the duplicates
                if(empty($toUseLabelText)){
                    $toUseLabelText = $type['labelText'];
                }
            }
            // if no tbx basic match is found, use the first match
            if(empty($toUseType)){
                $toUseType = $dataTypes[0];
                $toUseType['labelText'] = $toUseLabelText;
            }

            error_log("To be used as only attribute:".print_r($toUseType,1));

            // update the dataTypeId for all duplicates with the which should be used
            $db->query("UPDATE `terms_attributes` SET `dataTypeId`= ".$toUseType['id']." WHERE `dataTypeId` IN(".$ids.");");

            // remove all not needed dataTypes
            $toRemove = array_diff( explode(',',$res['duplicates']), [$toUseType['id']]);
            $toRemove = '"'.implode(',"',$toRemove).'"';
            error_log("Will be removed:".$toRemove);

            $db->query("DELETE FROM `terms_attributes_datatype` WHERE `id` IN(".$toRemove.");");

            error_log("End of migration for ".$ids);
        }
    }
} catch (Zend_Db_Statement_Exception $e) {
}



