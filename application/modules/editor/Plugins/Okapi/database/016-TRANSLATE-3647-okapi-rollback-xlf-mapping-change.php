<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;

set_time_limit(0);

/* @var $this ZfExtended_Models_Installer_DbUpdater */

$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7 || ! isset($config)) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

// $this->doNotSavePhpForDebugging = true;
$xlfExtensions = editor_Models_Import_FileParser_Xlf::getFileExtensions();
$db = Zend_Db_Table::getDefaultAdapter();
$updateTime = (int) $db->fetchOne('SELECT UNIX_TIMESTAMP(created) FROM Zf_dbversion WHERE filename="015-TRANSLATE-3647-okapi-integration-enable-upload-of-fprm-and-pln-files.php" ORDER BY id DESC');
if (! $updateTime) {
    return;
}
$updateTime += 7200; // add 2h

$bconf = new BconfEntity();
$bconfAll = $bconf->loadAll();

foreach ($bconfAll as $bconfData) {
    try {
        $bconf = new BconfEntity();
        $bconf->load($bconfData['id']);

        $updatedMapping = filemtime($bconf->getDataDirectory() . '/extensions-mapping.txt');
        if ($updatedMapping > $updateTime) {
            // We do not remove xlf/xliff, if the file-date of the extension.mapping is newer than 2h after the time, the upgrade was run
            continue;
        }

        $extensionMapping = $bconf->getExtensionMapping();
        $extensionMapping->removeExtensions($xlfExtensions);

        if ($bconf->isSystemDefault()) {
            $extensionMapping->addFilter('okf_xliff@translate5-xliff-with-CDATA', ['xliff-cdata']);
        }

        $extensionMapping->rescanFilters();
        $bconf->repackIfOutdated(true);
    } catch (Exception $e) {
        $msg = 'ERROR rescanning filters for bconf ' . $bconf->getId() . ', "' .
            $bconf->getName() . '": ' . $e->getMessage();
        error_log($msg);
    }
}
