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

namespace LanguageResource\ReimportSegments;

use editor_Models_Languages;
use MittagQI\Translate5\ContentProtection\Model\ContentRecognition;
use MittagQI\Translate5\ContentProtection\Model\InputMapping;
use MittagQI\Translate5\ContentProtection\Model\OutputMapping;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\KeepContentProtector;
use MittagQI\Translate5\LanguageResource\Adapter\UpdateSegmentDTO;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReApplyProtectionRules;
use MittagQI\Translate5\Repository\LanguageRepository;
use PHPUnit\Framework\TestCase;

class ReApplyProtectionRulesTest extends TestCase
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

    public function test(): void
    {
        $service = ReApplyProtectionRules::create();

        $source = 'segment <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="10"/> and <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="15"/> and <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="20"/>V';
        $target = 'segment <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="20"/>V and <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="15"/> and <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="10"/>';

        $dto = new UpdateSegmentDTO(
            'taskGuid',
            1,
            $source,
            $target,
            'fileName',
            'timestamp',
            'userName',
            'context',
        );

        $result = $service->reApplyRules($dto, (int) $this->sourceLang->getId(), (int) $this->targetLang->getId());

        self::assertSame(
            'segment <t5:n id="1" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA=" n="10"/> and <t5:n id="2" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA=" n="15"/> and <t5:n id="3" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1FCtObShJqwmN7cmOymzpKa4pqA4syYpsajGNyCxJtdRU0MjOkZPx8raXjEWZJCKpmYNiKqJ0dTULwUA" n="20"/>V',
            $result->source,
            'Source converted incorrectly'
        );

        self::assertSame(
            'segment <t5:n id="3" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1FCtObShJqwmN7cmOymzpKa4pqA4syYpsajGNyCxJtdRU0MjOkZPx8raXjEWZJCKpmYNiKqJ0dTULwUA" n="20"/>V and <t5:n id="2" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA=" n="15"/> and <t5:n id="1" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA=" n="10"/>',
            $result->target,
            'Target converted incorrectly'
        );
    }
}
