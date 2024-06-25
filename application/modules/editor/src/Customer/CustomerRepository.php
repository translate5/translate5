<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Customer;

use editor_Models_Customer_CustomerConfig;
use ZfExtended_Factory;

class CustomerRepository
{
    public function getConfigValue(int $customerId, string $configName): ?string
    {
        $config = ZfExtended_Factory::get(editor_Models_Customer_CustomerConfig::class);

        return $config->getCurrentValue($customerId, $configName);
    }
}