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

namespace MittagQI\Translate5\User\Operations;

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\ActionFeasibilityAssertInterface;
use MittagQI\Translate5\LSP\Contract\LspUserUnassignCustomersOperationInterface;
use MittagQI\Translate5\LSP\Operations\LspUserUnassignCustomersOperation;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssert;
use MittagQI\Translate5\User\Contract\UpdateUserCustomersAssignmentsOperationInterface;
use MittagQI\Translate5\User\Contract\UserAssignCustomersOperationInterface;
use MittagQI\Translate5\User\Contract\UserUnassignCustomersOperationInterface;
use MittagQI\Translate5\User\Model\User;

final class UpdateUserCustomersAssignmentsOperation implements UpdateUserCustomersAssignmentsOperationInterface
{
    public function __construct(
        private readonly UserAssignCustomersOperationInterface $assignCustomers,
        private readonly UserUnassignCustomersOperationInterface $unassignCustomers,
        private readonly LspUserRepositoryInterface $lspUserRepository,
        private readonly LspUserUnassignCustomersOperationInterface $lspUserUnassignCustomers,
        private readonly ActionFeasibilityAssertInterface $userActionFeasibilityChecker,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            UserAssignCustomersOperation::create(),
            UserUnassignCustomersOperation::create(),
            LspUserRepository::create(),
            LspUserUnassignCustomersOperation::create(),
            UserActionFeasibilityAssert::create(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function updateCustomers(User $user, array $customers, bool $forceUnassignment = false): void
    {
        $this->userActionFeasibilityChecker->assertAllowed(Action::Update, $user);

        $this->assignCustomers->assignCustomers($user, ...array_diff($customers, $user->getCustomersArray()));

        $unassignedCustomers = array_diff($user->getCustomersArray(), $customers);

        if (empty($unassignedCustomers)) {
            return;
        }

        $lspUser = $this->lspUserRepository->findByUser($user);

        if (null === $lspUser) {
            $this->unassignCustomers->unassignCustomers($user, ...$unassignedCustomers);

            return;
        }

        if ($forceUnassignment) {
            $this->lspUserUnassignCustomers->forceUnassignCustomers($lspUser, ...$unassignedCustomers);

            return;
        }

        $this->lspUserUnassignCustomers->unassignCustomers($lspUser, ...$unassignedCustomers);
    }
}