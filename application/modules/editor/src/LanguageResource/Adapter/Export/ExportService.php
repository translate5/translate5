<?php

declare(strict_types=1);

namespace MittagQI\Translate5\LanguageResource\Adapter\Export;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\T5Memory\ExportMemoryWorker;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZfExtended_Factory;
use ZfExtended_Models_Worker;

class ExportService
{
    /**
     * @throws \ZfExtended_Models_Entity_NotFoundException
     */
    public function getFilenameIfReady(int $workerId, string $token): ?string
    {
        $worker = ZfExtended_Factory::get(ZfExtended_Models_Worker::class);
        $worker->load($workerId);

        if ($worker->isDefunct()) {
            throw new Exception('E1607');
        }

        if (! $worker->isDone()) {
            return null;
        }

        $exportDir = $this->composeExportDir($token);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($exportDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (! $file->isDir() && str_contains($file->getRealPath(), $worker->getHash())) {
                return basename($file->getRealPath());
            }
        }

        throw new Exception('E1607');
    }

    public function cleanUp(string $token): void
    {
        $exportDir = $this->composeExportDir($token);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($exportDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (! $file->isDir()) {
                unlink($file->getRealPath());
            }
        }

        rmDir($exportDir);
    }

    public function exportStarted(string $token): bool
    {
        return is_dir($this->composeExportDir($token));
    }

    public function composeExportDir(string $token): string
    {
        return APPLICATION_PATH . '/../data/TMExport/' . $token;
    }

    public function composeExportFilepath(string $token, string $filename): string
    {
        return APPLICATION_PATH . "/../data/TMExport/$token/$filename";
    }

    public function queueExportWorker(LanguageResource $languageResource, string $mime, string $token): int
    {
        $worker = ZfExtended_Factory::get(ExportMemoryWorker::class);

        if ($worker->init(parameters: [
            'languageResourceId' => $languageResource->getId(),
            'mime' => $mime,
            'exportFolder' => $this->composeExportDir($token),
        ])) {
            return $worker->queue();
        }

        throw new Exception('E1608');
    }
}
