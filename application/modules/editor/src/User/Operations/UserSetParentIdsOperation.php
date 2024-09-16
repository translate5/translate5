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

use MittagQI\Translate5\LSP\Exception\CantCreateCoordinatorFromUserException;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Contract\UserSetParentIdsOperationInterface;
use MittagQI\Translate5\User\Exception\InvalidParentUserProvidedForJobCoordinatorException;
use MittagQI\Translate5\User\Exception\InvalidParentUserProvidedForLspUserException;
use MittagQI\Translate5\User\Exception\ProvidedParentIdCannotBeEvaluatedToUserException;
use MittagQI\Translate5\User\Model\User;
use Zend_Acl_Exception;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_ValidateException;

/**
 * Ment to be used to initialize parentIds for a user.
 * So only on User creation or in special cases where the parentIds need to be reinitialized.
 */
final class UserSetParentIdsOperation implements UserSetParentIdsOperationInterface
{
    /**
     * @var array<string, JobCoordinator>
     */
    private array $jobCoordinators = [];

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly LspUserRepositoryInterface $lspUserRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            new UserRepository(),
            new LspUserRepository(),
            JobCoordinatorRepository::create(),
        );
    }

    /**
     * @throws ProvidedParentIdCannotBeEvaluatedToUserException
     * @throws Zend_Acl_Exception
     * @throws ZfExtended_ValidateException
     */
    public function setParentIds(User $user, ?string $parentId): void
    {
        if (null === $parentId) {
            $user->setParentIds(',,');

            return;
        }

        try {
            $parentUser = $this->userRepository->resolveUser($parentId);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new ProvidedParentIdCannotBeEvaluatedToUserException($parentId);
        }

        $this->assertUserCanBeSetAsParentTo($parentUser, $user);

        $parentIds = $this->getParentIds($parentUser);

        $user->setParentIds(',' . implode(',', $parentIds) . ',');

        $user->validate();
    }

    private function assertUserCanBeSetAsParentTo(User $parentUser, User $childUser): void
    {
        $childLspUser = $this->lspUserRepository->findByUser($childUser);

        if (null === $childLspUser) {
            return;
        }

        try {
            JobCoordinator::fromLspUser($childLspUser);

            $childUserIsCoordinator =  true;
        } catch (CantCreateCoordinatorFromUserException) {
            $childUserIsCoordinator =  false;
        }

        $parentCoordinator = $this->fetchCoordinator($parentUser);

        if ($childUserIsCoordinator) {
            if (($parentUser->isPm() || $parentUser->isAdmin()) && $childLspUser->lsp->isDirectLsp()) {
                return;
            }

            if (null === $parentCoordinator) {
                throw new InvalidParentUserProvidedForJobCoordinatorException();
            }

            if ($parentCoordinator->isCoordinatorOf($childLspUser->lsp)) {
                return;
            }

            if ($childLspUser->lsp->isSubLspOf($parentCoordinator->lsp)) {
                return;
            }

            throw new InvalidParentUserProvidedForJobCoordinatorException();
        }

        if (null === $parentCoordinator || ! $parentCoordinator->isCoordinatorOf($childLspUser->lsp)) {
            throw new InvalidParentUserProvidedForLspUserException();
        }
    }

    private function fetchCoordinator(User $user): ?JobCoordinator
    {
        if (! isset($this->jobCoordinators[$user->getUserGuid()])) {
            $this->jobCoordinators[$user->getUserGuid()] = $this->coordinatorRepository->findByUser($user);
        }

        return $this->jobCoordinators[$user->getUserGuid()];
    }

    /**
     * @return int[]
     */
    private function getParentIds(User $parentUser): array
    {
        $parentCoordinator = $this->fetchCoordinator($parentUser);

        if (null !== $parentCoordinator) {
            return [(int) $parentUser->getId()];
        }

        $parentIds = [];

        if (! empty($parentUser->getParentIds())) {
            $parentIds = array_map(
                'intval',
                explode(',', trim($parentUser->getParentIds(), ' ,'))
            );
        }

        $parentIds[] = (int) $parentUser->getId();

        return $parentIds;
    }
}
