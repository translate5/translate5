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

namespace MittagQI\Translate5\Test\Integration\Services\T5Memory;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_LanguageResources_Languages as LanguageResourceLanguage;
use editor_Services_T5Memory_Connector as Connector;
use editor_Services_T5Memory_Service;
use Http\Client\Exception\HttpException;
use MittagQI\Translate5\LanguageResource\Adapter\LanguagePairDTO;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\T5Memory\CreateMemoryService;
use PHPUnit\Framework\TestCase;

class IsLanguageResourceHasEmptyMemoryTest extends TestCase
{
    private CreateMemoryService $createMemoryService;

    private Connector $connector;

    private LanguageResource $languageResource;

    private LanguageResourceLanguage $languageResourceLanguage;

    private ?string $t5memoryName = null;

    public function setUp(): void
    {
        $this->languageResource = new LanguageResource();
        $className = explode('\\', static::class);
        $className = array_pop($className);
        $this->languageResource->setName($className);
        $this->languageResource->setResourceId('editor_Services_T5Memory_1');
        $this->languageResource->setResourceType('tm');
        $this->languageResource->setServiceType('editor_Services_T5Memory');
        $this->languageResource->setServiceName(editor_Services_T5Memory_Service::NAME);
        $this->languageResource->save();

        $languageRepository = LanguageRepository::create();

        $en = $languageRepository->findByRfc5646('en');
        $de = $languageRepository->findByRfc5646('de');

        $this->languageResourceLanguage = new LanguageResourceLanguage();
        $this->languageResourceLanguage->setLanguageResourceId($this->languageResource->getId());
        $this->languageResourceLanguage->setSourceLang((int) $en->getId());
        $this->languageResourceLanguage->setSourceLangCode($en->getRfc5646());
        $this->languageResourceLanguage->setTargetLang((int) $de->getId());
        $this->languageResourceLanguage->setTargetLangCode($de->getRfc5646());
        $this->languageResourceLanguage->save();

        $this->createMemoryService = CreateMemoryService::create();

        $name = \ZfExtended_Utils::guid();

        try {
            $this->t5memoryName = $this->createMemoryService->createEmptyMemory($this->languageResource, $name);
        } catch (\Exception $e) {
            $error = $e->getMessage();

            if ($e instanceof HttpException) {
                $error = $e->getResponse()->getBody()->getContents() ?: $error;
            }

            self::fail('Could not create empty memory: ' . $error);
        }

        $this->connector = new Connector();
        $languagePair = new LanguagePairDTO((int) $en->getId(), (int) $de->getId());
        $this->connector->connectTo($this->languageResource, $languagePair);
    }

    public function tearDown(): void
    {
        if ($this->t5memoryName) {
            if (! $this->connector->deleteMemory($this->t5memoryName)) {
                self::fail('Could not delete memory: ' . $this->t5memoryName);
            }
        }

        $this->languageResource->delete();
        $this->languageResource->delete();
    }

    public function test(): void
    {
        self::assertTrue($this->connector->isEmpty(), 'The memory is not empty');
    }
}
