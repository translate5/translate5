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

namespace MittagQI\Translate5\Test\Unit\Customer\Events;

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\Customer\Events\CustomerDeletedEvent;
use MittagQI\Translate5\Customer\Events\EventEmitter;
use PHPUnit\Framework\TestCase;
use Zend_EventManager_Event;
use ZfExtended_EventManager;

class EventEmitterTest extends TestCase
{
    public function testTriggerCustomerDeletedEvent(): void
    {
        $eventManager = new ZfExtended_EventManager();

        $customer = $this->createMock(Customer::class);

        $eventManager->attach(
            CustomerDeletedEvent::class,
            function (Zend_EventManager_Event $zendEvent) use ($customer) {
                $event = $zendEvent->getParam('event');

                $this->assertInstanceOf(CustomerDeletedEvent::class, $event);
                $this->assertSame($customer, $event->customer);
            }
        );

        $ee = new EventEmitter($eventManager);

        $ee->triggerCustomerDeletedEvent($customer);
    }
}
