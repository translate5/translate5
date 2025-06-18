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

namespace MittagQI\Translate5\Plugins\CotiHotfolder\Service;

use Throwable;
use Zend_Registry;
use ZfExtended_Logger;

class T5Logger
{
    public const T5_EVENT_DOMAIN = 'plugin.coti_hotfolder';

    public function __construct(
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Registry::get('logger')->cloneMe(self::T5_EVENT_DOMAIN)
        );
    }

    public function unsuccessfulFileOperation(Throwable $e, array $extra = []): void
    {
        $this->logger->error(
            'E1717', // TODO: add more codes below when needed
            'Plug-In Coti: Unsuccessful file operation: {errorMsg}',
            array_merge([
                'errorMsg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], $extra)
        );
    }

    public function exception(Throwable $e): void
    {
        $this->logger->exception($e);
    }

    public function exportSuccess(\editor_Models_Task $task, string $path): void
    {
        $this->logger->info('E1717', 'Plug-In Coti: Task exported to \'{path}\'', compact('task', 'path'));
    }

    public function exportFailed(\editor_Models_Task $task, string $msg): void
    {
        $this->logger->error('E1717', 'Plug-In Coti: Task export failed: {msg}', compact('task', 'msg'));
    }

    public function filesystemQueuedForCheck(string $key): void
    {
        $this->logger->info('E1717', 'Plug-In Coti: Filesystem [{key}] queued for check', compact('key'));
    }

    public function importSuccess(string $project): void
    {
        $this->logger->info('E1717', 'Plug-In Coti: [{project}]: Import Success', compact('project'));
    }

    public function importResultedInError(string $project): void
    {
        $this->logger->error('E1717', 'Plug-In Coti: [{project}]: Moved to Error dir', compact('project'));
    }

    public function workflowNotFound(string $project, string $workflow): void
    {
        $this->logger->warn(
            'E1717',
            'Plug-In Coti: [{project}]: Provided workflow [{workflow}] does not exists',
            compact('project', 'workflow')
        );
    }

    /*public function bconfNotFound(string $project, string $bconf): void
    {
        $this->logger->warn(
            'E1717',
            'Plug-In Coti: [{project}]: Provided bconf [{bconf}] does not exists',
            compact('project', 'bconf')
        );
    }*/

    public function fileNotFound(string $path): void
    {
        $this->logger->warn('E1717', "Plug-In Coti: File '{path}' not found", compact('path'));
    }

    public function languageNotFound(string $lang): void
    {
        $this->logger->warn(
            'E1717',
            "Plug-In Coti: Language [{lang}] not found in DB",
            compact('lang')
        );
    }

    public function errorsInCotiPackage(string $file, array $errors): void
    {
        $this->logger->error(
            'E1717',
            'Plug-In Coti: [{file}] has errors: {errors}',
            [
                'file' => $file,
                'errors' => PHP_EOL . '- ' . implode(PHP_EOL . '- ', $errors),
            ]
        );
    }

    public function invalidFilesystemConfig(?object $config): void
    {
        $config = null !== $config && method_exists($config, 'toArray')
            ? $config->toArray()
            : (array) $config;
        $this->logger->error(
            'E1717',
            'Plug-In Coti: Invalid filesystem config provided: "{config}"',
            [
                'config' => json_encode($config),
            ]
        );
    }

    public function filesystemIsNotReachable(?object $config): void
    {
        $config = null !== $config && method_exists($config, 'toArray')
            ? $config->toArray()
            : (array) $config;
        $this->logger->error(
            'E1717',
            'Plug-In Coti: Filesystem is not reachable: "{config}"',
            [
                'config' => json_encode($config),
            ]
        );
    }
}
