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

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\Exception\InexistentCustomerException;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\User\ActionAssert\Action;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\LastCoordinatorException;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\PmInTaskException;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\UserIsNotEditableException;
use MittagQI\Translate5\User\ActionAssert\Permission\Exception\ClientRestrictionException;
use MittagQI\Translate5\User\ActionAssert\Permission\Exception\NotAccessibleLspUserException;
use MittagQI\Translate5\User\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\User\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use MittagQI\Translate5\User\DTO\CreateUserDto;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use MittagQI\Translate5\User\Exception\RoleConflictWithRoleThatPopulatedToRolesetException;
use MittagQI\Translate5\User\Exception\RolesetHasConflictingRolesException;
use MittagQI\Translate5\User\Exception\UserIsNotAuthorisedToAssignRoleException;
use MittagQI\Translate5\User\Service\UserCreateService;
use MittagQI\Translate5\User\Service\UserCustomerAssociationUpdateService;
use MittagQI\Translate5\User\Service\UserDeleteService;
use MittagQI\Translate5\User\Service\UserRolesUpdateService;

class Editor_UserController extends ZfExtended_UserController
{
    protected $entityClass = ZfExtended_Models_User::class;

    private UserActionPermissionAssert $permissionAssert;

    public function init(): void
    {
        parent::init();
        $this->permissionAssert = UserActionPermissionAssert::create();

        ZfExtended_Models_Entity_Conflict::addCodes([
            'E2002' => 'No object of type "{0}" was found by key "{1}"',
            'E2003' => 'Wrong value',
            'E1048' => 'The user can not be deleted, he is PM in one or more tasks.',
            'E1626' => 'The user can not be deleted, he is last Job Coordinator of LSP "{lsp}".',
            'E1627' => 'Attempts to manipulate not accessible user.',
            'E1628' => 'Tried to manipulate a not editable user.',
            'E1630' => 'You can not set role {role} with one of the following roles: {roles}',
        ], 'editor.user');
    }

    public function getAction()
    {
        parent::getAction();

        $this->permissionAssert->assertGranted(
            Action::READ,
            $this->entity,
            new PermissionAssertContext(ZfExtended_Authentication::getInstance()->getUser())
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
                $this->permissionAssert->assertGranted(
                    Action::READ,
                    clone $userModel,
                    new PermissionAssertContext($authUser)
                );
            } catch (PermissionExceptionInterface) {
                unset($rows[$key]);

                continue;
            }

            $notEditableUser = (int) $row['id'] !== (int) $authUser->getId()
                && $row['editable'] === '1'
                && ! empty($row['roles'])
                && $row['roles'] !== ','
                && ! empty(array_diff(explode(',', $row['roles']), $editableRoles));

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

        try {
            $authUser = ZfExtended_Authentication::getInstance()->getUser();

            $this->permissionAssert->assertGranted(
                Action::UPDATE,
                $this->entity,
                new PermissionAssertContext($authUser)
            );

            $this->decodePutData();

            if (! empty($this->data->lsp)) {
                throw ZfExtended_UnprocessableEntity::createResponse(
                    'E2003',
                    [
                        'lsp' => [
                            'Ein Wechsel des Sprachdienstleisters ist nicht zulässig.',
                        ],
                    ],
                );
            }

            $this->updateCustomers($authUser);
            $this->updateRoles($authUser);
        } catch (FeasibilityExceptionInterface) {
            throw ZfExtended_Models_Entity_Conflict::createResponse(
                'E1628',
                [
                    'Versucht, einen Benutzer zu manipulieren, der nicht bearbeitet werden kann.',
                ],
                [
                    'user' => $this->entity->getUserGuid(),
                    'userLogin' => $this->entity->getLogin(),
                    'userEmail' => $this->entity->getEmail(),
                ]
            );
        } catch (NotAccessibleLspUserException $e) {
            throw ZfExtended_Models_Entity_Conflict::createResponse(
                'E1627',
                [
                    'Versuch, einen nicht erreichbaren Benutzer zu manipulieren.',
                ],
                [
                    'coordinator' => $e->lspUser->guid,
                    'lsp' => $e->lspUser->lsp->getName(),
                    'lspId' => $e->lspUser->lsp->getId(),
                    'user' => $this->entity->getUserGuid(),
                    'userLogin' => $this->entity->getLogin(),
                    'userEmail' => $this->entity->getEmail(),
                ]
            );
        }

        // old controller code. will be refactored one the time comes, hopefully
        try {
            $this->processClientReferenceVersion();
            $this->setDataInEntity();
            if ($this->validate()) {
                $this->encryptPassword();
                $this->entity->save();
                $this->view->rows = $this->entity->getDataObject();
            }

            $this->handlePasswdMail();
            $this->credentialCleanup();

            if ($this->wasValid) {
                $this->csvToArray();
                $this->resetInvalidCounter();
            }

            $this->checkAndUpdateSession();
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            $this->handleLoginDuplicates($e);
        }
    }

