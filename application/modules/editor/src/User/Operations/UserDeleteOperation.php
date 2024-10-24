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
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Feasibility\ForceUserActionFeasibilityAssert;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssert;
use MittagQI\Translate5\User\Contract\UserDeleteOperationInterface;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\UserJob\Contract\DeleteUserJobAssignmentOperationInterface;
use MittagQI\Translate5\UserJob\Operation\DeleteUserJobAssignmentOperation;
use Zend_Registry;
use ZfExtended_Logger;

final class UserDeleteOperation implements UserDeleteOperationInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly ActionFeasibilityAssertInterface $userFeasibilityAssert,
        private readonly ActionFeasibilityAssertInterface $forceUserFeasibilityAssert,
        private readonly DeleteUserJobAssignmentOperationInterface $deleteUserJobAssignmentOperation,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            new UserRepository(),
            UserJobRepository::create(),
            UserActionFeasibilityAssert::create(),
            ForceUserActionFeasibilityAssert::create(),
            DeleteUserJobAssignmentOperation::create(),
            Zend_Registry::get('logger')->cloneMe('user.delete'),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function delete(User $user): void
    {
        $this->userFeasibilityAssert->assertAllowed(Action::Delete, $user);

        $this->deleteUser($user);
    }

    /**
     * {@inheritDoc}
     */
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

        $this->userRepository->delete($user);

        $this->logger->info(
            'E1637',
            'User audit: {message}',
            [
                'message' => sprintf('User (login: "%s") was deleted', $user->getLogin()),
                'user' => $user->getLogin(),
            ]
        );
    }
}
