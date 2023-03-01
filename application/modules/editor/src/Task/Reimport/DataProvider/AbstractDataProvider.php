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
declare(strict_types=1);

namespace MittagQI\Translate5\Task\Reimport\DataProvider;

use editor_Models_Task;
use MittagQI\Translate5\Task\Reimport\Exception;
use SplFileInfo;
use Zend_Exception;
use Zend_File_Transfer_Adapter_Http;
use Zend_File_Transfer_Exception;
use ZfExtended_Factory;
use ZfExtended_Utils;

/***
 * abstract data provider for the re-imported files
 */
abstract class AbstractDataProvider
{
    /**
     * Reimport upload file field
     */
    const UPLOAD_FILE_FIELD = 'fileReimport';

    const TEMP_DIR = '_tempReimport';

    public function __construct(protected editor_Models_Task $task, protected array $filesMetaData)
    {
    }

    public static function getForCleanup(editor_Models_Task $task): static
    {
        return ZfExtended_Factory::get(static::class, [$task, []]);
    }

    public function getFiles(): array
    {
        return $this->filesMetaData;
    }

    /**
     * @return void
     * @throws Exception
     * @throws Zend_Exception
     */
    public function checkAndPrepare(): void
    {
        $uploadFile = $this->getValidUploadedFile();

        if (empty($uploadFile)) {
            throw new Exception('E1429', [
                'task' => $this->task
            ]);
        }

        $this->cleanup(); //clean up old re-imports
        if (!$this->makeReimportTempDir()) {
            throw new Exception('E1431', [
                'task' => $this->task
            ]);
        }

        $this->handleUploads($uploadFile);
    }

    /***
     * Get and validate the uploaded files. This will also validate if the files inside the zipped archive are supported
     * by the segment processor
     * @return array
     * @throws Zend_File_Transfer_Exception
     * @throws Exception
     */
    protected function getValidUploadedFile(): array
    {
        $upload = new Zend_File_Transfer_Adapter_Http();
        $upload->addValidator('Extension', false, $this->getValidFileExtensions());

        // Returns all known internal file information
        $file = $upload->getFileInfo(self::UPLOAD_FILE_FIELD);
        $file = reset($file); //we are only posting one file

        if (empty($file) || !$upload->isUploaded(self::UPLOAD_FILE_FIELD)) {
            throw new Exception('E1427', [
                'task' => $this->task,
            ]);
        }

        // validators are ok ?
        if (!$upload->isValid(self::UPLOAD_FILE_FIELD)) {
            throw new Exception('E1430', [
                'task' => $this->task,
                'file' => $file['name'],
            ]);
        }

        return $file;
    }

    abstract protected function getValidFileExtensions(): array;

    public function cleanup(): void
    {
        $tempDir = $this->getTempDir();
        if ($tempDir->isDir()) {
            ZfExtended_Utils::recursiveDelete((string)$tempDir);
        }
    }

    protected function getTempDir(): SplFileInfo
    {
        return new SplFileInfo($this->task->getAbsoluteTaskDataPath() . '/' . self::TEMP_DIR);
    }

    private function makeReimportTempDir(): bool
    {
        $tempDir = $this->getTempDir();
        if ($tempDir->isDir()) {
            return true;
        }
        return mkdir((string)$tempDir) && is_dir((string)$tempDir);
    }

    abstract protected function handleUploads(array $uploadedFile): void;
}
