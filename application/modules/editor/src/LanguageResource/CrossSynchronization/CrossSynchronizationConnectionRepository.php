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

use editor_Models_Customer_Customer as Customer;
use editor_Models_LanguageResources_CustomerAssoc as CustomerAssoc;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use ZfExtended_Factory;

class CrossSynchronizationConnectionRepository
{
    public function createConnection(
        LanguageResource $source,
        LanguageResource $target,
        int $customerId,
    ): CrossSynchronizationConnection {
        $connection = ZfExtended_Factory::get(CrossSynchronizationConnection::class);
        $connection->setSourceLanguageResourceId((int) $source->getId());
        $connection->setSourceType($source->getResource()->getService());
        $connection->setTargetLanguageResourceId((int) $target->getId());
        $connection->setTargetType($target->getResource()->getService());
        $connection->setCustomerId($customerId);

        $connection->save();

        return $connection;
    }

    public function deleteConnection(CrossSynchronizationConnection $connection): void
    {
        $connection->delete();
    }

    public function getAllConnectionsRenderData(int $filterLanguageResourceId): array
    {
        $db = ZfExtended_Factory::get(CrossSynchronizationConnection::class)->db;
        $lrTable = ZfExtended_Factory::get(LanguageResource::class)->db->info($db::NAME);
        $customerTable = ZfExtended_Factory::get(Customer::class)->db->info($db::NAME);

        $select = $db->select()
            ->setIntegrityCheck(false)
            ->from(
                [
                    'connections' => $db->info($db::NAME),
                ],
                ['sourceLanguageResourceId', 'targetLanguageResourceId']
            )
            ->join(
                [
                    'customers' => $customerTable,
                ],
                'connections.customerId = customers.id',
                [
                    'customers.name as customerName',
                ]
            )
            ->join(
                [
                    'LanguageResourceSource' => $lrTable,
                ],
                'connections.sourceLanguageResourceId = LanguageResourceSource.id',
                [
                    'LanguageResourceSource.serviceName as sourceServiceName',
                    'LanguageResourceSource.name as sourceName',
                ]
            )
            ->join(
                [
                    'LanguageResourceTarget' => $lrTable,
                ],
                'connections.targetLanguageResourceId = LanguageResourceTarget.id',
                [
                    'LanguageResourceTarget.serviceName as targetServiceName',
                    'LanguageResourceTarget.name as targetName',
                ]
            )
            ->where('connections.sourceLanguageResourceId = ?', $filterLanguageResourceId)
            ->orWhere('connections.targetLanguageResourceId = ?', $filterLanguageResourceId);

        return $db->fetchAll($select)->toArray();
    }

    /**
     * @return iterable<array{sourceId: int, targetId: int}>
     */
    public function getConnectedPairsByAssoc(CustomerAssoc $assoc): iterable
    {
        $db = ZfExtended_Factory::get(CrossSynchronizationConnection::class)->db;
        $customerAssocTable = ZfExtended_Factory::get(CustomerAssoc::class)->db->info($db::NAME);

        $select = $db->select()
            ->setIntegrityCheck(false)
            ->distinct()
            ->from(
                [
                    'connections' => $db->info($db::NAME),
                ],
                ['sourceLanguageResourceId', 'targetLanguageResourceId']
            )
            ->join(
                [
                    'sourceCustomers' => $customerAssocTable,
                ],
                'sourceCustomers.languageResourceId = connections.sourceLanguageResourceId',
                []
            )
            ->join(
                [
                    'targetCustomers' => $customerAssocTable,
                ],
                'targetCustomers.languageResourceId = connections.targetLanguageResourceId',
                []
            )
            ->where(
                '(connections.sourceLanguageResourceId = ? OR connections.targetLanguageResourceId = ?)',
                $assoc->getLanguageResourceId()
            )
            ->where('sourceCustomers.customerId = ?', $assoc->getCustomerId())
            ->where('targetCustomers.customerId = ?', $assoc->getCustomerId())
            ->where('connections.customerId != ?', $assoc->getCustomerId())
        ;

        foreach ($db->fetchAll($select)->toArray() as $row) {
            yield [
                'sourceId' => (int) $row['sourceLanguageResourceId'],
                'targetId' => (int) $row['targetLanguageResourceId'],
            ];
        }
    }

