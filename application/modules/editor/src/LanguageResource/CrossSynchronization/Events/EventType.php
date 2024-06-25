<?php

declare(strict_types=1);

namespace MittagQI\Translate5\LanguageResource\CrossSynchronization\Events;

enum EventType: string
{
    case ConnectionCreated = 'languageResource.synchronization.connection.created';
    case ConnectionDeleted = 'languageResource.synchronization.connection.deleted';
}