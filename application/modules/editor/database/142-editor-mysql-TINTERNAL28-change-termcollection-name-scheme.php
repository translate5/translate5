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
 Change the termcollection folder name to the new scheme
 New name scheme is "tc_" + taskGuid
 */
set_time_limit(0);


/* @var $this ZfExtended_Models_Installer_DbUpdater */

$ds=DIRECTORY_SEPARATOR;
$importFilesDir=APPLICATION_PATH.$ds.'..'.$ds.'data'.$ds.'tbx-import'.$ds.'tbx-for-filesystem-import';

if(is_dir($importFilesDir)){
    $it = new FilesystemIterator($importFilesDir, FilesystemIterator::SKIP_DOTS);
    foreach ($it as $fileinfo) {
        
        //the folder starting prefix
        $prefix="Term Collection for Task:";
        
        //check if the curent folder starts with prefix
        if (strncmp($fileinfo->getFilename(), $prefix, strlen($prefix)) === 0){
            
            //find the taskhuid from the folder name
            if(preg_match('/\{?[a-f\d]{8}-(?:[a-f\d]{4}-){3}[a-f\d]{12}\}?/', $fileinfo->getFilename(), $matches)){
                $guid=$matches[0];
                $newName='tc_'.$guid;
                
                $oldName=$importFilesDir.$ds.$fileinfo->getFilename();
                $newName=$importFilesDir.$ds.$newName;
                //rename the folder
                rename($oldName, $newName);
                
                error_log("Tbx import folder was renamed:oldname->".$oldName.", newname:".$newName);
            }
            
        }
    }
}

//$this->doNotSavePhpForDebugging=false;
