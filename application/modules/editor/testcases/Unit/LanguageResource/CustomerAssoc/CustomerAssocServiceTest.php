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

namespace MittagQI\Translate5\Test\Unit\LanguageResource\CustomerAssoc;

use editor_Models_LanguageResources_CustomerAssoc as Association;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\Customer\CustomerRepository;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\CustomerAssocRepository;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\CustomerAssocService;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\DTO\AssociationFormValues;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\Events\EventEmitter;
use MittagQI\Translate5\LanguageResource\LanguageResourceRepository;
use PHPUnit\Framework\TestCase;

class CustomerAssocServiceTest extends TestCase
{
    public function testUpdateAssociations(): void
    {
        $eventEmitter = $this->createMock(EventEmitter::class);
        $assocRepository = $this->createMock(CustomerAssocRepository::class);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);

        $service = new CustomerAssocService(
            $eventEmitter,
            $assocRepository,
            $customerRepository,
            $languageResourceRepository,
        );

        $assoc = $this->createMock(Association::class);
        $assoc->method('__call')->willReturnMap([
            ['getCustomerId', [], 1],
        ]);

        $assocToDelete = $this->createMock(Association::class);
        $assocToDelete->method('__call')->willReturnMap([
            ['getCustomerId', [], 2],
        ]);

        $assocRepository->method('getByLanguageResource')->willReturn([$assoc, $assocToDelete]);
        $assocRepository->expects($this->once())->method('delete')->with($assocToDelete);

        $eventEmitter
            ->expects($this->once())
            ->method('triggerAssociationDeleted')
            ->with($this->equalTo($assocToDelete));

