<?php

namespace MittagQI\Translate5\User\PermissionAudit;

enum Action: string implements ActionInterface
{
    case CREATE = 'create';
    case READ = 'read';
    case UPDATE = 'update';
    case DELETE = 'delete';
}
