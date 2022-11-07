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
    /***
     * Task languages field name in the request when the project upload is used
     */
    const IMPORT_UPLOAD_LANGUAGES_NAME = 'importUpload_language';
    /***
     * Task type field name in the request when the project upload is used
     */
    const IMPORT_UPLOAD_TYPE_NAME = 'importUpload_type';

    const TASK_ARCHIV_ZIP_NAME = 'ImportArchiv.zip';
    const TASK_TEMP_IMPORT = '_tempImport';

    protected $task;
    protected $taskPath;
    protected $importFolder;
    /**
     * hashtable of additional files of the form $fileName => $filePath
     * @var array
     */
    protected array $additionalArchiveFiles = [];

    /**
     * DataProvider specific Checks (throwing Exceptions) and actions to prepare import data
     * @param editor_Models_Task $task
     */
    abstract public function checkAndPrepare(editor_Models_Task $task);

    /**
     * DataProvider specific method to create the import archive
     * @param string $filename optional, provide a different archive file name
     */
    abstract public function archiveImportedData($filename = null);

    /**
     * returns the absolute import path, mainly used by the import class
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
        $this->mkdir($this->importFolder);
    }

    /**
     * deletes the temporary import folder
     */
    protected function removeTempFolder(): void
    {
        if(isset($this->importFolder) && is_dir($this->importFolder)) {
            ZfExtended_Utils::recursiveDelete($this->importFolder);
        }
    }

    /**
     * exception throwing mkdir
     * @param string $path
     * @throws editor_Models_Import_DataProvider_Exception
     */
    public function mkdir(string $path) {
        if(is_dir($path)){
            return;
        }
        if(!mkdir($path, 0777, true) && !is_dir($path)) {
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
     * Adds an additional file that shall go into the archive (directly into the base dir)
     * Must obviously be called before the archive is created
     * Be aware that this will delete existing files if they already exist !
     * @param string $filePath
     * @param string|null $fileName: If given, this defines the filename in the destination / ZIP. Be aware, that the passed name may is changed to a web-save filename in case it contains special characters or whitespace
     * @return bool
     */
    public function addAdditonalFileToArchive(string $filePath, string $fileName=NULL) : bool {
        // check for proper filename or use basename of file path
        if($fileName == NULL || $fileName == '' || !editor_Utils::isSecureFilename($fileName)){
            $fileName = basename($filePath);
        }
        if(file_exists($filePath) && !array_key_exists($fileName, $this->additionalArchiveFiles)){
            $this->additionalArchiveFiles[$fileName] = $filePath;
            return true;
        }
        return false;
    }

    /**
     * sets the internal used task object
     * @param editor_Models_Task $task
     */
    protected function setTask(editor_Models_Task $task){
        $this->taskPath = $task->getAbsoluteTaskDataPath();
        $this->task = $task;
    }

    /***
     * @return editor_Models_Task
     */
    public function getTask(){
        return $this->task;
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

    /**
     * base functionality to create an import archive from directory-based data
     * @param string $zipFilename
     * @throws editor_Models_Import_DataProvider_Exception
     */
    protected function createImportedDataArchive(string $zipFilename) {

        /** @var Zend_Filter_Compress_Zip $zipCompresss */
        $zipCompresss = ZfExtended_Factory::get('Zend_Filter_Compress_Zip',[
            'options' => [
                'archive' => $zipFilename,
                'copyRootFolder' => false
            ]
        ]);

        $filter = new Zend_Filter_Compress($zipCompresss);

        // process the additional files by temporarily adding them to the importFolder
        $deletions = [];
        foreach($this->additionalArchiveFiles as $fileName => $filePath){
            $dest = rtrim($this->importFolder, '/').'/'.$fileName;
            @copy($filePath, $dest);
            $deletions[] = $dest;
        }
        if(!$filter->filter($this->importFolder)){
            //DataProvider Directory: Could not create archive-zip
            throw new editor_Models_Import_DataProvider_Exception('E1247', [
                'task' => $this->task,
            ]);
        }
        // cleanup additional files
        foreach($deletions as $file){
            @unlink($file);
        }
    }

    /***
     * extrahiert das geholte Zip File, bricht bei Fehlern ab
     * @param string $zipPath
     * @param string $extractTo
     * @return void
     * @throws editor_Models_Import_DataProvider_Exception
     */
    protected function unzipArchive(string $zipPath, string $extractTo): void
    {
        $zip = new ZipArchive();
        if (! $zip->open($zipPath)) {
            // DataProvider Zip: zip file could not be opened
            throw new editor_Models_Import_DataProvider_Exception('E1241', [
                'task' => $this->task,
                'zip' => $zipPath
            ]);
        }
        if (! $zip->extractTo($extractTo)) {
            // DataProvider Zip: content from zip file could not be extracted
            throw new editor_Models_Import_DataProvider_Exception('E1242', [
                'task' => $this->task,
                'zip' => $extractTo
            ]);
        }
        $zip->close();
    }
}