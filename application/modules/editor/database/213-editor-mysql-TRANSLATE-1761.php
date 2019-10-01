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
  README:
  Remove all old term collection tbx cache folders from the disc.
  Only folders for unexisting termcollections in the translate5 will be removed.
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '213-editor-mysql-TRANSLATE-1761.php'; 

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables 
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

//get the tbx import directory path
$collectionPath=editor_Models_Import_TermListParser_Tbx::getFilesystemCollectionDir();
if(!is_dir($collectionPath)){
    return;
}

//get the collection ids from the tbx folders
//the layout of the folder is tc_+ termcollection id
$dir = new DirectoryIterator($collectionPath);
$collectionIds=[];
foreach ($dir as $fileinfo) {
    if ($fileinfo->isDir() && !$fileinfo->isDot()) {
        $name=explode('_', $fileinfo->getFilename());
        if(count($name)==2 && $name[0]=='tc' && is_numeric($name[1])){
            $collectionIds[]=$name[1];
        }
    }
}

if(empty($collectionIds)){
    return;
}

//find all valid term collections
$db = Zend_Db_Table::getDefaultAdapter();
$sql = 'SELECT `id` FROM `LEK_languageresources` WHERE serviceName="TermCollection" and `id` IN ('.$db->quote($collectionIds).')';
$res = $db->query($sql, $SCRIPT_IDENTIFIER);
$existingCollections = $res->fetchAll(Zend_Db::FETCH_COLUMN);
//filter only the unexisting colections, so we delete only the unneeded folders and tbx files
$collectionIds =array_diff($collectionIds, $existingCollections);
$removedCount=0;
foreach ($collectionIds as $coll) {
    $collectionPath=editor_Models_Import_TermListParser_Tbx::getFilesystemCollectionDir().'tc_'.$coll;
    if(is_dir($collectionPath)){
        $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('Recursivedircleaner');
        /* @var $recursivedircleaner ZfExtended_Controller_Helper_Recursivedircleaner */
        $recursivedircleaner->delete($collectionPath);
        $removedCount++;
    }
}
echo 'Old tbx cache data was removed from '.$removedCount.' directories.';

