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

/* @var $config Zend_Config */
/* @var $this ZfExtended_Models_Installer_DbUpdater */

//FIXME convert me to CLI script!
return;

use MittagQI\Translate5\Segment\SegmentHistoryAggregation;
use MittagQI\Translate5\Statistics\SQLite;

$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

if (! isset($config) || empty($config->resources->db->statistics?->sqliteDbname)) {
    throw new ZfExtended_Exception(
        __FILE__ . ': searching for SQLite DB filename in config FAILED - stop migration script.'
    );
}

$dbFileName = trim($config->resources?->db?->statistics?->sqliteDbname);
mkdir(dirname($dbFileName), recursive: true);
touch($dbFileName);
chmod($dbFileName, 0666);

$this->output('Created writeable SQLite DB File: ' . $dbFileName);

$db = SQLite::create();

$tableSql = [
    SegmentHistoryAggregation::TABLE_NAME => 'CREATE TABLE %s (
taskGuid TEXT,
userGuid TEXT,
workflowName TEXT,
workflowStepName TEXT,
segmentId INTEGER,
editable INTEGER,
duration INTEGER,
matchRate INTEGER,
langResType TEXT,
langResId INTEGER,
PRIMARY KEY (taskGuid,segmentId,workflowStepName,userGuid)
)',
    SegmentHistoryAggregation::TABLE_NAME_LEV => 'CREATE TABLE %s (
taskGuid TEXT,
userGuid TEXT,
workflowName TEXT,
workflowStepName TEXT,
segmentId INTEGER,
editable INTEGER,
lastEdit INTEGER,
levenshteinOriginal INTEGER,
levenshteinPrevious INTEGER,
matchRate INTEGER,
langResType TEXT,
langResId INTEGER,
PRIMARY KEY (taskGuid,segmentId,workflowStepName)
)',
];

foreach ($tableSql as $tableName => $sql) {
    if (! $db->tableExists($tableName)) {
        $db->query(sprintf($sql, $tableName));
    }

    if ($db->tableExists($tableName)) {
        $this->output('SQLite table created successfully: ' . $tableName);
    } else {
        throw new ZfExtended_Exception(
            __FILE__ . ': create SQLite table ' . $tableName . ' FAILED - stop migration script.'
        );
    }
}
