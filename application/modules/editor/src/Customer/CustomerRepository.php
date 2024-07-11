<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Customer;

use editor_Models_Customer_Customer;
use editor_Models_Customer_CustomerConfig;
use ZfExtended_Factory;

class CustomerRepository
{
    /**
     * @throws \ZfExtended_Models_Entity_NotFoundException
     */
    public function get(int $id): editor_Models_Customer_Customer
    {
        $customer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);
        $customer->load($id);

        return $customer;
    }

    public function find(int $id): ?editor_Models_Customer_Customer
    {
        try {
            return $this->get($id);
        } catch (\ZfExtended_Models_Entity_NotFoundException) {
            return null;
        }
    }

    public function getConfigValue(int $customerId, string $configName): ?string
    {
        $config = ZfExtended_Factory::get(editor_Models_Customer_CustomerConfig::class);

        return $config->getCurrentValue($customerId, $configName);
    }
}