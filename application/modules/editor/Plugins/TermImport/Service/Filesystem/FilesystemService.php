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

use Iterator;
use IteratorIterator;
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

            $contents = $this->mountManager->listContents($path);
            $iterator = $contents instanceof Iterator ? $contents : new IteratorIterator($contents);
            $iterator->rewind(); //otherwise it might happen that valid is false

            return $iterator->valid();
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

    /**
     * Move contents of dir if instruction.ini file exists in that dir
     *
     * @throws FilesystemException
     * @throws \ReflectionException
     */
    private function moveReadyProjectDir(StorageAttributes $item, string $sourceDir, array &$projectDirs): bool
    {
        // If $item does not refer to instruction file - return false
        if (! $this->isInstructionFile($item)) {
            return false;
        }

        // Get ini-file contents
        $temp_ini_path = tempnam(APPLICATION_DATA . '/tmp', 'termimport');
        file_put_contents($temp_ini_path, $this->mountManager->read($item->path()));
        $instructions = parse_ini_file($temp_ini_path, true, INI_SCANNER_TYPED);

        // If some custom top-level section was added to ini-file - move it's properties to the root
        foreach ($instructions as $sectionOrProperty => $itemsOrValue) {
            if (! in_array($sectionOrProperty, ['FileMapping', 'CollectionMapping']) && is_array($itemsOrValue)) {
                foreach ($itemsOrValue as $property => $value) {
                    $instructions[$property] = $value;
                }
                unset($instructions[$sectionOrProperty]);
            }
        }

        // If unable to parse ini-file - log that and return false
        if (false === $instructions) {
            $this->logger->invalidInstructions($item->path(), ['Invalid INI file']);

            return false;
        }

        // Prepare target dir name from source one
        $targetDir = str_replace(self::IMPORT_DIR, self::PROCESSING_DIR, $sourceDir);

        // Check ini-file validity and on failure - log and return false
        try {
            $projectDirs[$targetDir] = new InstructionsDTO($instructions, $this->logger);
        } catch (InvalidInstructionsIniFileException $e) {
            $this->logger->invalidInstructions($item->path(), $e->errors);

            return false;
        }

        // Move contents from source dir to target dir and return true
        $this->moveContents($sourceDir, $targetDir);

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

    /**
     * Move not importable tbx files from Import/ to Error/ dir
     *
     * @throws FilesystemException
     */
    public function moveFailedDir(string $sourceDir): void
    {
        // If source dir is not a processing dir - return
        if (! str_contains($sourceDir, self::PROCESSING_DIR)) {
            return;
        }

        // Prepare target dir name from source one
        $targetDir = str_replace(self::PROCESSING_DIR, self::FAILED_DIR, $sourceDir);

        // Do move and log if something moved
        if ($this->moveContents($sourceDir, $targetDir)) {
            $this->logger->importResultedInError($sourceDir);
        }
    }

    /**
     * Move successfully imported tbx files from Import/ to Import-success/ dir
     *
     * @throws FilesystemException
     */
    public function moveSuccessfulDir(string $sourceDir, array $successfulTbxFiles): void
    {
        // If source dir is not a processing dir - return
        if (! str_contains($sourceDir, self::PROCESSING_DIR)) {
            return;
        }

        // Prepare target dir name from source one
        $targetDir = str_replace(self::PROCESSING_DIR, self::IMPORT_SUCCESS_DIR, $sourceDir);

        // Do move and log if something moved
        if ($this->moveContents($sourceDir, $targetDir, $successfulTbxFiles)) {
            $this->logger->importSuccess($sourceDir);
        }
    }

    /**
     * Move contents of source dir into target dir, so the source dir is kept
     *
     * @param array|null $whiteList If given - only explicitly listed files will be moved
     * @throws FilesystemException
     */
    private function moveContents(string $sourceDir, string $targetDir, ?array $whiteList = null): int
    {
        // If $sourceDir is not a valid dir - return
        if (! $this->validDir($sourceDir)) {
            return 0;
        }

        // Moved files counter
        $movedQty = 0;

        /** @var StorageAttributes $item */
        foreach ($this->mountManager->listContents($sourceDir) as $item) {
            // Get base name (i.e. filename and extension)
            $name = pathinfo($item->path(), PATHINFO_BASENAME);

            // If $item is not a file
            if (! $item->isFile()

                // Or is a file but is not a tbx-file
                || strtolower(pathinfo($item->path(), PATHINFO_EXTENSION)) !== 'tbx'

                // Or is a tbx-file but no whitelist is given
                || ! isset($whiteList)

                // Or is but tbx is in whitelist
                || in_array($name, $whiteList)) {
                // Delete target item if already exists
                if ($this->mountManager->has($target = "$targetDir/$name")) {
                    $this->mountManager->delete($target);
                }

                // Move item from source dir to target dir
                $this->mountManager->move("$sourceDir/$name", $target);

                // Increment moved items counters
                $movedQty++;
            }
        }

        // Return total quantity of moved files
        return $movedQty;
    }
}
