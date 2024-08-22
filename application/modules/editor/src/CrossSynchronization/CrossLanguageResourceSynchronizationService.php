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

namespace MittagQI\Translate5\LanguageResource\CrossSynchronization;

use editor_Models_LanguageResources_CustomerAssoc as CustomerAssoc;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Languages as Language;
use editor_Services_Manager;
use Generator;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\Dto\AvailableForConnectionOption;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\Dto\LanguageResourcePair;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\Events\ConnectionCreatedEvent;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\Events\ConnectionDeletedEvent;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\Events\LanguageResourcesConnectedEvent;
use MittagQI\Translate5\Repository\CrossSynchronizationConnectionRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;

class CrossLanguageResourceSynchronizationService
{
    /**
     * @var array<int, LanguageResource>
     */
    private array $cachedLanguageResources = [];

    /**
     * @var array<string, SynchronisationInterface>
     */
    private array $cachedSyncIntegration = [];

    public function __construct(
        private readonly editor_Services_Manager $serviceManager,
        private readonly EventDispatcher $eventDispatcher,
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly CrossSynchronizationConnectionRepository $connectionRepository,
        private readonly ConnectionOptionsRepository $connectionOptionsRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new editor_Services_Manager(),
            EventDispatcher::create(),
            new LanguageResourceRepository(),
            new CrossSynchronizationConnectionRepository(),
            ConnectionOptionsRepository::create(),
        );
    }

    /**
     * @return iterable<LanguageResourcePair>
     */
    public function getConnectedPairsByAssoc(CustomerAssoc $assoc): iterable
    {
        foreach ($this->connectionRepository->getConnectedPairsByAssoc($assoc) as $pair) {
            yield new LanguageResourcePair(
                $this->getCachedLanguageResource($pair['sourceId']),
                $this->getCachedLanguageResource($pair['targetId']),
            );
        }
    }

    public function pairHasConnection(int $sourceId, int $targetId): bool
    {
        return $this->connectionRepository->hasConnectionsForPair($sourceId, $targetId);
    }

    public function createConnection(
        LanguageResource $source,
        LanguageResource $target,
        Language $sourceLang,
        Language $targetLang,
        int $customerId,
    ): CrossSynchronizationConnection {
        $connection = $this->connectionRepository->createConnection(
            $source,
            $target,
            $sourceLang,
            $targetLang,
            $customerId
        );

        $this->eventDispatcher->dispatch(new ConnectionCreatedEvent($connection));

        return $connection;
    }

    public function getConnectionForLrPair(
        LanguageResource $source,
        LanguageResource $target
    ): ?CrossSynchronizationConnection {
        $connections = $this->connectionRepository->getConnectionsForPair((int) $source->getId(), (int) $target->getId());

        foreach ($connections as $connection) {
            return $connection;
        }

        return null;
    }

    public function deleteRelatedConnections(?int $languageResourceId = null, ?int $customerId = null): void
    {
        foreach ($this->connectionRepository->getConnectionsFor($languageResourceId, $customerId) as $connection) {
            $this->deleteConnection($connection);
        }
    }

    public function deleteConnections(LanguageResource $source, LanguageResource $target): void
    {
        $connections = $this->connectionRepository
            ->getConnectionsForPair((int) $source->getId(), (int) $target->getId());

        foreach ($connections as $connection) {
            $this->deleteConnection($connection);
        }
    }

    public function deleteConnection(CrossSynchronizationConnection $connection): void
    {
        $clone = clone $connection;
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

        foreach ($this->connectionOptionsRepository->getPotentialConnectionOptions($source) as $option) {
            $lr = $option->languageResource;
            $targetIntegration = $this->getSyncIntegration($lr->getServiceType());

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

            yield AvailableForConnectionOption::fromPotentialOption($option);
        }
    }

    public function connect(
        LanguageResource $sourceLr,
        LanguageResource $targetLr,
        Language $sourceLang,
        Language $targetLang,
    ): void {
        foreach ($sourceLr->getCustomers() as $customer) {
            if (! in_array($customer, $targetLr->getCustomers())) {
                continue;
            }

            try {
                $this->createConnection($sourceLr, $targetLr, $sourceLang, $targetLang, (int) $customer);
            } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey) {
                // connection already exists
            }
        }

        $this->eventDispatcher->dispatch(
            new LanguageResourcesConnectedEvent($sourceLr, $targetLr)
        );
    }

    private function getSyncIntegration(string $serviceType): ?SynchronisationInterface
    {
        if (! isset($this->cachedSyncIntegration[$serviceType])) {
            $this->cachedSyncIntegration[$serviceType] = $this->serviceManager
                ->getSynchronisationService($serviceType);
        }

        return $this->cachedSyncIntegration[$serviceType];
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
        SynchronisationInterface $targetIntegration
    ): bool {
        foreach ($sourceIntegration->syncSourceOf() as $syncType) {
            if (in_array($syncType, $targetIntegration->syncTargetFor())) {
                return true;
            }
        }

        return false;
    }

    private function getCachedLanguageResource(int $id): LanguageResource
    {
        if (! isset($this->cachedLanguageResources[$id])) {
            $this->cachedLanguageResources[$id] = $this->languageResourceRepository->get($id);
        }

        return $this->cachedLanguageResources[$id];
    }
}
