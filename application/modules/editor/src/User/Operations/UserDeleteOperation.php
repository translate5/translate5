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
use MittagQI\Translate5\JobAssignment\UserJob\Contract\DeleteUserJobOperationInterface;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DeleteUserJobOperation;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Feasibility\ForceUserActionFeasibilityAssert;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssert;
use MittagQI\Translate5\User\Contract\UserDeleteOperationInterface;
use MittagQI\Translate5\User\Model\User;
use Zend_Registry;
use ZfExtended_Logger;

final class UserDeleteOperation implements UserDeleteOperationInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly LspUserRepository $lspUserRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly ActionFeasibilityAssertInterface $userFeasibilityAssert,
        private readonly ActionFeasibilityAssertInterface $forceUserFeasibilityAssert,
        private readonly DeleteUserJobOperationInterface $deleteUserJobAssignmentOperation,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        $userRepository = new UserRepository();

        return new self(
            $userRepository,
            new LspUserRepository($userRepository),
            UserJobRepository::create(),
            UserActionFeasibilityAssert::create(),
            ForceUserActionFeasibilityAssert::create(),
            DeleteUserJobOperation::create(),
            Zend_Registry::get('logger')->cloneMe('user.delete'),
        );
    }

    public function delete(User $user): void
    {
        $this->userFeasibilityAssert->assertAllowed(Action::Delete, $user);

        $this->deleteUser($user);
    }

    public function forceDelete(User $user): void
    {
        $this->forceUserFeasibilityAssert->assertAllowed(Action::Delete, $user);

        $this->deleteUser($user);
    }

    private function deleteUser(User $user): void
    {
        foreach ($this->userJobRepository->getJobsByUserGuid($user->getUserGuid()) as $job) {
            $this->deleteUserJobAssignmentOperation->forceDelete($job);
        }

        $lspUser = $this->lspUserRepository->findByUser($user);

        if ($lspUser !== null) {
            $this->lspUserRepository->delete($lspUser);
        }

        $this->userRepository->delete($user);

        $this->logger->info(
            'E1637',
            'Audit: {message}',
            [
                'message' => sprintf('User (login: "%s") was deleted', $user->getLogin()),
                'user' => $user->getLogin(),
            ]
        );
    }
}
