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
  README:
  This script fixes the missing TBX entries from TRANSLATE-403
  The script is to be used only by DBUpdater!
 */
set_time_limit(0);

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$task = ZfExtended_Factory::get('editor_Models_Task');
/* @var $task editor_Models_Task */
$tasks = $task->loadAll();
$config = Zend_Registry::get('config');
if (isset($config->runtimeOptions->translate403)) {
    $toProcess = $config->runtimeOptions->translate403->toArray();
} else {
    $toProcess = null;
}

$termFormatter = function ($term) {
    return $term['mid'] . ' - ' . $term['term'];
};

foreach ($tasks as $taskContent) {
    if (! empty($toProcess) && ! in_array($taskContent['taskGuid'], $toProcess)) {
        continue;
    }
    $tbx = ZfExtended_Factory::get('editor_Models_Import_TermListParser_TbxReimportMissing');
    /* @var $tbx editor_Models_Import_TermListParser_TbxReimportMissing */
    $task->init($taskContent);
    if (! $tbx->importMissing($task)) {
        echo 'Could not re import TBX of Task ' . $task->getTaskName() . ' (' . $task->getTaskGuid() . ')' . "\n";

        continue;
    }

    echo "\n" . $task->getTaskName() . ' (' . $task->getTaskGuid() . ')' . "\n";
    echo 'Terms already in DB: ' . $tbx->getAlreadyExistingTerms() . "\n";
    $added = $tbx->getInsertedMissing();
    if (! empty($added)) {
        echo 'Terms added to this Task: ' . "\n";
        echo join("\n", array_map($termFormatter, $added));
    }
}
