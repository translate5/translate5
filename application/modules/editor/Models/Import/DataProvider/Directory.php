<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Gets the Import Data from a directory
 * the responsibility to clean up / delete the given Import Directory is not part of this class!
 */
class editor_Models_Import_DataProvider_Directory  extends editor_Models_Import_DataProvider_Abstract {
    /**
     * @param string $pathToImportDirectory
     */
    public function __construct($pathToImportDirectory){
        $this->importFolder = $pathToImportDirectory;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Abstract::checkAndPrepare()
     */
    public function checkAndPrepare(){
        if(!is_dir($this->importFolder)){
        	throw new Zend_Exception('Der übergebene importRootFolder '.$this->importFolder.' existiert nicht.');
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Abstract::archiveImportedData()
     */
    public function archiveImportedData() {
        $config = Zend_Registry::get('config');
        if(!$config->runtimeOptions->import->createArchivZip){
        	return;
        }
        $filter = new Zend_Filter_Compress(array(
            'adapter' => 'Zip',
            'options' => array(
                'archive' => $this->taskPath.DIRECTORY_SEPARATOR.'ImportArchive.zip'
            ),
        ));
        if(!$filter->filter($this->importFolder)){
            throw new Zend_Exception('Could not create export-zip of task '.$this->taskGuid.'.');
        }
    }
}