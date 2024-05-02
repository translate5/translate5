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

namespace MittagQI\Translate5\Plugins\TermImport\Service\Filesystem;

use League\Flysystem\FilesystemException;
use League\Flysystem\MountManager;
use League\Flysystem\StorageAttributes;
use MittagQI\Translate5\Plugins\TermImport\DTO\InstructionsDTO;
use MittagQI\Translate5\Plugins\TermImport\Exception\InvalidInstructionsIniFileException;
use MittagQI\Translate5\Plugins\TermImport\Service\LoggerService;

class FilesystemService
{
    public const EXPORT_DIR = 'Export';

    public const PROCESSING_DIR = 'Import-running';

    private const INSTRUCTION_FILE_NAME = 'instruction.ini';

    private const IMPORT_DIR = 'Import';

    private const IMPORT_SUCCESS_DIR = 'Import-success';

    private const FAILED_DIR = 'Error';

    private int $maxDepth = 3;

    public function __construct(
        private MountManager $mountManager,
        private LoggerService $logger,
        private string $adapterType
    ) {
    }

    public function getAdapterType()
    {
        return $this->adapterType;
    }

    public function validDir(string $path): bool
    {
        try {
            if (! $this->mountManager->directoryExists($path)) {
                return false;
            }

            return $this->mountManager->listContents($path)->getIterator()->valid();
        } catch (FilesystemException) {
            return false;
        }
    }

    /**
     * @return array<string, InstructionsDTO>
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function moveReadyDirsToProcessingDir(string $dirPath, int $depth = 0): array
    {
        if (! $this->validDir($dirPath)) {
            return [];
        }

        if (
            str_contains($dirPath, self::PROCESSING_DIR)
            || str_contains($dirPath, self::FAILED_DIR)
            || str_contains($dirPath, self::IMPORT_SUCCESS_DIR)
        ) {
            return [];
        }

        $projectDirs = [];

        /** @var StorageAttributes $item */
        foreach ($this->mountManager->listContents($dirPath) as $item) {
            if ($item->isDir() && $depth < $this->maxDepth) {
                $projectDirs = array_merge($projectDirs, $this->moveReadyDirsToProcessingDir($item->path(), $depth + 1));

                continue;
            }

            if (! str_contains($dirPath . '/', '/' . self::IMPORT_DIR . '/')) {
                continue;
            }

            if ($this->moveReadyProjectDir($item, $dirPath, $projectDirs)) {
                break;
            }
        }

        return $projectDirs;
    }

    private function isInstructionFile(StorageAttributes $file): bool
    {
        return $file->isFile() && str_contains($file->path(), self::INSTRUCTION_FILE_NAME);
    }

    private function moveReadyProjectDir(StorageAttributes $item, string $dirPath, array &$projectDirs): bool
    {
        if (! $this->isInstructionFile($item)) {
            return false;
        }

        $temp_ini_path = tempnam(APPLICATION_DATA . '/tmp', 'termimport');
        file_put_contents($temp_ini_path, $this->mountManager->read($item->path()));
        $instructions = parse_ini_file($temp_ini_path, true, INI_SCANNER_TYPED);

        if (false === $instructions) {
            $this->logger->invalidInstructions($item->path(), ['Invalid INI file']);

            return false;
        }

        $targetPath = str_replace(self::IMPORT_DIR, self::PROCESSING_DIR, $dirPath);

        try {
            $projectDirs[$targetPath] = new InstructionsDTO($instructions, $this->logger);
        } catch (InvalidInstructionsIniFileException $e) {
            $this->logger->invalidInstructions($item->path(), $e->errors);

            return false;
        }

        $this->mountManager->move($dirPath, $targetPath);

        return true;
    }

    public function downloadTbxFiles(
        InstructionsDTO $instructions,
        string $sourceDir,
        string $targetDir
    ): void {
        foreach ($instructions->FileMapping as $tbxFileName => $termCollectionName) {
            $sourceFilePath = $sourceDir . '/' . $tbxFileName;
            $targetFilePath = $targetDir . '/' . $tbxFileName;

            if (! $this->mountManager->fileExists($sourceFilePath)) {
                $this->logger->fileNotFound($tbxFileName);
                unset($instructions->FileMapping[$tbxFileName]);

                continue;
            }

            $this->mountManager->copy($sourceFilePath, $targetFilePath);
        }
    }

    public function moveFailedDir(string $dirPath): void
    {
        if (! $this->validDir($dirPath) || ! str_contains($dirPath, self::PROCESSING_DIR)) {
            return;
        }

        $targetPath = str_replace(self::PROCESSING_DIR, self::FAILED_DIR, $dirPath);

        $this->mountManager->move($dirPath, $targetPath);
        $this->logger->importResultedInError($dirPath);
    }

    public function moveSuccessfulDir(string $dirPath, array $successfulTbxFiles): void
    {
        if (! $this->validDir($dirPath) || ! str_contains($dirPath, self::PROCESSING_DIR)) {
            return;
        }

        $targetPath = str_replace(self::PROCESSING_DIR, self::IMPORT_SUCCESS_DIR, $dirPath);

        $fullSuccess = true;
        /** @var StorageAttributes $item */
        foreach ($this->mountManager->listContents($dirPath) as $item) {
            $name = pathinfo($item->path(), PATHINFO_BASENAME);
            if (! $item->isFile()
                || strtolower(pathinfo($item->path(), PATHINFO_EXTENSION)) !== 'tbx'
                || in_array($name, $successfulTbxFiles)) {
                $this->mountManager->move($dirPath . '/' . $name, $targetPath . '/' . $name);
            } else {
                $fullSuccess = false;
            }
        }

        if ($fullSuccess) {
            $this->mountManager->deleteDirectory($dirPath);
        }

        $this->logger->importSuccess($dirPath);
    }
}
