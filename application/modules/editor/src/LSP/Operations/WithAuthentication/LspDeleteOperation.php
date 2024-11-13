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

namespace MittagQI\Translate5\LSP\Operations\WithAuthentication;

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\LSP\ActionAssert\Permission\LspActionPermissionAssert;
use MittagQI\Translate5\LSP\Contract\LspDeleteOperationInterface;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\UserRepository;
use Zend_Registry;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Logger;

final class LspDeleteOperation implements LspDeleteOperationInterface
{
    public function __construct(
        private readonly LspDeleteOperationInterface $operation,
        private readonly ActionPermissionAssertInterface $permissionAssert,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly UserRepository $userRepository,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            \MittagQI\Translate5\LSP\Operations\LspDeleteOperation::create(),
            LspActionPermissionAssert::create(),
            ZfExtended_Authentication::getInstance(),
            new UserRepository(),
            Zend_Registry::get('logger')->cloneMe('lsp.delete')
        );
    }

    public function deleteLsp(LanguageServiceProvider $lsp): void
    {
        $authUser = $this->userRepository->get($this->authentication->getUserId());

        try {
            $this->permissionAssert->assertGranted(Action::Delete, $lsp, new PermissionAssertContext($authUser));

            $this->logger->info(
                'E1637',
                'Audit: {message}',
                [
                    'message' => sprintf(
                        'Attempt to delete LSP (name: "%s") by AuthUser (guid: %s) was granted',
                        $lsp->getName(),
                        $authUser->getUserGuid()
                    ),
                    'lsp' => $lsp->getName(),
                    'authUser' => $authUser->getLogin(),
                    'authUserGuid' => $authUser->getUserGuid(),
                ]
            );
        } catch (PermissionExceptionInterface $e) {
            $this->logger->warn(
                'E1637',
                'Audit: {message}',
                [
                    'message' => sprintf(
                        'Attempt to delete LSP (name: "%s") by AuthUser (guid: %s) was not granted',
                        $lsp->getName(),
                        $authUser->getUserGuid()
                    ),
                    'lsp' => $lsp->getName(),
                    'authUser' => $authUser->getLogin(),
                    'authUserGuid' => $authUser->getUserGuid(),
                    'reason' => $e::class,
                ]
            );

            throw $e;
        }

        $this->operation->deleteLsp($lsp);
    }
}
