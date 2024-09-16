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

namespace MittagQI\Translate5\User\Validation;

use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\Validation\LspCustomerAssociationValidator;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use ZfExtended_Models_User as User;

class UserCustomerAssociationValidator
{
    public function __construct(
        private readonly LspUserRepository $lspUserRepository,
        private readonly LspCustomerAssociationValidator $lspCustomerAssociationValidator,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            new LspUserRepository(),
            LspCustomerAssociationValidator::create(),
        );
    }

    /**
     * @param iterable<int> $customerIds
     * @throws CustomerDoesNotBelongToUserException
     */
    public function assertCustomersAreSubsetForUser(iterable $customerIds, User $user): void
    {
        $userCustomers = $user->getCustomersArray();

        foreach ($customerIds as $customerId) {
            if (! in_array((int) $customerId, $userCustomers, true)) {
                throw new CustomerDoesNotBelongToUserException((int) $customerId, $user->getUserGuid());
            }
        }
    }

    /**
     * @param iterable<int> $customerIds
     * @throws CustomerDoesNotBelongToLspException
     */
    public function assertCustomersMayBeAssociatedWithUser(iterable $customerIds, User $user): void
    {
        $lspUser = $this->lspUserRepository->findByUser($user);

        if (null !== $lspUser) {
            $this->lspCustomerAssociationValidator->assertCustomersAreSubsetForLSP($lspUser->lsp, $customerIds);
        }
    }
}
