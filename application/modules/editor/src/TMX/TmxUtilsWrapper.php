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

namespace MittagQI\Translate5\TMX;

use MittagQI\Translate5\T5Memory\DTO\TmxFilterOptions;
use MittagQI\Translate5\TMX\Exception\TmxUtilsException;

class TmxUtilsWrapper
{
    private readonly string $tmxUtilsPath;

    public function __construct(

    ) {
        $this->tmxUtilsPath = realpath(__DIR__ . '/binary/tmx-utils');
        if (! is_executable($this->tmxUtilsPath)) {
            chmod($this->tmxUtilsPath, 0755);
        }
    }

    public static function create(): self
    {
        return new self();
    }

    public function concatDirFiles(
        string $dirPath,
        string $outputFile,
        bool $unprotect,
    ): void {
        $boolVal = $unprotect ? 'true' : 'false';

        [$returnVar, $out, $error] = $this->runCommand(
            "{$this->tmxUtilsPath} concat_dir '$dirPath' '$outputFile' $boolVal"
        );

        if ($returnVar !== 0) {
            throw TmxUtilsException::concatFailed($error);
        }
    }

    public function concat(array $inputFiles, string $outputFile, bool $unprotect, bool $deleteInputFiles = true): void
    {
        $boolVal = $unprotect ? 'true' : 'false';

        $files = implode(' ', $inputFiles);

        [$returnVar, $out, $error] = $this->runCommand(
            "{$this->tmxUtilsPath} concat '$outputFile' $boolVal $files",
        );

        if ($returnVar !== 0) {
            throw TmxUtilsException::concatFailed($error);
        }

        if ($deleteInputFiles) {
            foreach ($inputFiles as $file) {
                @unlink($file);
            }
        }
    }

    public function trim(string $inputFile, string $outputFile, int $tuToSkipCount, bool $deleteInputFile = true): void
    {
        [$returnVar, $out, $error] = $this->runCommand(
            "{$this->tmxUtilsPath} trim '$inputFile' '$outputFile' $tuToSkipCount",
        );

        if ($returnVar !== 0) {
            throw TmxUtilsException::trimFailed($error);
        }

        if ($deleteInputFile) {
            @unlink($inputFile);
        }
    }

    public function filter(
        string $inputFile,
        string $outputFile,
        TmxFilterOptions $filterOptions,
        bool $deleteInputFile = true,
    ): void {
        $skipAuthor = $filterOptions->skipAuthor ? 'true' : 'false';
        $skipDocument = $filterOptions->skipDocument ? 'true' : 'false';
        $skipContext = $filterOptions->skipContext ? 'true' : 'false';
        $keepDiffTargets = $filterOptions->preserveTargets ? 'true' : 'false';

        // filter <input.tmx> <output.tmx> <skipAuthor: true|false> <skipDocument: true|false> <skipContext: true|false> <keepDiffTargets: true|false>
        [$returnVar, $out, $error] = $this->runCommand(
            "{$this->tmxUtilsPath} filter '$inputFile' '$outputFile' $skipAuthor $skipDocument $skipContext $keepDiffTargets",
        );

        if ($returnVar !== 0) {
            throw TmxUtilsException::filterFailed($error);
        }

        if ($deleteInputFile) {
            @unlink($inputFile);
        }
    }

    /**
     * @return array{int, string, string}
     */
    private function runCommand(string $cmd): array
    {
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($cmd, $descriptors, $pipes, __DIR__, null);
        if (! \is_resource($process)) {
            throw new \RuntimeException('Failed to start process');
        }

        fclose($pipes[0]); // no stdin

        // Read both streams (simple version; fine for small/medium outputs)
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [$exitCode, $stdout, $stderr];
    }
}
