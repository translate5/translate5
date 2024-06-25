<?php

declare(strict_types=1);

namespace MittagQI\Translate5\LanguageResource\CrossSynchronization;

use editor_Models_LanguageResources_CustomerAssoc as LanguageResourceCustomers;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_LanguageResources_Languages as LanguageResourceLanguages;
use editor_Services_Manager;
use Generator;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\Events\EventEmitter;
use ZfExtended_Factory;

class CrossLanguageResourceSynchronizationService
{
    public function __construct(
        private editor_Services_Manager $serviceManager,
        private EventEmitter $eventEmitter,
    ) {
        $this->logger = \Zend_Registry::get('logger')->cloneMe('editor.languageresource.synchronization');
    }

    public static function create(): self
    {
        return new self(
            new editor_Services_Manager(),
            EventEmitter::create(),
        );
    }

    public function createConnection(
        LanguageResource $source,
        LanguageResource $target
    ): CrossSynchronizationConnection {
        $connection = ZfExtended_Factory::get(CrossSynchronizationConnection::class);
        $connection->setSourceLanguageResourceId((int) $source->getId());
        $connection->setSourceType($source->getResource()->getService());
        $connection->setTargetLanguageResourceId((int) $target->getId());
        $connection->setTargetType($target->getResource()->getService());

        $connection->save();

        $this->eventEmitter->triggerConnectionCreatedEvent($connection);

        return $connection;
    }

    public function deleteConnection(CrossSynchronizationConnection $connection): void
    {
        $clone = clone $connection;
        $connection->delete();

        $this->eventEmitter->triggerConnectionDeleted($clone);
    }

    public function getSyncData(
        LanguageResource $source,
        LanguagePair $languagePair,
        ?int $customerId,
        SynchronizationType $synchronizationType
    ): Generator {
        $service = $this->getSyncConnectionService($source->getServiceType());

        if (null === $service) {
            yield from [];
        }

        yield from $service->getSyncData($source, $languagePair, $customerId, $synchronizationType);
    }

    /**
     * @return LanguageResource[]
     */
    public function getAvailableForConnectionLanguageResources(LanguageResource $source): array
    {
        $sourceService = $this->getSyncConnectionService($source->getServiceType());

        if (null === $sourceService) {
            return [];
        }

        if (empty($source->getSourceLang()) || empty($source->getTargetLang())) {
            return [];
        }

        $db = $source->db;

        $lrLangTable = ZfExtended_Factory::get(LanguageResourceLanguages::class)->db->info($db::NAME);
        $lrCustomerTable = ZfExtended_Factory::get(LanguageResourceCustomers::class)->db->info($db::NAME);

        /**
         * @var array<string, SyncConnectionService> $services
         */
        $services = [];

        foreach ($this->serviceManager->getAll() as $serviceType) {
            $targetService = $this->getSyncConnectionService($serviceType);

            if (null === $targetService) {
                continue;
            }

            foreach ($sourceService->syncSourceOf() as $syncType) {
                if (in_array($syncType, $targetService->syncTargetFor())) {
                    $services[$targetService->getName()] = $targetService;
                }
            }
        }

        $syncModel = ZfExtended_Factory::get(CrossSynchronizationConnection::class);
        $existingConnectionTargetsSelect = $syncModel->db
            ->select()
            ->from(
                $syncModel->db->info($syncModel->db::NAME),
                ['distinct(targetLanguageResourceId) as targetId']
            );

        $existingTargets = array_column(
            $syncModel->db->fetchAll($existingConnectionTargetsSelect)->toArray(),
            'targetId'
        );

        $select = $db->select()
            ->setIntegrityCheck(false)
            ->from(
                ['LanguageResources' => $db->info($db::NAME)],
                ['id', 'name', 'serviceName']
            )
            ->join(
                [
                    'LanguageResourceLanguages' => $lrLangTable,
                ],
                'LanguageResourceLanguages.languageResourceId = LanguageResources.id',
                []
            )
            ->join(
                [
                    'LanguageResourceCustomers' => $lrCustomerTable,
                ],
                'LanguageResourceCustomers.languageResourceId = LanguageResources.id',
                []
            )
            ->where('LanguageResourceLanguages.sourceLang IN (?)', (array) $source->getSourceLang())
            ->where('LanguageResourceLanguages.targetLang IN (?)', (array) $source->getTargetLang())
            ->where('LanguageResourceCustomers.customerId IN (?)', $source->getCustomers())
            ->where('LanguageResources.serviceName IN (?)', array_keys($services));

        $result = [];
        foreach ($db->fetchAll($select)->toArray() as $row) {
            if (in_array($row['id'], $existingTargets) && $services[$row['serviceName']]->isOneToOne()) {
                continue;
            }

            $lr = ZfExtended_Factory::get(LanguageResource::class);
            $lr->load($row['id']);

            $result[] = $lr;
        }

        return $result;
    }

    public function connectAllAvailable(LanguageResource $source): void
    {
        $sourceCustomers = $source->getCustomers();

        $targets = $this->getAvailableForConnectionLanguageResources($source);

        foreach ($targets as $target) {
            foreach ($target->getCustomers() as $customerId) {
                if (! in_array($customerId, $sourceCustomers)) {
                    continue;
                }

                $this->createConnection($source, $target);

                // Connection is unique for [source - target] regardless of customer
                continue 2;
            }
        }
    }

    private function getSyncConnectionService(string $serviceType): ?SyncConnectionService
    {
        $service = $this->serviceManager->getService($serviceType);

        if (! $service instanceof SyncConnectionService) {
            return null;
        }

        return $service;
    }
}