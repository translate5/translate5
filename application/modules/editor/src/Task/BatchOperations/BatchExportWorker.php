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

namespace MittagQI\Translate5\Task\BatchOperations;

use editor_Models_Export_Exported_ZipDefaultWorker;
use editor_Models_Export_Worker;
use Exception;
use MittagQI\Translate5\Repository\QueuedExportRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\ZfExtended\Worker\Exception\SetDelayedException;
use Zend_Filter_Compress_Zip;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Models_Worker;
use ZfExtended_Worker_Abstract;
use ZipArchive;

class BatchExportWorker extends ZfExtended_Worker_Abstract
{
    private array $taskIds;

    private string $exportFolder;

    private const CHECK_DELAY = 5;

    public function __construct()
    {
        parent::__construct();
        $this->log = \Zend_Registry::get('logger')->cloneMe('task.batch.export');
    }

    public static function queueExportWorker(int $userId, array $taskIds, string $taskExportFolder): int
    {
        $parentWorker = ZfExtended_Factory::get(self::class);

        if (! $parentWorker->init(parameters: [
            'taskIds' => $taskIds,
            'exportFolder' => $taskExportFolder,
        ])) {
            throw new \MittagQI\Translate5\Export\Exception('E1608');
        }

        $parentWorkerId = $parentWorker->queue(state: ZfExtended_Models_Worker::STATE_PREPARE);

        $taskRepository = TaskRepository::create();
        $config = Zend_Registry::get('config');
        $taskguiddirectory = $config->runtimeOptions->editor->export->taskguiddirectory;

        foreach ($taskIds as $taskId) {
            $task = $taskRepository->get((int) $taskId);

            $taskExportFolder = tempnam($task->getAbsoluteTaskDataPath(), 'batch-export-');
            unlink($taskExportFolder);
            @mkdir($taskExportFolder);

            $worker = ZfExtended_Factory::get(editor_Models_Export_Worker::class);
            $worker->initFolderExport($task, false, $taskExportFolder);
            $workerId = $worker->queue($parentWorkerId, ZfExtended_Models_Worker::STATE_PREPARE);

            $finalExportWorker = ZfExtended_Factory::get(editor_Models_Export_Exported_ZipDefaultWorker::class);
            $finalExportWorker->setup($task->getTaskGuid(), [
                'exportFolder' => $taskExportFolder,
                'userId' => $userId,
                'targetZipFilePath' => $task->getAbsoluteTaskDataPath() . DIRECTORY_SEPARATOR . 'export.zip',
                'cleanZipPaths' => $taskguiddirectory ? '' : basename($taskExportFolder),
            ]);

            $finalExportWorker->queue($workerId);
        }

        $parentWorker->getModel()->schedulePrepared();

        return $parentWorkerId;
    }

    protected function validateParameters(array $parameters): bool
    {
        if (! array_key_exists('exportFolder', $parameters)) {
            return false;
        }

        $this->exportFolder = $parameters['exportFolder'];

        if (! array_key_exists('taskIds', $parameters) || ! is_array(
            $parameters['taskIds']
        ) || empty($parameters['taskIds'])) {
            return false;
        }

        $this->taskIds = $parameters['taskIds'];

        return true;
    }

    protected function handleWorkerException(\Throwable $workException): void
    {
        $this->workerException = $workException;

        rmdir($this->exportFolder);
    }

    protected function work(): bool
    {
        if (is_dir($this->exportFolder)) {
            // export already running
            return true;
        }

        $workerId = (int) $this->workerModel->getId();
        if ($this->workerModel->hasWaitingChildWorker($workerId)) {
            throw new SetDelayedException(
                'BatchExportWorker',
                __CLASS__,
                self::CHECK_DELAY
            );
        }

        $queueModel = QueuedExportRepository::create()->findByWorkerId($workerId);

        if (null === $queueModel) {
            throw new Exception('Export failed: No queue model found');
        }

        mkdir($this->exportFolder, 0777, true);

        $extension = 'zip';
        $filename = "{$this->getModel()->getHash()}.$extension";
        $filePath = "{$this->exportFolder}/{$filename}";

        $this->addExportsToZip($filePath);

        if (! file_exists($filePath)) {
            throw new Exception('Export failed: Nothing was exported');
        }

        $queueModel->setResultFileName("{$queueModel->getResultFileName()}.$extension");
        $queueModel->setLocalFileName($filename);

        $queueModel->save();

        return true;
    }

    private function addExportsToZip(string $batchZipFilePath): void
    {
        $taskRepository = TaskRepository::create();

        touch($batchZipFilePath);

        foreach ($this->taskIds as $taskId) {
            $task = $taskRepository->get((int) $taskId);

            $zipFile = $task->getAbsoluteTaskDataPath() . DIRECTORY_SEPARATOR . 'export.zip';
            $errMsg = $this->addToZipArchive($batchZipFilePath, $zipFile, $this->getFileName($taskId . '-' . $task->getTaskName()) . '.zip');

            if ($errMsg) {
                $this->log->error(
                    'E1012',
                    'Batch export error: ' . $errMsg,
                    [
                        'taskId' => $taskId,
                    ]
                );

                break;
            }
        }
    }

    private function getFileName($s, $maxLength = 200)
    {
        $s = trim(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $s), '-');
        $s = preg_replace('/-+/', '-', $s);
        if (strlen($s) > $maxLength) {
            $s = substr($s, 0, $maxLength);
        }

        return $s;
    }

    private function addToZipArchive(string $zipFilePath, string $filePath, string $fileName): string
    {
        $zip = new ZipArchive();
        $zipOpened = $zip->open($zipFilePath, is_file($zipFilePath) ? 0 : ZipArchive::CREATE);
        if ($zipOpened !== true) {
            $errorHelper = new class() extends Zend_Filter_Compress_Zip {
                public function _errorString($error): string
                {
                    return parent::_errorString($error) . ' (' . $error . ')';
                }
            };

            /** @phpstan-ignore-next-line */
            return $errorHelper->_errorString($zipOpened);
        }

        // Add file to archive with no compression
        $zip->addFile($filePath, $fileName);
        $zip->setCompressionName($fileName, ZipArchive::CM_STORE);
        $zip->close();

        return '';
    }
}
