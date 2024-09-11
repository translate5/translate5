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

namespace MittagQI\Translate5\LSP\Operations;

use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\User\Contract\UserDeleteOperationInterface;
use MittagQI\Translate5\User\Operations\UserDeleteOperation;

class LspDeleteOperation
{
    public function __construct(
        private readonly LspRepositoryInterface $lspRepository,
        private readonly UserDeleteOperationInterface $userDeleteOperation,
        private readonly LspUserRepositoryInterface $lspUserRepository,
    ) {
    }

    public static function create(?LspRepository $lspRepository = null): self
    {
        $lspRepository = $lspRepository ?? LspRepository::create();

        return new self(
            $lspRepository,
            UserDeleteOperation::create(),
            new LspUserRepository(),
        );
    }

    public function deleteLsp(LanguageServiceProvider $lsp): void
    {
        $usersOfLsp = $this->lspUserRepository->getUsers($lsp);

        foreach ($usersOfLsp as $user) {
            $this->userDeleteOperation->forceDelete($user);
        }

        foreach ($this->lspRepository->getSubLspList($lsp) as $subLsp) {
            $this->deleteLsp($subLsp);
        }

        $this->lspRepository->delete($lsp);
    }
}
