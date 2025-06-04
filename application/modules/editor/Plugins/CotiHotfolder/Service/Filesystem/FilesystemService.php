<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

namespace MittagQI\Translate5\Plugins\CotiHotfolder\Service\Filesystem;

use Iterator;
use IteratorIterator;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperationFailed;
use League\Flysystem\MountManager;
use League\Flysystem\StorageAttributes;
use MittagQI\Translate5\Plugins\CotiHotfolder\Exception\UnableToMoveFileToProcessingException;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\T5Logger;
use const DIRECTORY_SEPARATOR;

class FilesystemService
{
    public const EXPORT_DIR = 'translated';

    public const PROCESSING_DIR = 'Import-running';

    public const INSTRUCTION_FILE_NAME = 'COTI.xml';

    public const IMPORT_FILE_EXT = '.coti';

    public const IMPORT_DIR = 'untranslated';

    public const IMPORT_SUCCESS_DIR = 'archive/untranslated';

    public const FAILED_DIR = 'errors/untranslated';

    public const LOG_DIR = 'logs/TMS';

    public function __construct(
        private readonly MountManager $mountManager,
        private readonly T5Logger $logger,
    ) {
    }

    public function validFile(string $path): bool
    {
        try {
            return $this->mountManager->fileExists($path);
        } catch (FilesystemException) {
            return false;
        }
    }

    public function validDir(string $path): bool
    {
        try {
            if (! $this->mountManager->directoryExists($path)) {
                return false;
            }

            $contents = $this->mountManager->listContents($path)->getIterator();
            $iterator = $contents instanceof Iterator ? $contents : new IteratorIterator($contents);

            return $iterator->valid();
        } catch (FilesystemException) {
            return false;
        }
    }

    /**
     * @return iterable<string>
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function getReadyCotiFilesList(string $dirPath): iterable
    {
        if (! $this->validDir($dirPath)
            || str_contains($dirPath, self::PROCESSING_DIR)
            || str_contains($dirPath, self::FAILED_DIR)
            || str_contains($dirPath, self::IMPORT_SUCCESS_DIR)
            || str_contains($dirPath, self::EXPORT_DIR)
        ) {
            return yield from [];
        }

        foreach ($this->mountManager->listContents($dirPath) as $dir) {
            if (! $this->validDir($dir->path())) {
                continue;
            }

            yield from $this->getReadyCotiFiles($dir->path());
        }
    }

    private function getReadyCotiFiles(string $directory): iterable
    {
        $importDir = $directory . DIRECTORY_SEPARATOR . self::IMPORT_DIR;

        if (! $this->validDir($importDir)) {
            return yield from [];
        }

        /** @var StorageAttributes $importFile */
        foreach ($this->mountManager->listContents($importDir) as $importFile) {
            if (! $importFile->isFile() || ! str_ends_with($importFile->path(), self::IMPORT_FILE_EXT)) {
                continue;
            }

            yield $importFile->path();
        }
    }

    /**
     * @throws UnableToMoveFileToProcessingException
     */
    public function moveReadyToProcessing(string $readyFilePath): string
    {
        $targetPath = str_replace(self::IMPORT_DIR, self::PROCESSING_DIR, $readyFilePath);

        try {
            $this->mountManager->move($readyFilePath, $targetPath);

            return $targetPath;
        } catch (FilesystemException|FilesystemOperationFailed $e) {
            $this->logOperationError($readyFilePath, $targetPath, $e);

            throw new UnableToMoveFileToProcessingException($readyFilePath, $targetPath);
        }
    }

    public function moveToFailedDir(string $filePath): void
    {
        if (! $this->validFile($filePath) || ! str_contains($filePath, self::PROCESSING_DIR)) {
            return;
        }

        $targetPath = str_replace([self::IMPORT_DIR, self::PROCESSING_DIR], self::FAILED_DIR, $filePath);

        try {
            $this->mountManager->move($filePath, $targetPath);
            $this->logger->importResultedInError($filePath);
        } catch (FilesystemOperationFailed|FilesystemException $e) {
            $this->logOperationError($filePath, $targetPath, $e);
        }
    }

    public function moveToSuccessfulDir(string $filePath, bool $keepFile): void
    {
        if (! $this->validFile($filePath) || ! str_contains($filePath, self::PROCESSING_DIR)) {
            return;
        }

        $targetPath = str_replace(self::PROCESSING_DIR, self::IMPORT_SUCCESS_DIR, $filePath);

        try {
            if ($keepFile) {
                $this->mountManager->move($filePath, $targetPath);
            } else {
                $this->mountManager->delete($filePath);
            }
            $this->logger->importSuccess($filePath);
        } catch (FilesystemException|FilesystemOperationFailed $e) {
            $this->logOperationError($filePath, $targetPath, $e);
        }
    }

    public function downloadCotiFile(string $remoteFile, string $localFile): void
    {
        try {
            $this->mountManager->copy($remoteFile, $localFile);
        } catch (FilesystemException|FilesystemOperationFailed $e) {
            $this->logOperationError($remoteFile, $localFile, $e);
        }
    }

    public function uploadCotiFile(string $localFile, string $remoteFile): bool
    {
        try {
            $this->mountManager->copy($localFile, $remoteFile);

            return true;
        } catch (FilesystemException|FilesystemOperationFailed $e) {
            $this->logOperationError($localFile, $remoteFile, $e);

            return false;
        }
    }

    public function uploadCotiLog(string $localFile, string $remoteCotiFile): void
    {
        $remoteLogFile = str_replace([self::PROCESSING_DIR, self::EXPORT_DIR], self::LOG_DIR, $remoteCotiFile) . '.log';

        try {
            if (! $this->mountManager->fileExists($localFile)) {
                return;
            }
            if ($this->mountManager->fileExists($remoteLogFile)) {
                $this->mountManager->write($remoteLogFile, $this->mountManager->read($remoteLogFile) . $this->mountManager->read($localFile));
                $this->mountManager->delete($localFile);
            } else {
                $this->mountManager->move($localFile, $remoteLogFile);
            }
        } catch (FilesystemException|FilesystemOperationFailed $e) {
            $this->logOperationError($localFile, $remoteLogFile, $e);
        }
    }

    private function logOperationError(string $filePath, string $targetPath, FilesystemException|\Exception $e): void
    {
        $extra = [];

        try {
            $extra = [
                'sourceExists' => $this->mountManager->fileExists($filePath),
                'targetExists' => $this->mountManager->directoryExists($targetPath),
                'originalMessage' => $e->getMessage(),
            ];
        } catch (FilesystemException $e) {
            $extra['fileOrDirExistsException'] = $e->getMessage();
        }

        $this->logger->unsuccessfulFileOperation($e, $extra);
    }
}
