<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\T5Memory;

namespace MittagQI\Translate5\T5Memory\Reorganize;

use DateTime;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use Http\Client\Exception\HttpException;
use MittagQI\Translate5\LanguageResource\Adapter\Export\TmFileExtension;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\T5Memory\CloneMemoryService;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\DTO\ReorganizeOptions;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\Exception\CloneException;
use MittagQI\Translate5\T5Memory\Exception\ImportResultedInErrorException;
use MittagQI\Translate5\T5Memory\Exception\ReorganizeException;
use MittagQI\Translate5\T5Memory\Exception\UnableToCreateMemoryException;
use MittagQI\Translate5\T5Memory\ExportService;
use MittagQI\Translate5\T5Memory\FlushMemoryService;
use MittagQI\Translate5\T5Memory\ImportService;
use MittagQI\Translate5\T5Memory\WipeMemoryService;
use Psr\Http\Client\ClientExceptionInterface;
use Zend_Registry;
use ZfExtended_Logger;

class ManualReorganizeService
{
    public const REORGANIZE_DIR = APPLICATION_DATA . '/reorganize';

    public function __construct(
        private readonly ZfExtended_Logger $logger,
        private readonly ExportService $exportService,
        private readonly ImportService $importService,
        private readonly CloneMemoryService $cloneService,
        private readonly FlushMemoryService $flushService,
        private readonly WipeMemoryService $wipeMemoryService,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Registry::get('logger')->cloneMe('editor.t5memory.reorganize'),
            ExportService::create(),
            ImportService::create(),
            CloneMemoryService::create(),
            FlushMemoryService::create(),
            WipeMemoryService::create(),
        );
    }

    /**
     * @throws ReorganizeException
     */
    public function reorganizeTm(
        LanguageResource $languageResource,
        string $tmName,
        ReorganizeOptions $reorganizeOptions,
        bool $isInternalFuzzy = false,
    ): void {
        $exportDir = self::REORGANIZE_DIR . '/' . date_format(new DateTime(), 'Y-m-d');
        $exportFilePath = $exportDir . '/' . $languageResource->getId() . '_' . $tmName . '.tmx';

        @mkdir($exportDir, 0777, true);

        $file = $this->exportService->export(
            $languageResource,
            TmFileExtension::TMX,
            $tmName,
            false,
        );

        if (null === $file || ! file_exists($file)) {
            $this->failReorganize($languageResource, $isInternalFuzzy);

            $this->logger->warn(
                'E1314',
                'Reorganize failed: Nothing was exported',
                [
                    'languageResource' => $languageResource,
                    'tmName' => $tmName,
                ]
            );

            throw new ReorganizeException('Reorganize failed: Nothing was exported');
        }

        $this->backupTm($languageResource, $tmName);

        rename($file, $exportFilePath);

        if (! file_exists($exportFilePath)) {
            $this->logger->warn(
                'E1314',
                'Reorganize failed: Moving file [{file}] to export dir failed',
                [
                    'languageResource' => $languageResource,
                    'tmName' => $tmName,
                    'file' => $file,
                ]
            );

            throw new ReorganizeException(sprintf('Reorganize failed: Moving file [%s] to export dir failed', $file));
        }

        $newTmName = $this->createNewMemory($languageResource, $tmName, $isInternalFuzzy);

        try {
            $this->importService->importTmxInMemory(
                $languageResource,
                $exportFilePath,
                $newTmName,
                new ImportOptions(
                    stripFramingTags: StripFramingTags::None,
                    saveDifferentTargetsForSameSource: $reorganizeOptions->saveDifferentTargetsForSameSource,
                ),
            );

            if (! $isInternalFuzzy) {
                $languageResource->setStatus(LanguageResourceStatus::AVAILABLE);
                $languageResource->save();
            }
        } catch (HttpException|UnableToCreateMemoryException|ImportResultedInErrorException $e) {
            $this->logger->warn(
                'E1314',
                'Reorganize failed: Could not import TMX into TM.',
                [
                    'languageResource' => $languageResource,
                    'tmName' => $tmName,
                    'newTmName' => $newTmName,
                    'error' => $e->getMessage(),
                ]
            );

            $this->failReorganize($languageResource, $isInternalFuzzy);

            throw new ReorganizeException('Reorganize failed: ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * @throws ReorganizeException
     */
    private function createNewMemory(
        LanguageResource $languageResource,
        string $tmName,
        bool $isInternalFuzzy
    ): string {
        try {
            return $this->wipeMemoryService->wipeMemory($languageResource, $tmName, $isInternalFuzzy);
        } catch (UnableToCreateMemoryException|HttpException $e) {
            $this->logger->warn(
                'E1314',
                'Reorganize failed: Could not recreate empty TM.',
                [
                    'languageResource' => $languageResource,
                    'error' => $e->getMessage(),
                ]
            );

            $this->failReorganize($languageResource, $isInternalFuzzy);

            throw new ReorganizeException('Reorganize failed: ' . $e->getMessage(), previous: $e);
        }
    }

    private function backupTm(
        LanguageResource $languageResource,
        string $tmName
    ): void {
        $timestamp = date_format(new DateTime(), 'Y-m-d\THis');

        try {
            $this->cloneService->clone(
                $languageResource,
                $tmName,
                $tmName . ".reorganise.before-flush.$timestamp",
            );

            $this->flushService->flush($languageResource, $tmName);

            $this->cloneService->clone(
                $languageResource,
                $tmName,
                $tmName . ".reorganise.after-flush.$timestamp",
            );
        } catch (ClientExceptionInterface|HttpException|CloneException $e) {
            $this->logger->warn(
                'E1314',
                'Could not create backup or flush TM before reorganize. Continuing anyway.',
                [
                    'languageResource' => $languageResource,
                    'error' => $e->getMessage(),
                ]
            );
            $this->logger->exception($e);
        }
    }

    private function failReorganize(LanguageResource $languageResource, bool $isInternalFuzzy): void
    {
        if (! $isInternalFuzzy) {
            $languageResource->setStatus(LanguageResourceStatus::REORGANIZE_FAILED);
            $languageResource->save();
        }
    }
}
