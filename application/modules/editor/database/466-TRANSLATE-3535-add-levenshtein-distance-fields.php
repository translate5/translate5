<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

set_time_limit(0);

/* @var $this ZfExtended_Models_Installer_DbUpdater */

//$this->doNotSavePhpForDebugging = false;

$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = Zend_Db_Table::getDefaultAdapter();

$db->query('ALTER TABLE `LEK_segments` ADD COLUMN IF NOT EXISTS `levenshteinOriginal` mediumint UNSIGNED DEFAULT 0 NOT NULL,
ADD COLUMN IF NOT EXISTS `levenshteinPrevious` mediumint UNSIGNED DEFAULT 0 NOT NULL,
ADD COLUMN IF NOT EXISTS `editedInStep` varchar(60) NOT NULL');

$db->query('ALTER TABLE `LEK_segment_history` ADD COLUMN IF NOT EXISTS `levenshteinOriginal` mediumint UNSIGNED DEFAULT 0 NOT NULL,
ADD COLUMN IF NOT EXISTS `levenshteinPrevious` mediumint UNSIGNED DEFAULT 0 NOT NULL,
ADD COLUMN IF NOT EXISTS `editedInStep` varchar(60) NOT NULL');

$conf = $db->getConfig();
$res = $db->query('show tables from `' . $conf['dbname'] . '` like "%LEK_segment_view_%";');
$tables = $res->fetchAll(Zend_Db::FETCH_NUM);
if (! empty($tables)) {
    foreach ($tables as $table) {
        $tableName = $table[0];
        $db->query('DROP TABLE IF EXISTS `' . $tableName . '`');
    }
}
