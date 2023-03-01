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

use editor_Models_Import_DataProvider_Exception;
use editor_Models_Import_FileParser;
use editor_Models_Task;
use MittagQI\Translate5\Task\Export\Package\Source\Task;
use Zend_File_Transfer;
use ZipArchive;

/***
 * Handle zip package data for task reimport. This class will validate and prepare the data used later by the segment
 * processor to reimport files inside a task.
 *
 */
class ZipDataProvider extends AbstractDataProvider
{
    protected function getValidFileExtensions(): array
    {
        return ['zip'];
    }

    /***
     * Check if the provided zip package has valid files for reimport. The files are valid if they are provided in
     * xliff suborder and the files are supported by the reimport segment processor
     * @param array $uploadedFile
     * @return void
     * @throws editor_Models_Import_DataProvider_Exception
     */
    protected function handleUploads(array $uploadedFile): void
    {
        //FIXME error handling, previous code was doing exception if one error occured!
        $zip = new ZipArchive();
        if (!$zip->open($uploadedFile['tmp_name'])) {
            // DataProvider Zip: zip file could not be opened
            throw new editor_Models_Import_DataProvider_Exception('E1241', [
                'task' => $this->task,
                'zip' => $uploadedFile['name']
            ]);
        }

        $hasValidFile = false;

        // loop over each file in zhe zip package(not unzipped) and validate if the provided
        // reimport file is supported by the segment processor
        for ($idx = 0; $zipFile = $zip->statIndex($idx); $idx++) {
            //although / is the only one according official spec,
            // still some libs create paths in the ZIP with \ under windows
            $zipFile['name'] = str_replace('\\', '/', $zipFile['name']);

            //get only workfiles
            if (! str_starts_with($zipFile['name'], Task::TASK_FOLDER_NAME.'/')) {
                continue;
            }

            // ignore workfile folder itself
            if (rtrim($zipFile['name'], '/') === Task::TASK_FOLDER_NAME) {
                continue;
            }

            $finalFileNames = array_column($this->filesMetaData, 'fileId', 'filteredFilePath');

            if (array_key_exists($zipFile['name'], $finalFileNames)) {
                /* @var FileDto $matchingFile */
                $matchingFile = $this->filesMetaData[$finalFileNames[$zipFile['name']]];
            } else {
                //FIXME LOG that the ZIP did contain additional files!
                continue;
            }

            if (is_subclass_of($matchingFile->fileParser, editor_Models_Import_FileParser::class)) {
                if (! $matchingFile->fileParser::IS_REIMPORTABLE) {
                    // FIXME logging
                    $this->uploadErrors[] = 'The reimport zip package contains unsupported file for reimport. The file:' . $zipFile['name'] . ' is with invalid file extension';
                    continue;
                }
            } else {
                //FIXME
                $this->uploadErrors[] = 'Invalid fileparser found!';
                continue;
            }

            $data = $zip->getFromIndex($idx);

            if ($data === false) {
                //FIXME
                $this->uploadErrors[] = 'Could not extract!';
                continue;
            }

            $errorMessage = '';
            if (! $matchingFile->fileParser::isParsable(substr($data, 0, 512), $errorMessage)) {
                //FIXME errors
                $this->uploadErrors[] = $errorMessage;
                continue;
            }

            $tempFile = $this->getTempDir().'/refile-'.$matchingFile->fileId;
            if (! file_put_contents($tempFile, $data)) {
                $this->uploadErrors[] = 'Could not write temp file to disk'; //FIXME
                continue;
            }

            $matchingFile->reimportFile = $tempFile;

            // FIXME TMX = 0 bytes Dateien beim export nicht erstellen!
            $hasValidFile = true;
        }

// FIXME collect unhandled files from tree, and log them like that:
//        $log = Zend_Registry::get('logger');
//        $log->warn('E1461', 'Reimport ZipDataProvider: The provided file in the zip package can not be name-matched with any of the task files.', [
//            'task' => $this->task,
//            'fileName' => $fileName
//        ]);

        $zip->close();

        $hasValidFile; //FIXME use me
    }
}