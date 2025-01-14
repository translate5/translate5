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

namespace MittagQI\Translate5\User\Operations\Factory;

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\CoordinatorGroupAction;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\CoordinatorGroupActionPermissionAssert;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupRepositoryInterface;
use MittagQI\Translate5\Repository\CoordinatorGroupRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Exception\AttemptToSetCoordinatorGroupForNonJobCoordinatorException;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\DTO\CreateUserDto;
use REST_Controller_Request_Http as Request;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Models_User;
use ZfExtended_Utils;

class CreateUserDtoFactory
{
    public function __construct(
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly UserRepository $userRepository,
        private readonly CoordinatorGroupRepositoryInterface $coordinatorGroupRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly ActionPermissionAssertInterface $coordinatorGroupPermissionAssert,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            ZfExtended_Authentication::getInstance(),
            new UserRepository(),
            CoordinatorGroupRepository::create(),
            JobCoordinatorRepository::create(),
            CoordinatorGroupActionPermissionAssert::create(),
        );
    }

    /**
     * @throws AttemptToSetCoordinatorGroupForNonJobCoordinatorException
     * @throws PermissionExceptionInterface
     * @throws \MittagQI\Translate5\CoordinatorGroup\Exception\CoordinatorGroupNotFoundException
     */
    public function fromRequest(Request $request): CreateUserDto
    {
        $data = $request->getParam('data');
        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        $roles = explode(',', trim($data['roles'] ?? '', ' ,'));

        if (! in_array(Roles::JOB_COORDINATOR, $roles) && isset($data['coordinatorGroup'])) {
            throw new AttemptToSetCoordinatorGroupForNonJobCoordinatorException();
        }

        $customerIds = array_filter(
            array_map(
                'intval',
                explode(',', trim($data['customers'] ?? '', ' ,'))
            )
        );

        $authUser = $this->userRepository->get($this->authentication->getUserId());

        $coordinatorGroup = $this->fetchCoordinatorGroupForAssignment($data['coordinatorGroup'] ?? null, $authUser);

        $coordinatorGroupId = null === $coordinatorGroup ? null : (int) $coordinatorGroup->getId();

        $guid = ZfExtended_Utils::guid(true);

        return new CreateUserDto(
            $guid,
            $data['login'],
            $data['email'],
            $data['firstName'],
            $data['surName'],
            $data['gender'] ?? ZfExtended_Models_User::GENDER_NONE,
            $roles,
            $customerIds,
            $coordinatorGroupId,
            isset($data['passwd']) ? trim($data['passwd']) : null,
            $data['locale'] ?? null,
        );
    }

    /**
     * @throws PermissionExceptionInterface
     * @throws \MittagQI\Translate5\CoordinatorGroup\Exception\CoordinatorGroupNotFoundException
     */
    private function fetchCoordinatorGroupForAssignment(?int $groupId, User $authUser): ?CoordinatorGroup
    {
        if (null !== $groupId) {
            $coordinatorGroup = $this->coordinatorGroupRepository->get($groupId);

            $this->coordinatorGroupPermissionAssert->assertGranted(
                CoordinatorGroupAction::Read,
                $coordinatorGroup,
                new PermissionAssertContext($authUser)
            );

            return $coordinatorGroup;
        }

        $coordinator = $this->coordinatorRepository->findByUser($authUser);

        return $coordinator?->group;
    }
}
