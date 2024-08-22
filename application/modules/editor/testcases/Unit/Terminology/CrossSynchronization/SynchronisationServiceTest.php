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

namespace Terminology\CrossSynchronization;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\CrossSynchronization\LanguagePair;
use MittagQI\Translate5\CrossSynchronization\SynchronisationType;
use MittagQI\Translate5\Terminology\CrossSynchronization\SynchronisationService;
use MittagQI\Translate5\Terminology\TermCollectionRepository;
use PHPUnit\Framework\TestCase;

class SynchronisationServiceTest extends TestCase
{
    public function testSyncSourceOf(): void
    {
        $termCollectionRepository = $this->createMock(TermCollectionRepository::class);
        $service = new SynchronisationService($termCollectionRepository);

        $this->assertEquals([SynchronisationType::Glossary], $service->syncSourceOf());
    }

    public function testSyncTargetFor(): void
    {
        $termCollectionRepository = $this->createMock(TermCollectionRepository::class);
        $service = new SynchronisationService($termCollectionRepository);

        $this->assertEquals([], $service->syncTargetFor());
    }

    public function testIsOneToOne(): void
    {
        $termCollectionRepository = $this->createMock(TermCollectionRepository::class);
        $service = new SynchronisationService($termCollectionRepository);

        $this->assertFalse($service->isOneToOne());
    }

    public function testGetSyncData(): void
    {
        $termCollectionRepository = $this->createMock(TermCollectionRepository::class);
        $service = new SynchronisationService($termCollectionRepository);

        $termCollectionRepository
            ->method('getTermTranslationsForLanguageCombo')
            ->willReturn([
                [
                    'source' => 'source1',
                    'target' => 'target1',
                ],
                [
                    'source' => 'source2',
                    'target' => '',
                ],
                [
                    'source' => 'source3',
                    'target' => 'source3',
                ],
            ]);

        $languageResource = $this->createMock(LanguageResource::class);

        $this->assertEquals(
            [
                [
                    'source' => 'source1',
                    'target' => 'target1',
                ],
                [
                    'source' => 'source3',
                    'target' => 'source3',
                ],
            ],
            iterator_to_array(
                $service->getSyncData(
                    $languageResource,
                    new LanguagePair(1, 2, 'en', 'de'),
                    SynchronisationType::Glossary,
                )
            )
        );
    }
}
