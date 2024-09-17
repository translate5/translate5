<?php

namespace MittagQI\Translate5\User\Validation;

use MittagQI\Translate5\LSP\Exception\CantCreateCoordinatorFromUserException;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\User\Exception\InvalidParentUserProvidedForJobCoordinatorException;
use MittagQI\Translate5\User\Exception\InvalidParentUserProvidedForLspUserException;
use MittagQI\Translate5\User\Model\User;
use ZfExtended_Models_User;

class ParentUserValidator
{
    public function __construct(
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
            new LspUserRepository(),
            JobCoordinatorRepository::create(),
        );
    }

    /**
     * @throws InvalidParentUserProvidedForJobCoordinatorException
     * @throws InvalidParentUserProvidedForLspUserException
     */
    public function assertUserCanBeSetAsParentTo(User $parentUser, ZfExtended_Models_User $childUser): void
    {
        $childLspUser = $this->lspUserRepository->findByUser($childUser);

        if (null === $childLspUser) {
            return;
        }

        try {
            JobCoordinator::fromLspUser($childLspUser);

            $childUserIsCoordinator = true;
        } catch (CantCreateCoordinatorFromUserException) {
            $childUserIsCoordinator = false;
        }

        $parentCoordinator = $this->coordinatorRepository->findByUser($parentUser);

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
}
