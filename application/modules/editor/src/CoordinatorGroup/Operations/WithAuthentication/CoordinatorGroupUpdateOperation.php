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

namespace MittagQI\Translate5\CoordinatorGroup\Operations\WithAuthentication;

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\CoordinatorGroupAction;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\CoordinatorGroupActionPermissionAssert;
use MittagQI\Translate5\CoordinatorGroup\Contract\CoordinatorGroupUpdateOperationInterface;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\CoordinatorGroup\Operations\DTO\UpdateCoordinatorGroupDto;
use MittagQI\Translate5\Repository\UserRepository;
use Zend_Registry;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Logger;

final class CoordinatorGroupUpdateOperation implements CoordinatorGroupUpdateOperationInterface
{
    public function __construct(
        private readonly CoordinatorGroupUpdateOperationInterface $operation,
        private readonly ActionPermissionAssertInterface $permissionAssert,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly UserRepository $userRepository,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            \MittagQI\Translate5\CoordinatorGroup\Operations\CoordinatorGroupUpdateOperation::create(),
            CoordinatorGroupActionPermissionAssert::create(),
            ZfExtended_Authentication::getInstance(),
            new UserRepository(),
            Zend_Registry::get('logger')->cloneMe('coordinatorGroup.update'),
        );
    }

    public function updateCoordinatorGroup(CoordinatorGroup $group, UpdateCoordinatorGroupDto $dto): void
    {
        $authUser = $this->userRepository->get($this->authentication->getUserId());

        try {
            $this->permissionAssert->assertGranted(CoordinatorGroupAction::Update, $group, new PermissionAssertContext($authUser));

            $this->logger->info(
                'E1637',
                'Audit: {message}',
                [
                    'message' => sprintf(
                        'Attempt to update Coordinator group (name: "%s") by AuthUser (guid: %s) was granted',
                        $group->getName(),
                        $authUser->getUserGuid()
                    ),
                    'coordinatorGroup' => $group->getName(),
                    'authUser' => $authUser->getLogin(),
                    'authUserGuid' => $authUser->getUserGuid(),
                ]
            );
        } catch (PermissionExceptionInterface $e) {
            $this->logger->warn(
                'E1637',
                'Audit: {message}',
                [
                    'message' => sprintf(
                        'Attempt to update Coordinator group (name: "%s") by AuthUser (guid: %s) was not granted',
                        $group->getName(),
                        $authUser->getUserGuid()
                    ),
                    'coordinatorGroup' => $group->getName(),
                    'authUser' => $authUser->getLogin(),
                    'authUserGuid' => $authUser->getUserGuid(),
                    'reason' => $e::class,
                ]
            );

            throw $e;
        }

        $this->operation->updateCoordinatorGroup($group, $dto);
    }
}
