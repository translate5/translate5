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

namespace MittagQI\Translate5\Test\Unit\LanguageResource\CrossSynchronization;

use editor_Models_LanguageResources_CustomerAssoc as Association;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Manager;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\CrossLanguageResourceSynchronizationService;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\CrossSynchronizationConnection;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\Events\ConnectionCreatedEvent;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\Events\ConnectionDeletedEvent;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\Events\LanguageResourcesConnectedEvent;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\LanguagePair;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\SynchronisationInterface;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\SynchronisationType;
use MittagQI\Translate5\Repository\CrossSynchronizationConnectionRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use PHPUnit\Framework\TestCase;

class CrossLanguageResourceSynchronizationIntegartionTest extends TestCase
{
    public function testGetConnectedPairsByAssoc(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $lr = $this->createMock(LanguageResource::class);

        $languageResourceRepository
            ->method('get')
            ->willReturnOnConsecutiveCalls($lr, $lr, $lr, $lr);

        $connectionRepository
            ->method('getConnectedPairsByAssoc')
            ->willReturn(
                [
                    [
                        'sourceId' => 1,
                        'targetId' => 2,
                    ],
                    [
                        'sourceId' => 3,
                        'targetId' => 4,
                    ],
                ]
            );

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $languageResourceRepository,
            $connectionRepository
        );

        $assoc = $this->createMock(Association::class);

