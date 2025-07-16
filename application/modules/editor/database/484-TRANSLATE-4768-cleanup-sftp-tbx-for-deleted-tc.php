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

/**
 * Remove all old term collection tbx cache folders from the disc.
 * Only folders for non-existing term-collections in the translate5 will be removed.
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '484-TRANSLATE-4768-cleanup-sftp-tbx-for-deleted-tc.php';

/* @var ZfExtended_Models_Installer_DbUpdater $this */

/**
 * define database credential variables
 */
$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

// Get array of TermCollections ids existing in db
$existingIdA = Zend_Db_Table
    ::getDefaultAdapter()
        ->query("SELECT `id` FROM `LEK_languageresources` WHERE serviceName='TermCollection'")
        ->fetchAll(PDO::FETCH_COLUMN);

// Foreach dir where TermCollections' tbx files are kept
foreach (editor_Models_Import_TermListParser_Tbx::getCollectionImportBaseDirectories() as $base) {
    // Quantity of deleted dirs
    $cleanedQty = 0;

    // Prepare iterator
    $dir = new DirectoryIterator($base);

    // Foreach item
    foreach ($dir as $fileinfo) {
        // If it's not a dir, or is but it's name is not like tc_XXX - skip
        if (! $fileinfo->isDir()
            || ! preg_match('~^tc_(?<collectionId>\d+)$~', $name = $fileinfo->getFilename(), $m)) {
            continue;
        }

        // If dir belongs to existing TermCollection - skip
        if (in_array($m['collectionId'], $existingIdA)) {
            continue;
        }

        // Delete dir
        ZfExtended_Utils::recursiveDelete("$base/$name");

        // Increment counter
        $cleanedQty++;
    }

    // Print result
    echo " - Removed $cleanedQty tbx directories from $base.\n";
}