    /**
     * @return iterable<CrossSynchronizationConnection>
     */
    public function getConnectionsFor(?int $languageResourceId, ?int $customerId = null): iterable
    {
        $syncModel = ZfExtended_Factory::get(CrossSynchronizationConnection::class);
        $db = $syncModel->db;

        $select = $db->select()
            ->from([
                'connections' => $db->info($db::NAME),
            ]);

        if ($languageResourceId) {
            $select->where('sourceLanguageResourceId = ? OR targetLanguageResourceId = ?', $languageResourceId);
        }

        if (null !== $customerId) {
            $select->where('customerId = ?', $customerId);
        }

        foreach ($db->fetchAll($select)->toArray() as $row) {
            $syncModel->hydrate($row);

            yield clone $syncModel;
        }
    }

    /**
     * @return iterable<CrossSynchronizationConnection>
     */
    public function getConnectionsWhereSource(int $filterLanguageResourceId): iterable
    {
        $syncModel = ZfExtended_Factory::get(CrossSynchronizationConnection::class);
        $db = $syncModel->db;

        $select = $db->select()
            ->from([
                'connections' => $db->info($db::NAME),
            ])
            ->where('sourceLanguageResourceId = ?', $filterLanguageResourceId);

        foreach ($db->fetchAll($select)->toArray() as $row) {
            $syncModel->hydrate($row);

            yield clone $syncModel;
        }
    }

    /**
     * @return iterable<array{sourceId: int, targetId: int}>
     */
    public function getConnectedPairsWhere(int $languageResourceId): iterable
    {
        $syncModel = ZfExtended_Factory::get(CrossSynchronizationConnection::class);
        $db = $syncModel->db;

        $select = $db->select()
            ->distinct()
            ->from(
                [
                    'connections' => $db->info($db::NAME),
                ],
                [
                    'sourceLanguageResourceId',
                    'targetLanguageResourceId',
                ]
            )
            ->where('sourceLanguageResourceId = ? OR targetLanguageResourceId = ?', $languageResourceId);

        foreach ($db->fetchAll($select)->toArray() as $row) {
            yield [
                'sourceId' => (int) $row['sourceLanguageResourceId'],
                'targetId' => (int) $row['targetLanguageResourceId'],
            ];
        }
    }

    public function hasConnectionsForPair(int $sourceId, int $targetId): bool
    {
        $syncModel = ZfExtended_Factory::get(CrossSynchronizationConnection::class);
        $db = $syncModel->db;

        $select = $db->select()
            ->from([
                'LanguageResourceSync' => $db->info($db::NAME),
            ], 'COUNT(*) as count')
            ->where('LanguageResourceSync.sourceLanguageResourceId = ?', $sourceId)
            ->orWhere('LanguageResourceSync.targetLanguageResourceId = ?', $targetId);

        return $db->fetchRow($select)->toArray()['count'] > 0;
    }

    /**
     * @return iterable<CrossSynchronizationConnection>
     */
    public function getConnectionsForPair(int $sourceId, int $targetId): iterable
    {
        $syncModel = ZfExtended_Factory::get(CrossSynchronizationConnection::class);
        $db = $syncModel->db;

        $select = $db->select()
            ->from([
                'LanguageResourceSync' => $db->info($db::NAME),
            ])
            ->where('LanguageResourceSync.sourceLanguageResourceId = ?', $sourceId)
            ->orWhere('LanguageResourceSync.targetLanguageResourceId = ?', $targetId);

        foreach ($db->fetchAll($select)->toArray() as $row) {
            $syncModel->hydrate($row);

            yield clone $syncModel;
        }
    }

    /**
     * @return int[]
     */
    public function getAllTargetLanguageResourceIds(): array
    {
        $syncModel = ZfExtended_Factory::get(CrossSynchronizationConnection::class);
        $existingConnectionTargetsSelect = $syncModel->db
            ->select()
            ->distinct()
            ->from(
                $syncModel->db->info($syncModel->db::NAME),
                ['targetLanguageResourceId']
            );

        $ids = array_column(
            $syncModel->db->fetchAll($existingConnectionTargetsSelect)->toArray(),
            'targetLanguageResourceId'
        );

        array_walk($ids, fn ($id) => (int) $id);

        return $ids;
    }
}
