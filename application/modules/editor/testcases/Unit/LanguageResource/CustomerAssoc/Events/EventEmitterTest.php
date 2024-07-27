<?php

namespace MittagQI\Translate5\Test\Unit\LanguageResource\CustomerAssoc\Events;

use editor_Models_LanguageResources_CustomerAssoc as Association;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\Events\AssociationCreatedEvent;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\Events\AssociationDeletedEvent;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\Events\EventEmitter;
use PHPUnit\Framework\TestCase;
use ZfExtended_EventManager;

class EventEmitterTest extends TestCase
{
    public function testTriggerAssociationCreatedEvent(): void
    {
        $assoc = $this->createMock(Association::class);
        $eventManager = new ZfExtended_EventManager();

        $eventManager->attach(
            AssociationCreatedEvent::class,
            function (\Zend_EventManager_Event $zendEvent) use ($assoc) {
                $event = $zendEvent->getParam('event');

                $this->assertInstanceOf(AssociationCreatedEvent::class, $event);
                $this->assertSame($assoc, $event->assoc);
            }
        );

        $ee = new EventEmitter($eventManager);

        $ee->triggerAssociationCreatedEvent($assoc);
    }

    public function testTriggerAssociationDeleted(): void
    {
        $assoc = $this->createMock(Association::class);
        $eventManager = new ZfExtended_EventManager();

        $eventManager->attach(
            AssociationDeletedEvent::class,
            function (\Zend_EventManager_Event $zendEvent) use ($assoc) {
                $event = $zendEvent->getParam('event');

                $this->assertInstanceOf(AssociationDeletedEvent::class, $event);
                $this->assertSame($assoc, $event->assoc);
            }
        );

        $ee = new EventEmitter($eventManager);

        $ee->triggerAssociationDeleted($assoc);
    }
}
