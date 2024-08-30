<?php

namespace MittagQI\Translate5\User\PermissionAudit;

enum Action: string
{
    case CREATE = 'create';
    case READ = 'read';
    case UPDATE = 'update';
    case DELETE = 'delete';

    public function isMutable(): bool
    {
        return in_array($this, [self::UPDATE, self::DELETE], true);
    }
}
