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
 * Gets the Import Data from single uploaded files
 */
class editor_Models_Import_DataProvider_SingleUploads  extends editor_Models_Import_DataProvider_Directory {
    /**
     * @var array
     */
    protected $review;
    protected $relais;
    protected $reference;
    protected $tbx;
    /**
     * @var Zend_Config
     */
    protected $config;
    
    /**
     * consumes all the given file paths
     * @param array $review
     * @param array $relais optional
     * @param array $reference optional
     * @param string $tbx optional
     */
    public function __construct(array $review, array $relais = array(), array $reference = array(), array $tbx = array()){
        $this->review = $review;
        $this->relais = $relais;
        $this->reference = $reference;
        $this->tbx = $tbx;
        $this->config = Zend_Registry::get('config');
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Abstract::checkAndPrepare()
     */
    public function checkAndPrepare(editor_Models_Task $task){
        $this->setTask($task);
        $this->checkAndMakeTempImportFolder();
        parent::checkAndPrepare($task);
        $this->handleReview();
        $this->handleRelais();
        $this->handleReference();
        $this->handleTbx();
    }

    /**
     * processes the review files
     */
    protected function handleReview() {
        //no if empty check, since we need a review dir. If this will be empty, the import process throws an error
        $review = $this->config->runtimeOptions->import->proofReadDirectory;
        $this->handleUploads($review, $this->review);
    }
    
    /**
     * processes the relais files
     */
    protected function handleRelais() {
        if(empty($this->relais)) {
            return;
        }
        $relais = $this->config->runtimeOptions->import->relaisDirectory;
        $this->handleUploads($relais, $this->relais);
    }
    
    /**
     * processes the reference files
     */
    protected function handleReference() {
        if(empty($this->reference)) {
            return;
        }
        $ref = $this->config->runtimeOptions->import->referenceDirectory;
        $this->handleUploads($ref, $this->reference);
    }
    
    /**
     * processes the TBX file
     */
    protected function handleTbx() {
        if(empty($this->tbx)) {
            return;
        }
        $target = $this->importFolder.DIRECTORY_SEPARATOR;
        $name = $target.DIRECTORY_SEPARATOR.$this->tbx['name'];
        if(!move_uploaded_file($this->tbx['tmp_name'], $name)) {
            $this->handleCannotMove($this->tbx, $target);
        }
    }
    
    /**
     * moves the given files to the desired folder
     * @param string $folder
     * @param array $files
     */
    protected function handleUploads($folder, array $files) {
        $target = $this->importFolder.DIRECTORY_SEPARATOR;
        if(!empty($folder)) {
            $target .= $folder;
            $this->mkdir($target);
        }
        foreach($files as $file) {
            $name = $target.DIRECTORY_SEPARATOR.$file['name'];
            if(!move_uploaded_file($file['tmp_name'], $name)) {
                $this->handleCannotMove($file, $target);
            }
        }
    }
    
    /**
     * reusable exception thrower
     * @param array $file
     * @param string $target
     * @throws ZfExtended_Exception
     */
    protected function handleCannotMove($file, $target) {
        try {
            $offlineTestcase = Zend_Registry::get('offlineTestcase');
        } catch (Exception $exc) {
            $offlineTestcase = false;
        }
        if($offlineTestcase===true){
            if(\copy($file['tmp_name'], $target.'/'.$file['name'])) {
                return;
            }
        }
        throw new ZfExtended_Exception('Uploaded file '.$file['name'].' cannot be moved to '.$target);
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
}