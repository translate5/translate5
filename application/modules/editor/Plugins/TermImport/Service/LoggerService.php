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

namespace MittagQI\Translate5\Plugins\TermImport\Service;

use Throwable;
use Zend_Registry;
use ZfExtended_Logger;

class LoggerService
{
    public const T5_EVENT_DOMAIN = 'plugin.termimport';

    private ZfExtended_Logger $logger;

    public function __construct()
    {
        $this->logger = Zend_Registry::get('logger')->cloneMe(self::T5_EVENT_DOMAIN);
    }

    public function exception(Throwable $e): void
    {
        $this->logger->exception($e);
    }

    public function invalidFilesystemConfig(?object $config): void
    {
        $config = null !== $config && method_exists($config, 'toArray')
            ? $config->toArray()
            : (array) $config;
        $this->logger->error(
            'E1569',
            'Plug-In TermImport: Invalid filesystem config provided: "{config}"',
            [
                'config' => json_encode($config),
            ]
        );
    }

    public function filesystemQueuedForCheck(string $key): void
    {
        $this->logger->info('E1570', 'Plug-In TermImport: Filesystem [{key}] queued for check', compact('key'));
    }

    public function filesystemIsNotReachable(?object $config): void
    {
        $config = null !== $config && method_exists($config, 'toArray')
            ? $config->toArray()
            : (array) $config;
        $this->logger->error(
            'E1571',
            'Plug-In TermImport: Filesystem is not reachable: "{config}"',
            [
                'config' => json_encode($config),
            ]
        );
    }

    public function invalidInstructions(string $instructionFile, array $errors): void
    {
        $this->logger->error(
            'E1572',
            'Plug-In TermImport: [{instruction}] has invalid structure: {errors}',
            [
                'instruction' => $instructionFile,
                'errors' => PHP_EOL . '- ' . implode(PHP_EOL . '- ', $errors),
            ]
        );
    }

    public function customerNotFound(string $customerNumber): void
    {
        $this->logger->error(
            'E1573',
            "Plug-In TermImport: Customer number [{customerNumber}] not found in DB",
            compact('customerNumber')
        );
    }

    public function filesystemProcessed(string $key): void
    {
        $this->logger->info('E1575', 'Plug-In TermImport: Filesystem [{key}] processed', compact('key'));
    }

    public function fileNotFound(string $path): void
    {
        $this->logger->warn(
            'E1577',
            "Plug-In TermImport: File '{path}' not found",
            compact('path')
        );
    }

    public function fileImportSuccess(string $path): void
    {
        $this->logger->info(
            'E1578',
            "Plug-In TermImport: Tbx file '{path}' was successfully imported",
            compact('path')
        );
    }

    public function fileImportFailure(string $path): void
    {
        $this->logger->error(
            'E1579',
            "Plug-In TermImport: Tbx file '{path}' import failed due to it's invalid or containing no term entries",
            compact('path')
        );
    }

    public function zeroImportedFiles(string $path): void
    {
        $this->logger->warn(
            'E1580',
            'Plug-In TermImport: No files were imported from dir "{path}"',
            compact('path')
        );
    }

    public function importSuccess(string $tbxDir): void
    {
        $this->logger->info('E1581', 'Plug-In TermImport: [{tbxDir}]: Import Success', compact('tbxDir'));
    }

    public function importResultedInError(string $tbxDir): void
    {
        $this->logger->error('E1582', 'Plug-In TermImport: [{tbxDir}]: Moved to Error dir', compact('tbxDir'));
    }
}
