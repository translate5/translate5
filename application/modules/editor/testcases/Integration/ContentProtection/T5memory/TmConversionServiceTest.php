<?php

namespace MittagQI\Translate5\Test\Integration\ContentProtection\T5memory;

use editor_Models_Languages;
use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\Model\ContentRecognition;
use MittagQI\Translate5\ContentProtection\Model\InputMapping;
use MittagQI\Translate5\ContentProtection\Model\LanguageRulesHashService;
use MittagQI\Translate5\ContentProtection\Model\OutputMapping;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\KeepContentProtector;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use PHPUnit\Framework\TestCase;

class TmConversionServiceTest extends TestCase
{
    private editor_Models_Languages $sourceLang;

    private editor_Models_Languages $targetLang;

    /**
     * @var ContentRecognition[]
     */
    private array $rules = [];

    protected function setUp(): void
    {
        $inputMapping = new InputMapping();
        foreach ($inputMapping->loadAll() as $item) {
            $inputMapping->load($item['id']);
            $inputMapping->delete();
        }

        $outputMapping = new OutputMapping();
        foreach ($outputMapping->loadAll() as $item) {
            $outputMapping->load($item['id']);
            $outputMapping->delete();
        }

        $languageRepository = LanguageRepository::create();

        $this->sourceLang = $languageRepository->findByRfc5646('de-DE') ?? new editor_Models_Languages();
        $this->targetLang = $languageRepository->findByRfc5646('it-IT') ?? new editor_Models_Languages();

        $keep1 = new ContentRecognition();
        $keep1->setName('default simple');
        $keep1->setType(KeepContentProtector::getType());
        $keep1->setEnabled(true);
        $keep1->setKeepAsIs(true);
        $keep1->setRegex('/(\s|^|\()([-+]?([1-9]\d+|\d))(([\.,;:?!](\s|$))|\s|$|\))/u');
        $keep1->setMatchId(2);
        $keep1->save();

        $this->rules[] = $keep1;

        $keep2 = new ContentRecognition();
        $keep2->setName('default simple (with units)');
        $keep2->setType(KeepContentProtector::getType());
        $keep2->setEnabled(true);
        $keep2->setKeepAsIs(true);
        $keep2->setRegex('/(\s|^|\()([-+]?([1-9]\d+|\d))(%|°|V|mm|kbit|s|psi|bar|MPa|mA)(([\.,:;?!](\s|$))|\s|$|\))/u');
        $keep2->setMatchId(2);
        $keep2->save();

        $this->rules[] = $keep2;

        $inputMapping = new InputMapping();
        $inputMapping->setLanguageId((int) $this->sourceLang->getId());
        $inputMapping->setContentRecognitionId($keep1->getId());
        $inputMapping->setPriority(4);
        $inputMapping->save();

        $inputMapping = new InputMapping();
        $inputMapping->setLanguageId((int) $this->sourceLang->getId());
        $inputMapping->setContentRecognitionId($keep2->getId());
        $inputMapping->setPriority(5);
        $inputMapping->save();
    }

    protected function tearDown(): void
    {
        foreach ($this->rules as $rule) {
            $rule->delete();
        }

        $inputMapping = new InputMapping();
        foreach ($inputMapping->loadAll() as $item) {
            $inputMapping->load($item['id']);
            $inputMapping->delete();
        }

        $outputMapping = new OutputMapping();
        foreach ($outputMapping->loadAll() as $item) {
            $outputMapping->load($item['id']);
            $outputMapping->delete();
        }
    }

    public function testConvertTMXForImport(): void
    {
        $contentProtectionRepository = $this->createMock(ContentProtectionRepository::class);
        $contentProtector = $this->createMock(ContentProtector::class);
        $languageRepository = $this->createMock(LanguageRepository::class);
        $languageRulesHashService = $this->createMock(LanguageRulesHashService::class);

        $sourceLang = $this->createMock(editor_Models_Languages::class);
        $sourceLang->method('getMajorRfc5646')->willReturn('de');
        $sourceLang->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getRfc5646', [], 'de'],
        ]);

        $targetLang = $this->createMock(editor_Models_Languages::class);
        $targetLang->method('getMajorRfc5646')->willReturn('en');
        $targetLang->method('__call')->willReturnMap([
            ['getId', [], 2],
            ['getRfc5646', [], 'en'],
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
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $logger = $this->createMock(\ZfExtended_Logger::class);

        $service = new TmConversionService(
            $contentProtectionRepository,
            $contentProtector,
            $languageRepository,
            $languageRulesHashService,
            $languageResourceRepository,
            $logger,
        );

        $file = $service->convertTMXForImport(__DIR__ . '/TmConversionServiceTest/small.tmx', 1, 2);

        self::assertFileEquals(__DIR__ . '/TmConversionServiceTest/expected_small.tmx', $file);
    }

    public function testConvertPair(): void
    {
        $service = TmConversionService::create();

        $source = 'segment <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="10"/> and <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="15"/> and <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="20"/>V';
        $target = 'segment <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="20"/>V and <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="15"/> and <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="10"/>';

        [$sourceResult, $targetResult] = $service->convertPair(
            $source,
            $target,
            (int) $this->sourceLang->getId(),
            (int) $this->targetLang->getId()
        );

        self::assertSame(
            'segment <t5:n id="1" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA=" n="10"/> and <t5:n id="2" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA=" n="15"/> and <t5:n id="3" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1FCtObShJqwmN7cmOymzpKa4pqA4syYpsajGNyCxJtdRU0MjOkZPx8raXjEWZJCKpmYNiKqJ0dTULwUA" n="20"/>V',
            $sourceResult,
            'Source converted incorrectly'
        );

        self::assertSame(
            'segment <t5:n id="3" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1FCtObShJqwmN7cmOymzpKa4pqA4syYpsajGNyCxJtdRU0MjOkZPx8raXjEWZJCKpmYNiKqJ0dTULwUA" n="20"/>V and <t5:n id="2" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA=" n="15"/> and <t5:n id="1" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA=" n="10"/>',
            $targetResult,
            'Target converted incorrectly'
        );
    }
}
