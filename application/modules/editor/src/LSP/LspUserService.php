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

namespace MittagQI\Translate5\LSP;

use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\ZfExtended\Acl\Roles;
use ZfExtended_Models_User;

class LspUserService
{
    public function __construct(
        private readonly LspRepository $lspRepository,
        private readonly JobCoordinatorRepository $jcRepository,
        private readonly LspUserRepository $lspUserRepository,
    ) {
    }

    public static function create(): self
    {
        $lspRepository = LspRepository::create();
        $lspUserRepository = new LspUserRepository();

        return new self(
            LspRepository::create(),
            new JobCoordinatorRepository(
                $lspRepository,
                $lspUserRepository
            ),
            $lspUserRepository,
        );
    }

    public function findLspUserBy(ZfExtended_Models_User $user): ?LspUser
    {
        return $this->lspUserRepository->findByUser($user);
    }

    public function findCoordinatorBy(ZfExtended_Models_User $user): ?JobCoordinator
    {
        return $this->jcRepository->findByUser($user);
    }

    public function getCoordinatorsCountFor(LanguageServiceProvider $lsp): int
    {
        return $this->jcRepository->getCoordinatorsCount($lsp);
    }

    /**
     * @return iterable<ZfExtended_Models_User>
     */
    public function getAccessibleUsers(JobCoordinator $coordinator): iterable
    {
        $lspUsers = $this->lspRepository->getUsers($coordinator->lsp);

        foreach ($lspUsers as $lspUser) {
            yield $lspUser;
        }

        $subCoordinators = $this->jcRepository->getSubLspJobCoordinators($coordinator);

        foreach ($subCoordinators as $subCoordinator) {
            yield $subCoordinator->user;
        }
    }

    public function isUserAccessibleFor(ZfExtended_Models_User $user, ZfExtended_Models_User $manager): bool
    {
        $roles = $manager->getRoles();

        if (array_intersect([Roles::ADMIN, Roles::SYSTEMADMIN], $roles)) {
            return true;
        }

        $coordinator = $this->findCoordinatorBy($manager);

        if (null === $coordinator) {
            return true;
        }

        foreach ($this->getAccessibleUsers($coordinator) as $accessibleUser) {
            if ($accessibleUser->getId() === $user->getId()) {
                return true;
            }
        }

        return false;
    }
}
