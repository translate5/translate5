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

namespace MittagQI\Translate5\Test\Unit\CrossSynchronization;

use editor_Models_LanguageResources_CustomerAssoc as Association;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Languages as Language;
use editor_Services_Manager;
use MittagQI\Translate5\CrossSynchronization\ConnectionOptionsRepository;
use MittagQI\Translate5\CrossSynchronization\CrossLanguageResourceSynchronizationService;
use MittagQI\Translate5\CrossSynchronization\CrossSynchronizationConnection;
use MittagQI\Translate5\CrossSynchronization\CrossSynchronizationConnectionCustomer;
use MittagQI\Translate5\CrossSynchronization\Dto\AvailableForConnectionOption;
use MittagQI\Translate5\CrossSynchronization\Dto\PotentialConnectionOption;
use MittagQI\Translate5\CrossSynchronization\Events\ConnectionCreatedEvent;
use MittagQI\Translate5\CrossSynchronization\Events\ConnectionDeletedEvent;
use MittagQI\Translate5\CrossSynchronization\Events\CustomerAddedEvent;
use MittagQI\Translate5\CrossSynchronization\Events\CustomerRemovedEvent;
use MittagQI\Translate5\CrossSynchronization\LanguagePair;
use MittagQI\Translate5\CrossSynchronization\SynchronisationInterface;
use MittagQI\Translate5\CrossSynchronization\SynchronisationType;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\Repository\CrossSynchronizationConnectionRepository;
use MittagQI\Translate5\Repository\LanguageRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CrossLanguageResourceSynchronizationServiceTest extends TestCase
{
    private MockObject|editor_Services_Manager $serviceManager;

    private MockObject|EventDispatcher $eventDispatcher;

    private MockObject|CrossSynchronizationConnectionRepository $connectionRepository;

    private MockObject|ConnectionOptionsRepository $connectionOptionsRepository;

    private MockObject|LanguageRepository $languageRepository;

    private CrossLanguageResourceSynchronizationService $service;

    protected function setUp(): void
    {
        $this->serviceManager = $this->createMock(editor_Services_Manager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
        $this->connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);
        $this->connectionOptionsRepository = $this->createMock(ConnectionOptionsRepository::class);
        $this->languageRepository = $this->createMock(LanguageRepository::class);

        $this->service = new CrossLanguageResourceSynchronizationService(
            $this->serviceManager,
            $this->eventDispatcher,
            $this->connectionRepository,
            $this->connectionOptionsRepository,
            $this->languageRepository,
        );
    }

    public function testGetConnectedPairsByAssoc(): void
    {
        $this->connectionRepository
            ->method('getConnectionsByLrCustomerAssoc')
            ->willReturn([
                $this->createMock(CrossSynchronizationConnection::class),
            ]);

        $assoc = $this->createMock(Association::class);

        foreach ($this->service->getConnectionsByLrCustomerAssoc($assoc) as $connection) {
            self::assertInstanceOf(CrossSynchronizationConnection::class, $connection);
        }
    }

    public function testCreateConnection(): void
    {
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ConnectionCreatedEvent::class));

        $connection = $this->createMock(CrossSynchronizationConnection::class);
        $sourceLR = $this->createMock(LanguageResource::class);
        $targetLR = $this->createMock(LanguageResource::class);
        $sourceLang = $this->createMock(Language::class);
        $targetLang = $this->createMock(Language::class);

        $this->connectionRepository->method('createConnection')->willReturn($connection);

        self::assertEquals($connection, $this->service->createConnection($sourceLR, $targetLR, $sourceLang, $targetLang));
    }

    public function testDeleteConnection(): void
    {
        $this->eventDispatcher->expects(self::once())->method('dispatch')->with(self::isInstanceOf(ConnectionDeletedEvent::class));

        $connection = $this->createMock(CrossSynchronizationConnection::class);

        $this->connectionRepository->method('createConnection')->willReturn($connection);

        $this->service->deleteConnection($connection);
    }

    public function testAddCustomer(): void
    {
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CustomerAddedEvent::class));

        $connection = $this->createMock(CrossSynchronizationConnection::class);

        $this->service->addCustomer($connection, 1);
    }

    public function testRemoveCustomer(): void
    {
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CustomerRemovedEvent::class));

        $assoc = $this->createMock(CrossSynchronizationConnectionCustomer::class);

        $this->service->removeCustomer($assoc);
    }

    public function testRemoveCustomerFromConnections(): void
    {
        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->with(self::isInstanceOf(CustomerRemovedEvent::class));

        $this->connectionRepository->method('getCustomerAssocsByCustomerAndLanguageResource')->willReturn([
            $this->createMock(CrossSynchronizationConnectionCustomer::class),
            $this->createMock(CrossSynchronizationConnectionCustomer::class),
        ]);

        $this->service->removeCustomerFromConnections(1, 1);
    }

    public function testDeleteRelatedConnections(): void
    {
        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->with(self::isInstanceOf(ConnectionDeletedEvent::class));

        $this->connectionRepository->method('getConnectionsFor')->willReturn([
            $this->createMock(CrossSynchronizationConnection::class),
            $this->createMock(CrossSynchronizationConnection::class),
        ]);

        $this->service->deleteRelatedConnections(1);
    }

    public function testGetSyncData(): void
    {
        $sourceLR = $this->createMock(LanguageResource::class);
        $sourceLR->method('__call')->willReturn('serviceType');

        $pair = new LanguagePair(1, 2);

        $synchronisationService = $this->createConfiguredMock(
            SynchronisationInterface::class,
            [
                'getSyncData' => (static function () {
                    yield [
                        'source' => 'source',
                        'target' => 'target',
                    ];
                })(),
            ]
        );

        $this->serviceManager
            ->method('getSynchronisationService')
            ->willReturnOnConsecutiveCalls(null, $synchronisationService)
        ;

        $data = $this->service->getSyncData($sourceLR, $pair, SynchronisationType::Glossary);

        self::assertFalse($data->valid());

        $data = $this->service->getSyncData($sourceLR, $pair, SynchronisationType::Glossary);

        self::assertTrue($data->valid());
    }

    public function testConnect(): void
    {
        $i = 0;
        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->with(
                self::callback(
                    static function ($event) use (&$i) {
                        return 0 === $i++
                            ? $event instanceof ConnectionCreatedEvent
                            : $event instanceof CustomerAddedEvent;
                    }
                )
            );

        $sourceLR = $this->createMock(LanguageResource::class);
        $sourceLR->method('getCustomers')->willReturn([1]);

        $targetLR = $this->createMock(LanguageResource::class);
        $targetLR->method('getCustomers')->willReturn([1]);

        $sourceLang = $this->createMock(Language::class);
        $targetLang = $this->createMock(Language::class);

        $this->service->connect($sourceLR, $targetLR, $sourceLang, $targetLang);
    }

    /**
     * No Synchronizable Integrations present - no lang resources returned
     */
    public function testGetAvailableForConnectionLanguageResources1(): void
    {
        $sourceLR = $this->createMock(LanguageResource::class);
        $sourceLR->method('getSourceLang')->willReturn(1);
        $sourceLR->method('getTargetLang')->willReturn(2);
        $sourceLR->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $sourceIntegration = $this->createIntegration();

        $this->serviceManager->method('getAll')->willReturn([]);
        $this->serviceManager
            ->method('getSynchronisationService')
            ->willReturnMap(
                [
                    [$sourceLR->getServiceType(), $sourceIntegration],
                ]
            );

        $options = $this->service->getAvailableForConnectionOptions($sourceLR);

        $count = 0;
        foreach ($options as $option) {
            $count++;
        }

        self::assertSame(0, $count);
    }

    /**
     * No connections exist. 1 corresponding LR returned
     */
    public function testGetAvailableForConnectionLanguageResourcesReturns2(): void
    {
        $this->connectionRepository->method('getAllTargetLanguageResourceIds')->willReturn([]);
        $this->connectionRepository->method('getConnectionsWhereSource')->willReturn([]);

        $targetLR = $this->createMock(LanguageResource::class);
        $targetLR->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'targetServiceType'],
        ]);

        $sourceLanguage = $this->createMock(Language::class);
        $sourceLanguage->method('__call')->willReturnMap([
            ['getId', [], '1'],
        ]);

        $targetLanguage = $this->createMock(Language::class);
        $targetLanguage->method('__call')->willReturnMap([
            ['getId', [], '2'],
        ]);

        $optionMock = new PotentialConnectionOption(
            $targetLR,
            $sourceLanguage,
            $targetLanguage,
        );
        $this->connectionOptionsRepository
            ->method('getPotentialConnectionOptions')
            ->willReturn([$optionMock]);

        $sourceLR = $this->createMock(LanguageResource::class);
        $sourceLR->method('getSourceLang')->willReturn($sourceLanguage->getId());
        $sourceLR->method('getTargetLang')->willReturn($targetLanguage->getId());
        $sourceLR->method('__call')->willReturnMap([
            ['getId', [], 2],
            ['getServiceType', [], 'sourceServiceType'],
            ['getSourceLang', [], $sourceLanguage->getId()],
            ['getTargetLang', [], $targetLanguage->getId()],
        ]);

        $sourceIntegration = $this->createIntegration();
        $targetIntegration = $this->createIntegration();
        $targetIntegration
            ->method('getSupportedLanguagePairs')
            ->willReturn([
                new LanguagePair((int) $sourceLanguage->getId(), (int) $targetLanguage->getId()),
            ]);

        $this->serviceManager->method('getAll')->willReturn([$targetLR->getServiceType()]);
        $this->serviceManager
            ->method('getSynchronisationService')
            ->willReturnMap(
                [
                    [$sourceLR->getServiceType(), $sourceIntegration],
                    [$targetLR->getServiceType(), $targetIntegration],
                ]
            );

        $options = $this->service->getAvailableForConnectionOptions($sourceLR);

        $count = 0;
        foreach ($options as $option) {
            self::assertInstanceOf(AvailableForConnectionOption::class, $option);
            $count++;
        }

        self::assertSame(1, $count);
    }

    /**
     * No connections present.
     * Only 1 LR belongs to integration that can be synchronized so return should contain 1 LR
     */
    public function testGetAvailableForConnectionLanguageResourcesReturns3(): void
    {
        $sourceLanguage = $this->createMock(Language::class);
        $sourceLanguage->method('__call')->willReturnMap([
            ['getId', [], '1'],
        ]);

        $targetLanguage = $this->createMock(Language::class);
        $targetLanguage->method('__call')->willReturnMap([
            ['getId', [], '2'],
        ]);

        $expectedTarget = $this->createMock(LanguageResource::class);
        $expectedTarget->method('getSourceLang')->willReturn($sourceLanguage->getId());
        $expectedTarget->method('getTargetLang')->willReturn($targetLanguage->getId());
        $expectedTarget->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'targetSyncServiceType'],
        ]);

        $validOptionMock = new PotentialConnectionOption(
            $expectedTarget,
            $sourceLanguage,
            $targetLanguage,
        );

        $additionalTarget = $this->createMock(LanguageResource::class);
        $additionalTarget->method('getSourceLang')->willReturn(1);
        $additionalTarget->method('getTargetLang')->willReturn(2);
        $additionalTarget->method('__call')->willReturnMap([
            ['getId', [], 2],
            ['getServiceType', [], 'targetNotSyncServiceType'],
        ]);

        $additionalOptionMock = new PotentialConnectionOption(
            $additionalTarget,
            $this->createMock(Language::class),
            $this->createMock(Language::class),
        );

        $this->connectionOptionsRepository
            ->method('getPotentialConnectionOptions')
            ->willReturn([$validOptionMock, $additionalOptionMock]);

        $this->connectionRepository->method('getAllTargetLanguageResourceIds')->willReturn([]);
        $this->connectionRepository->method('getConnectionsWhereSource')->willReturn([]);

        $sourceLR = $this->createMock(LanguageResource::class);
        $sourceLR->method('getSourceLang')->willReturn($sourceLanguage->getId());
        $sourceLR->method('getTargetLang')->willReturn($targetLanguage->getId());
        $sourceLR->method('__call')->willReturnMap([
            ['getId', [], 3],
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $sourceIntegration = $this->createIntegration();
        $targetSyncIntegration = $this->createIntegration();
        $targetSyncIntegration
            ->method('getSupportedLanguagePairs')
            ->willReturn([
                new LanguagePair((int) $sourceLanguage->getId(), (int) $targetLanguage->getId()),
            ]);
        $targetNotSyncIntegration = $this->createIntegration(false);

        $this->serviceManager->method('getAll')->willReturn([
            $expectedTarget->getServiceType(),
            $additionalTarget->getServiceType(),
        ]);
        $this->serviceManager
            ->method('getSynchronisationService')
            ->willReturnMap(
                [
                    [$sourceLR->getServiceType(), $sourceIntegration],
                    [$expectedTarget->getServiceType(), $targetSyncIntegration],
                    [$additionalTarget->getServiceType(), $targetNotSyncIntegration],
                ]
            );

        $options = $this->service->getAvailableForConnectionOptions($sourceLR);

        $count = 0;
        foreach ($options as $option) {
            self::assertInstanceOf(AvailableForConnectionOption::class, $option);
            self::assertSame($expectedTarget, $option->languageResource);
            $count++;
        }

        self::assertSame(1, $count);
    }

    /**
     * 1 LR already connected.
     * 2 LR belongs to integration that can be synchronized BUT return should contain 1 LR
     */
    public function testGetAvailableForConnectionLanguageResourcesReturns4(): void
    {
        $sourceLanguage = $this->createMock(Language::class);
        $sourceLanguage->method('__call')->willReturnMap([
            ['getId', [], '1'],
        ]);

        $targetLanguage = $this->createMock(Language::class);
        $targetLanguage->method('__call')->willReturnMap([
            ['getId', [], '2'],
        ]);

        $expectedTarget = $this->createMock(LanguageResource::class);
        $expectedTarget->method('getSourceLang')->willReturn($sourceLanguage->getId());
        $expectedTarget->method('getTargetLang')->willReturn($targetLanguage->getId());
        $expectedTarget->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'targetSyncServiceType'],
        ]);

        $validOptionMock = new PotentialConnectionOption(
            $expectedTarget,
            $sourceLanguage,
            $targetLanguage,
        );

        $connectedTarget = $this->createMock(LanguageResource::class);
        $connectedTarget->method('getSourceLang')->willReturn($sourceLanguage->getId());
        $connectedTarget->method('getTargetLang')->willReturn($targetLanguage->getId());
        $connectedTarget->method('__call')->willReturnMap([
            ['getId', [], 2],
            ['getServiceType', [], 'targetConnectedServiceType'],
        ]);

        $connectedOptionMock = new PotentialConnectionOption(
            $connectedTarget,
            $sourceLanguage,
            $targetLanguage,
        );

        $this->connectionRepository->method('getAllTargetLanguageResourceIds')->willReturn([]);

        $this->connectionOptionsRepository
            ->method('getPotentialConnectionOptions')
            ->willReturn([$validOptionMock, $connectedOptionMock]);

        $connection = $this->createMock(CrossSynchronizationConnection::class);
        $connection->method('__call')->willReturnMap([
            ['getTargetLanguageResourceId', [], $connectedTarget->getId()],
        ]);

        $this->connectionRepository->method('getConnectionsWhereSource')->willReturn([$connection]);

        $sourceLR = $this->createMock(LanguageResource::class);
        $sourceLR->method('getSourceLang')->willReturn($sourceLanguage->getId());
        $sourceLR->method('getTargetLang')->willReturn($targetLanguage->getId());
        $sourceLR->method('__call')->willReturnMap([
            ['getId', [], 3],
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $sourceIntegration = $this->createIntegration();
        $targetSyncIntegration = $this->createIntegration();
        $targetSyncIntegration
            ->method('getSupportedLanguagePairs')
            ->willReturn([
                new LanguagePair((int) $sourceLanguage->getId(), (int) $targetLanguage->getId()),
            ]);

        $this->serviceManager->method('getAll')->willReturn([
            $expectedTarget->getServiceType(),
            $connectedTarget->getServiceType(),
        ]);
        $this->serviceManager
            ->method('getSynchronisationService')
            ->willReturnMap(
                [
                    [$sourceLR->getServiceType(), $sourceIntegration],
                    [$expectedTarget->getServiceType(), $targetSyncIntegration],
                    [$connectedTarget->getServiceType(), $targetSyncIntegration],
                ]
            );

        $options = $this->service->getAvailableForConnectionOptions($sourceLR);

        $count = 0;
        foreach ($options as $option) {
            self::assertInstanceOf(AvailableForConnectionOption::class, $option);
            self::assertSame($expectedTarget, $option->languageResource);
            $count++;
        }

        self::assertSame(1, $count);
    }

    /**
     * 2 LRs connected.
     * 1 to current source LR and 1 to some other
     * LR that is not connected to current source has integration that supports only one-to-one connection
     * return must be empty
     */
    public function testGetAvailableForConnectionLanguageResourcesReturns5(): void
    {
        $sourceLanguage = $this->createMock(Language::class);
        $sourceLanguage->method('__call')->willReturnMap([
            ['getId', [], '1'],
        ]);

        $targetLanguage = $this->createMock(Language::class);
        $targetLanguage->method('__call')->willReturnMap([
            ['getId', [], '2'],
        ]);

        $targetConnectedToOtherSource = $this->createMock(LanguageResource::class);
        $targetConnectedToOtherSource->method('getSourceLang')->willReturn($sourceLanguage->getId());
        $targetConnectedToOtherSource->method('getTargetLang')->willReturn($targetLanguage->getId());
        $targetConnectedToOtherSource->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'targetConnectedToOtherServiceType'],
        ]);

        $connectedToOtherOptionMock = new PotentialConnectionOption(
            $targetConnectedToOtherSource,
            $sourceLanguage,
            $targetLanguage,
        );

        $connectedTarget = $this->createMock(LanguageResource::class);
        $connectedTarget->method('getSourceLang')->willReturn($sourceLanguage->getId());
        $connectedTarget->method('getTargetLang')->willReturn($targetLanguage->getId());
        $connectedTarget->method('__call')->willReturnMap([
            ['getId', [], 2],
            ['getServiceType', [], 'targetConnectedServiceType'],
        ]);

        $connectedOptionMock = new PotentialConnectionOption(
            $connectedTarget,
            $sourceLanguage,
            $targetLanguage,
        );

        $this->connectionOptionsRepository
            ->method('getPotentialConnectionOptions')
            ->willReturn([$connectedToOtherOptionMock, $connectedOptionMock])
        ;

        $this->connectionRepository
            ->method('getAllTargetLanguageResourceIds')
            ->willReturn([$targetConnectedToOtherSource->getId()])
        ;

        $connection = $this->createMock(CrossSynchronizationConnection::class);
        $connection->method('__call')->willReturnMap([
            ['getTargetLanguageResourceId', [], $connectedTarget->getId()],
        ]);

        $this->connectionRepository->method('getConnectionsWhereSource')->willReturn([$connection]);

        $sourceLR = $this->createMock(LanguageResource::class);
        $sourceLR->method('getSourceLang')->willReturn($sourceLanguage->getId());
        $sourceLR->method('getTargetLang')->willReturn($targetLanguage->getId());
        $sourceLR->method('__call')->willReturnMap([
            ['getId', [], 3],
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $sourceIntegration = $this->createIntegration();
        $targetSyncIntegration = $this->createIntegration();
        $targetSyncIntegration
            ->method('getSupportedLanguagePairs')
            ->willReturn([
                new LanguagePair((int) $sourceLanguage->getId(), (int) $targetLanguage->getId()),
            ]);

        $this->serviceManager->method('getAll')->willReturn([
            $targetConnectedToOtherSource->getServiceType(),
            $connectedTarget->getServiceType(),
        ]);
        $this->serviceManager
            ->method('getSynchronisationService')
            ->willReturnMap(
                [
                    [$sourceLR->getServiceType(), $sourceIntegration],
                    [$targetConnectedToOtherSource->getServiceType(), $targetSyncIntegration],
                    [$connectedTarget->getServiceType(), $targetSyncIntegration],
                ]
            );

        $options = $this->service->getAvailableForConnectionOptions($sourceLR);

        $count = 0;
        foreach ($options as $option) {
            $count++;
        }

        self::assertSame(0, $count);
    }

    /**
     * 2 LRs connected.
     * 1 to current source LR and 1 to some other
     * LR that is not connected to current source has integration that supports only one-to-many connection
     * this one LR expected to be returned
     */
    public function testGetAvailableForConnectionLanguageResourcesReturns6(): void
    {
        $sourceLanguage = $this->createMock(Language::class);
        $sourceLanguage->method('__call')->willReturnMap([
            ['getId', [], '1'],
        ]);

        $targetLanguage = $this->createMock(Language::class);
        $targetLanguage->method('__call')->willReturnMap([
            ['getId', [], '2'],
        ]);

        $targetConnectedToOtherSource = $this->createMock(LanguageResource::class);
        $targetConnectedToOtherSource->method('getSourceLang')->willReturn($sourceLanguage->getId());
        $targetConnectedToOtherSource->method('getTargetLang')->willReturn($targetLanguage->getId());
        $targetConnectedToOtherSource->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'targetConnectedToOtherServiceType'],
        ]);

        $connectedToOtherOptionMock = new PotentialConnectionOption(
            $targetConnectedToOtherSource,
            $sourceLanguage,
            $targetLanguage,
        );

        $connectedTarget = $this->createMock(LanguageResource::class);
        $connectedTarget->method('getSourceLang')->willReturn($sourceLanguage->getId());
        $connectedTarget->method('getTargetLang')->willReturn($targetLanguage->getId());
        $connectedTarget->method('__call')->willReturnMap([
            ['getId', [], 2],
            ['getServiceType', [], 'targetConnectedServiceType'],
        ]);

        $connectedOptionMock = new PotentialConnectionOption(
            $connectedTarget,
            $sourceLanguage,
            $targetLanguage,
        );

        $this->connectionOptionsRepository
            ->method('getPotentialConnectionOptions')
            ->willReturn([$connectedToOtherOptionMock, $connectedOptionMock])
        ;

        $this->connectionRepository
            ->method('getAllTargetLanguageResourceIds')
            ->willReturn([$targetConnectedToOtherSource->getId()])
        ;

        $connection = $this->createMock(CrossSynchronizationConnection::class);
        $connection->method('__call')->willReturnMap([
            ['getTargetLanguageResourceId', [], $connectedTarget->getId()],
        ]);

        $this->connectionRepository->method('getConnectionsWhereSource')->willReturn([$connection]);

        $sourceLR = $this->createMock(LanguageResource::class);
        $sourceLR->method('getSourceLang')->willReturn($sourceLanguage->getId());
        $sourceLR->method('getTargetLang')->willReturn($targetLanguage->getId());
        $sourceLR->method('__call')->willReturnMap([
            ['getId', [], 3],
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $sourceIntegration = $this->createIntegration();
        $targetSyncIntegration = $this->createIntegration();
        $targetOneToManySyncIntegration = $this->createIntegration(oneToOne: false);
        $targetOneToManySyncIntegration
            ->method('getSupportedLanguagePairs')
            ->willReturn([
                new LanguagePair((int) $sourceLanguage->getId(), (int) $targetLanguage->getId()),
            ]);

        $this->serviceManager->method('getAll')->willReturn([
            $targetConnectedToOtherSource->getServiceType(),
            $connectedTarget->getServiceType(),
        ]);
        $this->serviceManager
            ->method('getSynchronisationService')
            ->willReturnMap(
                [
                    [$sourceLR->getServiceType(), $sourceIntegration],
                    [$targetConnectedToOtherSource->getServiceType(), $targetOneToManySyncIntegration],
                    [$connectedTarget->getServiceType(), $targetSyncIntegration],
                ]
            );

        $options = $this->service->getAvailableForConnectionOptions($sourceLR);

        $count = 0;
        foreach ($options as $option) {
            self::assertInstanceOf(AvailableForConnectionOption::class, $option);
            self::assertSame($targetConnectedToOtherSource, $option->languageResource);
            $count++;
        }

        self::assertSame(1, $count);
    }

    private function createIntegration(
        bool $hasSyncTarget = true,
        bool $oneToOne = true,
    ): MockObject|SynchronisationInterface {
        return $this->createConfiguredMock(SynchronisationInterface::class, [
            'syncSourceOf' => [SynchronisationType::Glossary],
            'syncTargetFor' => $hasSyncTarget ? [SynchronisationType::Glossary] : [],
            'isOneToOne' => $oneToOne,
        ]);
    }
}
