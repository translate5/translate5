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
use MittagQI\Translate5\CoordinatorGroup\Exception\CoordinatorGroupNotFoundException;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroupCustomer;
use MittagQI\Translate5\CoordinatorGroup\Model\Db\CoordinatorGroupCustomerTable;
use MittagQI\Translate5\CoordinatorGroup\Model\Db\CoordinatorGroupUserTable;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupRepositoryInterface;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use Zend_Db_Table_Row;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class CoordinatorGroupRepository implements CoordinatorGroupRepositoryInterface
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(Zend_Db_Table::getDefaultAdapter());
    }

    public function getEmptyModel(): CoordinatorGroup
    {
        return new CoordinatorGroup();
    }

    public function getEmptyCoordinatorGroupCustomerModel(): CoordinatorGroupCustomer
    {
        return new CoordinatorGroupCustomer();
    }

    /**
     * @throws CoordinatorGroupNotFoundException
     */
    public function get(int $id): CoordinatorGroup
    {
        try {
            $model = new CoordinatorGroup();
            $model->load($id);

            return $model;
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            throw new CoordinatorGroupNotFoundException($id);
        }
    }

    public function find(int $id): ?CoordinatorGroup
    {
        try {
            return $this->get($id);
        } catch (CoordinatorGroupNotFoundException) {
            return null;
        }
    }

    public function save(CoordinatorGroup $group): void
    {
        $group->save();
    }

    public function delete(CoordinatorGroup $group): void
    {
        $group->delete();
    }

    public function findCustomerConnection(
        int $groupId,
        int $customerId,
    ): ?CoordinatorGroupCustomer {
        $model = new CoordinatorGroupCustomer();
        $db = $model->db;
        $select = $db->select()
            ->where('groupId = ?', $groupId)
            ->where('customerId = ?', $customerId);

        $row = $db->fetchRow($select);

        if (! $row) {
            return null;
        }

        $model->init($row);

        return $model;
    }

    public function saveCustomerAssignment(CoordinatorGroupCustomer $groupCustomer): void
    {
        $groupCustomer->save();
    }

    public function deleteCustomerAssignment(int $groupId, int $customerId): void
    {
        $model = new CoordinatorGroupCustomer();
        $model->db->delete(
            [
                'groupId = ?' => $groupId,
                'customerId = ?' => $customerId,
            ]
        );
    }

    /**
     * @return iterable<CoordinatorGroup>
     */
    public function getAll(): iterable
    {
        $model = new CoordinatorGroup();

        foreach ($model->loadAll() as $row) {
            $model->init(
                new Zend_Db_Table_Row(
                    [
                        'table' => $model->db,
                        'data' => $row,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            yield clone $model;
        }
    }

    /**
     * @return iterable<Customer>
     */
    public function getCustomers(CoordinatorGroup $group): iterable
    {
        $customer = new Customer();
        $customerDb = $customer->db;

        $select = $customerDb->select()
            ->setIntegrityCheck(false)
            ->from([
                'customer' => $customer->db->info($customer->db::NAME),
            ])
            ->join([
                'groupToCustomer' => CoordinatorGroupCustomerTable::TABLE_NAME,
            ], 'customer.id = groupToCustomer.customerId', [])
            ->where('groupToCustomer.groupId = ?', $group->getId())
            ->group('customer.id')
        ;

        $rows = $customerDb->fetchAll($select);

        if (! $rows->valid()) {
            return yield from [];
        }

        foreach ($rows as $row) {
            $customer->init($row);

            yield clone $customer;
        }
    }

    /**
     * @return int[]
     */
    public function getCustomerIds(int $groupId): array
    {
        $customer = new Customer();
        $customerDb = $customer->db;

        $select = $customerDb->select()
            ->setIntegrityCheck(false)
            ->distinct()
            ->from(
                [
                    'groupToCustomer' => CoordinatorGroupCustomerTable::TABLE_NAME,
                ],
                ['customerId']
            )
            ->where('groupToCustomer.groupId = ?', $groupId)
        ;

        $rows = $customerDb->fetchAll($select);

        $rows->rewind();

        return array_map('intval', array_column($rows->toArray(), 'customerId'));
    }

    /**
     * @return int[]
     */
    public function getCustomerIdsOfCoordinatorsCoordinator(int $coordinatorUserId): array
    {
        $customer = new Customer();
        $customerDb = $customer->db;

        $select = $customerDb->select()
            ->setIntegrityCheck(false)
            ->distinct()
            ->from(
                [
                    'groupToCustomer' => CoordinatorGroupCustomerTable::TABLE_NAME,
                ],
                ['customerId']
            )
            ->join(
                [
                    'groupUser' => CoordinatorGroupUserTable::TABLE_NAME,
                ],
                'groupUser.groupId = groupToCustomer.groupId',
                []
            )
            ->where('groupUser.userId = ?', $coordinatorUserId)
        ;

        $rows = $customerDb->fetchAll($select);

        $rows->rewind();

        return array_map('intval', array_column($rows->toArray(), 'customerId'));
    }

    /**
     * @return iterable<CoordinatorGroup>
     */
    public function getSubCoordinatorGroupList(CoordinatorGroup $group): iterable
    {
        $model = new CoordinatorGroup();

        $select = $model->db->select()->where('parentId = ?', $group->getId());

        $stmt = $this->db->query($select);

        yield from $this->generateModels($stmt, $model);
    }

    /**
     * @return int[]
     */
    public function getSubLspIds(CoordinatorGroup $group): array
    {
        $model = ZfExtended_Factory::get(CoordinatorGroup::class);
        $select = $model->db->select()->from($model->db, ['id'])->where('parentId = ?', $group->getId());

        return $this->db->fetchCol($select);
    }

    private function generateModels(
        \PDOStatement|\Zend_Db_Statement_Interface|\Zend_Db_Statement $stmt,
        \ZfExtended_Models_Entity_Abstract $model,
    ): \Generator {
        while ($row = $stmt->fetch()) {
            $model->init(
                new Zend_Db_Table_Row(
                    [
                        'table' => $model->db,
                        'data' => $row,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            yield clone $model;
        }
    }
}
