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

namespace MittagQI\Translate5\Repository;

use editor_Models_Customer_Customer as Customer;
use editor_Models_LanguageResources_CustomerAssoc as CustomerAssoc;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Languages;
use editor_Models_Languages as Language;
use MittagQI\Translate5\CrossSynchronization\CrossSynchronizationConnection;
use MittagQI\Translate5\CrossSynchronization\CrossSynchronizationConnectionCustomer;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class CrossSynchronizationConnectionRepository
{
    public function findConnection(int $id): ?CrossSynchronizationConnection
    {
        try {
            return $this->getConnection($id);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return null;
        }
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getConnection(int $id): CrossSynchronizationConnection
    {
        $connection = ZfExtended_Factory::get(CrossSynchronizationConnection::class);
        $connection->load($id);

        return $connection;
    }

    public function createConnection(
        LanguageResource $source,
        LanguageResource $target,
        Language $sourceLang,
        Language $targetLang,
    ): CrossSynchronizationConnection {
        $connection = ZfExtended_Factory::get(CrossSynchronizationConnection::class);
        $connection->setSourceLanguageResourceId((int) $source->getId());
        $connection->setSourceType($source->getResource()->getService());
        $connection->setTargetLanguageResourceId((int) $target->getId());
        $connection->setTargetType($target->getResource()->getService());
        $connection->setSourceLanguageId((int) $sourceLang->getId());
        $connection->setTargetLanguageId((int) $targetLang->getId());

        $connection->save();

        return $connection;
    }

    public function createCustomerAssoc(int $connectionId, int $customerId): CrossSynchronizationConnectionCustomer
    {
        $assoc = ZfExtended_Factory::get(CrossSynchronizationConnectionCustomer::class);
        $assoc->setConnectionId($connectionId);
        $assoc->setCustomerId($customerId);

        $assoc->save();

        return $assoc;
    }

    public function deleteConnection(CrossSynchronizationConnection $connection): void
    {
        $connection->delete();
    }

    public function deleteCustomerAssoc(CrossSynchronizationConnectionCustomer $assoc): void
    {
        $assoc->delete();
    }

    public function getAllConnectionsRenderData(int $filterLanguageResourceId): array
    {
        $db = ZfExtended_Factory::get(CrossSynchronizationConnection::class)->db;
        $lrTable = ZfExtended_Factory::get(LanguageResource::class)->db->info($db::NAME);
        $customerTable = ZfExtended_Factory::get(Customer::class)->db->info($db::NAME);
        $languageTable = ZfExtended_Factory::get(editor_Models_Languages::class)->db->info($db::NAME);
        $assocTable = ZfExtended_Factory::get(CrossSynchronizationConnectionCustomer::class)->db->info($db::NAME);

        $select = $db->select()
            ->setIntegrityCheck(false)
            ->from(
                [
                    'connections' => $db->info($db::NAME),
                ],
                ['id', 'sourceLanguageResourceId', 'targetLanguageResourceId']
            )
            ->join(
                [
                    'assocs' => $assocTable,
                ],
                'assocs.connectionId = connections.id',
                []
            )
            ->join(
                [
                    'customers' => $customerTable,
                ],
                'assocs.customerId = customers.id',
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
            ->join(
                [
                    'SourceLanguage' => $languageTable,
                ],
                'SourceLanguage.id = connections.sourceLanguageId',
                [
                    'SourceLanguage.langName as sourceLanguage',
                ]
            )
            ->join(
                [
                    'TargetLanguage' => $languageTable,
                ],
                'TargetLanguage.id = connections.targetLanguageId',
                [
                    'TargetLanguage.langName as targetLanguage',
                ]
            )
            ->where('connections.sourceLanguageResourceId = ?', $filterLanguageResourceId)
            ->orWhere('connections.targetLanguageResourceId = ?', $filterLanguageResourceId);

        return $db->fetchAll($select)->toArray();
    }

    /**
     * @return iterable<CrossSynchronizationConnection>
     */
    public function getConnectionsByLrCustomerAssoc(CustomerAssoc $assoc): iterable
    {
        $connection = ZfExtended_Factory::get(CrossSynchronizationConnection::class);
        $db = $connection->db;
        $customerAssocTable = ZfExtended_Factory::get(CustomerAssoc::class)->db->info($db::NAME);

        $select = $db->select()
            ->setIntegrityCheck(false)
            ->distinct()
            ->from(
                [
                    'connections' => $db->info($db::NAME),
                ],
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
        ;

        foreach ($db->fetchAll($select)->toArray() as $row) {
            $connection->hydrate($row);

            yield clone $connection;
        }
    }

    /**
     * @return iterable<CrossSynchronizationConnection>
     */
    public function getConnectionsFor(int $languageResourceId): iterable
    {
        $syncModel = ZfExtended_Factory::get(CrossSynchronizationConnection::class);
        $db = $syncModel->db;

        $select = $db->select()
            ->where('sourceLanguageResourceId = ? OR targetLanguageResourceId = ?', $languageResourceId);

        foreach ($db->fetchAll($select)->toArray() as $row) {
            $syncModel->hydrate($row);

            yield clone $syncModel;
        }
    }

    public function connectionHasAssociatedCustomers(CrossSynchronizationConnection $connection): bool
    {
        $assoc = ZfExtended_Factory::get(CrossSynchronizationConnectionCustomer::class);

        $db = $assoc->db;

        $select = $db->select()
            ->from(
                [
                    'assocs' => $db->info($db::NAME),
                ],
                [
                    'count' => 'COUNT(*)',
                ]
            )
            ->where('connectionId = ?', $connection->getId());

        return $db->fetchRow($select)->toArray()['count'] > 0;
    }

    /**
     * @return iterable<CrossSynchronizationConnectionCustomer>
     */
    public function getCustomerAssociations(CrossSynchronizationConnection $connection): iterable
    {
        $assoc = ZfExtended_Factory::get(CrossSynchronizationConnectionCustomer::class);

        $db = $assoc->db;

        $select = $db->select()
            ->from(
                [
                    'assocs' => $db->info($db::NAME),
                ],
            )
            ->where('connectionId = ?', $connection->getId());

        foreach ($db->fetchAll($select)->toArray() as $row) {
            $assoc->hydrate($row);

            yield clone $assoc;
        }
    }

    /**
     * @return iterable<CrossSynchronizationConnectionCustomer>
     */
    public function getCustomerAssocsByCustomerAndLanguageResource(
        int $customerId,
        ?int $languageResourceId = null
    ): iterable {
        $assoc = ZfExtended_Factory::get(CrossSynchronizationConnectionCustomer::class);

        $db = $assoc->db;

        $select = $db->select()
            ->setIntegrityCheck(false)
            ->from([
                'assocs' => $db->info($db::NAME),
            ])
            ->where('customerId = ?', $customerId);

        if ($languageResourceId !== null) {
            $connectionTable = ZfExtended_Factory::get(CrossSynchronizationConnection::class)->db->info($db::NAME);
            $lrTable = ZfExtended_Factory::get(LanguageResource::class)->db->info($db::NAME);

            $select
                ->join(
                    [
                        'connections' => $connectionTable,
                    ],
                    'connections.id = assocs.connectionId',
                    []
                )
                ->join(
                    [
                        'lr' => $lrTable,
                    ],
                    'connections.sourceLanguageResourceId = lr.id OR connections.targetLanguageResourceId = lr.id',
                    []
                )
                ->where('lr.id = ?', $languageResourceId);
        }

        foreach ($db->fetchAll($select)->toArray() as $row) {
            $assoc->hydrate($row);

            yield clone $assoc;
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
            ->from(
                $syncModel->db->info($syncModel->db::NAME),
                ['targetLanguageResourceId']
            );

        $ids = array_column(
            $syncModel->db->fetchAll($existingConnectionTargetsSelect)->toArray(),
            'targetLanguageResourceId'
        );

        return array_map('intval', $ids);
    }
}
