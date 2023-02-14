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

namespace MittagQI\Translate5\Task\Reimport;

use editor_Models_Foldertree;
use editor_Models_Import_Configuration;
use editor_Models_Import_DataProvider_Abstract;
use editor_Models_Import_DataProvider_Exception;
use editor_Models_Task;
use MittagQI\Translate5\Task\Reimport\SegmentProcessor\SegmentContent\FileHandler;
use Zend_Exception;
use Zend_File_Transfer;
use Zend_Registry;
use ZfExtended_ErrorCodeException;
use ZfExtended_EventManager;
use ZfExtended_Factory;
use ZfExtended_Utils;

/***
 * Handle single file data in task reimport. This class will validate and prepare single file for task reimport.
 */
class DataProvider extends editor_Models_Import_DataProvider_Abstract
{

    protected int $fileId;

    protected array $uploadFiles;

    protected array $uploadErrors;


    protected string $file;

    /**
     * @var ZfExtended_EventManager
     */
    protected $events = false;



    public function __construct()
    {
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', [self::class]);
    }

    /***
     * @param editor_Models_Task $task
     * @param int $fileId
     * @return void
     * @throws Exception
     * @throws Zend_Exception
     */
    public function checkAndPrepare(editor_Models_Task $task)
    {
        $this->setTask($task);

        $this->uploadFiles = $this->getValidFile();

        if(!empty($this->uploadErrors)){
            throw new Exception('E1427',[
                'errors' => $this->uploadErrors,
                'task' => $task
            ]);
        }

        if(empty($this->uploadFiles)){
            throw new Exception('E1429',[
                'task' => $task
            ]);
        }

        $this->handleUploads($task);
    }

    /***
     * Create the required file structure for the reimport and move the uploaded files there
     *
     * @param editor_Models_Task $task
     * @return void
     * @throws Exception
     * @throws Zend_Exception
     */
    protected function handleUploads(editor_Models_Task $task): void
    {
        // unpack the import archive in the tempImport folder location. This will be used for file/s reimport
        $this->unpackImportArchive();

        // replace the uploaded file in the matched file on the disk. The file on the disk is located in the
        // tempImport directory (extracted from the zip ImportArchive)
        $this->replaceUploadFile($this->uploadFiles['tmp_name'],$this->fileId);
    }

    /***
     * Unzip the import archive in temImport folder. The old version of the file/s will be replaced with the matching
     * file from the reimport.
     * @return void
     * @throws editor_Models_Import_DataProvider_Exception
     */
    protected function unpackImportArchive(): void
    {
        try {
            // make the _tempFolder
            $this->checkAndMakeTempImportFolder();
        }catch (editor_Models_Import_DataProvider_Exception $e){
            // if the tmp folder exist, don't break the reimport process, delete the folder and content, and create new
            ZfExtended_Utils::recursiveDelete($this->importFolder);
            // create new importDir
            $this->checkAndMakeTempImportFolder();
        }

        // extract the zip package
        $this->unzipArchive($this->getZipArchivePath(null),$this->importFolder);

        // fix the bug where the import archive contains _tempImport as root folder
        $this->fixArchiveTempFolder();
    }
    /***
     * Replace the original file in the tempImport directory with the matching uploaded file
     * @return void
     */
    protected function replaceUploadFile(string $newFile,int $fileId): void
    {

        // move the new file to the location in _tempFolder
        // the new file will have the same name as the one which is replaced
        $replaceFile = $this->getOriginalFilePath($this->task,$fileId);

        $this->replaceFile($newFile,$replaceFile);

        $this->file  = $replaceFile;
    }

    /***
     * Replace existing task file with another. This expects the source file to be uploaded file
     * @param string $newFile
     * @param string $replaceFile
     * @return void
     * @throws Exception
     */
    protected function replaceFile(string $newFile, string $replaceFile): void
    {
        // move uploaded file into upload target
        if (!move_uploaded_file($newFile, $replaceFile)) {
            throw new \MittagQI\Translate5\Task\Reimport\Exception('E1427',[
                'file' => 'Unable to move the uploaded file to:'.$replaceFile
            ]);
        }
    }

