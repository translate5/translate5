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

namespace MittagQI\Translate5\Test\Integration\T5Memory\TmxImportPreprocessor;

use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionDto;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\NumberProtection\NumberProtectorProvider;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\KeepContentProtector;
use MittagQI\Translate5\ContentProtection\NumberProtector;
use MittagQI\Translate5\ContentProtection\ProtectionTagsFilter;
use MittagQI\Translate5\ContentProtection\T5memory\ConvertT5MemoryTagService;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\DTO\TmxFilterOptions;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\Import\TmxImportPreprocessor\ContentProtectionProcessor;
use MittagQI\Translate5\T5Memory\TMX\CharacterReplacer;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger\NullBrokenTranslationUnitLogger;
use MittagQI\Translate5\TMX\TransUnitParser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zend_Config;

class ContentProtectionProcessorTest extends TestCase
{
    private MockObject|ContentProtectionRepository $contentProtectionRepository;

    private MockObject|Zend_Config $config;

    private ContentProtectionProcessor $processor;

    public function setUp(): void
    {
        $this->contentProtectionRepository = $this->createMock(ContentProtectionRepository::class);
        $this->config = $this->createMock(Zend_Config::class);
        $logger = $this->createMock(\ZfExtended_Logger::class);

        $numberProtector = new NumberProtector(
            $this->contentProtectionRepository,
            LanguageRepository::create(),
            $logger,
            NumberProtectorProvider::create(),
        );

        $contentProtector = new ContentProtector(
            [
                $numberProtector,
            ],
            [
                ProtectionTagsFilter::create(),
            ]
        );

        $this->processor = new ContentProtectionProcessor(
            $this->contentProtectionRepository,
            new ConvertT5MemoryTagService(
                $contentProtector,
                CharacterReplacer::create(),
                $logger,
                $this->contentProtectionRepository,
            ),
            new TransUnitParser(),
            $this->config,
        );
    }

    public function testSuccess(): void
    {
        $languageRepository = LanguageRepository::create();
        $de = $languageRepository->findByRfc5646('de');
        $ko = $languageRepository->findByRfc5646('ko');

        $this->contentProtectionRepository
            ->method('getAllForSource')
            ->willReturn([
                new ContentProtectionDto(
                    KeepContentProtector::getType(),
                    'complex type code rule',
                    '/(^|\s|\(| )((?!NOT)[A-Z]{2,5}(\/[A-Z]{3,5})?-(([A-Z0-9](?![a-z])([A-Z0-9]{0,4})(\/[A-Z0-9]{0,4})?)|((\.{3}|…)))(-([A-Z0-9]{1,5}(\/[A-Z0-9]{1,5})?|(\.{3}|…)))*)-?($|[\.,;:?!\s\) 。：， ]|\p{Hangul}|\p{Han})/',
                    2,
                    null,
                    true,
                    null,
                    1,
                    'aaa',
                ),
            ]);

        $this->contentProtectionRepository
            ->method('getAllForTarget')
            ->willReturn([
                new ContentProtectionDto(
                    KeepContentProtector::getType(),
                    'complex type code rule',
                    '/(^|\s|\(| )((?!NOT)[A-Z]{2,5}(\/[A-Z]{3,5})?-(([A-Z0-9](?![a-z])([A-Z0-9]{0,4})(\/[A-Z0-9]{0,4})?)|((\.{3}|…)))(-([A-Z0-9]{1,5}(\/[A-Z0-9]{1,5})?|(\.{3}|…)))*)-?($|[\.,;:?!\s\) 。：， ]|\p{Hangul}|\p{Han})/u',
                    2,
                    null,
                    true,
                    null,
                    1,
                    'aaa',
                ),
            ]);

        $options = new ImportOptions(StripFramingTags::None, new TmxFilterOptions());

        $result = $this->processor->process( // @phpstan-ignore-line
            <<<TU
        <tu tuid="1" creationdate="20160323T152428Z" creationid="MANAGER">
            <prop type="tmgr:segId">1</prop>
            <prop type="t5:InternalKey">7:1</prop>
            <prop type="tmgr:markup">OTMXUXLF</prop>
            <prop type="tmgr:docname">none</prop>
            <tuv xml:lang="de">
                <seg>Mit dem Feldbusstecker FBS-SUB-9-GS-DP-B von wird das Produkt komfortabel an den Feldbus angeschlossen.</seg>
            </tuv>
            <tuv xml:lang="ko">
                <seg>의 필드버스 커넥터 FBS-SUB-9-GS-DP-B를 이용하면 제품을 편리하게 필드버스에 연결할 수 있습니다.</seg>
            </tuv>
        </tu>
TU,
            $de,
            $ko,
            $options,
            NullBrokenTranslationUnitLogger::create(),
        )->current(); // @phpstan-ignore-line

        self::assertSame(
            <<<TU
        <tu tuid="1" creationdate="20160323T152428Z" creationid="MANAGER">
            <prop type="tmgr:segId">1</prop>
            <prop type="t5:InternalKey">7:1</prop>
            <prop type="tmgr:markup">OTMXUXLF</prop>
            <prop type="tmgr:docname">none</prop>
            <tuv xml:lang="de">
                <seg>Mit dem Feldbusstecker <t5:n id="1" r="aaa" n="FBS-SUB-9-GS-DP-B"/> von wird das Produkt komfortabel an den Feldbus angeschlossen.</seg>
            </tuv>
            <tuv xml:lang="ko">
                <seg>의 필드버스 커넥터 <t5:n id="1" r="aaa" n="FBS-SUB-9-GS-DP-B"/>를 이용하면 제품을 편리하게 필드버스에 연결할 수 있습니다.</seg>
            </tuv>
        </tu>
TU,
            $result
        );
    }
}
