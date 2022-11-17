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
use editor_Models_Import_DataProvider_Abstract;
use editor_Models_Import_DataProvider_Exception;
use editor_Models_Task;
use Zend_Exception;
use Zend_File_Transfer;
use ZfExtended_ErrorCodeException;
use ZfExtended_Factory;
use ZfExtended_Utils;

/***
 *
 */
class DataProvider extends editor_Models_Import_DataProvider_Abstract
{
    public const SUPORTED_FILE_EXTENSIONS = ['xliff','xml'];

    private array $uploadFiles;

    private array $uploadErrors;

    private string $file;

    /**
     * @param int $fileId
     */
    public function __construct(private int $fileId)
    {
    }

    /**
     * (non-PHPdoc)
     * @throws editor_Models_Import_DataProvider_Exception|ZfExtended_ErrorCodeException|Zend_Exception
     * @see editor_Models_Import_DataProvider_Abstract::checkAndPrepare()
     */
    public function checkAndPrepare(editor_Models_Task $task)
    {
        $this->setTask($task);

        $this->uploadFiles = $this->getValidFile();

        if(!empty($this->uploadErrors)){
            throw new \MittagQI\Translate5\Task\Reimport\Exception('E1427',$this->uploadErrors);
        }

        if( empty($this->uploadFiles)){
            throw new \MittagQI\Translate5\Task\Reimport\Exception('E1429');
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
        /** @var editor_Models_Foldertree $tree */
        $tree = ZfExtended_Factory::get('editor_Models_Foldertree');

        $path = $tree->getFileIdPath($task->getTaskGuid(),$this->fileId);

        try {
            // make the _tempFolder
            $this->checkAndMakeTempImportFolder();
        }catch (editor_Models_Import_DataProvider_Exception $e){
            // if the tmp folder exist, don't break the reimport process
        }

        // extract the zip package
        $this->unzipArchive($this->getZipArchivePath(null),$this->importFolder);

        // fix the bug where the import archive contains _tempImport as root folder
        $this->fixArchiveTempFolder();

        // move the new file to the location in _tempFolder
        // the new file will have the same name as the one which is replaced
        $newFile = $this->importFolder.DIRECTORY_SEPARATOR.$path;

        // move uploaded file into upload target
        if (!move_uploaded_file($this->uploadFiles['tmp_name'], $newFile)) {
            throw new \MittagQI\Translate5\Task\Reimport\Exception('E1427',[
                'file' => 'Unable to move the uploaded file to:'.$newFile
            ]);
        }

        $this->file  = $newFile;
    }

    /***
     * Validate and return all valid files
     * @return array
     */
    private function getValidFile(): array
    {
        $upload = new Zend_File_Transfer();
        $upload->addValidator('Extension', false, self::SUPORTED_FILE_EXTENSIONS);
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

            $validFiles[] = $info;
        }

        // only 1 file can be replaced per request
        return $validFiles[0] ?? [];
    }


    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
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

            if( !copy($zipPath, $target)) {
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
        if( is_dir($doubleTempFolder)){
            $fixedPath = $this->taskPath.DIRECTORY_SEPARATOR.'_fixedTempImport';
            // move the needed content into temporary directory
            ZfExtended_Utils::recursiveCopy($doubleTempFolder,$fixedPath);
            // remove the incorrect tempFolder file structure
            $this->removeTempFolder();
            // change the fixed temporary folder name to the real temp name
            rename($fixedPath,$this->getAbsImportPath());
        }
    }
}
