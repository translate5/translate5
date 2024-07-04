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

namespace MittagQI\Translate5\LanguageResource\CustomerAssoc;

use editor_Models_LanguageResources_CustomerAssoc as Association;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\DTO\AssociationFormValues;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\Events\EventEmitter;
use MittagQI\Translate5\LanguageResource\CustomerRepository;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class CustomerAssocService
{
    public function __construct(
        private EventEmitter $eventEmitter,
        private CustomerAssocRepository $assocRepository,
        private CustomerRepository $customerRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            EventEmitter::create(),
            new CustomerAssocRepository(),
            new CustomerRepository(),
        );
    }

    public function updateAssociations(AssociationFormValues $formValues): void
    {
        try {
            $customers = $formValues->customers ?: [$this->customerRepository->getDefaultCustomer()->getId()];
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            $customers = [];
        }

        $associatedCustomers = [];

        foreach ($this->assocRepository->getByLanguageResource($formValues->languageResourceId) as $association) {
            if (! in_array((int) $association->getCustomerId(), $customers)) {
                $this->delete($association);

                continue;
            }

            $associatedCustomers[] = (int) $association->getCustomerId();
        }

        foreach ($customers as $customerId) {
            if (! in_array($customerId, $associatedCustomers)) {
                $this->associate(
                    $formValues->languageResourceId,
                    $customerId,
                    in_array($customerId, $formValues->useAsDefaultCustomers),
                    in_array($customerId, $formValues->writeAsDefaultCustomers),
                    in_array($customerId, $formValues->pivotAsDefaultCustomers)
                );
            }
        }
    }

    public function associate(
        int $languageResourceId,
        int $customerId,
        bool $useAsDefault = false,
        bool $writeAsDefault = false,
        bool $pivotAsDefault = false,
    ): Association {
        $model = ZfExtended_Factory::get(Association::class);
        $model->setLanguageResourceId($languageResourceId);
        $model->setCustomerId($customerId);
        $model->setUseAsDefault($useAsDefault);
        $model->setWriteAsDefault($writeAsDefault);
        $model->setPivotAsDefault($pivotAsDefault);
        $model->save();

        $this->eventEmitter->triggerAssociationCreatedEvent($model);

        return $model;
    }

    /**
     * @param int[]|string[] $customerIds
     */
    public function associateCustomers(int $languageResourceId, array $customerIds): void
    {
        foreach ($customerIds as $customerId) {
            $this->associate($languageResourceId, (int) $customerId);
        }
    }

    public function separate(int $languageResourceId, int $customerId): void
    {
        $model = $this->assocRepository->findByLanguageResourceAndCustomer($languageResourceId, $customerId);

        if (null === $model) {
            return;
        }

        $this->delete($model);
    }

    public function delete(Association $assoc): void
    {
        $clone = clone $assoc;

        $assoc->delete();

        $this->eventEmitter->triggerAssociationDeleted($clone);
    }
}