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

use MittagQI\Translate5\JobAssignment\LspJob\Contract\DeleteLspJobAssignmentOperationInterface;
use MittagQI\Translate5\JobAssignment\LspJob\Operation\DeleteLspJobAssignmentOperation;
use MittagQI\Translate5\LSP\Contract\LspDeleteOperationInterface;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\LastCoordinatorException;
use MittagQI\Translate5\User\Contract\UserDeleteOperationInterface;
use MittagQI\Translate5\User\Operations\UserDeleteOperation;
use Zend_Registry;
use ZfExtended_Logger;

final class LspDeleteOperation implements LspDeleteOperationInterface
{
    public function __construct(
        private readonly LspRepositoryInterface $lspRepository,
        private readonly LspUserRepositoryInterface $lspUserRepository,
        private readonly LspJobRepository $lspJobRepository,
        private readonly UserRepository $userRepository,
        private readonly UserDeleteOperationInterface $deleteUserOperation,
        private readonly DeleteLspJobAssignmentOperationInterface $lspJobDeleteOperation,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            LspRepository::create(),
            LspUserRepository::create(),
            LspJobRepository::create(),
            new UserRepository(),
            UserDeleteOperation::create(),
            DeleteLspJobAssignmentOperation::create(),
            Zend_Registry::get('logger')->cloneMe('lsp.delete')
        );
    }

    public function deleteLsp(LanguageServiceProvider $lsp): void
    {
        $this->deleteLspJobs($lsp);

        $this->deleteLspUsers($lsp);

        foreach ($this->lspRepository->getSubLspList($lsp) as $subLsp) {
            $this->deleteLsp($subLsp);
        }

        $this->lspRepository->delete($lsp);

        $this->logger->info(
            'E1637',
            'Audit: {message}',
            [
                'message' => sprintf('LSP "%s" was deleted', $lsp->getName()),
                'lsp' => $lsp->getName(),
            ]
        );
    }

    public function deleteLspJobs(LanguageServiceProvider $lsp): void
    {
        $lspJobs = $this->lspJobRepository->getLspJobs((int) $lsp->getId());

        foreach ($lspJobs as $lspJob) {
            $this->lspJobDeleteOperation->forceDelete($lspJob);
        }
    }

    public function deleteLspUsers(LanguageServiceProvider $lsp): void
    {
        $usersOfLsp = $this->lspUserRepository->getUsers((int) $lsp->getId());

        foreach ($usersOfLsp as $user) {
            try {
                $this->deleteUserOperation->forceDelete($user);
            } catch (LastCoordinatorException) {
                $this->userRepository->delete($user);

                $this->logger->info(
                    'E1637',
                    'Audit: {message}',
                    [
                        'message' => sprintf('User (login: "%s") was deleted', $user->getLogin()),
                        'user' => $user->getLogin(),
                    ]
                );
            }
        }
    }
}
