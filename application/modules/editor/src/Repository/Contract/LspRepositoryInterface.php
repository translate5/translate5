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

namespace MittagQI\Translate5\Repository\Contract;

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\LSP\Exception\LspNotFoundException;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Model\LanguageServiceProviderCustomer;

interface LspRepositoryInterface
{
    public function getEmptyModel(): LanguageServiceProvider;

    public function getEmptyLspCustomerModel(): LanguageServiceProviderCustomer;

    /**
     * @throws LspNotFoundException
     */
    public function get(int $id): LanguageServiceProvider;

    public function save(LanguageServiceProvider $lsp): void;

    public function delete(LanguageServiceProvider $lsp): void;

    public function findCustomerConnection(
        int $lspId,
        int $customerId,
    ): ?LanguageServiceProviderCustomer;

    public function saveCustomerAssignment(LanguageServiceProviderCustomer $lspCustomer): void;

    public function deleteCustomerAssignment(int $lspId, int $customerId): void;

    /**
     * @return iterable<LanguageServiceProvider>
     */
    public function getAll(): iterable;

    /**
     * @return iterable<Customer>
     */
    public function getCustomers(LanguageServiceProvider $lsp): iterable;

    /**
     * @return int[]
     */
    public function getCustomerIds(int $lspId): array;

    /**
     * @return iterable<LanguageServiceProvider>
     */
    public function getSubLspList(LanguageServiceProvider $lsp): iterable;

    /**
     * @return int[]
     */
    public function getSubLspIds(LanguageServiceProvider $lsp): array;

    /**
     * @return int[]
     */
    public function getCustomerIdsOfCoordinatorsLsp(int $coordinatorUserId): array;
}
