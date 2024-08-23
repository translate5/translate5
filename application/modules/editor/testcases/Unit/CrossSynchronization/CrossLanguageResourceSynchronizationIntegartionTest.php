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
use MittagQI\Translate5\CrossSynchronization\Dto\AvailableForConnectionOption;
use MittagQI\Translate5\CrossSynchronization\Dto\PotentialConnectionOption;
use MittagQI\Translate5\CrossSynchronization\Events\ConnectionCreatedEvent;
use MittagQI\Translate5\CrossSynchronization\Events\ConnectionDeletedEvent;
use MittagQI\Translate5\CrossSynchronization\Events\CustomerAddedEvent;
use MittagQI\Translate5\CrossSynchronization\LanguagePair;
use MittagQI\Translate5\CrossSynchronization\SynchronisationInterface;
use MittagQI\Translate5\CrossSynchronization\SynchronisationType;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\Repository\CrossSynchronizationConnectionRepository;
use PHPUnit\Framework\TestCase;

class CrossLanguageResourceSynchronizationIntegartionTest extends TestCase
{
    public function testGetConnectedPairsByAssoc(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);
        $connectionOptionsRepository = $this->createMock(ConnectionOptionsRepository::class);

        $connectionRepository
            ->method('getConnectionsByLrCustomerAssoc')
            ->willReturn([
                $this->createMock(CrossSynchronizationConnection::class),
            ]);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $connectionRepository,
            $connectionOptionsRepository,
        );

        $assoc = $this->createMock(Association::class);

        foreach ($service->getConnectionsByLrCustomerAssoc($assoc) as $connection) {
            self::assertInstanceOf(CrossSynchronizationConnection::class, $connection);
        }
    }

    public function testCreateConnection(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ConnectionCreatedEvent::class));

        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);
        $connectionOptionsRepository = $this->createMock(ConnectionOptionsRepository::class);

        $connection = $this->createMock(CrossSynchronizationConnection::class);
        $sourceLR = $this->createMock(LanguageResource::class);
        $targetLR = $this->createMock(LanguageResource::class);
        $sourceLang = $this->createMock(Language::class);
        $targetLang = $this->createMock(Language::class);

        $connectionRepository->method('createConnection')->willReturn($connection);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $connectionRepository,
            $connectionOptionsRepository,
        );

        self::assertEquals($connection, $service->createConnection($sourceLR, $targetLR, $sourceLang, $targetLang, 1));
    }

    public function testDeleteConnection(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects(self::once())->method('dispatch')->with(self::isInstanceOf(ConnectionDeletedEvent::class));

        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);
        $connectionOptionsRepository = $this->createMock(ConnectionOptionsRepository::class);

        $connection = $this->createMock(CrossSynchronizationConnection::class);

        $connectionRepository->method('createConnection')->willReturn($connection);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $connectionRepository,
            $connectionOptionsRepository,
        );

        $service->deleteConnection($connection);
    }

    public function testGetSyncData(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);
        $connectionOptionsRepository = $this->createMock(ConnectionOptionsRepository::class);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $connectionRepository,
            $connectionOptionsRepository,
        );
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

        $serviceManager
            ->method('getSynchronisationService')
            ->willReturnOnConsecutiveCalls(null, $synchronisationService)
        ;

        $data = $service->getSyncData($sourceLR, $pair, SynchronisationType::Glossary);

        self::assertFalse($data->valid());

        $data = $service->getSyncData($sourceLR, $pair, SynchronisationType::Glossary);

        self::assertTrue($data->valid());
    }

    public function testConnect(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);

        $eventDispatcher = $this->createMock(EventDispatcher::class);

        $i = 0;
        $eventDispatcher
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

        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);
        $connectionOptionsRepository = $this->createMock(ConnectionOptionsRepository::class);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $connectionRepository,
            $connectionOptionsRepository,
        );

        $sourceLR = $this->createMock(LanguageResource::class);
        $sourceLR->method('getCustomers')->willReturn([1]);

        $targetLR = $this->createMock(LanguageResource::class);
        $targetLR->method('getCustomers')->willReturn([1]);

        $sourceLang = $this->createMock(Language::class);
        $targetLang = $this->createMock(Language::class);

        $service->connect($sourceLR, $targetLR, $sourceLang, $targetLang);
    }

    /**
     * No Synchronizable Integrations present - no lang resources returned
     */
    public function testGetAvailableForConnectionLanguageResources1(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);
        $connectionOptionsRepository = $this->createMock(ConnectionOptionsRepository::class);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $connectionRepository,
            $connectionOptionsRepository,
        );
        $sourceLR = $this->createMock(LanguageResource::class);
        $sourceLR->method('getSourceLang')->willReturn(1);
        $sourceLR->method('getTargetLang')->willReturn(2);
        $sourceLR->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $sourceIntegration = $this->createIntegration();

        $serviceManager->method('getAll')->willReturn([]);
        $serviceManager
            ->method('getSynchronisationService')
            ->willReturnMap(
                [
                    [$sourceLR->getServiceType(), $sourceIntegration],
                ]
            );

        $options = $service->getAvailableForConnectionOptions($sourceLR);

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
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $connectionOptionsRepository = $this->createMock(ConnectionOptionsRepository::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $connectionRepository->method('getAllTargetLanguageResourceIds')->willReturn([]);
        $connectionRepository->method('getConnectionsWhereSource')->willReturn([]);

        $targetLR = $this->createMock(LanguageResource::class);
        $targetLR->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'targetServiceType'],
        ]);

        $optionMock = new PotentialConnectionOption(
            $targetLR,
            $this->createMock(Language::class),
            $this->createMock(Language::class),
        );
        $connectionOptionsRepository->method('getPotentialConnectionOptions')->willReturn([$optionMock]);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $connectionRepository,
            $connectionOptionsRepository,
        );

        $sourceLR = $this->createMock(LanguageResource::class);
        $sourceLR->method('getSourceLang')->willReturn(1);
        $sourceLR->method('getTargetLang')->willReturn(2);
        $sourceLR->method('__call')->willReturnMap([
            ['getId', [], 2],
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $sourceIntegration = $this->createIntegration();
        $targetIntegration = $this->createIntegration();

        $serviceManager->method('getAll')->willReturn([$targetLR->getServiceType()]);
        $serviceManager
            ->method('getSynchronisationService')
            ->willReturnMap(
                [
                    [$sourceLR->getServiceType(), $sourceIntegration],
                    [$targetLR->getServiceType(), $targetIntegration],
                ]
            );

        $options = $service->getAvailableForConnectionOptions($sourceLR);

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
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $connectionOptionsRepository = $this->createMock(ConnectionOptionsRepository::class);

        $expectedTarget = $this->createMock(LanguageResource::class);
        $expectedTarget->method('getSourceLang')->willReturn(1);
        $expectedTarget->method('getTargetLang')->willReturn(2);
        $expectedTarget->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'targetSyncServiceType'],
        ]);

        $validOptionMock = new PotentialConnectionOption(
            $expectedTarget,
            $this->createMock(Language::class),
            $this->createMock(Language::class),
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

        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $connectionOptionsRepository
            ->method('getPotentialConnectionOptions')
            ->willReturn([$validOptionMock, $additionalOptionMock]);

        $connectionRepository->method('getAllTargetLanguageResourceIds')->willReturn([]);
        $connectionRepository->method('getConnectionsWhereSource')->willReturn([]);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $connectionRepository,
            $connectionOptionsRepository,
        );

        $sourceLR = $this->createMock(LanguageResource::class);
        $sourceLR->method('getSourceLang')->willReturn(1);
        $sourceLR->method('getTargetLang')->willReturn(2);
        $sourceLR->method('__call')->willReturnMap([
            ['getId', [], 3],
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $sourceIntegration = $this->createIntegration();
        $targetSyncIntegration = $this->createIntegration();
        $targetNotSyncIntegration = $this->createIntegration(false);

        $serviceManager->method('getAll')->willReturn([
            $expectedTarget->getServiceType(),
            $additionalTarget->getServiceType(),
        ]);
        $serviceManager
            ->method('getSynchronisationService')
            ->willReturnMap(
                [
                    [$sourceLR->getServiceType(), $sourceIntegration],
                    [$expectedTarget->getServiceType(), $targetSyncIntegration],
                    [$additionalTarget->getServiceType(), $targetNotSyncIntegration],
                ]
            );

        $options = $service->getAvailableForConnectionOptions($sourceLR);

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
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $connectionOptionsRepository = $this->createMock(ConnectionOptionsRepository::class);

        $expectedTarget = $this->createMock(LanguageResource::class);
        $expectedTarget->method('getSourceLang')->willReturn(1);
        $expectedTarget->method('getTargetLang')->willReturn(2);
        $expectedTarget->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'targetSyncServiceType'],
        ]);

        $validOptionMock = new PotentialConnectionOption(
            $expectedTarget,
            $this->createMock(Language::class),
            $this->createMock(Language::class),
        );

        $connectedTarget = $this->createMock(LanguageResource::class);
        $connectedTarget->method('getSourceLang')->willReturn(1);
        $connectedTarget->method('getTargetLang')->willReturn(2);
        $connectedTarget->method('__call')->willReturnMap([
            ['getId', [], 2],
            ['getServiceType', [], 'targetConnectedServiceType'],
        ]);

        $connectedOptionMock = new PotentialConnectionOption(
            $connectedTarget,
            $this->createMock(Language::class),
            $this->createMock(Language::class),
        );

        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $connectionRepository->method('getAllTargetLanguageResourceIds')->willReturn([]);

        $connectionOptionsRepository
            ->method('getPotentialConnectionOptions')
            ->willReturn([$validOptionMock, $connectedOptionMock]);

        $connection = $this->createMock(CrossSynchronizationConnection::class);
        $connection->method('__call')->willReturnMap([
            ['getTargetLanguageResourceId', [], $connectedTarget->getId()],
        ]);

        $connectionRepository->method('getConnectionsWhereSource')->willReturn([$connection]);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $connectionRepository,
            $connectionOptionsRepository,
        );

        $sourceLR = $this->createMock(LanguageResource::class);
        $sourceLR->method('getSourceLang')->willReturn(1);
        $sourceLR->method('getTargetLang')->willReturn(2);
        $sourceLR->method('__call')->willReturnMap([
            ['getId', [], 3],
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $sourceIntegration = $this->createIntegration();
        $targetSyncIntegration = $this->createIntegration();

        $serviceManager->method('getAll')->willReturn([
            $expectedTarget->getServiceType(),
            $connectedTarget->getServiceType(),
        ]);
        $serviceManager
            ->method('getSynchronisationService')
            ->willReturnMap(
                [
                    [$sourceLR->getServiceType(), $sourceIntegration],
                    [$expectedTarget->getServiceType(), $targetSyncIntegration],
                    [$connectedTarget->getServiceType(), $targetSyncIntegration],
                ]
            );

        $options = $service->getAvailableForConnectionOptions($sourceLR);

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
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $connectionOptionsRepository = $this->createMock(ConnectionOptionsRepository::class);

        $targetConnectedToOtherSource = $this->createMock(LanguageResource::class);
        $targetConnectedToOtherSource->method('getSourceLang')->willReturn(1);
        $targetConnectedToOtherSource->method('getTargetLang')->willReturn(2);
        $targetConnectedToOtherSource->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'targetConnectedToOtherServiceType'],
        ]);

        $connectedToOtherOptionMock = new PotentialConnectionOption(
            $targetConnectedToOtherSource,
            $this->createMock(Language::class),
            $this->createMock(Language::class),
        );

        $connectedTarget = $this->createMock(LanguageResource::class);
        $connectedTarget->method('getSourceLang')->willReturn(1);
        $connectedTarget->method('getTargetLang')->willReturn(2);
        $connectedTarget->method('__call')->willReturnMap([
            ['getId', [], 2],
            ['getServiceType', [], 'targetConnectedServiceType'],
        ]);

        $connectedOptionMock = new PotentialConnectionOption(
            $connectedTarget,
            $this->createMock(Language::class),
            $this->createMock(Language::class),
        );

        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $connectionOptionsRepository
            ->method('getPotentialConnectionOptions')
            ->willReturn([$connectedToOtherOptionMock, $connectedOptionMock])
        ;

        $connectionRepository
            ->method('getAllTargetLanguageResourceIds')
            ->willReturn([$targetConnectedToOtherSource->getId()])
        ;

        $connection = $this->createMock(CrossSynchronizationConnection::class);
        $connection->method('__call')->willReturnMap([
            ['getTargetLanguageResourceId', [], $connectedTarget->getId()],
        ]);

        $connectionRepository->method('getConnectionsWhereSource')->willReturn([$connection]);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $connectionRepository,
            $connectionOptionsRepository,
        );

        $sourceLR = $this->createMock(LanguageResource::class);
        $sourceLR->method('getSourceLang')->willReturn(1);
        $sourceLR->method('getTargetLang')->willReturn(2);
        $sourceLR->method('__call')->willReturnMap([
            ['getId', [], 3],
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $sourceIntegration = $this->createIntegration();
        $targetSyncIntegration = $this->createIntegration();

        $serviceManager->method('getAll')->willReturn([
            $targetConnectedToOtherSource->getServiceType(),
            $connectedTarget->getServiceType(),
        ]);
        $serviceManager
            ->method('getSynchronisationService')
            ->willReturnMap(
                [
                    [$sourceLR->getServiceType(), $sourceIntegration],
                    [$targetConnectedToOtherSource->getServiceType(), $targetSyncIntegration],
                    [$connectedTarget->getServiceType(), $targetSyncIntegration],
                ]
            );

        $options = $service->getAvailableForConnectionOptions($sourceLR);

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
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $connectionOptionsRepository = $this->createMock(ConnectionOptionsRepository::class);

        $targetConnectedToOtherSource = $this->createMock(LanguageResource::class);
        $targetConnectedToOtherSource->method('getSourceLang')->willReturn(1);
        $targetConnectedToOtherSource->method('getTargetLang')->willReturn(2);
        $targetConnectedToOtherSource->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'targetConnectedToOtherServiceType'],
        ]);

        $connectedToOtherOptionMock = new PotentialConnectionOption(
            $targetConnectedToOtherSource,
            $this->createMock(Language::class),
            $this->createMock(Language::class),
        );

        $connectedTarget = $this->createMock(LanguageResource::class);
        $connectedTarget->method('getSourceLang')->willReturn(1);
        $connectedTarget->method('getTargetLang')->willReturn(2);
        $connectedTarget->method('__call')->willReturnMap([
            ['getId', [], 2],
            ['getServiceType', [], 'targetConnectedServiceType'],
        ]);

        $connectedOptionMock = new PotentialConnectionOption(
            $connectedTarget,
            $this->createMock(Language::class),
            $this->createMock(Language::class),
        );

        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $connectionOptionsRepository
            ->method('getPotentialConnectionOptions')
            ->willReturn([$connectedToOtherOptionMock, $connectedOptionMock])
        ;

        $connectionRepository
            ->method('getAllTargetLanguageResourceIds')
            ->willReturn([$targetConnectedToOtherSource->getId()])
        ;

        $connection = $this->createMock(CrossSynchronizationConnection::class);
        $connection->method('__call')->willReturnMap([
            ['getTargetLanguageResourceId', [], $connectedTarget->getId()],
        ]);

        $connectionRepository->method('getConnectionsWhereSource')->willReturn([$connection]);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $connectionRepository,
            $connectionOptionsRepository
        );

        $sourceLR = $this->createMock(LanguageResource::class);
        $sourceLR->method('getSourceLang')->willReturn(1);
        $sourceLR->method('getTargetLang')->willReturn(2);
        $sourceLR->method('__call')->willReturnMap([
            ['getId', [], 3],
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $sourceIntegration = $this->createIntegration();
        $targetSyncIntegration = $this->createIntegration();
        $targetOneToManySyncIntegration = $this->createIntegration(oneToOne: false);

        $serviceManager->method('getAll')->willReturn([
            $targetConnectedToOtherSource->getServiceType(),
            $connectedTarget->getServiceType(),
        ]);
        $serviceManager
            ->method('getSynchronisationService')
            ->willReturnMap(
                [
                    [$sourceLR->getServiceType(), $sourceIntegration],
                    [$targetConnectedToOtherSource->getServiceType(), $targetOneToManySyncIntegration],
                    [$connectedTarget->getServiceType(), $targetSyncIntegration],
                ]
            );

        $options = $service->getAvailableForConnectionOptions($sourceLR);

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
    ): SynchronisationInterface {
        return $this->createConfiguredMock(SynchronisationInterface::class, [
            'syncSourceOf' => [SynchronisationType::Glossary],
            'syncTargetFor' => $hasSyncTarget ? [SynchronisationType::Glossary] : [],
            'isOneToOne' => $oneToOne,
        ]);
    }
}
