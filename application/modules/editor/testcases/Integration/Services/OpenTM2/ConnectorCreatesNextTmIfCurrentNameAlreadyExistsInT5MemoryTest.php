<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

namespace MittagQI\Translate5\Test\Integration\Services\OpenTM2;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_LanguageResources_Languages;
use editor_Services_OpenTM2_Connector as Connector;
use editor_Services_OpenTM2_Service;
use MittagQI\Translate5\Repository\LanguageRepository;
use PHPUnit\Framework\TestCase;

class ConnectorCreatesNextTmIfCurrentNameAlreadyExistsInT5MemoryTest extends TestCase
{
    private Connector $connector;

    private LanguageResource $languageResource;

    /**
     * @var string[]
     */
    private array $memoriesToDelete = [];

    public function setUp(): void
    {
        $this->languageResource = new LanguageResource();
        $className = explode('\\', static::class);
        $className = array_pop($className);
        $this->languageResource->setName($className);
        $this->languageResource->setResourceId('editor_Services_OpenTM2_1');
        $this->languageResource->setResourceType('tm');
        $this->languageResource->setServiceType('editor_Services_OpenTM2');
        $this->languageResource->setServiceName(editor_Services_OpenTM2_Service::NAME);
        $this->languageResource->save();

        $languageRepository = LanguageRepository::create();

        $en = $languageRepository->findByRfc5646('en');
        $de = $languageRepository->findByRfc5646('de');

        $lrLanguages = new editor_Models_LanguageResources_Languages();
        $lrLanguages->setLanguageResourceId((int) $this->languageResource->getId());
        $lrLanguages->setSourceLang((int) $en->getId());
        $lrLanguages->setSourceLangCode($en->getRfc5646());
        $lrLanguages->setTargetLang((int) $de->getId());
        $lrLanguages->setTargetLangCode($de->getRfc5646());
        $lrLanguages->save();

        $this->connector = new Connector();
        $this->connector->connectTo($this->languageResource, (int) $en->getId(), (int) $de->getId());
    }

    public function tearDown(): void
    {
        foreach ($this->memoriesToDelete as $memory) {
            if (! $this->connector->deleteMemory($memory)) {
                self::fail('Could not delete memory: ' . $memory);
            }
        }

        $this->languageResource->delete();
    }

    public function test(): void
    {
        $tmFilename = __DIR__ . '/ConnectorCreatesNextTmIfCurrentNameAlreadyExistsInT5MemoryTest/Test_TM.tmx';
        $copy = __DIR__ . '/ConnectorCreatesNextTmIfCurrentNameAlreadyExistsInT5MemoryTest/Test_TM_COPY.tmx';

        // file will be deleted in addTm call
        copy($tmFilename, $copy);

        $mime = $this->connector->getValidExportTypes()['TMX'];

        $fileinfo = [
            'tmp_name' => $copy,
            'type' => $mime,
            'name' => basename($copy),
        ];

        // add the TM first time to store name in t5memory
        self::assertTrue($this->connector->addTm($fileinfo, [
            'createNewMemory' => true,
        ]));

        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);

        self::assertCount(1, $memories);

        $memory = $memories[0]['filename'];

        self::assertStringNotContainsString(Connector::NEXT_SUFFIX, $memory);

        $this->memoriesToDelete[] = $memory;

        // clean memories so new call will try to create new memory with same name as above
        $this->languageResource->addSpecificData('memories', []);
        $this->languageResource->save();

        copy($tmFilename, $copy);

        self::assertTrue($this->connector->addTm($fileinfo, [
            'createNewMemory' => true,
        ]));

        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);

        self::assertCount(1, $memories);

        $memory = $memories[0]['filename'];

        self::assertStringContainsString(Connector::NEXT_SUFFIX, $memory);

        $this->memoriesToDelete[] = $memory;
    }
}
