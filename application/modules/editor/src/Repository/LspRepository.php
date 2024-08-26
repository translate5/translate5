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
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\Model\Db\LanguageServiceProviderCustomerTable;
use MittagQI\Translate5\LSP\Model\Db\LanguageServiceProviderUserTable;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Model\LanguageServiceProviderCustomer;
use MittagQI\Translate5\LSP\Model\LanguageServiceProviderUser;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use Zend_Db_Table_Row;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User;

class LspRepository
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
    ) {
    }

    public static function create(): self
    {
        return new self(Zend_Db_Table::getDefaultAdapter());
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function get(int $id): LanguageServiceProvider
    {
        $model = ZfExtended_Factory::get(LanguageServiceProvider::class);
        $model->load($id);

        return $model;
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
        LanguageServiceProvider $lsp,
        Customer $customer,
    ): ?LanguageServiceProviderCustomer {
        $model = ZfExtended_Factory::get(LanguageServiceProviderCustomer::class);
        $db = $model->db;
        $select = $db->select()
            ->where('lspId = ?', $lsp->getId())
            ->where('customerId = ?', $customer->getId());

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

    public function deleteCustomerAssignment(LanguageServiceProviderCustomer $lspCustomer): void
    {
        $lspCustomer->delete();
    }

    /**
     * @return iterable<LanguageServiceProvider>
     */
    public function getAll(): iterable
    {
        $model = ZfExtended_Factory::get(LanguageServiceProvider::class);

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
     * @return iterable<ZfExtended_Models_User>
     */
    public function getUsers(LanguageServiceProvider $lsp): iterable
    {
        $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $lspToUserTable = ZfExtended_Factory::get(LanguageServiceProviderUser::class)
            ->db
            ->info(LanguageServiceProviderUserTable::NAME);
        $userDb = $user->db;

        $select = $userDb->select()
            ->setIntegrityCheck(false)
            ->from([
                'user' => $user->db->info($user->db::NAME),
            ])
            ->join([
                'lspToUser' => $lspToUserTable,
            ], 'user.id = lspToUser.userId', [])
            ->where('lspToUser.lspId = ?', $lsp->getId());

        $rows = $userDb->fetchAll($select);

        if (! $rows->valid()) {
            return yield from [];
        }

        foreach ($rows as $row) {
            $user->init($row);

            yield clone $user;
        }
    }

    /**
     * @return iterable<Customer>
     */
    public function getCustomers(LanguageServiceProvider $lsp): iterable
    {
        $customer = ZfExtended_Factory::get(Customer::class);
        $lspToCustomerTable = ZfExtended_Factory::get(LanguageServiceProviderCustomer::class)
            ->db
            ->info(LanguageServiceProviderCustomerTable::NAME);
        $customerDb = $customer->db;

        $select = $customerDb->select()
            ->setIntegrityCheck(false)
            ->from([
                'customer' => $customer->db->info($customer->db::NAME),
            ])
            ->join([
                'lspToCustomer' => $lspToCustomerTable,
            ], 'customer.id = lspToCustomer.customerId', [])
            ->where('lspToCustomer.lspId = ?', $lsp->getId());

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
     * @return iterable<LanguageServiceProvider>
     */
    public function getForJobCoordinator(JobCoordinator $jc): iterable
    {
        yield $jc->lsp;

        foreach ($this->getSubLspList($jc->lsp) as $lsp) {
            yield $lsp;
        }
    }

    public function getForPmRole(): iterable
    {
        $model = ZfExtended_Factory::get(LanguageServiceProvider::class);
        $select = $model->db->select()->where('parentId IS NULL');

        $stmt = $this->db->query($select);

        yield from $this->generateModels($stmt, $model);
    }

    /**
     * @return iterable<LanguageServiceProvider>
     */
    public function getSubLspList(LanguageServiceProvider $lsp): iterable
    {
        $model = ZfExtended_Factory::get(LanguageServiceProvider::class);

        $select = $model->db->select()->where('parentId = ?', $lsp->getId());

        $stmt = $this->db->query($select);

        yield from $this->generateModels($stmt, $model);
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
