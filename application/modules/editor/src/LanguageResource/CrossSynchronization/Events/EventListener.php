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

use editor_Models_LanguageResources_CustomerAssoc;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\CrossSynchronizationConnectionRepository;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\Events as CustomerAssocEvents;
use Zend_EventManager_Event;
use Zend_EventManager_SharedEventManager;

class EventListener
{
    public function __construct(
        private Zend_EventManager_SharedEventManager $eventManager,
        private CrossSynchronizationConnectionRepository $synchronizationRepository,
        private EventEmitter $synchronizationEventEmitter,
    ) {
    }

    public static function create(Zend_EventManager_SharedEventManager $eventManager): self
    {
        return new self(
            $eventManager,
            new CrossSynchronizationConnectionRepository(),
            EventEmitter::create(),
        );
    }

    public function attachAll(): void
    {
        $this->eventManager->attach(
            CustomerAssocEvents\EventEmitter::class,
            CustomerAssocEvents\EventType::AssociationCreated->value,
            [$this, 'triggerCustomerAddedToConnectionEvents']
        );
        $this->eventManager->attach(
            CustomerAssocEvents\EventEmitter::class,
            CustomerAssocEvents\EventType::AssociationDeleted->value,
            [$this, 'triggerCustomerWasSeparatedFromConnectionEvents']
        );
    }

    public function triggerCustomerAddedToConnectionEvents(Zend_EventManager_Event $event): void
    {
        /** @var editor_Models_LanguageResources_CustomerAssoc $association */
        $association = $event->getParam('association');

        $connections = $this->synchronizationRepository->getAllConnections((int) $association->getLanguageResourceId());

        foreach ($connections as $connection) {
            $this->synchronizationEventEmitter->triggerNewCustomerAssociatedWithConnectionEvent(
                $connection,
                $association
            );
        }
    }

    public function triggerCustomerWasSeparatedFromConnectionEvents(Zend_EventManager_Event $event): void
    {
        /** @var editor_Models_LanguageResources_CustomerAssoc $association */
        $association = $event->getParam('deletedAssociation');

        $connections = $this->synchronizationRepository->getAllConnections((int) $association->getLanguageResourceId());

        foreach ($connections as $connection) {
            $this->synchronizationEventEmitter->triggerCustomerWasSeparatedFromConnectionEvent(
                $connection,
                $association
            );
        }
    }
}