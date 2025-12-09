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

namespace MittagQI\Translate5\CrossSynchronization;

use editor_Models_LanguageResources_CustomerAssoc as CustomerAssoc;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Languages as Language;
use editor_Services_Manager;
use Generator;
use MittagQI\Translate5\CrossSynchronization\Dto\AvailableForConnectionOption;
use MittagQI\Translate5\CrossSynchronization\Dto\PotentialConnectionOption;
use MittagQI\Translate5\CrossSynchronization\Events\ConnectionCreatedEvent;
use MittagQI\Translate5\CrossSynchronization\Events\ConnectionDeletedEvent;
use MittagQI\Translate5\CrossSynchronization\Events\CustomerAddedEvent;
use MittagQI\Translate5\CrossSynchronization\Events\CustomerRemovedEvent;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\Repository\CrossSynchronizationConnectionRepository;
use MittagQI\Translate5\Repository\LanguageRepository;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;

class CrossLanguageResourceSynchronizationService
{
    public function __construct(
        private readonly editor_Services_Manager $serviceManager,
        private readonly EventDispatcher $eventDispatcher,
        private readonly CrossSynchronizationConnectionRepository $connectionRepository,
        private readonly ConnectionOptionsRepository $connectionOptionsRepository,
        private readonly LanguageRepository $languageRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            new editor_Services_Manager(),
            EventDispatcher::create(),
            new CrossSynchronizationConnectionRepository(),
            ConnectionOptionsRepository::create(),
            LanguageRepository::create(),
        );
    }

    public function findConnection(int $id): ?CrossSynchronizationConnection
    {
        return $this->connectionRepository->findConnection($id);
    }

    /**
     * @return iterable<CrossSynchronizationConnection>
     */
    public function getConnectionsByLrCustomerAssoc(CustomerAssoc $assoc): iterable
    {
        return $this->connectionRepository->getConnectionsByLrCustomerAssoc($assoc);
    }

    /**
     * @return iterable<CustomerAssoc>
     */
    public function getLrCustomerAssocsBy(CrossSynchronizationConnection $connection, LanguageResource $lr): iterable
    {
        return $this->connectionRepository->getLrCustomerAssocsBy($connection, $lr);
    }

    public function createConnection(
        LanguageResource $source,
        LanguageResource $target,
        Language $sourceLang,
        Language $targetLang,
    ): CrossSynchronizationConnection {
        $connection = $this->connectionRepository->createConnection(
            $source,
            $target,
            $sourceLang,
            $targetLang,
        );

        $this->eventDispatcher->dispatch(new ConnectionCreatedEvent($connection));

        return $connection;
    }

    public function addCustomer(CrossSynchronizationConnection $connection, int $customerId): void
    {
        $assoc = $this->connectionRepository->createCustomerAssoc((int) $connection->getId(), $customerId);

        $this->eventDispatcher->dispatch(new CustomerAddedEvent($assoc));
    }

    public function deleteRelatedConnections(int $languageResourceId): void
    {
        foreach ($this->connectionRepository->getConnectionsFor($languageResourceId) as $connection) {
            $this->deleteConnection($connection);
        }
    }

    public function connectionHasAssociatedCustomers(CrossSynchronizationConnection $connection): bool
    {
        return $this->connectionRepository->connectionHasAssociatedCustomers($connection);
    }

    public function removeCustomerFromConnections(int $customerId, ?int $languageResourceId = null): void
    {
        $associations = $this->connectionRepository->getCustomerAssocsByCustomerAndLanguageResource(
            $customerId,
            $languageResourceId
        );

        foreach ($associations as $association) {
            $this->removeCustomer($association);
        }
    }

    public function removeCustomer(CrossSynchronizationConnectionCustomer $association): void
    {
        $clone = clone $association;

        $this->connectionRepository->deleteCustomerAssoc($association);

        $this->eventDispatcher->dispatch(new CustomerRemovedEvent($clone));
    }

    public function deleteConnection(CrossSynchronizationConnection $connection): void
    {
        $clone = clone $connection;

        foreach ($this->connectionRepository->getCustomerAssociations($connection) as $association) {
            $this->removeCustomer($association);
        }

        $this->connectionRepository->deleteConnection($connection);

        $this->eventDispatcher->dispatch(new ConnectionDeletedEvent($clone));
    }

    /**
     * @return Generator<array{source: string, target: string}|null>
     */
    public function getSyncData(
        LanguageResource $source,
        LanguagePair $languagePair,
        SynchronisationType $synchronizationType,
        ?int $customerId = null,
    ): Generator {
        $integration = $this->getSyncIntegration($source->getServiceType());

        if (null === $integration) {
            return yield from [];
        }

        return yield from $integration->getSyncData($source, $languagePair, $synchronizationType, $customerId);
    }

    /**
     * @return AvailableForConnectionOption[]
     */
    public function getAvailableForConnectionOptions(LanguageResource $source): iterable
    {
        $sourceIntegration = $this->getSyncIntegration($source->getServiceType());

        if (null === $sourceIntegration || empty($source->getSourceLang()) || empty($source->getTargetLang())) {
            return [];
        }

        if (! $this->hasTargetSyncIntegration($sourceIntegration)) {
            return [];
        }

        $allExistingTargets = $this->connectionRepository->getAllTargetLanguageResourceIds();
        $connections = $this->connectionRepository->getConnectionsWhereSource((int) $source->getId());

        $alreadyConnectedResources = [];

        foreach ($connections as $connection) {
            $alreadyConnectedResources[(int) $connection->getTargetLanguageResourceId()] = true;
        }

        /** @var array<string, SynchronisationInterface> $syncIntegrations */
        $syncIntegrations = [];

        /** @var array<string, LanguagePair[]> $supportedLanguagePairs */
        $supportedLanguagePairs = [];

        $rfcToIdMap = $this->languageRepository->getRfc5646ToIdMap();

        foreach ($this->connectionOptionsRepository->getPotentialConnectionOptions($source) as $option) {
            $lr = $option->languageResource;

            if (! isset($syncIntegrations[$lr->getServiceType()])) {
                $syncIntegrations[$lr->getServiceType()] = $this->getSyncIntegration($lr->getServiceType());
            }

            $targetIntegration = $syncIntegrations[$lr->getServiceType()];

            if (null === $targetIntegration) {
                continue;
            }

            if (! $this->syncIntegrationIsSourceForTarget($sourceIntegration, $targetIntegration)) {
                continue;
            }

            if (isset($alreadyConnectedResources[(int) $lr->getId()])) {
                continue;
            }

            if (in_array((int) $lr->getId(), $allExistingTargets) && $targetIntegration->isOneToOne()) {
                // we can't connect to Language Resource with ono-to-one type if it is already have connection
                continue;
            }

            if (! isset($supportedLanguagePairs[$lr->getServiceType()])) {
                $supportedLanguagePairs[$lr->getServiceType()] = $targetIntegration->getSupportedLanguagePairs($lr);
            }

            $supportedPairs = $supportedLanguagePairs[$lr->getServiceType()];

            if (! $this->optionIsInSupportedLanguagePairs($option, $supportedPairs, $rfcToIdMap)) {
                continue;
            }

            yield AvailableForConnectionOption::fromPotentialOption($option);
        }
    }

    public function connect(
        LanguageResource $sourceLr,
        LanguageResource $targetLr,
        Language $sourceLang,
        Language $targetLang,
    ): void {
        $connection = $this->createConnection($sourceLr, $targetLr, $sourceLang, $targetLang);

        foreach ($sourceLr->getCustomers() as $customer) {
            if (! in_array($customer, $targetLr->getCustomers())) {
                continue;
            }

            try {
                $this->addCustomer($connection, (int) $customer);
            } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey) {
                // connection already exists
            }
        }
    }

    private function getSyncIntegration(string $serviceType): ?SynchronisationInterface
    {
        return $this->serviceManager->getSynchronisationService($serviceType);
    }

    private function hasTargetSyncIntegration(SynchronisationInterface $sourceIntegration): bool
    {
        foreach ($this->serviceManager->getAll() as $serviceType) {
            $targetIntegration = $this->getSyncIntegration($serviceType);

            if (null === $targetIntegration) {
                continue;
            }

            if ($this->syncIntegrationIsSourceForTarget($sourceIntegration, $targetIntegration)) {
                return true;
            }
        }

        return false;
    }

    private function syncIntegrationIsSourceForTarget(
        SynchronisationInterface $sourceIntegration,
        SynchronisationInterface $targetIntegration,
    ): bool {
        foreach ($sourceIntegration->syncSourceOf() as $syncType) {
            if (in_array($syncType, $targetIntegration->syncTargetFor())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param LanguagePair[] $supportedPairs
     * @param array<string, string> $rfcToIdMap
     */
    private function optionIsInSupportedLanguagePairs(
        PotentialConnectionOption $option,
        array $supportedPairs,
        array $rfcToIdMap,
    ): bool {
        foreach ($supportedPairs as $pair) {
            if (
                $pair->sourceId === (int) $option->sourceLanguage->getId()
                && $pair->targetId === (int) $option->targetLanguage->getId()
            ) {
                return true;
            }

            $majorSourceId = (int) $option->sourceLanguage->getId();

            if ($option->sourceLanguage->getMajorRfc5646() !== $option->sourceLanguage->getRfc5646()) {
                $majorSourceId = (int) ($rfcToIdMap[$option->sourceLanguage->getMajorRfc5646()] ?? 0);
            }

            $majorTargetId = (int) $option->targetLanguage->getId();

            if ($option->targetLanguage->getMajorRfc5646() !== $option->targetLanguage->getRfc5646()) {
                $majorTargetId = (int) ($rfcToIdMap[$option->targetLanguage->getMajorRfc5646()] ?? 0);
            }

            if ($pair->sourceId === $majorSourceId && $pair->targetId === $majorTargetId) {
                return true;
            }
        }

        return false;
    }
}
