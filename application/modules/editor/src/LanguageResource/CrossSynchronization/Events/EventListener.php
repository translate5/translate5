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

namespace MittagQI\Translate5\LanguageResource\CrossSynchronization\Events;

use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\CrossLanguageResourceSynchronizationService;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\SynchronisationDirigent;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\Events as CustomerAssocEvents;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use Zend_EventManager_Event;
use Zend_EventManager_SharedEventManager;
use ZfExtended_Models_Entity_NotFoundException;

class EventListener
{
    public function __construct(
        private readonly Zend_EventManager_SharedEventManager $eventManager,
        private readonly CrossLanguageResourceSynchronizationService $synchronizationService,
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly SynchronisationDirigent $queueSynchronizationService,
    ) {
    }

    public static function create(Zend_EventManager_SharedEventManager $eventManager): self
    {
        return new self(
            $eventManager,
            CrossLanguageResourceSynchronizationService::create(),
            new LanguageResourceRepository(),
            SynchronisationDirigent::create(),
        );
    }

    public function attachAll(): void
    {
        $this->eventManager->attach(
            EventDispatcher::class,
            ConnectionDeletedEvent::class,
            $this->queueCleanupOnConnectionDeleted()
        );
        $this->eventManager->attach(
            EventDispatcher::class,
            ConnectionCreatedEvent::class,
            $this->queueConnectionSynchronization()
        );
        $this->eventManager->attach(
            EventDispatcher::class,
            LanguageResourcesConnectedEvent::class,
            $this->queueDefaultSynchronization()
        );

        $this->eventManager->attach(
            EventDispatcher::class,
            CustomerAssocEvents\AssociationCreatedEvent::class,
            $this->addCustomerToConnections()
        );
        $this->eventManager->attach(
            EventDispatcher::class,
            CustomerAssocEvents\AssociationDeletedEvent::class,
            $this->deleteConnectionWhenCustomerAssocDeleted()
        );
    }

    /**
     * @phpstan-return callable(Zend_EventManager_Event)
     */
    private function queueCleanupOnConnectionDeleted(): callable
    {
        return function (Zend_EventManager_Event $zendEvent) {
            /** @var ConnectionDeletedEvent $event */
            $event = $zendEvent->getParam('event');

            $this->queueSynchronizationService->cleanupOnConnectionDeleted($event->connection);

            $pairHasConnection = $this->synchronizationService->pairHasConnection(
                (int) $event->connection->getSourceLanguageResourceId(),
                (int) $event->connection->getTargetLanguageResourceId(),
            );

            if ($pairHasConnection) {
                return;
            }

            try {
                $source = $this->languageResourceRepository->get((int) $event->connection->getSourceLanguageResourceId());
                $target = $this->languageResourceRepository->get((int) $event->connection->getTargetLanguageResourceId());

                $this->queueSynchronizationService->cleanupDefaultSynchronization($source, $target);
            } catch (ZfExtended_Models_Entity_NotFoundException) {
                // no resource - nothing no cleanup
            }
        };
    }

    private function queueConnectionSynchronization(): callable
    {
        return function (Zend_EventManager_Event $zendEvent) {
            /** @var ConnectionCreatedEvent $event */
            $event = $zendEvent->getParam('event');

            $this->queueSynchronizationService->queueConnectionSynchronization($event->connection);
        };
    }

    private function queueDefaultSynchronization(): callable
    {
        return function (Zend_EventManager_Event $zendEvent) {
            /** @var LanguageResourcesConnectedEvent $event */
            $event = $zendEvent->getParam('event');

            $this->queueSynchronizationService->queueDefaultSynchronization($event->source, $event->target);
        };
    }

    private function addCustomerToConnections(): callable
    {
        return function (Zend_EventManager_Event $zendEvent) {
            /** @var CustomerAssocEvents\AssociationCreatedEvent $event */
            $event = $zendEvent->getParam('event');

            $langResPairs = $this->synchronizationService->getConnectedPairsByAssoc($event->assoc);

            foreach ($langResPairs as $pair) {
                try {
                    $this->synchronizationService->createConnection(
                        $pair->source,
                        $pair->target,
                        (int) $event->assoc->getCustomerId()
                    );
                } catch (\ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey) {
                    // connection already exists
                }
            }
        };
    }

    private function deleteConnectionWhenCustomerAssocDeleted(): callable
    {
        return function (Zend_EventManager_Event $zendEvent) {
            /** @var CustomerAssocEvents\AssociationDeletedEvent $event */
            $event = $zendEvent->getParam('event');

            $this->synchronizationService->deleteRelatedConnections(
                (int) $event->assoc->getLanguageResourceId(),
                (int) $event->assoc->getCustomerId()
            );
        };
    }
}
