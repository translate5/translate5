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
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\LSP\ActionAssert\Permission\LspActionPermissionAssert;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Exception\AttemptToSetLspForNonJobCoordinatorException;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use MittagQI\Translate5\User\Exception\UserIsNotAuthorisedToAssignRoleException;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\DTO\CreateUserDto;
use MittagQI\Translate5\Acl\Validation\RolesValidator;
use MittagQI\Translate5\User\Validation\UserCustomerAssociationValidator;
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
        private readonly LspRepositoryInterface $lspRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly ActionPermissionAssertInterface $lspPermissionAssert,
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
            LspRepository::create(),
            JobCoordinatorRepository::create(),
            LspActionPermissionAssert::create(),
            RolesValidator::create(),
            UserCustomerAssociationValidator::create(),
        );
    }

    /**
     * @throws AttemptToSetLspForNonJobCoordinatorException
     * @throws CustomerDoesNotBelongToUserException
     * @throws PermissionExceptionInterface
     * @throws UserIsNotAuthorisedToAssignRoleException
     */
    public function fromRequest(Request $request): CreateUserDto
    {
        $data = $request->getParam('data');
        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        $roles = explode(',', trim($data['roles'] ?? '', ' ,'));

        if (! in_array(Roles::JOB_COORDINATOR, $roles) && isset($data['lsp'])) {
            throw new AttemptToSetLspForNonJobCoordinatorException();
        }

        $customerIds = array_filter(
            array_map(
                'intval',
                explode(',', trim($data['customers'] ?? '', ' ,'))
            )
        );

        $authUser = $this->userRepository->get($this->authentication->getUserId());

        $this->userCustomerAssociationValidator->assertUserCanAssignCustomers($authUser, $customerIds);

        $this->rolesValidator->assertUserCanSetRoles($authUser, $roles);

        $lsp = $this->fetchLspForAssignment($data['lsp'] ?? null, $authUser);

        $lspId = null === $lsp ? null : (int) $lsp->getId();

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
            $lspId,
            isset($data['passwd']) ? trim($data['passwd']) : null,
            $data['locale'] ?? null,
        );
    }

    private function fetchLspForAssignment(?int $lspId, User $authUser): ?LanguageServiceProvider
    {
        if (null !== $lspId) {
            $lsp = $this->lspRepository->get($lspId);

            $this->lspPermissionAssert->assertGranted(
                Action::Read,
                $lsp,
                new PermissionAssertContext($authUser)
            );

            return $lsp;
        }

        $coordinator = $this->coordinatorRepository->findByUser($authUser);

        if ($coordinator) {
            return $coordinator->lsp;
        }

        return null;
    }
}
