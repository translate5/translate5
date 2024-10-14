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

namespace MittagQI\Translate5\UserJob\Operation\WithAuthentication;

use editor_Models_TaskUserAssoc as UserJob;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Exception\InexistentUserException;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Task\ActionAssert\Permission\TaskActionPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use MittagQI\Translate5\UserJob\Contract\CreateUserJobOperationInterface;
use MittagQI\Translate5\UserJob\Operation\DTO\NewUserJobDto;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_NotAuthenticatedException;

class CreateUserJobAssignmentOperation implements CreateUserJobOperationInterface
{
    public function __construct(
        private readonly CreateUserJobOperationInterface $operation,
        private readonly ActionPermissionAssertInterface $userPermissionAssert,
        private readonly ActionPermissionAssertInterface $taskPermissionAssert,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly UserRepository $userRepository,
        private readonly TaskRepository $taskRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            \MittagQI\Translate5\UserJob\Operation\CreateUserJobAssignmentOperation::create(),
            UserActionPermissionAssert::create(),
            TaskActionPermissionAssert::create(),
            ZfExtended_Authentication::getInstance(),
            new UserRepository(),
            new TaskRepository(),
        );
    }
    public function assignJob(NewUserJobDto $dto): UserJob
    {
        try {
            $authUser = $this->userRepository->get($this->authentication->getUserId());
        } catch (InexistentUserException) {
            throw new ZfExtended_NotAuthenticatedException();
        }

        $context = new PermissionAssertContext($authUser);
        $task = $this->taskRepository->getByGuid($dto->taskGuid);
        $user = $this->userRepository->getByGuid($dto->userGuid);

        $this->taskPermissionAssert->assertGranted(Action::READ, $task, $context);
        $this->userPermissionAssert->assertGranted(Action::READ, $user, $context);


        return $this->operation->assignJob($dto);
    }
}