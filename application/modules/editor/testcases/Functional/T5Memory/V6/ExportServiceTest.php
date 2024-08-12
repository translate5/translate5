<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Functional\T5Memory\V6;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_LanguageResources_Resource;
use GuzzleHttp\Psr7\Stream;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;
use MittagQI\Translate5\LanguageResource\Adapter\Export\ExportTmFileExtension;
use MittagQI\Translate5\T5Memory\Api\V6\VersionedApi as V5VersionedApi;
use MittagQI\Translate5\T5Memory\Api\VersionedApiFactory;
use MittagQI\Translate5\T5Memory\ExportService;
use MittagQI\Translate5\T5Memory\VersionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_Logger;
use ZipArchive;

class ExportServiceTest extends TestCase
{
    private ExportService $service;

    private MockObject & ZfExtended_Logger $loggerMock;

    private MockObject & VersionService $versionService;

    private MockObject & VersionedApiFactory $versionedApiFactory;

    private MockObject & V5VersionedApi $versionedApi;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(ZfExtended_Logger::class);
        $this->versionService = $this->createMock(VersionService::class);
        $this->versionedApiFactory = $this->createMock(VersionedApiFactory::class);
        $this->versionedApi = $this->createMock(V5VersionedApi::class);

        $this->versionService->method('getT5MemoryVersion')->willReturn('0.6.14');
        $this->versionedApiFactory->method('get')->willReturn($this->versionedApi);

        $this->service = new ExportService(
            $this->loggerMock,
            $this->versionService,
            TmConversionService::create(),
            $this->versionedApiFactory,
        );
    }

    public function testExportAllAsOneTmx(): void
    {
        $languageResource = $this->createMock(LanguageResource::class);
        $memories = [[
            'id' => 0,
            'filename' => 'memory1',
        ], [
            'id' => 1,
            'filename' => 'memory2',
        ]];

        $resource = $this->createConfiguredMock(
            editor_Models_LanguageResources_Resource::class,
            [
                'getUrl' => 'http://example.com',
            ]
        );

        $languageResource->method('getResource')->willReturn($resource);

        $languageResource
            ->method('getSpecificData')
            ->with('memories', true)
            ->willReturn($memories);

        $streamCallback = fn () => [new Stream(fopen(__DIR__ . '/1seg-mock.tmx', 'r'))];

        $this->versionedApi
            ->method('downloadTmx')
            ->willReturnCallback($streamCallback);

        $file = $this->service->export($languageResource, ExportTmFileExtension::TMX);

        self::assertNotNull($file);
        self::assertSame(
            file_get_contents(__DIR__ . '/ExportAllAsOneTmx/expected.tmx'),
            file_get_contents($file)
        );
    }

    public function testExportSingleTm(): void
    {
        $languageResource = $this->createMock(LanguageResource::class);
        $memories = [[
            'id' => 0,
            'filename' => 'memory1',
        ], [
            'id' => 1,
            'filename' => 'memory2',
        ]];

        $resource = $this->createConfiguredMock(
            editor_Models_LanguageResources_Resource::class,
            [
                'getUrl' => 'http://example.com',
            ]
        );

        $languageResource->method('getResource')->willReturn($resource);

        $languageResource
            ->method('getSpecificData')
            ->with('memories', true)
            ->willReturn($memories);

        $streamCallback = fn () => new Stream(fopen(__DIR__ . '/1seg-mock.tmx', 'r'));

        $this->versionedApi
            ->method('downloadTm')
            ->willReturnCallback($streamCallback);

        $file = $this->service->export($languageResource, ExportTmFileExtension::TM, 'memory1');

        self::assertNotNull($file);
        self::assertSame(
            file_get_contents(__DIR__ . '/ExportSingleTm/expected.tm'),
            file_get_contents($file)
        );
    }

    public function testExportAllAsArchive(): void
    {
        $languageResource = $this->createMock(LanguageResource::class);
        $memories = [[
            'id' => 0,
            'filename' => 'memory1',
        ], [
            'id' => 1,
            'filename' => 'memory2',
        ]];

        $resource = $this->createConfiguredMock(
            editor_Models_LanguageResources_Resource::class,
            [
                'getUrl' => 'http://example.com',
            ]
        );

        $languageResource->method('getResource')->willReturn($resource);

        $languageResource
            ->method('getSpecificData')
            ->with('memories', true)
            ->willReturn($memories);

        $streamCallback = fn (): Stream => new Stream(fopen(__DIR__ . '/1seg-mock.tmx', 'r'));

        $this->versionedApi
            ->method('downloadTm')
            ->willReturnCallback($streamCallback);

        $file = $this->service->export($languageResource, ExportTmFileExtension::ZIP);

        self::assertNotNull($file);

        $zip = new ZipArchive();
        $zip->open($file, ZipArchive::RDONLY);

        $expectedFileContent = file_get_contents(__DIR__ . '/1seg-mock.tmx');

        for ($i = 0; $i < $zip->count(); $i++) {
            self::assertSame($expectedFileContent, $zip->getFromIndex($i));
        }

        $zip->close();

        unlink($file);
    }
}
