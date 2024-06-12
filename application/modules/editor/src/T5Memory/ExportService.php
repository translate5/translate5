<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Connector_Exception as ConnectorException;
use Generator;
use GuzzleHttp\Client;
use LogicException;
use MittagQI\Translate5\ContentProtection\T5memory\T5NTagSchemaFixFilter;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;
use MittagQI\Translate5\LanguageResource\Adapter\Export\ExportTmFileExtension;
use MittagQI\Translate5\T5Memory\Api\VersionFetchingApi;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\StreamInterface;
use Throwable;
use XMLReader;
use ZfExtended_Logger;
use ZipArchive;

class ExportService
{
    private const T5N_TAG_FILTER = 'fix-t5n-tag';

    public function __construct(
        private ZfExtended_Logger $logger,
        private VersionService $versionService,
        private TmConversionService $conversionService,
        private Api\VersionedApiFactory $versionedApiFactory,
    ) {
    }

    public static function create(): self
    {
        $httpClient = new Client();

        return new self(
            \Zend_Registry::get('logger'),
            new VersionService(new VersionFetchingApi($httpClient)),
            TmConversionService::create(),
            new Api\VersionedApiFactory($httpClient),
        );
    }

    public function export(
        LanguageResource $languageResource,
        ExportTmFileExtension $extension,
        ?string $tmName = null,
    ): ?string {
        return match ($extension) {
            ExportTmFileExtension::TMX => $this->composeTmxFile($languageResource, $tmName),
            ExportTmFileExtension::TM => $this->exportSingleTm($languageResource, $tmName),
            ExportTmFileExtension::ZIP => $this->exportAllAsArchive($languageResource, $tmName),
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
            $tmFilename = $this->composeFilename($languageResource, ExportTmFileExtension::TM);
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
        $version = $this->versionService->getT5MemoryVersion($languageResource);

        return match (true) {
            Api\V6\VersionedApi::isVersionSupported($version) => $this->versionedApiFactory
                ->get(Api\V6\VersionedApi::class)
                ->downloadTm($languageResource->getResource()->getUrl(), $tmName),

            Api\V5\VersionedApi::isVersionSupported($version) => $this->versionedApiFactory
                ->get(Api\V5\VersionedApi::class)
                ->getTm($languageResource->getResource()->getUrl(), $tmName),

            default => throw new LogicException('Unsupported T5Memory version: ' . $version)
        };
    }

    private function composeTmxFile(LanguageResource $languageResource, ?string $tmName): string
    {
        $tmxFilename = $this->composeFilename($languageResource, ExportTmFileExtension::TMX);

        foreach ($this->exportAllAsOneTmx($languageResource, $tmName) as $chunk) {
            file_put_contents($tmxFilename, $chunk, FILE_APPEND);
        }

        return $tmxFilename;
    }

    /**
     * @return iterable<string>
     */
    private function exportAllAsOneTmx(LanguageResource $languageResource, ?string $tmName): iterable
    {
        $memories = $this->getMemories($languageResource, $tmName);

        if (empty($memories)) {
            return null;
        }

        $writtenElements = 0;
        $atLeastOneFileRead = false;

        stream_filter_register(self::T5N_TAG_FILTER, T5NTagSchemaFixFilter::class);

        yield '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;

        foreach ($memories as $memoryNumber => $tmName) {
            try {
                yield from $this->exportTmx(
                    $languageResource,
                    $tmName,
                    $memoryNumber,
                    $writtenElements,
                    $atLeastOneFileRead
                );
            } catch (ClientExceptionInterface $e) {
                $this->logger->exception(ConnectorException::fromApiRequestError(
                    $e->getMessage(),
                    $languageResource->getResource()->getName(),
                    $languageResource,
                    $tmName,
                    $e
                ));
            }
        }

        if (0 !== $writtenElements) {
            // Finalizing document with $writer->endDocument() adds closing tags for all bpt-ept tags
            // so add body and tmx closing tags manually
            yield '</body>' . PHP_EOL;
        }

        if ($atLeastOneFileRead) {
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
    ): iterable {
        foreach ($this->exportTmxChunk($languageResource, $tmName) as $stream) {
            $iterator = $this->iterateTmx($stream, $memoryNumber > 0, $writtenElements);

            if ($iterator?->valid()) {
                $atLeastOneFileRead = true;

                yield from $iterator;
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
        $version = $this->versionService->getT5MemoryVersion($languageResource);

        if (Api\V6\VersionedApi::isVersionSupported($version)) {
            return yield from $this->versionedApiFactory
                ->get(Api\V6\VersionedApi::class)
                ->downloadTmx($languageResource->getResource()->getUrl(), $tmName, 20);
        }

        if (Api\V5\VersionedApi::isVersionSupported($version)) {
            return yield from [
                $this->versionedApiFactory
                    ->get(Api\V5\VersionedApi::class)
                    ->getTmx($languageResource->getResource()->getUrl(), $tmName),
            ];
        }

        throw new LogicException('Unsupported T5Memory version: ' . $version);
    }

    /**
     * @return Generator<string>|null
     */
    private function iterateTmx(StreamInterface $stream, bool $notFirstMemory, int &$writtenElements): ?Generator
    {
        $reader = new XMLReader();

        try {
            $reader->open(Psr7StreamWrapper::register($stream));
        } catch (Throwable $e) {
            dump($e);
            $this->logger->exception($e);

            return null;
        }

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'tu') {
                $writtenElements++;

                // suppress: namespace error : Namespace prefix t5 on n is not defined
                yield $this->conversionService->convertT5MemoryTagToContent(@$reader->readOuterXML()) . PHP_EOL;
            }

            if ($notFirstMemory) {
                continue;
            }

            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'header') {
                yield $reader->readOuterXML() . PHP_EOL;
            }

            if (! in_array($reader->name, ['tmx', 'body'])) {
                continue;
            }

            if ($reader->nodeType === XMLReader::ELEMENT) {
                // Get the opening part of the 'element' element
                $openingPart = '<' . $reader->name;

                // Get attributes of the 'element' element
                while ($reader->moveToNextAttribute()) {
                    $openingPart .= ' ' . $reader->name . '="' . $reader->value . '"';
                }

                // If self-closing
                $openingPart .= $reader->isEmptyElement ? '/>' : '>';

                yield $openingPart . PHP_EOL;
            }
        }

        $reader->close();
    }

    private function exportAllAsArchive(LanguageResource $languageResource, ?string $tmName): ?string
    {
        $memories = $this->getMemories($languageResource, $tmName);

        if (empty($memories)) {
            return null;
        }

        $exportDir = APPLICATION_PATH . '/../data/TMExport/';
        $tmpDir = $exportDir . $languageResource->getId() . '_' . uniqid() . '/';
        @mkdir($tmpDir, recursive: true);

        $zipFilename = $this->composeFilename($languageResource, ExportTmFileExtension::ZIP);

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

    private function composeFilename(LanguageResource $languageResource, ExportTmFileExtension $extension): string
    {
        return APPLICATION_PATH
            . '/../data/TMExport/'
            . sprintf('%s_%s.%s', $languageResource->getId(), uniqid(), $extension->value);
    }
}
