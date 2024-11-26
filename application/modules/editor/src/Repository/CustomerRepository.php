<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
             https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\Repository;

use editor_Models_Customer_Customer;
use editor_Models_Customer_CustomerConfig;
use MittagQI\Translate5\Customer\Exception\InexistentCustomerException;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class CustomerRepository
{
    public static function create(): self
    {
        return new self();
    }

    /**
     * @throws InexistentCustomerException
     */
    public function get(int $id): editor_Models_Customer_Customer
    {
        $customer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);

        try {
            $customer->load($id);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new InexistentCustomerException($id);
        }

        return $customer;
    }

    public function find(int $id): ?editor_Models_Customer_Customer
    {
        try {
            return $this->get($id);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return null;
        }
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getByNumber(string $number): editor_Models_Customer_Customer
    {
        $customer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);
        $customer->loadByNumber($number);

        return $customer;
    }

    public function delete(editor_Models_Customer_Customer $customer): void
    {
        $customer->delete();
    }

    public function getConfigValue(int $customerId, string $configName): ?string
    {
        $config = ZfExtended_Factory::get(editor_Models_Customer_CustomerConfig::class);

        return $config->getCurrentValue($customerId, $configName);
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getDefaultCustomer(): editor_Models_Customer_Customer
    {
        return ZfExtended_Factory::get(editor_Models_Customer_Customer::class)->loadByDefaultCustomer();
    }

    /**
     * @return editor_Models_Customer_Customer[]
     * @throws InexistentCustomerException
     */
    public function getList(int ...$customerIds): array
    {
        $customers = [];
        foreach ($customerIds as $customerId) {
            $customers[] = $this->get($customerId);
        }

        return $customers;
    }
}
