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
	    $this->checkAndMakeTempImportFolder();
	    $this->unzip();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see editor_Models_Import_DataProvider_Abstract::archiveImportedData()
	 */
	public function archiveImportedData($filename = null) {
	    $config = Zend_Registry::get('config');
	    if(!$config->runtimeOptions->import->createArchivZip){
	        unlink($this->importZip);
	        return;
	    }
	    $target = $this->getZipArchivePath($filename);
	    if(file_exists($target)) {
	        //DataProvider Zip: TaskData Import Archive Zip already exists
	        throw new editor_Models_Import_DataProvider_Exception('E1243', [
	            'task' => $this->task,
	            'target' => $target,
	        ]);
	    }
	    rename($this->importZip, $target);
	}

	/**
	 * extrahiert das geholte Zip File, bricht bei Fehlern ab
	 */
	protected function unzip() {
		$zip = new ZipArchive;
		if (!$zip->open($this->importZip)) {
		    //DataProvider Zip: zip file could not be opened
		    throw new editor_Models_Import_DataProvider_Exception('E1241', [
		        'task' => $this->task,
		        'zip' => $this->importZip,
		    ]);
		}
		if(!$zip->extractTo($this->importFolder)){
		    //DataProvider Zip: content from zip file could not be extracted
		    throw new editor_Models_Import_DataProvider_Exception('E1242', [
		        'task' => $this->task,
		        'zip' => $this->importZip,
		    ]);
		}
		$zip->close();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see editor_Models_Import_DataProvider_Abstract::postImportHandler()
	 */
	public function postImportHandler() {
	    $this->removeTempFolder();
	}

	/**
	 * (non-PHPdoc)
	 * @see editor_Models_Import_DataProvider_Abstract::handleImportException()
	 */
	public function handleImportException(Exception $e) {
	    $this->removeTempFolder();
	}
	
    public function __sleep() {
        $parent = parent::__sleep();
        $parent[] = 'importZip';
        return $parent;
    }
}