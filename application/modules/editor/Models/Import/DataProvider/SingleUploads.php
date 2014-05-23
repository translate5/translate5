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
 * Gets the Import Data from single uploaded files
 */
class editor_Models_Import_DataProvider_SingleUploads  extends editor_Models_Import_DataProvider_Directory {
    /**
     * @var array
     */
    protected $proofRead;
    protected $relais;
    protected $reference;
    protected $tbx;
    /**
     * @var Zend_Config
     */
    protected $config;
    
    /**
     * consumes all the given file paths
     * @param array $proofRead
     * @param array $relais optional
     * @param array $reference optional
     * @param string $tbx optional
     */
    public function __construct(array $proofRead, array $relais = array(), array $reference = array(), array $tbx = array()){
        $this->proofRead = $proofRead;
        $this->relais = $relais;
        $this->reference = $reference;
        $this->tbx = $tbx;
        $this->config = Zend_Registry::get('config');
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Abstract::checkAndPrepare()
     */
    public function checkAndPrepare(){
        $this->checkAndMakeTempImportFolder();
        parent::checkAndPrepare();
        $this->handleProofRead();
        $this->handleRelais();
        $this->handleReference();
        $this->handleTbx();
    }

    /**
     * processes the proofread files
     */
    protected function handleProofRead() {
        //no if empty check, since we need a proofread dir. If this will be empty, the import process throws an error
        $proofRead = $this->config->runtimeOptions->import->proofReadDirectory;
        $this->handleUploads($proofRead, $this->proofRead);
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
            $this->handleCannotMove($this->tbx['name'], $target);
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
                $this->handleCannotMove($file['name'], $target);
            }
        }
    }
    
    /**
     * reusable exception thrower
     * @param string $file
     * @param string $target
     * @throws ZfExtended_Exception
     */
    protected function handleCannotMove($file, $target) {
        throw new ZfExtended_Exception('Uploaded file '.$file.' cannot be moved to '.$target);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Abstract::postImportHandler()
     */
    public function postImportHandler() {
        parent::postImportHandler();
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