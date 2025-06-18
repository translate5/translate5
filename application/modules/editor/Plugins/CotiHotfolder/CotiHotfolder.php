<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\CotiHotfolder;

use editor_Models_Task;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\CotiLogEntry;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\CotiLogger;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\Filesystem\FilesystemFactory;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\Filesystem\FilesystemService;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\FilesystemProcessor;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\T5Logger;
use MittagQI\Translate5\Plugins\CotiHotfolder\Worker\CheckHostForUpdates;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Zend_Filter_Compress_Zip;
use ZfExtended_Utils;
use ZipArchive;
use const DIRECTORY_SEPARATOR;

class CotiHotfolder
{
    public const PLUGIN_PREFIX = 'coti_hotfolder::';

    /**
     * "domain" which is used for all event-logging entries of this plugin
     * see also https://confluence.translate5.net/display/TAD/ErrorHandling+and+Application+Logger.
     *
     * @var string
     */
    private const PREPARED_EXPORT_DIR = 'CotiHotfolderExport';

    public function __construct(
        private readonly FilesystemFactory $filesystemFactory,
        private readonly FilesystemProcessor $filesystemProcessor,
        private readonly T5Logger $logger,
        private readonly CotiLogger $cotiLogger,
    ) {
    }

    public static function create(): self
    {
        return new self(
            FilesystemFactory::create(),
            FilesystemProcessor::create(),
            T5Logger::create(),
            new CotiLogger()
        );
    }

    public static function getTaskPreparedExportDir(editor_Models_Task $task): string
    {
        $dir = realpath($task->getAbsoluteTaskDataPath()) . DIRECTORY_SEPARATOR . self::PREPARED_EXPORT_DIR;

        if (! is_dir($dir)) {
            @mkdir($dir);
        }

        return $dir;
    }

    public static function computeFilesystemKey(int $customerId): string
    {
        return base64_encode((string) $customerId);
    }

    public function checkFilesystem(string $key): void
    {
        $filesystem = $this->filesystemFactory->getFilesystem($key);

        if (null === $filesystem) {
            return;
        }

        $this->filesystemProcessor->process($filesystem, $key);
    }

    public function checkFilesystems(): void
    {
        if (null !== $this->filesystemFactory->getFilesystem(FilesystemFactory::DEFAULT_HOST_LABEL)) {
            $this->queueFilesystem(FilesystemFactory::DEFAULT_HOST_LABEL);
        }

        $db = new \editor_Models_Db_CustomerConfig();
        $customerConfigs = $db->fetchAll(
            $db
                ->select()
                ->from($db, ['customerId'])
                ->where('LEK_customer_config.name = ?', FilesystemFactory::FILESYSTEM_CONFIG_NAME)
        );

        foreach ($customerConfigs as $config) {
            $key = self::computeFilesystemKey((int) $config['customerId']);

            if (null !== $this->filesystemFactory->getFilesystem($key)) {
                $this->queueFilesystem($key);
            }
        }
    }

