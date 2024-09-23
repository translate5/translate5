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

use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Exception\AttemptToChangeLspForUserException;
use MittagQI\Translate5\User\Exception\AttemptToSetLspForNonJobCoordinatorException;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use MittagQI\Translate5\User\Exception\UserIsNotAuthorisedToAssignRoleException;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\DTO\ParentIdDto;
use MittagQI\Translate5\User\Operations\DTO\PasswordDto;
use MittagQI\Translate5\User\Operations\DTO\UpdateUserDto;
use MittagQI\Translate5\User\Validation\RolesValidator;
use MittagQI\Translate5\User\Validation\UserCustomerAssociationValidator;
use MittagQI\ZfExtended\Acl\SystemResource;
use REST_Controller_Request_Http as Request;
use Zend_Acl_Exception;
use ZfExtended_Acl;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Models_User;

class UpdateUserDtoFactory
{
    public function __construct(
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly UserRepository $userRepository,
        private readonly ZfExtended_Acl $acl,
        private readonly RolesValidator $rolesValidator,
        private readonly UserCustomerAssociationValidator $userCustomerAssociationValidator,
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
            ZfExtended_Acl::getInstance(),
            RolesValidator::create(),
            UserCustomerAssociationValidator::create(),
        );
    }

    /**
     * @param Request $request
     * @throws AttemptToSetLspForNonJobCoordinatorException
     * @throws CustomerDoesNotBelongToUserException
     * @throws PermissionExceptionInterface
     * @throws UserIsNotAuthorisedToAssignRoleException
     */
    public function fromRequest(Request $request): UpdateUserDto
    {
        $data = $request->getParam('data');
        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        if (isset($data['lsp'])) {
            throw new AttemptToChangeLspForUserException((int) $request->getParam('id'));
        }

        $roles = explode(',', trim($data['roles'] ?? '', ' ,'));

        $customerIds = array_filter(
            array_map(
                'intval',
                explode(',', trim($data['customers'] ?? '', ' ,'))
            )
        );

        $authUser = $this->userRepository->get($this->authentication->getUserId());

        $this->userCustomerAssociationValidator->assertUserCatAssignCustomers($authUser, $customerIds);

        $this->rolesValidator->assertUserCanSetRoles($authUser, $roles);

        $parentId = $this->resolveParentUserId($data['parentIds'] ?? null, $authUser);

        $parentIdDto = $parentId ? new ParentIdDto($parentId) : null;

        return new UpdateUserDto(
            $data['login'],
            $data['email'],
            $data['firstName'],
            $data['surName'],
            $data['gender'] ?? ZfExtended_Models_User::GENDER_NONE,
            $roles,
            $customerIds,
            array_key_exists('passwd', $data)
                ? new PasswordDto(null !== $data['passwd'] ? trim($data['passwd']) : null)
                : null,
            $parentIdDto,
            $data['locale'] ?? null,
        );
    }

    private function resolveParentUserId(?string $parentId, User $authUser): ?string
    {
        if ($this->canSeeAllUsers($authUser)) {
            return $parentId;
        }

        return null;
    }

    private function canSeeAllUsers(User $authUser): bool
    {
        try {
            return $this->acl->isInAllowedRoles(
                $authUser->getRoles(),
                SystemResource::ID,
                SystemResource::SEE_ALL_USERS
            );
        } catch (Zend_Acl_Exception) {
            return false;
        }
    }
}