    /***
     * Validate and return all valid files
     * @return array
     */
    protected function getValidFile(): array
    {

        $upload = new Zend_File_Transfer();
        $upload->addValidator('Extension', false, FileHandler::getSupportedFileTypes());
        // Returns all known internal file information
        $files = $upload->getFileInfo();
        $validFiles = [];
        foreach ($files as $file => $info) {
            // file uploaded ?
            if (!$upload->isUploaded($file)) {
                $this->uploadErrors[] = 'The file is not uploaded';
                continue;
            }

            // validators are ok ?
            if (!$upload->isValid($file)) {
                $this->uploadErrors[] = 'The file:' .$info['name']. ' is with invalid file extension';
                continue;
            }

            // fire an event so external plugins can attach and validate if the current upload file
            // is valid for reimport
            $eventResponse = $this->events->trigger('onValidateFile', $this, ['file' => $info]);
            if($eventResponse->stopped()){
                foreach ($eventResponse->last() as $error){
                    $this->uploadErrors[] = $error;
                }
                continue;
            }
            $validFiles[] = $info;
        }

        // only 1 file can be replaced per request
        return $validFiles[0] ?? [];
    }


    /**
     * @return array
     */
    public function getFiles(): array
    {
        return [
            $this->fileId => $this->file
        ];
    }

    /***
     * Get the given file absolute path on the disk after the zip package is extracted.
     * This function will also handle in case the workfiles directory inside the zip archive
     * still uses the old name (proofRead)
     *
     * @param editor_Models_Task $task
     * @param int $fileId
     * @return string
     * @throws Zend_Exception
     */
    protected function getOriginalFilePath(editor_Models_Task $task, int $fileId): string
    {
        /** @var editor_Models_Foldertree $tree */
        $tree = ZfExtended_Factory::get('editor_Models_Foldertree');

        $tree->setPathPrefix('');

        $path = $tree->getFileIdPath($task->getTaskGuid(),$fileId);

        $workfilesDir = editor_Models_Import_Configuration::WORK_FILES_DIRECTORY;

        $newFile = $this->importFolder.DIRECTORY_SEPARATOR.$workfilesDir.DIRECTORY_SEPARATOR.$path;

        if(!is_file($newFile)){
            // if the file does not exist withing workfiles directory, try to find it from the configured file name
            $config = Zend_Registry::get('config');
            $workfilesDir = $config->runtimeOptions->import->proofReadDirectory;
            $newFile = $this->importFolder.DIRECTORY_SEPARATOR.$workfilesDir.DIRECTORY_SEPARATOR.$path;
        }
        return $newFile;
    }


    /**
     * DataProvider specific method to create the import archive
     * @param string $filename optional, provide a different archive file name
     * @throws editor_Models_Import_DataProvider_Exception|Exception
     */
    public function archiveImportedData($filename = null)
    {
        $zipPath = $this->getZipArchivePath($filename);

        // If the zip archive exist(this should always be the case) -> make backup
        if (file_exists($zipPath)) {

            $target = $this->getArchiveUniqueName();

            if(!copy($zipPath, $target)) {
                throw new Exception('E1430', [
                    'task' => $this->task,
                    'file' => $zipPath,
                    'target' => $target,
                ]);
            }
        }

        $this->createImportedDataArchive($zipPath);
    }

    /***
     * Return unique name for an task archive used for backing up the old archive
     * @return string
     */
    private function getArchiveUniqueName(): string
    {
        return $this->taskPath.DIRECTORY_SEPARATOR.date('Y-m-d__H_i_s').'_'.self::TASK_ARCHIV_ZIP_NAME;
    }


    /***
     * This is a fix for a bug where the archived zip pachage is zipped with _temImport as
     * root folder.
     * @return void
     */
    private function fixArchiveTempFolder(): void
    {
        $doubleTempFolder = $this->importFolder.DIRECTORY_SEPARATOR.self::TASK_TEMP_IMPORT;

        // check if there is _tempFolder after the zip archive is extracted
        if(is_dir($doubleTempFolder)){
            $fixedPath = $this->taskPath.DIRECTORY_SEPARATOR.'_fixedTempImport';
            // move the needed content into temporary directory
            ZfExtended_Utils::recursiveCopy($doubleTempFolder,$fixedPath);
            // remove the incorrect tempFolder file structure
            $this->removeTempFolder();
            // change the fixed temporary folder name to the real temp name
            rename($fixedPath,$this->getAbsImportPath());
        }
    }

    /***
     * @return void
     */
    public function cleanTempFolder(): void
    {
        $this->removeTempFolder();
    }

    /**
     * @param int $fileId
     */
    public function setFileId(int $fileId): void
    {
        $this->fileId = $fileId;
    }
}
