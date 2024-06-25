<?php

declare(strict_types=1);

namespace MittagQI\Translate5\LanguageResource\CrossSynchronization\Events;

use MittagQI\Translate5\LanguageResource\CrossSynchronization\CrossSynchronizationConnection;
use ZfExtended_EventManager;
use ZfExtended_Factory;

class EventEmitter
{
    public function __construct(
        private ZfExtended_EventManager $eventManager,
    ) {
    }

    public static function create(): self
    {
        return new self(ZfExtended_Factory::get(ZfExtended_EventManager::class, [self::class]));
    }

    public function triggerConnectionCreatedEvent(CrossSynchronizationConnection $connection): void
    {
        $this->eventManager->trigger(EventType::ConnectionCreated->value, argv: [
            'connection' => $connection,
        ]);
    }

    public function triggerConnectionDeleted(CrossSynchronizationConnection $deletedConnection): void
    {
        $this->eventManager->trigger(EventType::ConnectionDeleted->value, argv: [
            'deletedConnection' => $deletedConnection,
        ]);
    }
}