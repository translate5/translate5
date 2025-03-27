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

namespace MittagQI\Translate5\Test\Functional\ContentProtection\T5memory;

use editor_Models_Languages;
use MittagQI\Translate5\ContentProtection\Model\ContentRecognition;
use MittagQI\Translate5\ContentProtection\Model\InputMapping;
use MittagQI\Translate5\ContentProtection\Model\OutputMapping;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\KeepContentProtector;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;
use MittagQI\Translate5\Repository\LanguageRepository;
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

        $this->sourceLang = $languageRepository->findByRfc5646('de-de') ?? new editor_Models_Languages();
        $this->targetLang = $languageRepository->findByRfc5646('it-it') ?? new editor_Models_Languages();

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
        $service = TmConversionService::create();

        $file = $service->convertTMXForImport(
            __DIR__ . '/TmConversionServiceTest/small.tmx',
            (int) $this->sourceLang->getId(),
            (int) $this->targetLang->getId(),
        );

        self::assertFileEquals(__DIR__ . '/TmConversionServiceTest/expected_small.tmx', $file);
    }
}
