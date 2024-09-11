<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Export;

use MittagQI\Translate5\Export\Model\QueuedExport;
use MittagQI\Translate5\Repository\QueuedExportRepository;
use MittagQI\Translate5\Repository\WorkerRepository;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZfExtended_Factory;
use ZfExtended_NotFoundException;

class QueuedExportService
{
    public function __construct(
        private readonly string $exportDirRoot,
        private readonly QueuedExportRepository $queuedExportRepository,
        private readonly WorkerRepository $workerRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            APPLICATION_PATH . '/../data/Export/',
            QueuedExportRepository::create(),
            new WorkerRepository(),
        );
    }

    public function makeQueueRecord(string $token, int $workerId, string $resultFilename): QueuedExport
    {
        $model = ZfExtended_Factory::get(QueuedExport::class);
        $model->setToken($token);
        $model->setWorkerId($workerId);
        $model->setResultFileName($resultFilename);
        $model->save();

        return $model;
    }

    /**
     * @throws ZfExtended_NotFoundException
     */
    public function getRecordByToken(string $token): QueuedExport
    {
        $queueModel = $this->queuedExportRepository->findByToken($token);

        if (! $queueModel) {
            throw new ZfExtended_NotFoundException('Token no longer exists');
        }

        return $queueModel;
    }

    /**
     * @throws ZfExtended_NotFoundException
     */
    public function isReady(QueuedExport $queue): bool
    {
        $worker = $this->workerRepository->find((int) $queue->getWorkerId());

        if (! $worker) {
            throw new ZfExtended_NotFoundException('Export was terminated');
        }

        if ($worker->isDefunct()) {
            throw new Exception('E1607');
        }

        return $worker->isDone();
    }

    public function cleanUp(QueuedExport $queue): void
    {
        $exportDir = $this->composeExportDir($queue->getToken());

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($exportDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (! $file->isDir()) {
                unlink($file->getRealPath());
            }
        }

        rmdir($exportDir);

        $queue->delete();
    }

    public function composeExportDir(string $token): string
    {
        if (! is_dir($this->exportDirRoot)) {
            mkdir($this->exportDirRoot);
        }

        return $this->exportDirRoot . $token;
    }

    public function composeExportFilepath(QueuedExport $queueModel): string
    {
        $exportDir = $this->composeExportDir($queueModel->getToken());

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($exportDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (! $file->isDir() && str_contains($file->getRealPath(), $queueModel->getLocalFileName())) {
                return $exportDir . '/' . basename($file->getRealPath());
            }
        }

        throw new Exception('E1607');
    }
}
