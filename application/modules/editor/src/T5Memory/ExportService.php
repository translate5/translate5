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

namespace MittagQI\Translate5\T5Memory;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Connector_Exception as ConnectorException;
use GuzzleHttp\Exception\RequestException;
use LogicException;
use MittagQI\Translate5\LanguageResource\Adapter\Export\TmFileExtension;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use MittagQI\Translate5\T5Memory\Exception\ExportException;
use MittagQI\Translate5\TMX\ConcatTmx;
use MittagQI\Translate5\TMX\TmxIterator;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\StreamInterface;
use Throwable;
use Zend_Config;
use Zend_Registry;
use ZfExtended_Logger;
use ZfExtended_Utils;
use ZipArchive;

class ExportService
{
    private const CHUNKSIZE = 10000;

    public function __construct(
        private readonly ZfExtended_Logger $logger,
        private readonly T5MemoryApi $t5MemoryApi,
        private readonly PersistenceService $persistenceService,
        private readonly Zend_Config $config,
        private readonly ConcatTmx $concatTmx,
        private readonly TmxIterator $tmxIterator,
        private readonly DirectoryPath $directoryPath,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            Zend_Registry::get('logger')->cloneMe('editor.t5memory.export'),
            T5MemoryApi::create(),
            PersistenceService::create(),
            Zend_Registry::get('config'),
            ConcatTmx::create(),
            TmxIterator::create(),
            DirectoryPath::create(),
        );
    }

    public function export(
        LanguageResource $languageResource,
        TmFileExtension $extension,
        ?string $tmName = null,
        bool $unprotect = true,
    ): ?string {
        return match ($extension) {
            TmFileExtension::TMX => $this->composeTmxFile($languageResource, $tmName, $unprotect),
            TmFileExtension::TM => $this->exportSingleTm($languageResource, $tmName),
            TmFileExtension::ZIP => $this->exportAllAsArchive($languageResource, $tmName),
        };
    }

    /**
     * @return string[]
     */
    private function getMemories(LanguageResource $languageResource, ?string $tmName): array
    {
        $memories = $languageResource->getSpecificData('memories', parseAsArray: true) ?: [];

        usort($memories, fn ($m1, $m2) => $m1['id'] <=> $m2['id']);

        $names = array_column($memories, 'filename');

        if (null === $tmName) {
            return $names;
        }

        if (! in_array($tmName, $names)) {
            throw new LogicException('Memory not found: ' . $tmName);
        }

        return [$tmName];
    }

    private function exportSingleTm(LanguageResource $languageResource, ?string $tmName): ?string
    {
        $tmName = current($this->getMemories($languageResource, $tmName)) ?: null;

        if ($tmName === null) {
            return null;
        }

        try {
            $tmFilename = $this->composeFilename($languageResource, TmFileExtension::TM);
            $stream = $this->getSingleTmStream($languageResource, $tmName);

            file_put_contents($tmFilename, $stream->detach());
        } catch (ClientExceptionInterface $e) {
            $this->logger->exception($e);

            return null;
        }

        return $tmFilename;
    }

    /**
     * @throws ClientExceptionInterface
     */
    private function getSingleTmStream(LanguageResource $languageResource, string $tmName): StreamInterface
    {
        return $this->t5MemoryApi->downloadTm(
            $languageResource->getResource()->getUrl(),
            $this->persistenceService->addTmPrefix($tmName)
        );
    }

    private function composeTmxFile(LanguageResource $languageResource, ?string $tmName, bool $unprotect): ?string
    {
        if ($this->config->runtimeOptions->LanguageResources->t5memory->downloadParallel) {
            return $this->downloadTmx($languageResource, $tmName, $unprotect);
        }

        $tmxFilename = $this->composeFilename($languageResource, TmFileExtension::TMX);

        try {
            foreach ($this->exportAllAsOneTmx($languageResource, $tmName, $unprotect) as $chunk) {
                file_put_contents($tmxFilename, $chunk, FILE_APPEND);
            }
        } catch (ExportException) {
            unlink($tmxFilename);

            return null;
        }

        return $tmxFilename;
    }

    private function downloadTmx(
        LanguageResource $languageResource,
        ?string $exactTmName,
        bool $unprotect,
    ): ?string {
        $memories = $this->getMemories($languageResource, $exactTmName);

        if (empty($memories)) {
            return null;
        }

        $exportDir = $this->getExportDir($languageResource, true);

        $chunkNumber = 1;

        $tmData = [];
        foreach ($memories as $i => $memory) {
            $memory = $this->persistenceService->addTmPrefix($memory);
            $tmData[$memory] = [
                'filePath' => $this->composeChunkFilename(
                    $languageResource,
                    TmFileExtension::TMX,
                    $i + 1,
                    $chunkNumber
                ),
                'startFromInternalKey' => null,
                'part' => $i + 1,
            ];
        }

        do {
            $chunkNumber++;

            ['success' => $responses, 'failures' => $failures] = $this->t5MemoryApi->downloadParallel(
                $languageResource->getResource()->getUrl(),
                $tmData,
                self::CHUNKSIZE,
            );

            foreach ($failures as $tmName => $failure) {
                $this->logger->exception($this->getBadGatewayException($failure, $languageResource, $tmName));
            }

            if (! empty($failures)) {
                ZfExtended_Utils::recursiveDelete($exportDir);

                throw new ExportException('Downloads failed');
            }

            foreach ($responses as $tmName => $response) {
                if ($response->isLockingTimeoutOccurred()) {
                    $response = $this->t5MemoryApi->fetchChunk(
                        $languageResource->getResource()->getUrl(),
                        $tmName,
                        self::CHUNKSIZE,
                        $tmData[$tmName]['startFromInternalKey'],
                    );

                    if ($response->isLockingTimeoutOccurred()) {
                        throw new ExportException('Could not acquire lock for TM ' . $tmName);
                    }
                }

                if (null === $response->nextInternalKey) {
                    unset($tmData[$tmName]);

                    continue;
                }

                $tmData[$tmName]['startFromInternalKey'] = $response->nextInternalKey;
                $tmData[$tmName]['filePath'] = $this->composeChunkFilename(
                    $languageResource,
                    TmFileExtension::TMX,
                    $tmData[$tmName]['part'],
                    $chunkNumber
                );
            }
        } while (! empty($tmData));

        $tmxFilename = $this->composeFilename($languageResource, TmFileExtension::TMX);

        $this->concatTmx->concatDirFiles($exportDir, $tmxFilename, $unprotect);

        return $tmxFilename;
    }

    /**
     * @return iterable<string>
     * @throws ExportException
     */
    private function exportAllAsOneTmx(LanguageResource $languageResource, ?string $exactTmName, bool $unprotect): iterable
    {
        $memories = $this->getMemories($languageResource, $exactTmName);

        if (empty($memories)) {
            return yield from [];
        }

        $writtenElements = 0;
        $atLeastOneFileRead = false;

        yield '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;

        $exceptionWasThrown = false;

        foreach ($memories as $memoryNumber => $tmName) {
            try {
                yield from $this->exportTmx(
                    $languageResource,
                    $tmName,
                    $memoryNumber,
                    $writtenElements,
                    $atLeastOneFileRead,
                    $unprotect
                );
            } catch (ClientExceptionInterface $e) {
                $exceptionWasThrown = true;

                $this->logger->exception(ConnectorException::fromApiRequestError(
                    $e->getMessage(),
                    $languageResource->getResource()->getName(),
                    $languageResource,
                    $tmName,
                    $e
                ));
            }
        }

        if (0 === $writtenElements && $exceptionWasThrown) {
            throw new ExportException();
        }

        if ($atLeastOneFileRead) {
            // Finalizing document with $writer->endDocument() adds closing tags for all bpt-ept tags
            // so add body and tmx closing tags manually
            yield '</body>' . PHP_EOL;
            yield '</tmx>';
        }
    }

    /**
     * @return iterable<string>
     * @throws ClientExceptionInterface
     */
    private function exportTmx(
        LanguageResource $languageResource,
        string $tmName,
        int $memoryNumber,
        int &$writtenElements,
        bool &$atLeastOneFileRead,
        bool $unprotect,
    ): iterable {
        $firstChunk = true;

        foreach ($this->exportTmxChunk($languageResource, $tmName) as $stream) {
            $iterator = $this->tmxIterator->iterateTmx(
                $stream,
                $firstChunk && $memoryNumber === 0,
                $writtenElements,
                $unprotect
            );

            if ($iterator?->valid()) {
                $atLeastOneFileRead = true;
                $firstChunk = false;

                foreach ($iterator as $item) {
                    yield $item;
                }
            }
        }
    }

    /**
     * @return iterable<StreamInterface>
     *
     * @throws ClientExceptionInterface
     * @throws LogicException
     */
    private function exportTmxChunk(LanguageResource $languageResource, string $tmName): iterable
    {
        return yield from $this->t5MemoryApi->downloadTmx(
            $languageResource->getResource()->getUrl(),
            $this->persistenceService->addTmPrefix($tmName),
            self::CHUNKSIZE
        );
    }

    private function exportAllAsArchive(LanguageResource $languageResource, ?string $exactTmName): ?string
    {
        $memories = $this->getMemories($languageResource, $exactTmName);

        if (empty($memories)) {
            return null;
        }

        $exportDir = $this->directoryPath->tmExportDir();
        $tmpDir = $exportDir . '/' . $languageResource->getId() . '_' . uniqid() . '/';

        if (! mkdir($tmpDir, recursive: true) && ! is_dir($tmpDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $tmpDir));
        }

        $zipFilename = $this->composeFilename($languageResource, TmFileExtension::ZIP);

        $files = [];

        foreach ($memories as $index => $tmName) {
            try {
                $stream = $this->getSingleTmStream($languageResource, $tmName);
            } catch (ClientExceptionInterface $e) {
                $this->logger->exception($e);

                continue;
            }

            $filepath = $tmpDir . ($index + 1) . '.tm';
            $files[] = $filepath;

            file_put_contents($filepath, $stream->detach());
        }

        $zip = new ZipArchive();
        $zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $filePath) {
            $zip->addFile($filePath, basename($filePath));
        }

        $zip->close();

        foreach ($files as $filePath) {
            unlink($filePath);
        }
        rmdir($tmpDir);

        return $zipFilename;
    }

    private function getBadGatewayException(
        Throwable $e,
        LanguageResource $languageResource,
        string $tmName,
    ): ConnectorException {
        $ecode = 'E1313';

        $request = null;
        $response = null;

        if ($e instanceof RequestExceptionInterface) {
            $request = $e->getRequest();
        }

        if ($e instanceof RequestException) {
            $response = $e->getResponse();
        }

        if ($request && $request->getBody()->isSeekable()) {
            $request->getBody()->rewind();
        }

        if ($request && $response->getBody()->isSeekable()) {
            $response->getBody()->rewind();
        }

        $data = [
            'service' => $languageResource->getResource()->getName(),
            'languageResource' => $languageResource,
            'tmName' => $tmName,
            'error' => $e->getMessage(),
            'uri' => $request?->getUri() ?? $languageResource->getResource()->getUrl(),
            'request' => $request ? $request->getBody()->getContents() : '',
            'response' => $response ? $response->getBody()->getContents() : '',
        ];

        if (str_contains($data['response'], 'needs to be organized')) {
            $ecode = 'E1314';
            $data['tm'] = $languageResource->getName();
        }

        if (str_contains($data['response'], 'too many open translation memory databases')) {
            $ecode = 'E1333';
        }

        return new ConnectorException($ecode, $data, $e);
    }

    private function composeChunkFilename(
        LanguageResource $languageResource,
        TmFileExtension $extension,
        int $part,
        int $chunkNumber,
    ): string {
        $exportDir = $this->getExportDir($languageResource, true);

        return $exportDir . sprintf('%s-%s.%s', $part, $chunkNumber, $extension->value);
    }

    private function composeFilename(
        LanguageResource $languageResource,
        TmFileExtension $extension,
    ): string {
        $exportDir = $this->getExportDir($languageResource, false);

        return $exportDir . sprintf('%s_%s.%s', $languageResource->getId(), uniqid(), $extension->value);
    }

    private function getExportDir(
        LanguageResource $languageResource,
        bool $multipleParts,
    ): string {
        $exportDir = $this->directoryPath->tmExportDir() . '/';

        if (! $multipleParts) {
            return $exportDir;
        }

        $exportDir .= $languageResource->getId() . '/';

        if (! is_dir($exportDir) && ! mkdir($exportDir, recursive: true)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $exportDir));
        }

        return $exportDir;
    }
}
