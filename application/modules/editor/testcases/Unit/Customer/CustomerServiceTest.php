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

namespace MittagQI\Translate5\Test\Unit\Customer;

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\Customer\CustomerRepository;
use MittagQI\Translate5\Customer\CustomerService;
use MittagQI\Translate5\Customer\Events\EventEmitter;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\CrossLanguageResourceSynchronizationService;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\CustomerAssocService;
use PHPUnit\Framework\TestCase;

class CustomerServiceTest extends TestCase
{
    public function testDelete(): void
    {
        $eventEmitter = $this->createMock(EventEmitter::class);
        $customerAssocService = $this->createMock(CustomerAssocService::class);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $synchronizationService = $this->createMock(CrossLanguageResourceSynchronizationService::class);

        $service = new CustomerService(
            $eventEmitter,
            $customerAssocService,
            $customerRepository,
            $synchronizationService,
        );

        $customer = $this->createMock(Customer::class);
        $customer->method('__call')->willReturnMap([
            ['getId', [], 1],
        ]);

        $synchronizationService
            ->expects($this->once())
            ->method('deleteRelatedConnections')
            ->with(null, $this->identicalTo($customer->getId()));

        $customerAssocService
            ->expects($this->once())
            ->method('separateByCustomer')
            ->with($this->identicalTo($customer->getId()));

        $eventEmitter
            ->expects($this->once())
            ->method('triggerCustomerDeletedEvent')
            ->with($this->equalTo($customer));

        $service->delete($customer);
    }
}
