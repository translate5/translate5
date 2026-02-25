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

use GuzzleHttp\Psr7\Stream;
use MittagQI\Translate5\T5Memory\Exception\ExportException;
use MittagQI\Translate5\TMX\Exception\TmxConcatException;
use MittagQI\Translate5\TMX\Exception\TmxUtilsException;
use Zend_Config;
use ZfExtended_Utils;

class ConcatTmx
{
    public function __construct(
        private readonly TmxUtilsWrapper $tmxUtilsWrapper,
        private readonly TmxIterator $tmxIterator,
        private readonly Zend_Config $config,
    ) {
    }

    public static function create(): self
    {
        return new self(
            TmxUtilsWrapper::create(),
            TmxIterator::create(),
            \Zend_Registry::get('config'),
        );
    }

    public function concatDirFiles(
        string $dirPath,
        string $resultTmxFilename,
        bool $unprotect,
        bool $deleteFiles = true
    ): void {
        if ($this->config->runtimeOptions->LanguageResources->t5memory?->useTmxUtilsConcat) {
            try {
                $this->tmxUtilsWrapper->concatDirFiles($dirPath, $resultTmxFilename, $unprotect);
            } catch (TmxUtilsException $e) {
                throw new ExportException($e->getMessage());
            } finally {
                if ($deleteFiles) {
                    ZfExtended_Utils::recursiveDelete($dirPath);
                }
            }

            return;
        }

        $out = fopen($resultTmxFilename, 'w');
        fwrite($out, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);

        $writtenElements = 0;

        foreach (glob($dirPath . DIRECTORY_SEPARATOR . '*.tmx') ?: [] as $i => $file) {
            $resource = fopen($file, 'r');
            foreach ($this->tmxIterator->iterateTmx(new Stream($resource), 0 === $i, $writtenElements, $unprotect) as $node) {
                fwrite($out, $node);
            }
        }

        if ($writtenElements > 0) {
            fwrite($out, '</body>' . PHP_EOL);
        }

        fwrite($out, '</tmx>');
        fclose($out);

        if ($deleteFiles) {
            \ZfExtended_Utils::recursiveDelete($dirPath);
        }
    }

    public function concat(array $tmxFiles, string $resultTmxFilename, bool $unprotect, bool $deleteFiles = true): void
    {
        if ($this->config->runtimeOptions->LanguageResources->t5memory->useTmxUtilsConcat) {
            $files = [];
            foreach ($tmxFiles as $file) {
                $files[] = escapeshellarg(realpath($file));
            }

            try {
                $this->tmxUtilsWrapper->concat($files, $resultTmxFilename, $unprotect);
            } catch (TmxUtilsException $e) {
                throw new TmxConcatException($e->getMessage());
            }

            return;
        }

        $out = fopen($resultTmxFilename, 'w');
        fwrite($out, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);

        $tuCount = 0;

        foreach ($tmxFiles as $i => $file) {
            $resource = fopen($file, 'r');
            foreach ($this->tmxIterator->iterateTmx(new Stream($resource), 0 === $i, $tuCount, $unprotect) as $node) {
                fwrite($out, $node);
            }
        }

        if ($tuCount > 0) {
            fwrite($out, '</body>' . PHP_EOL);
        }

        fwrite($out, '</tmx>');
        fclose($out);

        if (! $deleteFiles) {
            return;
        }

        foreach ($tmxFiles as $file) {
            @unlink($file);
        }
    }
}