        foreach ($service->getConnectedPairsByAssoc($assoc) as $pair) {
            self::assertInstanceOf(LanguageResource::class, $pair->source);
            self::assertInstanceOf(LanguageResource::class, $pair->target);
        }
    }

    public function testPairHasConnection(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $connectionRepository
            ->method('hasConnectionsForPair')
            ->willReturnOnConsecutiveCalls(true, false);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $languageResourceRepository,
            $connectionRepository
        );

        self::assertTrue($service->pairHasConnection(1, 2));
        self::assertFalse($service->pairHasConnection(1, 2));
    }

    public function testCreateConnection(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ConnectionCreatedEvent::class));

        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $connection = $this->createMock(CrossSynchronizationConnection::class);
        $source = $this->createMock(LanguageResource::class);
        $target = $this->createMock(LanguageResource::class);

        $connectionRepository->method('createConnection')->willReturn($connection);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $languageResourceRepository,
            $connectionRepository
        );

        self::assertEquals($connection, $service->createConnection($source, $target, 1));
    }

    public function testDeleteConnections(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ConnectionDeletedEvent::class));

        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $connection = $this->createMock(CrossSynchronizationConnection::class);
        $source = $this->createMock(LanguageResource::class);
        $target = $this->createMock(LanguageResource::class);

        $connectionRepository->method('getConnectionsForPair')->willReturn([$connection]);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $languageResourceRepository,
            $connectionRepository
        );

        $service->deleteConnections($source, $target);
    }

    public function testDeleteConnection(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects(self::once())->method('dispatch')->with(self::isInstanceOf(ConnectionDeletedEvent::class));

        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $connection = $this->createMock(CrossSynchronizationConnection::class);

        $connectionRepository->method('createConnection')->willReturn($connection);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $languageResourceRepository,
            $connectionRepository
        );

        $service->deleteConnection($connection);
    }

    public function testGetSyncData(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $languageResourceRepository,
            $connectionRepository
        );
        $source = $this->createMock(LanguageResource::class);
        $source->method('__call')->willReturn('serviceType');

        $pair = new LanguagePair(1, 2, 'en', 'de');

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

        $data = $service->getSyncData($source, $pair, SynchronisationType::Glossary);

        self::assertFalse($data->valid());

        $data = $service->getSyncData($source, $pair, SynchronisationType::Glossary);

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
                            : $event instanceof LanguageResourcesConnectedEvent;
                    }
                )
            );

        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $languageResourceRepository,
            $connectionRepository
        );

        $source = $this->createMock(LanguageResource::class);
        $source->method('getCustomers')->willReturn([1]);

        $target = $this->createMock(LanguageResource::class);
        $target->method('getCustomers')->willReturn([1]);

        $service->connect($source, $target);
    }

    /**
     * No Synchronizable Integrations present - no lang resources returned
     */
    public function testGetAvailableForConnectionLanguageResources1(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $languageResourceRepository,
            $connectionRepository
        );
        $source = $this->createMock(LanguageResource::class);
        $source->method('getSourceLang')->willReturn(1);
        $source->method('getTargetLang')->willReturn(2);
        $source->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $sourceIntegration = $this->createIntegration();

        $serviceManager->method('getAll')->willReturn([]);
        $serviceManager
            ->method('getSynchronisationService')
            ->willReturnMap(
                [
                    [$source->getServiceType(), $sourceIntegration],
                ]
            );

        $resources = $service->getAvailableForConnectionLanguageResources($source);

        self::assertEmpty($resources);
    }

    /**
     * No connections exist. 1 corresponding LR returned
     */
    public function testGetAvailableForConnectionLanguageResourcesReturns2(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);

        $target = $this->createMock(LanguageResource::class);
        $target->method('getSourceLang')->willReturn(1);
        $target->method('getTargetLang')->willReturn(2);
        $target->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'targetServiceType'],
        ]);
        $languageResourceRepository->method('getRelatedByLanguageCombinationsAndCustomers')->willReturn([$target]);

        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $connectionRepository->method('getAllTargetLanguageResourceIds')->willReturn([]);
        $connectionRepository->method('getConnectionsWhereSource')->willReturn([]);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $languageResourceRepository,
            $connectionRepository
        );
        $source = $this->createMock(LanguageResource::class);
        $source->method('getSourceLang')->willReturn(1);
        $source->method('getTargetLang')->willReturn(2);
        $source->method('__call')->willReturnMap([
            ['getId', [], 2],
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $sourceIntegration = $this->createIntegration();
        $targetIntegration = $this->createIntegration();

        $serviceManager->method('getAll')->willReturn([$target->getServiceType()]);
        $serviceManager
            ->method('getSynchronisationService')
            ->willReturnMap(
                [
                    [$source->getServiceType(), $sourceIntegration],
                    [$target->getServiceType(), $targetIntegration],
                ]
            );

        $resources = $service->getAvailableForConnectionLanguageResources($source);

        self::assertNotEmpty($resources);
    }

    /**
     * No connections present.
     * Only 1 LR belongs to integration that can be synchronized so return should contain 1 LR
     */
    public function testGetAvailableForConnectionLanguageResourcesReturns3(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);

        $expectedTarget = $this->createMock(LanguageResource::class);
        $expectedTarget->method('getSourceLang')->willReturn(1);
        $expectedTarget->method('getTargetLang')->willReturn(2);
        $expectedTarget->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'targetSyncServiceType'],
        ]);

        $additionalTarget = $this->createMock(LanguageResource::class);
        $additionalTarget->method('getSourceLang')->willReturn(1);
        $additionalTarget->method('getTargetLang')->willReturn(2);
        $additionalTarget->method('__call')->willReturnMap([
            ['getId', [], 2],
            ['getServiceType', [], 'targetNotSyncServiceType'],
        ]);

        $languageResourceRepository
            ->method('getRelatedByLanguageCombinationsAndCustomers')
            ->willReturn([$expectedTarget, $additionalTarget])
        ;

        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $connectionRepository->method('getAllTargetLanguageResourceIds')->willReturn([]);
        $connectionRepository->method('getConnectionsWhereSource')->willReturn([]);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $languageResourceRepository,
            $connectionRepository
        );

        $source = $this->createMock(LanguageResource::class);
        $source->method('getSourceLang')->willReturn(1);
        $source->method('getTargetLang')->willReturn(2);
        $source->method('__call')->willReturnMap([
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
                    [$source->getServiceType(), $sourceIntegration],
                    [$expectedTarget->getServiceType(), $targetSyncIntegration],
                    [$additionalTarget->getServiceType(), $targetNotSyncIntegration],
                ]
            );

        $resources = $service->getAvailableForConnectionLanguageResources($source);

        self::assertCount(1, $resources);
        self::assertSame($expectedTarget, $resources[0]);
    }

    /**
     * 1 LR already connected.
     * 2 LR belongs to integration that can be synchronized BUT return should contain 1 LR
     */
    public function testGetAvailableForConnectionLanguageResourcesReturns4(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);

        $expectedTarget = $this->createMock(LanguageResource::class);
        $expectedTarget->method('getSourceLang')->willReturn(1);
        $expectedTarget->method('getTargetLang')->willReturn(2);
        $expectedTarget->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'targetSyncServiceType'],
        ]);

        $connectedTarget = $this->createMock(LanguageResource::class);
        $connectedTarget->method('getSourceLang')->willReturn(1);
        $connectedTarget->method('getTargetLang')->willReturn(2);
        $connectedTarget->method('__call')->willReturnMap([
            ['getId', [], 2],
            ['getServiceType', [], 'targetConnectedServiceType'],
        ]);

        $languageResourceRepository
            ->method('getRelatedByLanguageCombinationsAndCustomers')
            ->willReturn([$expectedTarget, $connectedTarget])
        ;

        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $connectionRepository->method('getAllTargetLanguageResourceIds')->willReturn([]);

        $connection = $this->createMock(CrossSynchronizationConnection::class);
        $connection->method('__call')->willReturnMap([
            ['getTargetLanguageResourceId', [], $connectedTarget->getId()],
        ]);

        $connectionRepository->method('getConnectionsWhereSource')->willReturn([$connection]);

        $service = new CrossLanguageResourceSynchronizationService(
            $serviceManager,
            $eventDispatcher,
            $languageResourceRepository,
            $connectionRepository
        );

        $source = $this->createMock(LanguageResource::class);
        $source->method('getSourceLang')->willReturn(1);
        $source->method('getTargetLang')->willReturn(2);
        $source->method('__call')->willReturnMap([
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
                    [$source->getServiceType(), $sourceIntegration],
                    [$expectedTarget->getServiceType(), $targetSyncIntegration],
                    [$connectedTarget->getServiceType(), $targetSyncIntegration],
                ]
            );

        $resources = $service->getAvailableForConnectionLanguageResources($source);

        self::assertCount(1, $resources);
        self::assertSame($expectedTarget, $resources[0]);
    }

    /**
     * 2 LRs connected.
     * 1 to current source LR and 1 to some other
     * LR that is not connected to current source has integration that supports only one-to-one connection
     * return be empty
     */
    public function testGetAvailableForConnectionLanguageResourcesReturns5(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);

        $targetConnectedToOtherSource = $this->createMock(LanguageResource::class);
        $targetConnectedToOtherSource->method('getSourceLang')->willReturn(1);
        $targetConnectedToOtherSource->method('getTargetLang')->willReturn(2);
        $targetConnectedToOtherSource->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'targetConnectedToOtherServiceType'],
        ]);

        $connectedTarget = $this->createMock(LanguageResource::class);
        $connectedTarget->method('getSourceLang')->willReturn(1);
        $connectedTarget->method('getTargetLang')->willReturn(2);
        $connectedTarget->method('__call')->willReturnMap([
            ['getId', [], 2],
            ['getServiceType', [], 'targetConnectedServiceType'],
        ]);

        $languageResourceRepository
            ->method('getRelatedByLanguageCombinationsAndCustomers')
            ->willReturn([$targetConnectedToOtherSource, $connectedTarget])
        ;

        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

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
            $languageResourceRepository,
            $connectionRepository
        );

        $source = $this->createMock(LanguageResource::class);
        $source->method('getSourceLang')->willReturn(1);
        $source->method('getTargetLang')->willReturn(2);
        $source->method('__call')->willReturnMap([
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
                    [$source->getServiceType(), $sourceIntegration],
                    [$targetConnectedToOtherSource->getServiceType(), $targetSyncIntegration],
                    [$connectedTarget->getServiceType(), $targetSyncIntegration],
                ]
            );

        $resources = $service->getAvailableForConnectionLanguageResources($source);

        self::assertEmpty($resources);
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
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);

        $targetConnectedToOtherSource = $this->createMock(LanguageResource::class);
        $targetConnectedToOtherSource->method('getSourceLang')->willReturn(1);
        $targetConnectedToOtherSource->method('getTargetLang')->willReturn(2);
        $targetConnectedToOtherSource->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'targetConnectedToOtherServiceType'],
        ]);

        $connectedTarget = $this->createMock(LanguageResource::class);
        $connectedTarget->method('getSourceLang')->willReturn(1);
        $connectedTarget->method('getTargetLang')->willReturn(2);
        $connectedTarget->method('__call')->willReturnMap([
            ['getId', [], 2],
            ['getServiceType', [], 'targetConnectedServiceType'],
        ]);

        $languageResourceRepository
            ->method('getRelatedByLanguageCombinationsAndCustomers')
            ->willReturn([$targetConnectedToOtherSource, $connectedTarget])
        ;

        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

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
            $languageResourceRepository,
            $connectionRepository
        );

        $source = $this->createMock(LanguageResource::class);
        $source->method('getSourceLang')->willReturn(1);
        $source->method('getTargetLang')->willReturn(2);
        $source->method('__call')->willReturnMap([
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
                    [$source->getServiceType(), $sourceIntegration],
                    [$targetConnectedToOtherSource->getServiceType(), $targetOneToManySyncIntegration],
                    [$connectedTarget->getServiceType(), $targetSyncIntegration],
                ]
            );

        $resources = $service->getAvailableForConnectionLanguageResources($source);

        self::assertCount(1, $resources);
        self::assertSame($targetConnectedToOtherSource, $resources[0]);
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
