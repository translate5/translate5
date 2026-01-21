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
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\DTO\AssociationFormValues;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\Events\AssociationCreatedEvent;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\Events\AssociationDeletedEvent;
use MittagQI\Translate5\Repository\CustomerAssocRepository;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class CustomerAssocService
{
    private array $cachedLanguageResources = [];

    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly CustomerAssocRepository $assocRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly LanguageResourceRepository $languageResourceRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            EventDispatcher::create(),
            new CustomerAssocRepository(),
            new CustomerRepository(),
            new LanguageResourceRepository(),
        );
    }

    public function updateAssociations(AssociationFormValues $formValues): void
    {
        try {
            $customers = $formValues->customers ?: [$this->customerRepository->getDefaultCustomer()->getId()];
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            $customers = [];
        }

        /** @var array<int, Association> $associatedCustomers */
        $associatedCustomers = [];

        foreach ($this->assocRepository->getByLanguageResource($formValues->languageResourceId) as $association) {
            if (! in_array((int) $association->getCustomerId(), $customers)) {
                $this->delete($association);

                continue;
            }

            $associatedCustomers[(int) $association->getCustomerId()] = $association;
        }

        foreach ($customers as $customerId) {
            $useAsDefault = in_array($customerId, $formValues->useAsDefaultCustomers);
            $writeAsDefault = in_array($customerId, $formValues->writeAsDefaultCustomers);
            $pivotAsDefault = in_array($customerId, $formValues->pivotAsDefaultCustomers);
            $useTqeAsDefault = in_array($customerId, $formValues->useTqeAsDefaultCustomers);
            $useTqeInstantTranslateAsDefault = in_array($customerId, $formValues->useTqeInstantTranslateAsDefaultCustomers);

            if (isset($associatedCustomers[$customerId])) {
                $association = $associatedCustomers[$customerId];

                $association->setUseAsDefault($useAsDefault);
                $association->setWriteAsDefault($writeAsDefault);
                $association->setPivotAsDefault($pivotAsDefault);
                $association->setTqeAsDefault($useTqeAsDefault);
                $association->setTqeInstantTranslateAsDefault($useTqeInstantTranslateAsDefault);

                $this->assocRepository->save($association);

                continue;
            }

            $this->associate(
                $formValues->languageResourceId,
                (int) $customerId,
                $useAsDefault,
                $writeAsDefault,
                $pivotAsDefault,
                $useTqeAsDefault,
                $useTqeInstantTranslateAsDefault,
            );
        }
    }

    public function associate(
        int $languageResourceId,
        int $customerId,
        bool $useAsDefault = false,
        bool $writeAsDefault = false,
        bool $pivotAsDefault = false,
        bool $useTqeAsDefault = false,
        bool $useTqeInstantTranslateAsDefault = false,
    ): Association {
        $languageResource = $this->getLanguageResource($languageResourceId);

        $model = ZfExtended_Factory::get(Association::class);
        $model->setLanguageResourceId((int) $languageResource->getId());
        $model->setLanguageResourceServiceName($languageResource->getServiceName());
        $model->setCustomerId($customerId);
        $model->setUseAsDefault($useAsDefault);
        $model->setWriteAsDefault($writeAsDefault);
        $model->setPivotAsDefault($pivotAsDefault);
        $model->setTqeAsDefault($useTqeAsDefault);
        $model->setTqeInstantTranslateAsDefault($useTqeInstantTranslateAsDefault);

        $this->assocRepository->save($model);

        $this->eventDispatcher->dispatch(new AssociationCreatedEvent($model));

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

    public function separateByCustomer(int $customerId): void
    {
        foreach ($this->assocRepository->getByCustomer($customerId) as $assoc) {
            $this->delete($assoc);
        }
    }

    public function separateByLanguageResource(int $languageResourceId): void
    {
        foreach ($this->assocRepository->getByLanguageResource($languageResourceId) as $assoc) {
            $this->delete($assoc);
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

        $this->assocRepository->delete($assoc);

        $this->eventDispatcher->dispatch(new AssociationDeletedEvent($clone));
    }

    private function getLanguageResource(int $languageResourceId): LanguageResource
    {
        if (! isset($this->cachedLanguageResources[$languageResourceId])) {
            $this->cachedLanguageResources[$languageResourceId] = $this->languageResourceRepository->get($languageResourceId);
        }

        return $this->cachedLanguageResources[$languageResourceId];
    }
}
