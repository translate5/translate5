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

namespace MittagQI\Translate5\Test\Unit\CrossSynchronization\Events;

use editor_Models_LanguageResources_CustomerAssoc as Association;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\CrossSynchronization\CrossLanguageResourceSynchronizationService;
use MittagQI\Translate5\CrossSynchronization\CrossSynchronizationConnection;
use MittagQI\Translate5\CrossSynchronization\CrossSynchronizationConnectionCustomer;
use MittagQI\Translate5\CrossSynchronization\Events\ConnectionCreatedEvent;
use MittagQI\Translate5\CrossSynchronization\Events\ConnectionDeletedEvent;
use MittagQI\Translate5\CrossSynchronization\Events\CustomerAddedEvent;
use MittagQI\Translate5\CrossSynchronization\Events\CustomerRemovedEvent;
use MittagQI\Translate5\CrossSynchronization\Events\EventListener;
use MittagQI\Translate5\CrossSynchronization\SynchronisationDirigent;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\Events as CustomerAssocEvents;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use PHPUnit\Framework\TestCase;
use Zend_EventManager_Event;
use Zend_EventManager_SharedEventManager;

class EventListenerTest extends TestCase
{
    public function testAttachAll(): void
    {
        $em = new Zend_EventManager_SharedEventManager();
        $synchronizationService = $this->createMock(CrossLanguageResourceSynchronizationService::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $synchronizationDirigent = $this->createMock(SynchronisationDirigent::class);

        $el = new EventListener($em, $synchronizationService, $languageResourceRepository, $synchronizationDirigent);
        $el->attachAll();

        /** @phpstan-ignore-next-line  */
        self::assertFalse($em->getListeners(EventDispatcher::class, ConnectionDeletedEvent::class)->isEmpty());
        /** @phpstan-ignore-next-line  */
        self::assertFalse($em->getListeners(EventDispatcher::class, ConnectionCreatedEvent::class)->isEmpty());
        /** @phpstan-ignore-next-line  */
        self::assertFalse($em->getListeners(EventDispatcher::class, CustomerAddedEvent::class)->isEmpty());
        /** @phpstan-ignore-next-line  */
        self::assertFalse($em->getListeners(EventDispatcher::class, CustomerRemovedEvent::class)->isEmpty());
        /** @phpstan-ignore-next-line  */
        self::assertFalse($em->getListeners(EventDispatcher::class, CustomerAssocEvents\AssociationCreatedEvent::class)->isEmpty());
        /** @phpstan-ignore-next-line  */
        self::assertFalse($em->getListeners(EventDispatcher::class, CustomerAssocEvents\AssociationDeletedEvent::class)->isEmpty());
    }

    public function testConnectionDeletedEventHandler(): void
    {
        $em = new Zend_EventManager_SharedEventManager();

        $synchronizationService = $this->createMock(CrossLanguageResourceSynchronizationService::class);

        $synchronizationDirigent = $this->createMock(SynchronisationDirigent::class);
        $synchronizationDirigent->expects($this->once())->method('cleanupDefaultSynchronization');

        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);

        $el = new EventListener($em, $synchronizationService, $languageResourceRepository, $synchronizationDirigent);
        $el->attachAll();

        /** @phpstan-ignore-next-line  */
        $closure = $em->getListeners(EventDispatcher::class, ConnectionDeletedEvent::class)->top();

        $connection = $this->createMock(CrossSynchronizationConnection::class);

        $closure(new Zend_EventManager_Event(params: [
            'event' => new ConnectionDeletedEvent($connection),
        ]));
    }

    public function testConnectionCreatedEventHandler(): void
    {
        $em = new Zend_EventManager_SharedEventManager();

        $synchronizationService = $this->createMock(CrossLanguageResourceSynchronizationService::class);

        $synchronizationDirigent = $this->createMock(SynchronisationDirigent::class);
        $synchronizationDirigent->expects($this->once())->method('queueDefaultSynchronization');

        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);

        $el = new EventListener($em, $synchronizationService, $languageResourceRepository, $synchronizationDirigent);
        $el->attachAll();

        /** @phpstan-ignore-next-line  */
        $closure = $em->getListeners(EventDispatcher::class, ConnectionCreatedEvent::class)->top();

        $connection = $this->createMock(CrossSynchronizationConnection::class);

