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

use MittagQI\Translate5\LSP\LspUserService;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\PermissionAudit\Exception\ClientRestrictionException;
use MittagQI\Translate5\User\PermissionAudit\Exception\LastCoordinatorException;
use MittagQI\Translate5\User\PermissionAudit\Exception\NotAccessibleForLspUserException;
use MittagQI\Translate5\User\PermissionAudit\Exception\PmInTaskException;
use MittagQI\Translate5\User\PermissionAudit\Exception\UserIsNotEditableException;
use MittagQI\Translate5\User\UserService;

class Editor_UserController extends ZfExtended_UserController
{
    protected $entityClass = User::class;

    private LspUserService $lspUserService;

    public function init(): void
    {
        parent::init();
        $this->lspUserService = LspUserService::create();
    }

    public function deleteAction(): void
    {
        $this->entityLoad();

        $userService = new UserService($this->lspUserService, new UserRepository());

        try {
            $userService->delete($this->entity, ZfExtended_Authentication::getInstance()->getUser());
        } catch (PmInTaskException $e) {
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1048' => 'The user can not be deleted, he is PM in one or more tasks.',
            ]);

            throw ZfExtended_Models_Entity_Conflict::createResponse(
                'E1048',
                [
                    'Der Benutzer kann nicht gelöscht werden, er ist PM in einer oder mehreren Aufgaben.',
                ],
                [
                    'tasks' => join(', ', $e->taskGuids),
                    'user' => $this->entity->getUserGuid(),
                    'userLogin' => $this->entity->getLogin(),
                    'userEmail' => $this->entity->getEmail(),
                ]
            );
        } catch (LastCoordinatorException $e) {
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1626' => 'The user can not be deleted, he is last Job Coordinator of LSP "{lsp}".',
            ]);

            throw ZfExtended_Models_Entity_Conflict::createResponse(
                'E1626',
                [
                    'Der Benutzer kann nicht gelöscht werden, er ist der letzte Job-Koordinator des LSP "{lsp}".',
                ],
                [
                    'lsp' => $e->coordinator->lsp->getName(),
                    'lspId' => $e->coordinator->lsp->getId(),
                    'user' => $this->entity->getUserGuid(),
                    'userLogin' => $this->entity->getLogin(),
                    'userEmail' => $this->entity->getEmail(),
                ]
            );
        } catch (NotAccessibleForLspUserException $e) {
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1627' => 'Job coordinator can not delete this user.',
            ]);

            throw ZfExtended_Models_Entity_Conflict::createResponse(
                'E1627',
                [
                    'Der Jobkoordinator kann diesen Benutzer nicht löschen.',
                ],
                [
                    'coordinator' => $e->coordinator->guid,
                    'lsp' => $e->coordinator->lsp->getName(),
                    'lspId' => $e->coordinator->lsp->getId(),
                    'user' => $this->entity->getUserGuid(),
                    'userLogin' => $this->entity->getLogin(),
                    'userEmail' => $this->entity->getEmail(),
                ]
            );
        } catch (UserIsNotEditableException) {
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1628' => 'Tried to manipulate a not editable user.',
            ]);

            throw ZfExtended_Models_Entity_Conflict::createResponse(
                'E1628',
                [
                    'Versucht, einen nicht bearbeitbaren Benutzer zu manipulieren.',
                ],
                [
                    'user' => $this->entity->getUserGuid(),
                    'userLogin' => $this->entity->getLogin(),
                    'userEmail' => $this->entity->getEmail(),
                ]
            );
        } catch (ClientRestrictionException) {
            throw new ZfExtended_NoAccessException('Deletion of User is not allowed due to client-restriction');
        } catch (Exception $e) {
            throw new ZfExtended_NoAccessException(previous: $e);
        }
    }
}
