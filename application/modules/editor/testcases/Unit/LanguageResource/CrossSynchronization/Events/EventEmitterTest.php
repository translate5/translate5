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

namespace MittagQI\Translate5\Test\Unit\LanguageResource\CrossSynchronization\Events;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\CrossSynchronizationConnection;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\Events\ConnectionCreatedEvent;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\Events\ConnectionDeletedEvent;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\Events\EventEmitter;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\Events\LanguageResourcesConnectedEvent;
use PHPUnit\Framework\TestCase;
use ZfExtended_EventManager;

class EventEmitterTest extends TestCase
{
    public function testTriggerConnectionCreatedEvent(): void
    {
        $connection = $this->createMock(CrossSynchronizationConnection::class);
        $eventManager = new ZfExtended_EventManager();

        $eventManager->attach(
            ConnectionCreatedEvent::class,
            function (\Zend_EventManager_Event $zendEvent) use ($connection) {
                $event = $zendEvent->getParam('event');

                $this->assertInstanceOf(ConnectionCreatedEvent::class, $event);
                $this->assertSame($connection, $event->connection);
            }
        );

        $ee = new EventEmitter($eventManager);

        $ee->triggerConnectionCreatedEvent($connection);
    }

    public function testTriggerConnectionDeleted(): void
    {
        $connection = $this->createMock(CrossSynchronizationConnection::class);
        $eventManager = new ZfExtended_EventManager();

        $eventManager->attach(
            ConnectionDeletedEvent::class,
            function (\Zend_EventManager_Event $zendEvent) use ($connection) {
                $event = $zendEvent->getParam('event');

                $this->assertInstanceOf(ConnectionDeletedEvent::class, $event);
                $this->assertSame($connection, $event->connection);
            }
        );

        $ee = new EventEmitter($eventManager);

        $ee->triggerConnectionDeleted($connection);
    }

    public function testTriggerLanguageResourcesConnected(): void
    {
        $source = $this->createMock(LanguageResource::class);
        $target = $this->createMock(LanguageResource::class);
        $eventManager = new ZfExtended_EventManager();

        $eventManager->attach(
            LanguageResourcesConnectedEvent::class,
            function (\Zend_EventManager_Event $zendEvent) use ($source, $target) {
                $event = $zendEvent->getParam('event');

                $this->assertInstanceOf(LanguageResourcesConnectedEvent::class, $event);
                $this->assertSame($source, $event->source);
                $this->assertSame($target, $event->target);
            }
        );

        $ee = new EventEmitter($eventManager);

        $ee->triggerLanguageResourcesConnected($source, $target);
    }
}
