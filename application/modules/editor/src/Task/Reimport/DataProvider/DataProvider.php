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

use editor_Models_Task as Task;
use MittagQI\Translate5\Task\Reimport\Exception;
use MittagQI\Translate5\Task\Reimport\FileparserRegistry;
use ZfExtended_Factory;

/***
 * Handle single file data in task reimport. This class will validate and prepare single file for task reimport.
 */
class DataProvider extends AbstractDataProvider
{
    protected string $targetFile;

    public function __construct(protected Task $task, protected array $filesMetaData, protected int $fileId)
    {
        parent::__construct($task, $filesMetaData);
    }

    public static function getForCleanup(Task $task): static
    {
        return ZfExtended_Factory::get(static::class, [$task, [], 0]); //for clean-up we need only the task
    }

    /**
     * Create the required file structure for the reimport and move the uploaded files there
     *
     * @param array $uploadedFile
     * @return void
     * @throws Exception
     */
    protected function handleUploads(array $uploadedFile): void
    {
        // move uploaded file into upload target
        $this->targetFile = $this->getTempDir().'/reimport-'.$this->fileId;
        if (!move_uploaded_file($uploadedFile['tmp_name'], $this->targetFile)) {
            throw new Exception('E1428', [
                'file' => $this->targetFile,
                'task' => $this->task,
            ]);
        }
    }

    protected function getValidFileExtensions(): array
    {
        return FileparserRegistry::getInstance()->getSupportedFileTypes();
    }

    /**
     * returns the file meta-data with added path to the re-import file.
     * @return array
     */
    public function getFiles(): array
    {
        /* @var FileDto $fileToReimport */
        $fileToReimport = $this->filesMetaData[$this->fileId];
        $fileToReimport->reimportFile = $this->targetFile;
        return [
            $this->fileId => $fileToReimport
        ];
    }
}
