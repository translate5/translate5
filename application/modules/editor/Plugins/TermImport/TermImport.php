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

namespace MittagQI\Translate5\Plugins\TermImport;

use MittagQI\Translate5\Plugins\TermImport\DTO\InstructionsDTO;
use MittagQI\Translate5\Plugins\TermImport\Exception\TermImportException;
use MittagQI\Translate5\Plugins\TermImport\Service\Filesystem\FilesystemFactory;
use MittagQI\Translate5\Plugins\TermImport\Service\Filesystem\FilesystemService;
use MittagQI\Translate5\Plugins\TermImport\Service\LoggerService;
use MittagQI\Translate5\Plugins\TermImport\Worker\CheckHostForUpdates;
use editor_Plugins_TermImport_Services_Import as ImportService;
use Throwable;
class TermImport
{
    private LoggerService $logger;
    private ImportService $importService;

    public function __construct(private FilesystemFactory $filesystemFactory)
    {
        $this->logger = new LoggerService();
        $this->importService = new ImportService();
    }

    public static function computeFilesystemKey(int $customerId): string
    {
        return base64_encode((string) $customerId);
    }

    public function checkFilesystems(): void
    {
        $this->queueFilesystem(FilesystemFactory::DEFAULT_HOST_LABEL);

        $db = new \editor_Models_Db_CustomerConfig();
        $customerConfigs = $db->fetchAll($db
            ->select()->setIntegrityCheck(false)
            ->from($db, ['customerId', 'value'])
            ->where('LEK_customer_config.name = ?', FilesystemFactory::FILESYSTEM_CONFIG_NAME)
        );

        foreach ($customerConfigs as $config) {
            $filesystemConfig = json_decode($config['value']);

            if (empty($filesystemConfig)) {
                continue;
            }

            if (!FilesystemFactory::isValidFilesystemConfig($filesystemConfig)) {
                $this->logger->invalidFilesystemConfig($filesystemConfig);

                continue;
            }

            $this->queueFilesystem(self::computeFilesystemKey((int) $config['customerId']));
        }
    }

    public function queueFilesystem(string $key): void
    {
        $worker = new CheckHostForUpdates();
        $worker->init(null, ['filesystemKey' => $key]);
        $worker->queue();

        $this->logger->filesystemQueuedForCheck($key);
    }

    public function checkFilesystem(string $key): void
    {
        $filesystem = $this->filesystemFactory->getFilesystem($key);
        if (null === $filesystem) {
            return;
        }

        if (!$filesystem->validDir("{$key}://")) {
            return;
        }

        $processingDirs = $filesystem->moveReadyDirsToProcessingDir("{$key}://");
        $this->processFilesystem($processingDirs, $filesystem, $key);
    }

    private function processFilesystem(
        array $processingDirs,
        FilesystemService $filesystem,
        string $filesystemKey
    ): void {
        foreach ($processingDirs as $processingDir => $instructions) {
            $c = 0;
            while ($c < 2) {
                try {
                    $this->processDir($processingDir, $instructions, $filesystem, $filesystemKey);

                    // If we reached this line, it means there was not exception thrown by above,
                    // but still some tbx-files may have failed to import, and if so - they are
                    // kept in Import-running (e.g not moved to Import-success) so we rename that
                    // Import-running dir to Failed dir
                    $filesystem->moveFailedDir($processingDir);

                    continue 2;
                } catch (Throwable $e) {
                    $this->logger->exception(new TermImportException('E1574', [], $e));
                    ++$c;
                    // let's wait a couple of seconds before give another chance (network, db, etc)
                    sleep(random_int(1, 10));
                }
            }

            // Move failed to separate dir for debug purposes
            $filesystem->moveFailedDir($processingDir);
        }

        $this->logger->filesystemProcessed($filesystemKey);
    }

    /**
     * @throws TermImportException
     * @throws Throwable
     */
    private function processDir(
        string $processingDir,
        InstructionsDTO $instructions,
        FilesystemService $filesystem,
        string $filesystemKey
    ): void {

        // Get filesystem identifier usable in directory name, e.g it
        // will be either 'default' or some customer id from our db
        // We do that because $filesystemKey is a base64-encoded value
        // so it contains characters incompatible for use in any paths to dirs/files
        $filesystemKeyDecoded = $filesystemKey === FilesystemFactory::DEFAULT_HOST_LABEL
            ? $filesystemKey
            : base64_decode($filesystemKey);

        // Get download path
        $targetTbxDir = $this->importService->prepareTbxDownloadTempDir($filesystemKeyDecoded);

        // Try to download tbx files mentioned in $instructions->FileMapping
        try {

            // For each file that mentioned in $instructions->FileMapping but that not exists
            // - warning with code E1577 will be logged and it will be unset from $instructions->FileMapping
            $filesystem->downloadTbxFiles(
                $instructions,
                $processingDir,
                'local://' . ltrim($targetTbxDir, '/')
            );

        // If something was thrown - remove temp dir andre throw
        } catch (Throwable $e) {
            rmdir($targetTbxDir);
            throw $e;
        }

        // If there are still file mappings kept/left
        if (count($instructions->FileMapping) > 0) {

            // Do import and get tbx-files that were successfully imported
            $importedTbxFiles = $this->importService->import($instructions, $targetTbxDir, $filesystem->getAdapterType(), $this->logger);

            // Move processing-dir's successfully imported tbx-files to successful-dir
            // or the whole dir if no failed tbx-files
            if ($importedTbxFiles) {
                $filesystem->moveSuccessfulDir($processingDir, array_keys($importedTbxFiles));
            }
        }
    }
}
