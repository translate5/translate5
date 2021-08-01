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
    const TASK_TEMP_IMPORT = '_tempImport';

    protected $task;
    protected $taskPath;
    protected $importFolder;

    /**
     * DataProvider specific Checks (throwing Exceptions) and actions to prepare import data
     * @param editor_Models_Task $task
     */
    abstract public function checkAndPrepare(editor_Models_Task $task);

    /**
     * sets the internal used task object
     * @param editor_Models_Task $task
     */
    protected function setTask(editor_Models_Task $task){
        $this->taskPath = $task->getAbsoluteTaskDataPath();
        $this->task = $task;
    }

    /**
     * DataProvider specific method to create the import archive
     * @param string $filename optional, provide a different archive file name
     */
    abstract public function archiveImportedData($filename = null);

    /**
     * returns the the absolute import path, mainly used by the import class
     * @return string
     */
    public function getAbsImportPath(){
    	return $this->importFolder;
    }

    /**
     * creates a temporary folder to contain the import data
     * @throws editor_Models_Import_DataProvider_Exception
     */
    protected function checkAndMakeTempImportFolder() {
        $this->importFolder = $this->taskPath.DIRECTORY_SEPARATOR.self::TASK_TEMP_IMPORT;
        if(is_dir($this->importFolder)) {
            //DataProvider: Temporary directory does already exist - path: "{path}"'
            throw new editor_Models_Import_DataProvider_Exception('E1246', [
                'task' => $this->task,
                'path' => $this->importFolder,
            ]);
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
     * @throws editor_Models_Import_DataProvider_Exception
     */
    protected function mkdir(string $path) {
        if(!@mkdir($path)) {
            //DataProvider: Could not create folder "{path}"
            throw new editor_Models_Import_DataProvider_Exception('E1245', [
                'task' => $this->task,
                'path' => $path,
            ]);
        }
    }

    /**
     * returns the fix defined (=> final) archiveZipPath
     * @param string $filename optional, provide a different filename as the default
     * @return string
     */
    protected final function getZipArchivePath($filename = null) {
        if(empty($filename)){
            return $this->taskPath.DIRECTORY_SEPARATOR.self::TASK_ARCHIV_ZIP_NAME;
        }
        return $this->taskPath.DIRECTORY_SEPARATOR.$filename;
    }

    /**
     * is bound to importCleanup event after import process by the import class.
     * stub method, to be overridden.
     */
    public function postImportHandler() {
        //intentionally empty
    }

    /**
     * stub method, is called after an execption occured in the import process.
     * To be overridden.
     */
    public function handleImportException(Exception $e) {}

    /**
     * magic method to restore events after serialization
     *  since import is done in a worker, binding the events in __wakeup is sufficient,
     *  in __construct this is not needed so far!
     */
    public function __wakeup() {
        $eventManager = Zend_EventManager_StaticEventManager::getInstance();
        /* @var $eventManager Zend_EventManager_StaticEventManager */
        //must be called before default cleanup (which has priority 1)
        $eventManager->attach('editor_Models_Import_Worker_Import', 'importCleanup', array($this, 'postImportHandler'), -100);

        //restoring the taskPath as SPLInfo
        $this->taskPath = new SplFileInfo($this->taskPath);
    }

    public function __sleep() {
        $this->taskPath = (string) $this->taskPath;
        return ['importFolder', 'taskPath'];
    }
}