        $closure(new Zend_EventManager_Event(params: [
            'event' => new ConnectionCreatedEvent($connection),
        ]));
    }

    public function testCustomerAddedEventHandler(): void
    {
        $em = new Zend_EventManager_SharedEventManager();

        $synchronizationService = $this->createMock(CrossLanguageResourceSynchronizationService::class);
        $synchronizationService->method('findConnection')->willReturn($this->createMock(CrossSynchronizationConnection::class));

        $synchronizationDirigent = $this->createMock(SynchronisationDirigent::class);
        $synchronizationDirigent->expects($this->once())->method('queueCustomerSynchronization');

        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);

        $el = new EventListener($em, $synchronizationService, $languageResourceRepository, $synchronizationDirigent);
        $el->attachAll();

        /** @phpstan-ignore-next-line  */
        $closure = $em->getListeners(EventDispatcher::class, CustomerAddedEvent::class)->top();

        $assoc = $this->createMock(CrossSynchronizationConnectionCustomer::class);

        $closure(new Zend_EventManager_Event(params: [
            'event' => new CustomerAddedEvent($assoc),
        ]));
    }

    public function testCustomerRemovedEventHandler(): void
    {
        $em = new Zend_EventManager_SharedEventManager();

        $synchronizationService = $this->createMock(CrossLanguageResourceSynchronizationService::class);
        $synchronizationService->method('findConnection')->willReturn(
            $this->createMock(CrossSynchronizationConnection::class)
        );
        $synchronizationService->method('connectionHasAssociatedCustomers')->willReturn(false);

        $synchronizationService->expects($this->once())->method('deleteConnection');

        $synchronizationDirigent = $this->createMock(SynchronisationDirigent::class);
        $synchronizationDirigent->expects($this->once())->method('cleanupOnCustomerRemovedFromConnection');

        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $languageResourceRepository->method('get')->willReturn(
            $this->createMock(LanguageResource::class)
        );

        $el = new EventListener($em, $synchronizationService, $languageResourceRepository, $synchronizationDirigent);
        $el->attachAll();

        /** @phpstan-ignore-next-line  */
        $closure = $em->getListeners(EventDispatcher::class, CustomerRemovedEvent::class)->top();

        $assoc = $this->createMock(CrossSynchronizationConnectionCustomer::class);

        $closure(new Zend_EventManager_Event(params: [
            'event' => new CustomerRemovedEvent($assoc),
        ]));
    }

    public function testAssociationCreatedEventHandler(): void
    {
        $em = new Zend_EventManager_SharedEventManager();

        $synchronizationService = $this->createMock(CrossLanguageResourceSynchronizationService::class);

        $synchronizationService->method('getConnectionsByLrCustomerAssoc')
            ->willReturn([
                $this->createMock(CrossSynchronizationConnection::class),
                $this->createMock(CrossSynchronizationConnection::class),
            ]);

        $synchronizationService->expects($this->exactly(2))->method('addCustomer');

        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $synchronizationDirigent = $this->createMock(SynchronisationDirigent::class);

        $el = new EventListener($em, $synchronizationService, $languageResourceRepository, $synchronizationDirigent);
        $el->attachAll();

        /** @phpstan-ignore-next-line  */
        $closure = $em->getListeners(EventDispatcher::class, CustomerAssocEvents\AssociationCreatedEvent::class)->top();

        $association = $this->createMock(Association::class);

        $closure(new Zend_EventManager_Event(params: [
            'event' => new CustomerAssocEvents\AssociationCreatedEvent($association),
        ]));
    }

    public function testAssociationDeletedEventHandler(): void
    {
        $em = new Zend_EventManager_SharedEventManager();

        $synchronizationService = $this->createMock(CrossLanguageResourceSynchronizationService::class);

        $synchronizationService->expects($this->once())->method('removeCustomerFromConnections');

        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $synchronizationDirigent = $this->createMock(SynchronisationDirigent::class);

        $el = new EventListener($em, $synchronizationService, $languageResourceRepository, $synchronizationDirigent);
        $el->attachAll();

        /** @phpstan-ignore-next-line  */
        $closure = $em->getListeners(EventDispatcher::class, CustomerAssocEvents\AssociationDeletedEvent::class)->top();

        $association = $this->createMock(Association::class);

        $closure(new Zend_EventManager_Event(params: [
            'event' => new CustomerAssocEvents\AssociationDeletedEvent($association),
        ]));
    }
}
