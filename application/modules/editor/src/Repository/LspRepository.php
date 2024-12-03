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
use MittagQI\Translate5\LSP\Exception\LspNotFoundException;
use MittagQI\Translate5\LSP\Model\Db\LanguageServiceProviderCustomerTable;
use MittagQI\Translate5\LSP\Model\Db\LanguageServiceProviderUserTable;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Model\LanguageServiceProviderCustomer;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use Zend_Db_Table_Row;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class LspRepository implements LspRepositoryInterface
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

    public function getEmptyModel(): LanguageServiceProvider
    {
        return new LanguageServiceProvider();
    }

    public function getEmptyLspCustomerModel(): LanguageServiceProviderCustomer
    {
        return new LanguageServiceProviderCustomer();
    }

    /**
     * @throws LspNotFoundException
     */
    public function get(int $id): LanguageServiceProvider
    {
        try {
            $model = new LanguageServiceProvider();
            $model->load($id);

            return $model;
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            throw new LspNotFoundException($id);
        }
    }

    public function find(int $id): ?LanguageServiceProvider
    {
        try {
            return $this->get($id);
        } catch (LspNotFoundException) {
            return null;
        }
    }

    public function save(LanguageServiceProvider $lsp): void
    {
        $lsp->save();
    }

    public function delete(LanguageServiceProvider $lsp): void
    {
        $lsp->delete();
    }

    public function findCustomerAssignment(
        int $lspId,
        int $customerId,
    ): ?LanguageServiceProviderCustomer {
        $model = new LanguageServiceProviderCustomer();
        $db = $model->db;
        $select = $db->select()
            ->where('lspId = ?', $lspId)
            ->where('customerId = ?', $customerId);

        $row = $db->fetchRow($select);

        if (! $row) {
            return null;
        }

        $model->init($row);

        return $model;
    }

    public function saveCustomerAssignment(LanguageServiceProviderCustomer $lspCustomer): void
    {
        $lspCustomer->save();
    }

    public function deleteCustomerAssignment(int $lspId, int $customerId): void
    {
        $model = new LanguageServiceProviderCustomer();
        $model->db->delete(
            [
                'lspId = ?' => $lspId,
                'customerId = ?' => $customerId,
            ]
        );
    }

    /**
     * @return iterable<LanguageServiceProvider>
     */
    public function getAll(): iterable
    {
        $model = new LanguageServiceProvider();

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
    public function getCustomers(LanguageServiceProvider $lsp): iterable
    {
        $customer = new Customer();
        $customerDb = $customer->db;

        $select = $customerDb->select()
            ->setIntegrityCheck(false)
            ->from([
                'customer' => $customer->db->info($customer->db::NAME),
            ])
            ->join([
                'lspToCustomer' => LanguageServiceProviderCustomerTable::TABLE_NAME,
            ], 'customer.id = lspToCustomer.customerId', [])
            ->where('lspToCustomer.lspId = ?', $lsp->getId())
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
    public function getCustomerIds(int $lspId): array
    {
        $customer = new Customer();
        $customerDb = $customer->db;

        $select = $customerDb->select()
            ->setIntegrityCheck(false)
            ->distinct()
            ->from(
                [
                    'lspToCustomer' => LanguageServiceProviderCustomerTable::TABLE_NAME,
                ],
                ['customerId']
            )
            ->where('lspToCustomer.lspId = ?', $lspId)
        ;

        $rows = $customerDb->fetchAll($select);

        $rows->rewind();

        return array_map('intval', array_column($rows->toArray(), 'customerId'));
    }

    /**
     * @return int[]
     */
    public function getCustomerIdsOfCoordinatorsLsp(int $coordinatorUserId): array
    {
        $customer = new Customer();
        $customerDb = $customer->db;

        $select = $customerDb->select()
            ->setIntegrityCheck(false)
            ->distinct()
            ->from(
                [
                    'lspToCustomer' => LanguageServiceProviderCustomerTable::TABLE_NAME,
                ],
                ['customerId']
            )
            ->join(
                [
                    'lspUser' => LanguageServiceProviderUserTable::TABLE_NAME,
                ],
                'lspUser.lspId = lspToCustomer.lspId',
                []
            )
            ->where('lspUser.userId = ?', $coordinatorUserId)
        ;

        $rows = $customerDb->fetchAll($select);

        $rows->rewind();

        return array_map('intval', array_column($rows->toArray(), 'customerId'));
    }

    /**
     * @return iterable<LanguageServiceProvider>
     */
    public function getSubLspList(LanguageServiceProvider $lsp): iterable
    {
        $model = new LanguageServiceProvider();

        $select = $model->db->select()->where('parentId = ?', $lsp->getId());

        $stmt = $this->db->query($select);

        yield from $this->generateModels($stmt, $model);
    }

    /**
     * @return int[]
     */
    public function getSubLspIds(LanguageServiceProvider $lsp): array
    {
        $model = ZfExtended_Factory::get(LanguageServiceProvider::class);
        $select = $model->db->select()->from($model->db, ['id'])->where('parentId = ?', $lsp->getId());

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
