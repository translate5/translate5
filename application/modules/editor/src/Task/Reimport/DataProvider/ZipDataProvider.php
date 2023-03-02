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

namespace MittagQI\Translate5\Task\Reimport\DataProvider;

use editor_Models_Import_FileParser;
use MittagQI\Translate5\Task\Export\Package\Source\Task;
use MittagQI\Translate5\Task\Reimport\Exception;
use Zend_Filter_Compress_Zip;
use ZipArchive;

/***
 * Handle zip package data for task reimport. This class will validate and prepare the data used later by the segment
 * processor to reimport files inside a task.
 *
 */
class ZipDataProvider extends AbstractDataProvider
{
    private array $zipAdditionalFile = [];

    protected function getValidFileExtensions(): array
    {
        return ['zip'];
    }

    /***
     * Check if the provided zip package has valid files for reimport. The files are valid if they are provided in
     * xliff suborder and the files are supported by the reimport segment processor
     * @param array $uploadedFile
     * @return void
     * @throws Exception
     */
    protected function handleUploads(array $uploadedFile): void
    {
        $zip = $this->openZip($uploadedFile);

        $filesFoundForImport = 0;

        // loop over each file in zhe zip package(not unzipped) and validate if the provided
        // reimport file is supported by the segment processor
        for ($idx = 0; $zipFile = $zip->statIndex($idx); $idx++) {
            $nameFromZip = $this->checkFileFromZip($zipFile);
            if (is_null($nameFromZip)) {
                continue; //was no valid file to be processed
            }

            $matchingFile = $this->getMatchedFileDto($nameFromZip);
            if (is_null($matchingFile)) {
                continue;
            }

            $data = $this->verifyFileparserAndGetData($matchingFile, $zip, $idx);
            if (is_null($data)) {
                continue;
            }

            $tempFile = $this->getTempDir().'/refile-'.$matchingFile->fileId;
            if (! file_put_contents($tempFile, $data)) {
                $matchingFile->addError('Could not write temp file to disk');
                continue;
            }

            $matchingFile->reimportFile = $tempFile;

            // FIXME TMX = 0 bytes Dateien beim export nicht erstellen!
            $filesFoundForImport++;
        }

        $zip->close();

        //if at least one file was found, go ahead
        if ($filesFoundForImport === 0) {
            throw new Exception('E1462', [
                'task' => $this->task
            ]);
        }
    }

    /**
     * returns a list of errors happend on mapping files from zip to task files
     * @return string[]
     */
    public function getCollectedErrors(): array
    {
        //loop over Dtos and get from there, also take from
        return array_merge(
            array_map(function ($additonalFile) {
                return $additonalFile.': ignored - no match in task files;';
            }, $this->zipAdditionalFile),
            array_filter(array_map(function (FileDto $file) {
                if (empty($file->getErrors())) {
                    return null;
                }
                return $file->filePath.join(', ', $file->getErrors()).';';
            }, $this->getFiles()))
        );
    }

    /**
     * Checks the file info from zip, return the filename if it is a file to be processed
     * @param array $zipFile
     * @return string|null
     */
    private function checkFileFromZip(array $zipFile): ?string
    {
        //although / is the only one according official spec,
        // still some libs create paths in the ZIP with \ under windows
        $name = str_replace('\\', '/', $zipFile['name']);

        //get only workfiles
        if (! str_starts_with($name, Task::TASK_FOLDER_NAME.'/')) {
            return null;
        }

        // ignore workfile folder itself
        if (rtrim($name, '/') === Task::TASK_FOLDER_NAME) {
            return null;
        }
        return $name;
    }

    private function getMatchedFileDto(string $nameFromZip): ?FileDto
    {
        $finalFileNames = array_column($this->filesMetaData, 'fileId', 'filteredFilePath');

        if (array_key_exists($nameFromZip, $finalFileNames)) {
            /* @var FileDto $matchingFile */
            return $this->filesMetaData[$finalFileNames[$nameFromZip]];
        }
        $this->zipAdditionalFile[] = $nameFromZip;
        return null;
    }

    private function verifyFileparserAndGetData(FileDto $matchingFile, ZipArchive $zip, int $idx): ?string
    {
        if (is_subclass_of($matchingFile->fileParser, editor_Models_Import_FileParser::class)) {
            if (! $matchingFile->fileParser::IS_REIMPORTABLE) {
                $matchingFile->addError('Re-import is not supported for that file type');
                return null;
            }
        } else {
            $matchingFile->addError('Invalid fileparser found!');
            return null;
        }

        $data = $zip->getFromIndex($idx);

        if ($data === false) {
            $matchingFile->addError('Could not extract file from zip!');
            return null;
        }

        $errorMessage = '';
        if (! $matchingFile->fileParser::isParsable(substr($data, 0, 512), $errorMessage)) {
            $matchingFile->addError($errorMessage);
            return null;
        }

        return $data;
    }

    /**
     * Opens the zip and throws exception on errors
     * @throws Exception
     */
    private function openZip(array $uploadedFile): ZipArchive
    {
        $zip = new ZipArchive();
        $zipOpened = $zip->open($uploadedFile['tmp_name']);
        if ($zipOpened === true) {
            return $zip;
        }

        $errorHelper = new class() extends Zend_Filter_Compress_Zip {
            public function _errorString($error): string
            {
                return parent::_errorString($error).' ('.$error.')';
            }
        };

        throw new Exception('E1442', [
            'task' => $this->task,
            'zip' => $uploadedFile['name'],
            'zipError' => $errorHelper->_errorString($zipOpened),
        ]);
    }
}