        $lr = $this->createMock(LanguageResource::class);
        $lr->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceName', [], 'Test Service'],
        ]);

        $languageResourceRepository->method('get')->willReturn($lr);

        $newAssoc = new Association();
        $newAssoc->setLanguageResourceId((int) $lr->getId());
        $newAssoc->setLanguageResourceServiceName($lr->getServiceName());
        $newAssoc->setCustomerId(3);
        $newAssoc->setUseAsDefault(true);
        $newAssoc->setWriteAsDefault(true);
        $newAssoc->setPivotAsDefault(true);

        $assocRepository
            ->expects($this->once())
            ->method('save')
            ->with($newAssoc);

        $eventEmitter
            ->expects($this->once())
            ->method('triggerAssociationCreatedEvent')
            ->with($this->equalTo($newAssoc));

        $formValues = new AssociationFormValues(
            1,
            [
                (int) $assoc->getCustomerId(),
                (int) $newAssoc->getCustomerId(),
            ],
            [
                (int) $newAssoc->getCustomerId(),
            ],
            [
                (int) $newAssoc->getCustomerId(),
            ],
            [
                (int) $newAssoc->getCustomerId(),
            ]
        );

        $service->updateAssociations($formValues);
    }

    public function testAssociate(): void
    {
        $eventEmitter = $this->createMock(EventEmitter::class);
        $assocRepository = $this->createMock(CustomerAssocRepository::class);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);

        $service = new CustomerAssocService(
            $eventEmitter,
            $assocRepository,
            $customerRepository,
            $languageResourceRepository,
        );

        $lr = $this->createMock(LanguageResource::class);
        $lr->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceName', [], 'Test Service'],
        ]);

        $languageResourceRepository->method('get')->willReturn($lr);

        $assoc = new Association();
        $assoc->setLanguageResourceId((int) $lr->getId());
        $assoc->setLanguageResourceServiceName($lr->getServiceName());
        $assoc->setCustomerId(1);
        $assoc->setUseAsDefault(true);
        $assoc->setWriteAsDefault(true);
        $assoc->setPivotAsDefault(true);

        $assocRepository
            ->expects($this->once())
            ->method('save')
            ->with($assoc);

        $eventEmitter
            ->expects($this->once())
            ->method('triggerAssociationCreatedEvent')
            ->with($this->equalTo($assoc));

        $service->associate(
            (int) $assoc->getLanguageResourceId(),
            (int) $assoc->getCustomerId(),
            true,
            true,
            true
        );
    }

    public function testAssociateCustomers(): void
    {
        $eventEmitter = $this->createMock(EventEmitter::class);
        $assocRepository = $this->createMock(CustomerAssocRepository::class);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);

        $service = new CustomerAssocService(
            $eventEmitter,
            $assocRepository,
            $customerRepository,
            $languageResourceRepository,
        );

        $lr = $this->createMock(LanguageResource::class);
        $lr->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getServiceName', [], 'Test Service'],
        ]);

        $languageResourceRepository->method('get')->willReturn($lr);

        $assoc = new Association();
        $assoc->setLanguageResourceId((int) $lr->getId());
        $assoc->setLanguageResourceServiceName($lr->getServiceName());
        $assoc->setCustomerId(1);
        $assoc->setUseAsDefault(false);
        $assoc->setWriteAsDefault(false);
        $assoc->setPivotAsDefault(false);

        $assocRepository
            ->expects($this->once())
            ->method('save')
            ->with($assoc);

        $eventEmitter
            ->expects($this->once())
            ->method('triggerAssociationCreatedEvent')
            ->with($this->equalTo($assoc));

        $service->associateCustomers(
            (int) $assoc->getLanguageResourceId(),
            [(int) $assoc->getCustomerId()],
        );
    }

    public function testDelete(): void
    {
        $eventEmitter = $this->createMock(EventEmitter::class);
        $assocRepository = $this->createMock(CustomerAssocRepository::class);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);

        $service = new CustomerAssocService(
            $eventEmitter,
            $assocRepository,
            $customerRepository,
            $languageResourceRepository,
        );

        $assoc = $this->createMock(Association::class);

        $assocRepository
            ->expects($this->once())
            ->method('delete')
            ->with($assoc);

        $eventEmitter
            ->expects($this->once())
            ->method('triggerAssociationDeleted')
            ->with($this->equalTo($assoc));

        $service->delete($assoc);
    }

    public function testSeparateByCustomer(): void
    {
        $eventEmitter = $this->createMock(EventEmitter::class);
        $assocRepository = $this->createMock(CustomerAssocRepository::class);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);

        $service = new CustomerAssocService(
            $eventEmitter,
            $assocRepository,
            $customerRepository,
            $languageResourceRepository,
        );

        $assoc = $this->createMock(Association::class);

        $assocRepository->method('getByCustomer')->willReturn([$assoc]);
        $assocRepository
            ->expects($this->once())
            ->method('delete')
            ->with($assoc);

        $eventEmitter
            ->expects($this->once())
            ->method('triggerAssociationDeleted')
            ->with($this->equalTo($assoc));

        $service->separateByCustomer(1);
    }

    public function testSeparateByLanguageResource(): void
    {
        $eventEmitter = $this->createMock(EventEmitter::class);
        $assocRepository = $this->createMock(CustomerAssocRepository::class);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);

        $service = new CustomerAssocService(
            $eventEmitter,
            $assocRepository,
            $customerRepository,
            $languageResourceRepository,
        );

        $assoc = $this->createMock(Association::class);

        $assocRepository->method('getByLanguageResource')->willReturn([$assoc]);
        $assocRepository
            ->expects($this->once())
            ->method('delete')
            ->with($assoc);

        $eventEmitter
            ->expects($this->once())
            ->method('triggerAssociationDeleted')
            ->with($this->equalTo($assoc));

        $service->separateByLanguageResource(1);
    }

    public function testSeparate(): void
    {
        $eventEmitter = $this->createMock(EventEmitter::class);
        $assocRepository = $this->createMock(CustomerAssocRepository::class);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $languageResourceRepository = $this->createMock(LanguageResourceRepository::class);

        $service = new CustomerAssocService(
            $eventEmitter,
            $assocRepository,
            $customerRepository,
            $languageResourceRepository,
        );

        $assoc = $this->createMock(Association::class);

        $assocRepository->method('findByLanguageResourceAndCustomer')->willReturn($assoc);
        $assocRepository
            ->expects($this->once())
            ->method('delete')
            ->with($assoc);

        $eventEmitter
            ->expects($this->once())
            ->method('triggerAssociationDeleted')
            ->with($this->equalTo($assoc));

        $service->separate(1, 1);
    }
}
