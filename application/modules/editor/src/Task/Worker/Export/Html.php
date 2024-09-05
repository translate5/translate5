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

namespace MittagQI\Translate5\Task\Worker\Export;

use editor_Models_Task as Task;
use Exception;
use MittagQI\Translate5\Repository\QueuedExportRepository;
use MittagQI\Translate5\Task\Export\ExportService;
use ZfExtended_Factory;
use ZfExtended_Worker_Abstract;

/**
 * Contains the Import Worker (the scheduling parts)
 * The import process itself is encapsulated in editor_Models_Import_Worker_Import
 */
class Html extends ZfExtended_Worker_Abstract
{
    private int $taskId;

    private string $exportFolder;

    public function __construct()
    {
        parent::__construct();
        $this->log = \Zend_Registry::get('logger')->cloneMe('editor.languageResource.tm.export');
    }

    public static function queueExportWorker(Task $task, string $exportFolder): int
    {
        $worker = ZfExtended_Factory::get(self::class);

        if ($worker->init(parameters: [
            'taskId' => $task->getId(),
            'exportFolder' => $exportFolder,
        ])) {
            return $worker->queue();
        }

        throw new \MittagQI\Translate5\Export\Exception('E1608');
    }

    protected function validateParameters($parameters = [])
    {
        if (! array_key_exists('exportFolder', $parameters)) {
            return false;
        }

        $this->exportFolder = $parameters['exportFolder'];

        if (! array_key_exists('taskId', $parameters)) {
            return false;
        }

        $this->taskId = (int) $parameters['taskId'];

        return true;
    }

    protected function handleWorkerException(\Throwable $workException)
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

        $exportService = ExportService::create();

        mkdir($this->exportFolder, 0777, true);

        $html = $exportService->asHtml($this->taskId);

        $filename = "{$this->getModel()->getHash()}.html";

        $filePath = "{$this->exportFolder}/{$filename}";

        file_put_contents($filePath, $html);

        if (! file_exists($filePath)) {
            throw new Exception('Export failed: Saving file to export dir failed');
        }

        $queueModel->setLocalFileName($filename);

        $queueModel->save();

        return true;
    }
}
