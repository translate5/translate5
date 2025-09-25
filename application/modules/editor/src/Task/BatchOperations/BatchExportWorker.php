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

use editor_Models_Export_Exported_Worker;
use Exception;
use MittagQI\Translate5\Repository\QueuedExportRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Task\TaskLockService;
use SplFileInfo;
use Zend_Filter_Compress_Zip;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Utils;
use ZfExtended_Worker_Abstract;
use ZipArchive;

class BatchExportWorker extends ZfExtended_Worker_Abstract
{
    private int $userId;

    private array $taskIds;

    private string $exportFolder;

    public function __construct()
    {
        parent::__construct();
        $this->log = \Zend_Registry::get('logger')->cloneMe('task.batch.export');
    }

    public static function queueExportWorker(int $userId, array $taskIds, string $exportFolder): int
    {
        $worker = ZfExtended_Factory::get(self::class);

        if ($worker->init(parameters: [
            'userId' => $userId,
            'taskIds' => $taskIds,
            'exportFolder' => $exportFolder,
        ])) {
            return $worker->queue();
        }

        throw new \MittagQI\Translate5\Export\Exception('E1608');
    }

    protected function validateParameters(array $parameters): bool
    {
        if (! array_key_exists('exportFolder', $parameters)) {
            return false;
        }

        $this->exportFolder = $parameters['exportFolder'];

        if (! array_key_exists('userId', $parameters)) {
            return false;
        }

        $this->userId = $parameters['userId'];

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

        $queueModel = QueuedExportRepository::create()->findByWorkerId((int) $this->workerModel->getId());

        if (null === $queueModel) {
            throw new Exception('Export failed: No queue model found');
        }

        mkdir($this->exportFolder, 0777, true);

        $extension = 'zip';
        $filename = "{$this->getModel()->getHash()}.$extension";
        $filePath = "{$this->exportFolder}/{$filename}";

        // export to $filePath
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
        // Info: only 1 task export per task is allowed. In case user trys to export task which has already running
        // exports, the user will get error message. This is checked by checkExportAllowed and this function must be
        // used if other export types are implemented in future

        $taskRepository = TaskRepository::create();
        $events = ZfExtended_Factory::get('ZfExtended_EventManager', [get_class($this)]);
        $taskLockService = TaskLockService::create();
        $config = Zend_Registry::get('config');
        $taskguiddirectory = $config->runtimeOptions->editor->export->taskguiddirectory;

        touch($batchZipFilePath);

        foreach ($this->taskIds as $taskId) {
            $task = $taskRepository->get((int) $taskId);

            // an export may be handled by plugins
            $events->trigger(
                'beforeTaskExport',
                $this,
                [
                    'task' => $task,
                    'context' => '',
                    'diff' => false,
                    'lock' => $taskLockService,
                ]
            );

            /** @var editor_Models_Export_Exported_Worker $finalExportWorker */
            $finalExportWorker = editor_Models_Export_Exported_Worker::factory('');

            $task->checkExportAllowed($finalExportWorker::class);

            $worker = ZfExtended_Factory::get('editor_Models_Export_Worker');
            $exportFolder = $worker->initExport($task, false);
            $workerId = $worker->queue();

            // Setup worker, assume return value is zipFile name
            $zipFile = (string) $finalExportWorker->setup($task->getTaskGuid(), [
                'exportFolder' => $exportFolder,
                'userId' => $this->userId,
            ]);

            $finalExportWorker->setBlocking(); //we have to wait for the underlying worker to provide the download
            $finalExportWorker->queue($workerId);

            // remove the taskGuid from root folder name in the exported package
            if (! $taskguiddirectory) {
                ZfExtended_Utils::cleanZipPaths(new SplFileInfo($zipFile), basename($exportFolder));
            }

            $errMsg = $this->addToZipArchive($batchZipFilePath, $zipFile, $this->getFileName($taskId . '-' . $task->getTaskName()) . '.zip');

            //rename file after usage to export.zip to keep backwards compatibility
            rename($zipFile, dirname($zipFile) . DIRECTORY_SEPARATOR . 'export.zip');

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
