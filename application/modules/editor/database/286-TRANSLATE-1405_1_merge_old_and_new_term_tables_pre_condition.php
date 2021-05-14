<?php
///*
//START LICENSE AND COPYRIGHT
//
// This file is part of translate5
//
// Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
//
// Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
//
// This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
// as published by the Free Software Foundation and appearing in the file agpl3-license.txt
// included in the packaging of this file.  Please review the following information
// to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
// http://www.gnu.org/licenses/agpl.html
//
// There is a plugin exception available for use with this release of translate5 for
// translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
// Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
// folder of translate5.
//
// @copyright  Marc Mittag, MittagQI - Quality Informatics
// @author     MittagQI - Quality Informatics
// @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
//             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
//
//END LICENSE AND COPYRIGHT
//*/
///*
// * Prepare and FIX old termPortal LEK_term_ tables for new terms_ table migration
// * Here we need to get term IDs to associate for corrupted termEntries
// */
//
//set_time_limit(0);
//
////uncomment the following line, so that the file is not marked as processed:
////$this->doNotSavePhpForDebugging = false;
//
///* @var $this ZfExtended_Models_Installer_DbUpdater */
//
//$argc = count($argv);
//if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
//    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
//}
//
//$db = Zend_Db_Table::getDefaultAdapter();
//$db->query("SET @@group_concat_max_len = 2048;");
//$db->query("ALTER TABLE LEK_term_entry ADD tmpTermId LONGTEXT;");
//$db->query("ALTER TABLE LEK_term_attributes ADD tmpLangSetGuid LONGTEXT;");
//$db->query("DELETE FROM LEK_terms WHERE term IS NULL OR term = '';");
//
//$startEntryInsertTime = microtime(true);
//$sqlLekAttributes = '
//UPDATE LEK_term_attributes t1, (
//    SELECT collectionId, termEntryId, attrLang, UUID() newLangSetUuid -- oder language, evaluieren welches.
//    FROM LEK_term_attributes
//    WHERE NOT attrLang IS NULL -- a langset entry must have a language
//    AND not termEntryId IS NULL -- why are there attributes with termEntry = null, must not be, should be solved first by filling termEntryId via termId
//    GROUP BY collectionId, termEntryId, attrLang) t2
//SET t1.tmpLangSetGuid = t2.newLangSetUuid
//WHERE t1.collectionId = t2.collectionId
//  AND t1.termEntryId = t2.termEntryId
//  AND t1.attrLang = t2.attrLang;';
//$resLekAttributes = $db->query($sqlLekAttributes);
//
//
////$sqlLekTermEntries = '
////INSERT INTO LEK_term_entry (LEK_term_entry.collectionId, LEK_term_entry.groupId, LEK_term_entry.tmpTermId)
////SELECT LEK_terms.collectionId, LEK_terms.groupId, GROUP_CONCAT(LEK_terms.id)
////FROM LEK_terms WHERE LEK_terms.termEntryId is null
////GROUP BY LEK_terms.collectionId, LEK_terms.groupId;';
////
////$resLekTermEntries = $db->query($sqlLekTermEntries);
//
////$sqlGet = '
////SELECT LEK_term_entry.id, LEK_term_entry.collectionId, LEK_term_entry.groupId, LEK_term_entry.tmpTermId
////FROM LEK_term_entry WHERE LEK_term_entry.tmpTermId is not null;';
////
////$resTermEntries = $db->query($sqlGet);
////$result = $resTermEntries->fetchAll();
//
//$sqlUpdate = "UPDATE LEK_terms LekT, (
//                SELECT LEK_terms.id, LEK_terms.collectionId, LEK_terms.groupId, LEK_terms.termEntryId
//                FROM LEK_terms WHERE LEK_terms.termEntryId is null
//                GROUP BY LEK_terms.collectionId, LEK_terms.groupId) LekTR
//                SET LekT.termEntryId = LekTR.termEntryId
//                WHERE LekT.id = LekTR.id";
//
////if (!empty($result)) {
////    foreach ($result as $termEntry) {
////        foreach(explode(',', $termEntry['tmpTermId']) as $termId){
////            $sqlUpdate = "UPDATE LEK_terms
////                            SET LEK_terms.termEntryId = " . $termEntry['id'] . "
////                            WHERE LEK_terms.id = " . $termId;
////            $db->query($sqlUpdate);
////        }
////    }
////}
//
//$res = $db->query("ALTER TABLE LEK_term_entry DROP COLUMN tmpTermId;");
