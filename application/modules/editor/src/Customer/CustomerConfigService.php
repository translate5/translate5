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

namespace MittagQI\Translate5\Customer;

use MittagQI\Translate5\Customer\Events\CustomerConfigDeletedEvent;
use MittagQI\Translate5\Customer\Events\CustomerConfigUpdatedEvent;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\Repository\CustomerRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

class CustomerConfigService
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CustomerRepository $customerRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            EventDispatcher::create(),
            new CustomerRepository(),
        );
    }

    public function getConfigValue(int $customerId, string $name): ?string
    {
        return $this->customerRepository->getConfigValue($customerId, $name);
    }

    public function upsertConfig(int $customerId, string $name, string $value): void
    {
        $oldValue = $this->customerRepository->getConfigValue($customerId, $name);
        $this->customerRepository->upsertConfigValue($customerId, $name, $value);

        $this->eventDispatcher->dispatch(new CustomerConfigUpdatedEvent($customerId, $name, $value, $oldValue));
    }

    public function deleteConfig(int $customerId, string $name): void
    {
        $oldValue = $this->customerRepository->getConfigValue($customerId, $name);
        $this->customerRepository->deleteConfigValue($customerId, $name);

        $this->eventDispatcher->dispatch(new CustomerConfigDeletedEvent($customerId, $name, $oldValue));
    }
}
