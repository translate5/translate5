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

namespace LanguageResource\CrossSynchronization;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Manager;
use MittagQI\Translate5\CrossSynchronization\CrossSynchronizationConnection;
use MittagQI\Translate5\CrossSynchronization\SynchronisationDirigent;
use MittagQI\Translate5\CrossSynchronization\SynchronisationInterface;
use MittagQI\Translate5\Repository\CrossSynchronizationConnectionRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use PHPUnit\Framework\TestCase;

class SynchronizationDirigentTest extends TestCase
{
    public function testNoQueueIfNoSynchronizationForPair(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $dirigent = new SynchronisationDirigent($serviceManager, $languageResourceRepository, $connectionRepository);

        $languageResource = $this->createMock(LanguageResource::class);
        $languageResource->method('__call')->willReturnMap([
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $languageResourceRepository->expects($this->once())->method('get')->willReturn($languageResource);

        $serviceManager->method('getSynchronisationService')->willReturn(null);

        $dirigent->queueSynchronizationForPair(1, 2);
    }

    public function testQueueSynchronizationForPair(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $dirigent = new SynchronisationDirigent($serviceManager, $languageResourceRepository, $connectionRepository);

        $languageResource = $this->createMock(LanguageResource::class);
        $languageResource->method('__call')->willReturnMap([
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $languageResourceRepository->method('get')->willReturn($languageResource);

        $syncIntegration = $this->createMock(SynchronisationInterface::class);
        $syncIntegration->expects($this->once())->method('queueDefaultSynchronisation');
        $syncIntegration->expects($this->once())->method('queueCustomerSynchronisation');

        $serviceManager->method('getSynchronisationService')->willReturn($syncIntegration);

        $connection = $this->createMock(CrossSynchronizationConnection::class);

        $connectionRepository->method('getConnectionsForPair')->willReturn([$connection]);

        $dirigent->queueSynchronizationForPair(1, 2);
    }

    public function testQueueSynchronizationWhere(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $dirigent = new SynchronisationDirigent($serviceManager, $languageResourceRepository, $connectionRepository);

        $languageResource = $this->createMock(LanguageResource::class);
        $languageResource->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $languageResourceRepository->method('get')->willReturn($languageResource);

        $connection = $this->createMock(CrossSynchronizationConnection::class);

        $connectionRepository->method('getConnectionsFor')->willReturn([$connection, $connection]);
        $connectionRepository
            ->method('getConnectedPairsWhere')
            ->willReturn([
                [
                    'sourceId' => 1,
                    'targetId' => 2,
                ],
            ]);

        $syncIntegration = $this->createMock(SynchronisationInterface::class);
        $syncIntegration->expects($this->once())->method('queueDefaultSynchronisation');
        $syncIntegration->expects($this->exactly(2))->method('queueCustomerSynchronisation');

        $serviceManager->method('getSynchronisationService')->willReturn($syncIntegration);

        $dirigent->queueSynchronizationWhere($languageResource);
    }

    public function testQueueCleanupDefaultSynchronization(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $dirigent = new SynchronisationDirigent($serviceManager, $languageResourceRepository, $connectionRepository);

        $source = $this->createMock(LanguageResource::class);
        $target = $this->createMock(LanguageResource::class);
        $target->method('__call')->willReturnMap([
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $syncIntegration = $this->createMock(SynchronisationInterface::class);
        $syncIntegration->expects($this->once())->method('cleanupDefaultSynchronisation');

        $serviceManager->method('getSynchronisationService')->willReturn($syncIntegration);

        $dirigent->cleanupDefaultSynchronization($source, $target);
    }

    public function testQueueCleanupOnConnectionDeleted(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $dirigent = new SynchronisationDirigent($serviceManager, $languageResourceRepository, $connectionRepository);

        $connection = $this->createMock(CrossSynchronizationConnection::class);
        $connection->method('__call')->willReturnMap([
            ['getTargetLanguageResourceId', [], 1],
        ]);

        $target = $this->createMock(LanguageResource::class);
        $target->method('__call')->willReturnMap([
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $languageResourceRepository->method('get')->willReturn($target);

        $syncIntegration = $this->createMock(SynchronisationInterface::class);
        $syncIntegration->expects($this->once())->method('cleanupOnConnectionDeleted');

        $serviceManager->method('getSynchronisationService')->willReturn($syncIntegration);

        $dirigent->cleanupOnConnectionDeleted($connection);
    }

    public function testQueueDefaultSynchronization(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $dirigent = new SynchronisationDirigent($serviceManager, $languageResourceRepository, $connectionRepository);

        $source = $this->createMock(LanguageResource::class);
        $target = $this->createMock(LanguageResource::class);
        $target->method('__call')->willReturnMap([
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $syncIntegration = $this->createMock(SynchronisationInterface::class);
        $syncIntegration->expects($this->once())->method('queueDefaultSynchronisation');

        $serviceManager->method('getSynchronisationService')->willReturn($syncIntegration);

        $dirigent->queueDefaultSynchronization($source, $target);
    }

    public function testQueueConnectionSynchronization(): void
    {
        $serviceManager = $this->createMock(editor_Services_Manager::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $connectionRepository = $this->createMock(CrossSynchronizationConnectionRepository::class);

        $dirigent = new SynchronisationDirigent($serviceManager, $languageResourceRepository, $connectionRepository);

        $connection = $this->createMock(CrossSynchronizationConnection::class);
        $connection->method('__call')->willReturnMap([
            ['getTargetLanguageResourceId', [], 1],
        ]);

        $target = $this->createMock(LanguageResource::class);
        $target->method('__call')->willReturnMap([
            ['getServiceType', [], 'sourceServiceType'],
        ]);

        $languageResourceRepository->method('get')->willReturn($target);

        $syncIntegration = $this->createMock(SynchronisationInterface::class);
        $syncIntegration->expects($this->once())->method('queueCustomerSynchronisation');

        $serviceManager->method('getSynchronisationService')->willReturn($syncIntegration);

        $dirigent->queueConnectionSynchronization($connection);
    }
}
