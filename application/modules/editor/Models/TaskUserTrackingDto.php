<?php

declare(strict_types=1);

class editor_Models_TaskUserTrackingDto
{
    public function __construct(
        public readonly string $taskGuid,
        public readonly ?string $userGuid,
        public readonly string $firstName,
        public readonly string $surName,
        public readonly string $userName,
        public readonly string $role,
    ) {
    }

    public static function fromUser(string $taskGuid, ZfExtended_Models_User $user, string $role): self
    {
        return new self(
            taskGuid: $taskGuid,
            userGuid: $user->getUserGuid(),
            firstName: $user->getFirstName(),
            surName: $user->getSurName(),
            userName: $user->getUserName(),
            role: $role,
        );
    }

    public static function fromUsername(string $taskGuid, string $username, string $role = ''): self
    {
        [$firstName, $surName] = explode(' ', preg_replace('/\s{2,}/', ' ', $username) . ' ');

        return new self(
            taskGuid: $taskGuid,
            userGuid: null,
            firstName: $firstName,
            surName: $surName,
            userName: $username,
            role: $role,
        );
    }
}