    public function uploadCotiArchive(array $taskIds): void
    {
        $task = new editor_Models_Task();
        $task->load(array_pop($taskIds)); // load last task, which initiated the upload

        $remoteDir = str_replace(
            [self::PLUGIN_PREFIX, FilesystemService::PROCESSING_DIR],
            ['', FilesystemService::EXPORT_DIR],
            $task->getForeignId()
        );

        $filesystemKey = explode('://', $remoteDir)[0];
        $filesystem = $this->filesystemFactory->getFilesystem($filesystemKey);

        if (null === $filesystem) {
            return;
        }

        $preparedExportDir = self::getTaskPreparedExportDir($task); // CotiExport, with 'translation files' dir
        if (! is_dir($preparedExportDir)) {
            return;
        }

        $preparedExportDirs = [];
        $remoteExportDir = dirname(dirname($remoteDir));

        [$cotiPkgName, $cotiProjectDirName] = explode('/', substr($remoteDir, strlen($remoteExportDir) + 1));
        // COTI.xml is stored there and we create temp. zip archive there to upload
        $projectDataPath = self::getProjectDataPath((int) $task->getProjectId());
        $cotiPkgName .= FilesystemService::IMPORT_FILE_EXT; // add file extension
        $cotiArchivePathInProjectDir = realpath($projectDataPath) . $cotiPkgName;
        $this->cotiLogger->setLogTarget($cotiArchivePathInProjectDir);
        $this->cotiLogger->log(CotiLogEntry::PackageTranslated, '', __FILE__);
        // shouldn't exist, but to be safe
        if (is_file($cotiArchivePathInProjectDir)) {
            unlink($cotiArchivePathInProjectDir);
        }

        $preparedExportDirs[] = $preparedExportDir;

        self::copyInstructionsFile($projectDataPath, $preparedExportDir . DIRECTORY_SEPARATOR);

        $errorMsg = self::addToZipArchive(
            $cotiArchivePathInProjectDir,
            $preparedExportDir,
            $cotiProjectDirName . DIRECTORY_SEPARATOR
        );

        if (! $errorMsg && ! empty($taskIds)) {
            // add related tasks
            foreach ($taskIds as $taskId) {
                $relatedTask = new editor_Models_Task();
                $relatedTask->load($taskId);
                $preparedExportDir = self::getTaskPreparedExportDir($relatedTask); // CotiExport, with 'translation files' dir
                if (is_dir($preparedExportDir)) {
                    $preparedExportDirs[] = $preparedExportDir;

                    $projectDataPath = self::getProjectDataPath((int) $relatedTask->getProjectId());
                    self::copyInstructionsFile($projectDataPath, $preparedExportDir . DIRECTORY_SEPARATOR);

                    $errorMsg = self::addToZipArchive(
                        $cotiArchivePathInProjectDir,
                        $preparedExportDir,
                        basename($relatedTask->getForeignId()) . DIRECTORY_SEPARATOR
                    );
                    if ($errorMsg) {
                        break;
                    }
                }
            }
        }

        $remoteCotiFile = $remoteExportDir . DIRECTORY_SEPARATOR . $cotiPkgName;
        if (empty($errorMsg)) {
            $this->cotiLogger->log(CotiLogEntry::PackageGenerated, '', __FILE__);

            if ($filesystem->uploadCotiFile(
                'local://' . ltrim($cotiArchivePathInProjectDir, DIRECTORY_SEPARATOR),
                $remoteCotiFile
            )) {
                $this->cotiLogger->log(CotiLogEntry::Completed, '', __FILE__);
                $this->logger->exportSuccess($task, $remoteCotiFile);
            } else {
                $this->cotiLogger->log(CotiLogEntry::PackageWriteError, '', __FILE__);
                $errorMsg = 'Package can\’t be uploaded';
            }
        }
        if (! empty($errorMsg)) {
            $this->logger->exportFailed($task, $errorMsg . ' (' . $cotiPkgName . ')');
        }

        $filesystem->uploadCotiLog(
            'local://' . ltrim($this->cotiLogger->getLogFilePath(), DIRECTORY_SEPARATOR),
            $remoteCotiFile
        );

        if (is_file($cotiArchivePathInProjectDir)) {
            unlink($cotiArchivePathInProjectDir);
        }
        foreach ($preparedExportDirs as $preparedExportDir) {
            ZfExtended_Utils::recursiveDelete($preparedExportDir);
        }
    }

    public function queueFilesystem(string $key): void
    {
        $worker = new CheckHostForUpdates();
        $worker->init(null, [
            'filesystemKey' => $key,
        ]);
        $worker->queue();

        $this->logger->filesystemQueuedForCheck($key);
    }

    private static function addToZipArchive(string $zipFilePath, string $dirToPackPath, string $projectDir): string
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

        // Create recursive directory iterator and add files
        /** @var SplFileInfo[] $files */
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirToPackPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            // Skip directories (they would be added automatically)
            if (! $file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($dirToPackPath) + 1);
                // Add current file to archive
                $zip->addFile($filePath, $projectDir . $relativePath);
            }
        }
        $zip->close();

        return '';
    }

    private static function getProjectDataPath(int $projectId): string
    {
        $project = new editor_Models_Task();
        $project->load($projectId);

        return $project->getAbsoluteTaskDataPath() . DIRECTORY_SEPARATOR;
    }

    private static function copyInstructionsFile(string $pathFrom, string $pathTo): void
    {
        copy($pathFrom . FilesystemService::INSTRUCTION_FILE_NAME, $pathTo . FilesystemService::INSTRUCTION_FILE_NAME);
    }
}
