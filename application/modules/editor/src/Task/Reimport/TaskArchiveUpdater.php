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

use editor_Models_Import_DataProvider_Abstract;
use editor_Models_Task;
use MittagQI\Translate5\Task\Reimport\DataProvider\FileDto;
use ZipArchive;

/**
 * Updates the given files [fileId => absPathToNewContent] in the archive zip.
 * The file to update in the zip is determined by the filepath stored in the foldertree
 *
 * TODO:
 *  - encapsulate TaskArchive generally, instead using editor_Models_Import_DataProvider_Abstract::TASK_TEMP_IMPORT
 */
class TaskArchiveUpdater
{
    /**
     * @param editor_Models_Task $task
     * @param FileDto[] $fileToUpdate
     * @return bool
     *
     */
    public function updateFiles(editor_Models_Task $task, array $fileToUpdate): bool
    {
        $zip = new ZipArchive();
        $origName = editor_Models_Import_DataProvider_Abstract::TASK_ARCHIV_ZIP_NAME;

        $originalArchiveName = $task->getAbsoluteTaskDataPath() . '/' . $origName;
        if (!file_exists($originalArchiveName) || !is_writable($task->getAbsoluteTaskDataPath())) {
            return false;
        }

        $tempArchive = tempnam($task->getAbsoluteTaskDataPath(), '_tempArchive');
        copy($originalArchiveName, $tempArchive); //we operate on a copy
        $zip->open($tempArchive);

        foreach ($fileToUpdate as $fileDto) {
            $zipIndex = $zip->locateName($fileDto->filteredFilePath);
            if ($zipIndex === false) {
                $zip->addFile($fileDto->reimportFile, $fileDto->filteredFilePath);
            } else {
                $zip->replaceFile($fileDto->reimportFile, $zipIndex);
            }
        }
        $zip->close();

        $backup = $task->getAbsoluteTaskDataPath() . '/' . date('Y-m-d_H_i_s') . '_' . $origName;
        rename($originalArchiveName, $backup);
        rename($tempArchive, $originalArchiveName);

        return true;
    }
}