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
use editor_Models_Import_DataProvider_Exception;
use editor_Models_Task;
use FilesystemIterator;
use MittagQI\Translate5\Task\Export\Package\Source\Task;
use MittagQI\Translate5\Task\Reimport\SegmentProcessor\SegmentContent\FileHandler;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Zend_File_Transfer;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Utils;
use ZipArchive;

/***
 * Handle zip package data for task reimport. This class will validate and prepare the data used later by the segment
 * processor to reimport files inside a task.
 *
 */
class ZipDataProvider extends DataProvider
{

    /***
     * Folder name for temporary extracted upload files
     */
    public const TEMP_REIMPORT_ARCHIVE = '_tmpReimportArchive';

    /***
     * Reimport upload file field
     */
    public const UPLOAD_FILE_FIELD = 'fileReimport';

    private array $matchedFiles = [];

    private string $tmpArchive = '';

    protected function handleUploads(editor_Models_Task $task): void
    {
        $this->unpackImportArchive();

        $this->tmpArchive = $this->unzipReimportArchive();

        $xlifPath = $this->tmpArchive.DIRECTORY_SEPARATOR.Task::TASK_FOLDER_NAME.DIRECTORY_SEPARATOR;

        $objects = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($xlifPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST
        );

        $tree = ZfExtended_Factory::get(editor_Models_Foldertree::class);
        $paths = $tree->getPaths($this->task->getTaskGuid(),editor_Models_Foldertree::TYPE_FILE);

        $log = Zend_Registry::get('logger');

        foreach($objects as $file => $fileInfo) {
            if($fileInfo->isFile()) {

                // remove the task path from the fileName, so it is easy to compare
                $fileName = str_replace($this->tmpArchive.DIRECTORY_SEPARATOR,'',$file);

                $matchFound = false;
                foreach ($paths as $fileId => $originalFile){
                    if( $fileName === $originalFile){
                        $this->matchedFiles[$fileId] = $file;
                        $matchFound = true;
                    }
                }
                if( !$matchFound){
                    $log->warn('E1461', 'Reimport ZipDataProvider: The provided file in the zip package can not be name-matched with any of the task files.', [
                        'task' => $this->task,
                        'fileName' => $fileName
                    ]);
                }
            }
        }

        foreach ($this->matchedFiles as $fileId => $fileName){
            $this->replaceUploadFile($fileName,$fileId);
        }
    }

    /***
     * Overwrite the replace files function because for zip data provider, the new file exist on the disk
     * @param string $newFile
     * @param string $replaceFile
     * @return void
     * @throws Exception
     */
    protected function replaceFile(string $newFile, string $replaceFile): void
    {
        if( !copy($newFile,$replaceFile)){
            throw new Exception('E1462',[
                'original' => $replaceFile,
                'newFile' => $newFile
            ]);
        }
    }

    public function getFiles(): array
    {
        return $this->matchedFiles;
    }

    /***
     * Create the tempReimportArchive and unzip the reimport archive
     *
     * @return string
     * @throws editor_Models_Import_DataProvider_Exception
     */
    protected function unzipReimportArchive(){
        $tempReimportArchive = $this->task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR.self::TEMP_REIMPORT_ARCHIVE;
        if( is_dir($tempReimportArchive)){
            ZfExtended_Utils::recursiveDelete($tempReimportArchive);
        }
        $this->mkdir($tempReimportArchive);
        $this->unzipArchive($this->uploadFiles['tmp_name'],$tempReimportArchive);
        return $tempReimportArchive;
    }


    /***
     * Get and validate the uploaded files. This will also validate if the files inside the zipped archive are supported
     * by the segment processor
     * @throws editor_Models_Import_DataProvider_Exception
     */
    protected function getValidFile(): array
    {

        $upload = new Zend_File_Transfer();
        $upload->addValidator('Extension', false, ['zip']);

        // Returns all known internal file information
        $file = $upload->getFileInfo()[self::UPLOAD_FILE_FIELD] ?? [];
        if( empty($file)){
            return [];
        }

        if (!$upload->isUploaded(self::UPLOAD_FILE_FIELD)) {
            $this->uploadErrors[] = 'The file is not uploaded';
            return [];
        }

        // validators are ok ?
        if (!$upload->isValid(self::UPLOAD_FILE_FIELD)) {
            $this->uploadErrors[] = 'The file:' .$file['name']. ' is with invalid file extension';
            return [];
        }

        // are the files valid in the zip xliff folder
        if($this->validateUploadZipContent($file) === false){
            return [];
        }
        return $file;
    }

    /***
     * Check if the provided zip package has valid files for reimport. The files are valid if they are provided in
     * xliff suborder and the files are supported by the reimport segment processor
     * @param array $zipFileInfo
     * @return bool
     * @throws editor_Models_Import_DataProvider_Exception
     */
    private function validateUploadZipContent(array $zipFileInfo): bool
    {

        $zip = new ZipArchive();
        if (! $zip->open($zipFileInfo['tmp_name'])) {
            // DataProvider Zip: zip file could not be opened
            throw new editor_Models_Import_DataProvider_Exception('E1241', [
                'task' => $this->task,
                'zip' => $zipFileInfo['name']
            ]);
        }
        $hasValidFile = false;

        // validate if the provided reimport files are supported by the segment processor
        for ($idx = 0; $zipFile = $zip->statIndex($idx); $idx++) {
            if (!is_dir($zipFile['name']) && str_starts_with($zipFile['name'],Task::TASK_FOLDER_NAME) && !str_ends_with($zipFile['name'],DIRECTORY_SEPARATOR)) {
                $extension = strtolower(pathinfo($zipFile['name'], PATHINFO_EXTENSION));
                // check if the the extension is supported
                if( !in_array($extension, FileHandler::getSupportedFileTypes(), true)){
                    $this->uploadErrors[] = 'The reimport zip package contains unsupported file for reimport. The file:' .$zipFile['name']. ' is with invalid file extension';
                    continue;
                }
                // fire an event so external plugins can attach and validate if the current upload file is valid for reimport
                $eventResponse = $this->events->trigger('onValidateFile', $this, ['file' => [
                    'name' => $zipFile['name'],
                    'data' => $zip->getFromIndex($idx)
                ]]);

                if($eventResponse->stopped()){
                    foreach ($eventResponse->last() as $error){
                        $this->uploadErrors[] = $error;
                        continue;
                    }
                }
                $hasValidFile = true;
            }
        }

        $zip->close();

        return $hasValidFile;
    }

    /**
     * deletes the temporary import folder
     */
    protected function removeTempFolder(): void
    {
        if(isset($this->tmpArchive) && is_dir($this->tmpArchive)) {
            ZfExtended_Utils::recursiveDelete($this->tmpArchive);
        }
        parent::removeTempFolder();
    }
}