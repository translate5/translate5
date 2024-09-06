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
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\User\Contract\UserDeleteServiceInterface;
use MittagQI\Translate5\User\Service\UserDeleteService;

class LspService
{
    public function __construct(
        private readonly LspRepositoryInterface $lspRepository,
        private readonly UserDeleteServiceInterface $userDeleteService,
        private readonly LspUserRepositoryInterface $lspUserRepository,
    ) {
    }

    public static function create(): self
    {
        $lspRepository = LspRepository::create();

        return new self(
            $lspRepository,
            UserDeleteService::create(),
            new LspUserRepository(),
        );
    }

    public function getLsp(int $id): LanguageServiceProvider
    {
        return $this->lspRepository->get($id);
    }

    public function createLsp(string $name, ?string $description, ?JobCoordinator $coordinator): LanguageServiceProvider
    {
        $lsp = $this->lspRepository->getEmptyModel();
        $lsp->setName($name);
        $lsp->setDescription($description);

        if (null !== $coordinator) {
            $lsp->setParentId((int) $coordinator->lsp->getId());
        }

        $this->lspRepository->save($lsp);

        return $lsp;
    }

    public function updateInfoFields(LanguageServiceProvider $lsp, string $name, ?string $description): void
    {
        $lsp->setName($name);
        $lsp->setDescription($description);

        $this->lspRepository->save($lsp);
    }

    public function deleteLsp(LanguageServiceProvider $lsp): void
    {
        $usersOfLsp = $this->lspUserRepository->getUsers($lsp);

        foreach ($usersOfLsp as $user) {
            $this->userDeleteService->forceDelete($user);
        }

        foreach ($this->lspRepository->getSubLspList($lsp) as $subLsp) {
            $this->deleteLsp($subLsp);
        }

        $this->lspRepository->delete($lsp);
    }
}
