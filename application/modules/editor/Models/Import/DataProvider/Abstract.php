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
 * Provides the import data as an abstract interface to the import process
 */
abstract class editor_Models_Import_DataProvider_Abstract {
    const TASK_ARCHIV_ZIP_NAME = 'ImportArchiv.zip';
    protected $task;
    protected $taskPath;
    protected $importFolder;
    /**
     * DataProvider specific Checks (throwing Exceptions) and actions to prepare import data
     */
    abstract public function checkAndPrepare();

    /**
     * DataProvider specific method to create the import archive
     */
    abstract public function archiveImportedData();
    
    /**
     * returns the the absolute import path, mainly used by the import class
     * @return string 
     */
    public function getAbsImportPath(){
    	return $this->importFolder;
    }
    
    /**
     * creates a temporary folder to contain the import data 
     * @throws Zend_Exception
     */
    protected function checkAndMakeTempImportFolder() {
        $this->importFolder = $this->taskPath.DIRECTORY_SEPARATOR.'_tempImport';
        if(is_dir($this->importFolder)) {
            throw new Zend_Exception('Temporary directory for Task GUID ' . $this->task->getTaskGuid() . ' already exists!');
        }
        $msg = 'Temporary directory for Task GUID ' . $this->task->getTaskGuid() . ' could not be created!';
        $this->mkdir($this->importFolder, $msg);
    }
    
    /**
     * deletes the temporary import folder
     */
    protected function removeTempFolder() {
        /* @var $recursivedircleaner ZfExtended_Controller_Helper_Recursivedircleaner */
        $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
                    'Recursivedircleaner'
        );
        if(isset($this->importFolder) && is_dir($this->importFolder)) {
            $recursivedircleaner->delete($this->importFolder);
        }
    }
    
    /**
     * exception throwing mkdir
     * @param string $path
     * @param string $errMsg
     * @throws Zend_Exception
     */
    protected function mkdir($path, $errMsg = null) {
        if(empty($errMsg)) {
            $errMsg = 'Could not create folder '.$path;
        }
        if(!@mkdir($path)) {
            throw new Zend_Exception($errMsg);
        }
    }
    
    /**
     * sets the internal used task object
     * @param editor_Models_Task $task
     */
    public function setTask(editor_Models_Task $task){
        $this->taskPath = $task->getAbsoluteTaskDataPath();
        $this->task = $task;
    }
    
    /**
     * returns the fix defined (=> final) archiveZipPath
     * @return string
     */
    protected final function getZipArchivePath() {
        return $this->taskPath.DIRECTORY_SEPARATOR.self::TASK_ARCHIV_ZIP_NAME;
    }
    
    /**
     * is called after import process by the import class. 
     */
    public function postImportHandler() {
        //we should use __CLASS__ here, if not we loose bound handlers to base class in using subclasses
        $eventManager = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        $eventManager->trigger('beforeArchiveImportedData', $this, array());
        $this->archiveImportedData();
    }
    
    /**
     * stub method, is called after an execption occured in the import process. 
     * To be overridden.
     */
    public function handleImportException(Exception $e) {}
}