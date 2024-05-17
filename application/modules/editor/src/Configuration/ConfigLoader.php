<?php

namespace MittagQI\Translate5\Configuration;

use editor_Models_Config;

/**
 * Config loader with restricted access to the config values. By default, the access is not restricted.
 * Can be used to load user, task, customer and instance level config values.
 */
class ConfigLoader
{
    public function __construct(
        private editor_Models_Config $configModel,
        private bool $accessRestricted = false,
    ) {
    }

    public function loadUserConfig(string $userGuid, string $taskGuid = null): array
    {
        $taskConfig = [];
        if (! empty($taskGuid)) {
            $taskConfig = $this->configModel->mergeTaskValues(
                $taskGuid,
                [],
                false,
                $this->accessRestricted
            );
        }

        return array_values(
            $this->configModel->mergeUserValues($userGuid, $taskConfig, $this->accessRestricted)
        );
    }

    public function loadTaskConfig(string $taskGuid): array
    {
        return array_values(
            $this->configModel->mergeTaskValues($taskGuid, accessRestricted: $this->accessRestricted)
        );
    }

    public function loadCustomerConfig(int $customerId): array
    {
        return array_values(
            $this->configModel->mergeCustomerValues($customerId, accessRestricted: $this->accessRestricted)
        );
    }

    public function loadInstanceConfig(): array
    {
        return array_values(
            $this->configModel->mergeInstanceValue([], $this->accessRestricted)
        );
    }
}