    public function deleteAction(): void
    {
        $this->entityLoad();

        try {
            $this->permissionAssert->assertGranted(
                Action::DELETE,
                $this->entity,
                new PermissionAssertContext(
                    ZfExtended_Authentication::getInstance()->getUser()
                )
            );

            UserDeleteService::create()->delete($this->entity);
        } catch (PmInTaskException $e) {
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
        } catch (NotAccessibleLspUserException $e) {
            throw ZfExtended_Models_Entity_Conflict::createResponse(
                'E1627',
                [
                    'Versuch, einen nicht erreichbaren Benutzer zu manipulieren.',
                ],
                [
                    'lspUser' => $e->lspUser->guid,
                    'lsp' => $e->lspUser->lsp->getName(),
                    'lspId' => $e->lspUser->lsp->getId(),
                    'user' => $this->entity->getUserGuid(),
                    'userLogin' => $this->entity->getLogin(),
                    'userEmail' => $this->entity->getEmail(),
                ]
            );
        } catch (UserIsNotEditableException) {
            throw ZfExtended_Models_Entity_Conflict::createResponse(
                'E1628',
                [
                    'Versucht, einen Benutzer zu manipulieren, der nicht bearbeitet werden kann.',
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

    public function postAction()
    {
        try {
            $this->decodePutData();

            $this->entity = UserCreateService::create()->createUser(
                CreateUserDto::fromArray((array) $this->data)
            );
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            $this->handleLoginDuplicates($e);
        }

        $authUser = ZfExtended_Authentication::getInstance()->getUser();

        $this->updateCustomers($authUser);
        $this->updateRoles($authUser);
        $this->assignLsp($authUser);

        // make sure fields not processed by setDataInEntity
        unset($this->data->firstName);
        unset($this->data->surName);
        unset($this->data->login);
        unset($this->data->email);
        unset($this->data->gender);

        $this->setDataInEntity($this->postBlacklist);

        if ($this->validate()) {
            $this->encryptPassword();
            $this->entity->save();
            $this->view->rows = $this->entity->getDataObject();
        }

        $this->handlePasswdMail();
        $this->credentialCleanup();
        if ($this->wasValid) {
            $this->csvToArray();
        }
    }

    /**
     * @throws FeasibilityExceptionInterface
     */
    private function updateCustomers(ZfExtended_Models_User $authUser): void
    {
        $sentCustomerIds = array_filter(
            array_map(
                'intval',
                explode(',', trim($this->getDataField('customers'), ','))
            )
        );

        try {
            UserCustomerAssociationUpdateService::create()->updateAssociatedCustomersBy(
                $this->entity,
                $sentCustomerIds,
                $authUser
            );
        } catch (InexistentCustomerException $e) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E2002',
                [
                    'customers' => [
                        'Der referenzierte Kunde existiert nicht (mehr).',
                    ],
                ],
                [
                    editor_Models_Customer_Customer::class,
                    $e->customerId,
                ]
            );
        } catch (CustomerDoesNotBelongToUserException|CustomerDoesNotBelongToLspException $e) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E2003',
                [
                    'customers' => [
                        'Sie können den Kunden "{id}" hier nicht angeben',
                    ],
                ],
                [
                    'id' => $e->customerId,
                ]
            );
        }

        // make sure customers not processed by setDataInEntity
        unset($this->data->customers);
    }

    /**
     * @throws FeasibilityExceptionInterface
     */
    private function updateRoles(ZfExtended_Models_User $authUser): void
    {
        if (empty($this->data->roles)) {
            return;
        }

        $roles = explode(',', trim($this->data->roles, ','));

        try {
            UserRolesUpdateService::create()->updateRolesBy($this->entity, $roles, $authUser);
        } catch (RolesetHasConflictingRolesException $e) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E1630',
                [
                    'roles' => [
                        'Sie können die Rolle {role} nicht mit einer der folgenden Rollen festlegen: {roles}',
                    ],
                ],
                [
                    'role' => $e->role,
                    'roles' => join(', ', $e->conflictsWith),
                ]
            );
        } catch (RoleConflictWithRoleThatPopulatedToRolesetException $e) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E1630',
                [
                    'roles' => [
                        'Sie können die Rolle {role} nicht mit einer der folgenden Rollen festlegen: {roles}',
                    ],
                ],
                [
                    'role' => $e->role,
                    'roles' => join(', ', array_merge([$e->conflictsWith], $e->becauseOf)),
                    'conflictsWith' => $e->conflictsWith,
                    'becauseOf' => $e->becauseOf,
                ]
            );
        } catch (Zend_Acl_Exception|UserIsNotAuthorisedToAssignRoleException $e) {
            throw new ZfExtended_NoAccessException(previous: $e);
        }

        // make sure roles not processed by setDataInEntity
        unset($this->data->roles);
    }

    private function assignLsp(ZfExtended_Models_User $authUser): void
    {
        $jobCoordinatorRepo = JobCoordinatorRepository::create();
        $authCoordinator = $jobCoordinatorRepo->findByUser($authUser);
        $userIsCoordinator = in_array(Roles::JOB_COORDINATOR, $this->entity->getRoles());

        if (! $userIsCoordinator && ! $authCoordinator && empty($this->data->lsp)) {
            return;
        }

        // for lsp users lsp is set automatically from the coordinator
        if (! $userIsCoordinator && ! empty($this->data->lsp)) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E2003',
                [
                    'lsp' => [
                        'Ein Wechsel des Sprachdienstleisters ist nicht zulässig.',
                    ],
                ],
            );
        }

        // for coordinators lsp is mandatory
        if ($userIsCoordinator && empty($this->data->lsp)) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E2003',
                [
                    'lsp' => [
                        'Sprachdienstleister ist ein Pflichtfeld für die Rolle des Jobkoordinators.',
                    ],
                ],
            );
        }

        $lsp = (int) $this->fetchLspForAssignment($authCoordinator);


    }

    private function fetchLspForAssignment(?JobCoordinator $authCoordinator): LanguageServiceProvider
    {
        $lspRepository = LspRepository::create();

        if (! empty($this->data->lsp)) {
            return $lspRepository->get((int) $this->data->lsp);
        }

        if ($authCoordinator) {
            return $authCoordinator->lsp;
        }

        throw new \LogicException('Unexpected logic branch. Either lsp or coordinator must be set at this point.');
    }
}
