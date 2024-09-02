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

use MittagQI\Translate5\LSP\LspUserRepository;
use MittagQI\Translate5\User\Action\Action;
use MittagQI\Translate5\User\Action\FeasibilityCheck\Exception\LastCoordinatorException;
use MittagQI\Translate5\User\Action\FeasibilityCheck\Exception\PmInTaskException;
use MittagQI\Translate5\User\Action\FeasibilityCheck\Exception\UserIsNotEditableException;
use MittagQI\Translate5\User\Action\PermissionAudit\Exception\ClientRestrictionException;
use MittagQI\Translate5\User\Action\PermissionAudit\Exception\NotAccessibleForLspUserException;
use MittagQI\Translate5\User\Action\PermissionAudit\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\User\Action\PermissionAudit\PermissionAuditContext;
use MittagQI\Translate5\User\Action\PermissionAudit\UserActionPermissionAuditor;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\UserService;

class Editor_UserController extends ZfExtended_UserController
{
    protected $entityClass = User::class;

    private UserActionPermissionAuditor $permissionAuditor;

    public function init(): void
    {
        parent::init();
        $this->permissionAuditor = UserActionPermissionAuditor::create();
    }

    public function getAction()
    {
        parent::getAction();

        $this->permissionAuditor->assertGranted(
            Action::READ,
            $this->entity,
            new PermissionAuditContext(ZfExtended_Authentication::getInstance()->getUser())
        );

        $lspUserRepo = new LspUserRepository();

        // @phpstan-ignore-next-line
        $this->view->rows->lsp = $lspUserRepo->findByUser($this->entity)?->lsp->getId();
    }

    public function indexAction()
    {
        $rows = $this->entity->loadAll();

        $lspUserRepo = new LspUserRepository();
        $userIdToLspIdMap = $lspUserRepo->getUserIdToLspIdMap();

        $userModel = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $authUser = ZfExtended_Authentication::getInstance()->getUser();
        $editableRoles = $authUser->getSetableRoles();

        foreach ($rows as $key => $row) {
            $userModel->init(
                new Zend_Db_Table_Row(
                    [
                        'table' => $userModel->db,
                        'data' => $row,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            try {
                $this->permissionAuditor->assertGranted(
                    Action::READ,
                    clone $userModel,
                    new PermissionAuditContext($authUser)
                );
            } catch (PermissionExceptionInterface) {
                unset($rows[$key]);

                continue;
            }

            $notEditableUser = (int) $row['id'] !== (int) $authUser->getId()
                && $row['editable'] === '1'
                && ! empty($row['roles'])
                && $row['roles'] !== ','
                && ! empty(array_diff(explode(',', $row['roles']), $editableRoles))
            ;

            if ($notEditableUser) {
                $rows[$key]['editable'] = '0';
            }

            $rows[$key]['lsp'] = $userIdToLspIdMap[$row['id']] ?? null;
        }

        $this->view->rows = $rows;
        $this->view->total = count($rows);

        $this->csvToArray();
    }

    public function putAction(): void
    {
        $this->entityLoad();

        $this->permissionAuditor->assertGranted(
            Action::UPDATE,
            $this->entity,
            new PermissionAuditContext(ZfExtended_Authentication::getInstance()->getUser())
        );

        parent::putAction();
    }

    public function deleteAction(): void
    {
        $this->entityLoad();

        try {
            $this->permissionAuditor->assertGranted(
                Action::DELETE,
                $this->entity,
                new PermissionAuditContext(ZfExtended_Authentication::getInstance()->getUser())
            );

            UserService::create()->delete($this->entity);
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
