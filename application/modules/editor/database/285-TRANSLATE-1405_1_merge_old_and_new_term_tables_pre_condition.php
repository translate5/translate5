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
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

/* @var $this ZfExtended_Models_Installer_DbUpdater */

$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = Zend_Db_Table::getDefaultAdapter();
$db->query("SET @@group_concat_max_len = 2048;");
$db->query("ALTER TABLE LEK_term_entry ADD tmpTermId LONGTEXT;");
$db->query("DELETE FROM LEK_terms WHERE term IS NULL OR term = '';");

$startEntryInsertTime = microtime(true);
$sql = '
INSERT INTO LEK_term_entry (LEK_term_entry.collectionId, LEK_term_entry.groupId, LEK_term_entry.tmpTermId)
SELECT LEK_terms.collectionId, LEK_terms.groupId, GROUP_CONCAT(LEK_terms.id)
FROM LEK_terms WHERE LEK_terms.termEntryId is null
GROUP BY LEK_terms.collectionId, LEK_terms.groupId;';

$res = $db->query($sql);
$statisticInsertEntry = "ENTRY sec.: " . (microtime(true) - $startEntryInsertTime)
    . " - Memory usage: " . ((memory_get_usage() / 1024) / 1024) .' MB';


$sqlGet = '
SELECT LEK_term_entry.id, LEK_term_entry.collectionId, LEK_term_entry.groupId, LEK_term_entry.tmpTermId
FROM LEK_term_entry WHERE LEK_term_entry.tmpTermId is not null;';

$resTermEntries = $db->query($sqlGet);
$result = $resTermEntries->fetchAll();

$startEntryTime = microtime(true);
if (!empty($result)) {
    foreach ($result as $termEntry) {
        foreach(explode(',', $termEntry['tmpTermId']) as $termId){
            $sqlUpdate = "UPDATE LEK_terms 
                            SET LEK_terms.termEntryId = " . $termEntry['id'] . " 
                            WHERE LEK_terms.id = " . $termId;
            $db->query($sqlUpdate);
        }
    }
}
$statisticEntry = "ENTRY sec.: " . (microtime(true) - $startEntryTime)
    . " - Memory usage: " . ((memory_get_usage() / 1024) / 1024) .' MB';

$res = $db->query("ALTER TABLE LEK_term_entry DROP COLUMN tmpTermId;");
