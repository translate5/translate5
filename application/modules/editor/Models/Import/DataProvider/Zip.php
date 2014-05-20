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
* Gets the Import Data from a Zip File
*/
class editor_Models_Import_DataProvider_Zip extends editor_Models_Import_DataProvider_Abstract {
	protected $importZip;
	public function __construct($pathToZipFile){
		$this->importZip = $pathToZipFile;
	}

	/**
	 * (non-PHPdoc)
	 * @see editor_Models_Import_DataProvider_Abstract::checkAndPrepare()
	 */
	public function checkAndPrepare() {
	    $this->checkAndMakeUnzipFolder();
	    $this->unzip();
	}
	
	/**
	 * creates the temporary folder to unzip the data 
	 * @throws Zend_Exception
	 */
	protected function checkAndMakeUnzipFolder() {
	    $this->importFolder = $this->taskPath.DIRECTORY_SEPARATOR.'_tempImport';
	    if(is_dir($this->importFolder)) {
	    	throw new Zend_Exception('Temporary directory for Task GUID ' . $this->task->getTaskGuid() . ' already exists!');
	    }
	    if(!@mkdir($this->importFolder)) {
	    	throw new Zend_Exception('Temporary directory for Task GUID ' . $this->task->getTaskGuid() . ' could not be created!');
	    }
	}

	/**
	 * (non-PHPdoc)
	 * @see editor_Models_Import_DataProvider_Abstract::archiveImportedData()
	 */
	public function archiveImportedData() {
	    $config = Zend_Registry::get('config');
	    if(!$config->runtimeOptions->import->createArchivZip){
	        unlink($this->importZip);
	        return;
	    }
	    $target = $this->getZipArchivePath();
	    if(file_exists($target)) {
	        throw new Zend_Exception('TaskData Import Archive Zip already exists: '.$target);
	    }
	    rename($this->importZip, $target);
	}

	/**
	 * extrahiert das geholte Zip File, bricht bei Fehlern ab
	 */
	protected function unzip() {
		$zip = new ZipArchive;
		if (!$zip->open($this->importZip)) {
			throw new Zend_Exception('Zip Datei ' . $this->importZip . ' konnte nicht geöffnet werden!');
		}
		if(!$zip->extractTo($this->importFolder)){
			throw new Zend_Exception('Zip Datei ' . $this->importZip . ' konnte nicht entpackt werden!');
		}
		$zip->close();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see editor_Models_Import_DataProvider_Abstract::postImportHandler()
	 */
	public function postImportHandler() {
	    parent::postImportHandler();
	    $this->removeUnzipFolder();
	}

	/**
	 * (non-PHPdoc)
	 * @see editor_Models_Import_DataProvider_Abstract::handleImportException()
	 */
	public function handleImportException(Exception $e) {
	    $this->removeUnzipFolder();
	}
	
	/**
	 * deletes the temporary import folder
	 */
	protected function removeUnzipFolder() {
	    /* @var $recursivedircleaner ZfExtended_Controller_Helper_Recursivedircleaner */
	    $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
	    		'Recursivedircleaner'
	    );
	    if(isset($this->importFolder) && is_dir($this->importFolder)) {
    	    $recursivedircleaner->delete($this->importFolder);
	    }
	}
}