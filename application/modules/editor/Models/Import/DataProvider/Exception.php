<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

class editor_Models_Import_DataProvider_Exception extends editor_Models_Import_Exception {
        /**
         * @var string
         */
        protected $domain = 'editor.import.dataprovider';
        
        static protected $localErrorCodes = [
            'E1241' => 'DataProvider Zip: zip file could not be opened: "{zip}"',
            'E1242' => 'DataProvider Zip: content from zip file could not be extracted: "{zip}"',
            'E1243' => 'DataProvider Zip: TaskData Import Archive Zip already exists: {target}',
            'E1244' => 'DataProvider SingleUpload: Uploaded file "{file}" cannot be moved to "{target}',
            'E1245' => 'DataProvider: Could not create folder "{path}"',
            'E1246' => 'DataProvider: Temporary directory does already exist - path: "{path}"',
            'E1247' => 'DataProvider Directory: Could not create archive-zip',
            'E1248' => 'DataProvider Directory: The importRootFolder "{importRoot}" does not exist!',
            'E1249' => 'DataProvider ZippedUrl: fetched file can not be saved to path {path}',
            'E1250' => 'DataProvider ZippedUrl: ZIP file could not be fetched from URL {url}',
            'E1265' => 'DataProvider Factory: The task to be cloned does not have a import archive zip! Path: {path}',
            'E1369' => 'DataProvider Project: No matching work-files where found for the task.',
            'E1372' => 'DataProvider Zip: Uploaded zip file "{file}" cannot be moved to "{target}',
            'E1384' => 'DataProvider Project: Maximum number of allowable file uploads has been exceeded.'
        ];
}