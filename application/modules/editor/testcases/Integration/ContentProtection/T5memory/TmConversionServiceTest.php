<?php

namespace MittagQI\Translate5\Test\Integration\ContentProtection\T5memory;

use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\Model\LanguageRulesHashService;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;
use MittagQI\Translate5\Repository\LanguageRepository;
use PHPUnit\Framework\TestCase;

class TmConversionServiceTest extends TestCase
{
    public function testConvertTMXForImport(): void
    {
        $contentProtectionRepository = $this->createMock(ContentProtectionRepository::class);
        $contentProtector = $this->createMock(ContentProtector::class);
        $languageRepository = $this->createMock(LanguageRepository::class);
        $languageRulesHashService = $this->createMock(LanguageRulesHashService::class);

        $sourceLang = $this->createMock(\editor_Models_Languages::class);
        $sourceLang->method('getMajorRfc5646')->willReturn('de');
        $sourceLang->method('__call')->willReturnMap([
            ['getId', [], 1],
        ]);

        $targetLang = $this->createMock(\editor_Models_Languages::class);
        $targetLang->method('getMajorRfc5646')->willReturn('de');
        $targetLang->method('__call')->willReturnMap([
            ['getId', [], 2],
        ]);

        $languageRepository->method('find')->willReturnMap([
            [1, $sourceLang],
            [2, $targetLang],
        ]);

        $contentProtectionRepository->method('hasActiveRules')->willReturn(true);

        $contentProtector->method('filterTags')->willReturnOnConsecutiveCalls(
            [
                '<number type="replace-content" name="replace" source="SOME_TEXT" iso="SOME_TEXT -> OTHER_TEXT" target="OTHER_TEXT" regex="04+LSdFWCA12UdEHAA=="/>',
                '<number type="replace-content" name="replace" source="SOME_TEXT" iso="SOME_TEXT -> OTHER_TEXT" target="OTHER_TEXT" regex="04+LSdFWCA12UdEHAA=="/>',
            ],
            [
                '<number type="keep-content" name="keep" source="VQ1" iso="VQ1" target="VQ1" regex="LSdFWCA12UdEHAA"/>',
                '<number type="keep-content" name="keep" source="VQ1" iso="VQ1" target="VQ1" regex="LSdFWCA12UdEHAA"/>',
            ]
        );

        $contentProtector->method('unprotect')->willReturnCallback(fn (string $segment) => $segment);

        $service = new TmConversionService(
            $contentProtectionRepository,
            $contentProtector,
            $languageRepository,
            $languageRulesHashService
        );

        $file = $service->convertTMXForImport(__DIR__ . '/TmConversionServiceTest/small.tmx', 1, 2);

        self::assertFileEquals(__DIR__ . '/TmConversionServiceTest/expected_small.tmx', $file);
    }
}
