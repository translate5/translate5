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

namespace MittagQI\Translate5\User\Operations\WithAuthentication;

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\LSP\ActionAssert\Action;
use MittagQI\Translate5\LSP\ActionAssert\Permission\LspActionPermissionAssert;
use MittagQI\Translate5\LSP\ActionAssert\Permission\LspActionPermissionAssertInterface;
use MittagQI\Translate5\LSP\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Contract\UserCreateOperationInterface;
use MittagQI\Translate5\User\DTO\CreateUserDto;
use MittagQI\Translate5\User\Exception\AttemptToSetLspForNonJobCoordinatorException;
use MittagQI\Translate5\User\Exception\UserExceptionInterface;
use MittagQI\Translate5\User\Model\User;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_ValidateException;

final class UserCreateOperation implements UserCreateOperationInterface
{
    public function __construct(
        private readonly UserCreateOperationInterface $operation,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly LspRepositoryInterface $lspRepository,
        private readonly LspActionPermissionAssertInterface $lspPermissionAssert,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            \MittagQI\Translate5\User\Operations\UserCreateOperation::createWithAuthentication(),
            ZfExtended_Authentication::getInstance(),
            JobCoordinatorRepository::create(),
            LspRepository::create(),
            LspActionPermissionAssert::create(),
            new UserRepository(),
        );
    }

    /**
     * @throws UserExceptionInterface
     * @throws ZfExtended_ValidateException
     */
    public function createUser(CreateUserDto $dto): User
    {
        if (! in_array(Roles::JOB_COORDINATOR, $dto->roles) && null !== $dto->lsp) {
            throw new AttemptToSetLspForNonJobCoordinatorException();
        }

        $lsp = $this->fetchLspForAssignment($dto->lsp);

        $authUser = $this->userRepository->get($this->authentication->getUserId());

        if (null !== $lsp) {
            $this->lspPermissionAssert->assertGranted(
                Action::READ,
                $lsp,
                new PermissionAssertContext($authUser)
            );
        }

        $lspId = null === $lsp ? null : (int) $lsp->getId();

        $dto = new CreateUserDto(
            $dto->guid,
            $dto->login,
            $dto->email,
            $dto->firstName,
            $dto->surName,
            $dto->gender,
            $dto->roles,
            $dto->customers,
            $lspId,
            $dto->password,
            $dto->parentId,
            $dto->locale,
        );

        return $this->operation->createUser($dto);
    }

    private function fetchLspForAssignment(?int $lspId): ?LanguageServiceProvider
    {
        if (null !== $lspId) {
            return $this->lspRepository->get($lspId);
        }

        $coordinator = $this->coordinatorRepository->findByUser($this->authentication->getUser());

        if ($coordinator) {
            return $coordinator->lsp;
        }

        return null;
    }
}
