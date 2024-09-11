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
use MittagQI\Translate5\Repository\UserRepository;
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
use MittagQI\Translate5\User\DTO\UpdateUserDto;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use MittagQI\Translate5\User\Exception\GuidAlreadyInUseException;
use MittagQI\Translate5\User\Exception\LoginAlreadyInUseException;
use MittagQI\Translate5\User\Exception\RoleConflictWithRoleThatPopulatedToRolesetException;
use MittagQI\Translate5\User\Exception\RolesetHasConflictingRolesException;
use MittagQI\Translate5\User\Exception\UnableToAssignJobCoordinatorRoleToExistingUserException;
use MittagQI\Translate5\User\Exception\UserIsNotAuthorisedToAssignRoleException;
use MittagQI\Translate5\User\Mail\ResetPasswordEmail;
use MittagQI\Translate5\User\Operations\UserCreateOperation;
use MittagQI\Translate5\User\Operations\UserCustomerAssociationUpdateOperation;
use MittagQI\Translate5\User\Operations\UserDeleteOperation;
use MittagQI\Translate5\User\Operations\UserInitRolesOperation;
use MittagQI\Translate5\User\Operations\UserUpdateDataOperation;
use MittagQI\Translate5\User\Operations\UserUpdateParentIdsOperation;
use MittagQI\Translate5\User\Operations\UserUpdatePasswordOperation;
use MittagQI\Translate5\User\Operations\UserUpdateRolesOperation;
use MittagQI\ZfExtended\Acl\SystemResource;

class Editor_UserController extends ZfExtended_UserController
{
    protected $entityClass = ZfExtended_Models_User::class;

    private UserActionPermissionAssert $permissionAssert;

    public function init(): void
    {
        $this->_filterTypeMap = [
            'customers' => [
                'list' => 'listCommaSeparated',
                'string' => new ZfExtended_Models_Filter_JoinHard(
                    'editor_Models_Db_Customer',
                    'name',
                    'id',
                    'customers',
                    'listCommaSeparated'
                ),
            ],
        ];

        parent::init();
        $this->permissionAssert = UserActionPermissionAssert::create();

        ZfExtended_UnprocessableEntity::addCodes([
            'E1420' => 'Old password is required',
            'E1421' => 'Old password does not match',
            'E2003' => 'Wrong value',
            'E1630' => 'You can not set role {role} with one of the following roles: {roles}',
            'E1094' => 'User can not be saved: the chosen login does already exist.',
            'E1095' => 'User can not be saved: the chosen userGuid does already exist.',
            'E1631' => 'Role "Job Coordinator" can be set only on User creation process or to LSP User',
        ]);

        ZfExtended_Models_Entity_Conflict::addCodes([
            'E2002' => 'No object of type "{0}" was found by key "{1}"',
            'E1048' => 'The user can not be deleted, he is PM in one or more tasks.',
            'E1626' => 'The user can not be deleted, he is last Job Coordinator of LSP "{lsp}".',
            'E1627' => 'Attempts to manipulate not accessible user.',
            'E1628' => 'Tried to manipulate a not editable user.',
        ], 'editor.user');
    }

    public function getAction()
    {
        $userRepo = new UserRepository();

        $user = $userRepo->get($this->getParam('id'));

        if ($user->getLogin() == ZfExtended_Models_User::SYSTEM_LOGIN) {
            $e = new ZfExtended_Models_Entity_NotFoundException();
            $e->setMessage("System Benutzer wurde versucht zu erreichen", true);

            throw $e;
        }

        $this->view->rows = $user->getDataObject();

        $this->permissionAssert->assertGranted(
            Action::READ,
            $user,
            new PermissionAssertContext(ZfExtended_Authentication::getInstance()->getUser())
        );

        $lspUserRepo = new LspUserRepository();

        // @phpstan-ignore-next-line
        $this->view->rows->lsp = $lspUserRepo->findByUser($user)?->lsp->getId();

        $this->csvToArray();
        $this->credentialCleanup();
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

            UserUpdateDataOperation::create()->update(
                $this->entity,
                UpdateUserDto::fromRequestData((array) $this->data),
            );
            $this->updateCustomers($authUser);
            $this->updateRoles($authUser);
            $this->updatePassword($authUser);

            unset($this->data->passwd);

            if (! empty($this->data->parentIds)) {
                UserUpdateParentIdsOperation::create()->updateParentIdsBy(
                    $this->entity,
                    $this->data->parentIds,
                    $authUser,
                );

                unset($this->data->parentIds);
            }
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
        } catch (LoginAlreadyInUseException) {
            throw ZfExtended_UnprocessableEntity::createResponse('E1094', [
                'login' => [
                    'duplicateLogin' => 'Dieser Anmeldename wird bereits verwendet.',
                ],
            ]);
        } catch (GuidAlreadyInUseException) {
            throw ZfExtended_UnprocessableEntity::createResponse('E1095', [
                'login' => [
                    'duplicateUserGuid' => 'Diese UserGuid wird bereits verwendet.',
                ],
            ]);
        } catch (ZfExtended_ValidateException $e) {
            $this->handleValidateException($e);
        }

        $this->view->rows = $this->entity->getDataObject();

        $this->credentialCleanup();
        $this->csvToArray();
        $this->checkAndUpdateSession();
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

            UserDeleteOperation::create()->delete($this->entity);
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
        $authUser = ZfExtended_Authentication::getInstance()->getUser();

        try {
            $this->decodePutData();

            $this->entity = UserCreateOperation::create()->createUser(
                CreateUserDto::fromRequestData(
                    ZfExtended_Utils::guid(true),
                    (array) $this->data
                ),
            );
            $this->initRoles($authUser);
            UserUpdateParentIdsOperation::create()->setParentIdsOnUserCreationBy(
                $this->entity,
                $this->data->parentIds ?? null,
                $authUser,
            );
            $this->updatePassword($authUser);
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            $this->handleLoginDuplicates($e);
        } catch (LoginAlreadyInUseException) {
            throw ZfExtended_UnprocessableEntity::createResponse('E1094', [
                'login' => [
                    'duplicateLogin' => 'Dieser Anmeldename wird bereits verwendet.',
                ],
            ]);
        } catch (GuidAlreadyInUseException) {
            throw ZfExtended_UnprocessableEntity::createResponse('E1095', [
                'login' => [
                    'duplicateUserGuid' => 'Diese UserGuid wird bereits verwendet.',
                ],
            ]);
        } catch (ZfExtended_ValidateException $e) {
            $this->handleValidateException($e);
        }

        $this->updateCustomers($authUser);
        $this->assignLsp($authUser);

        // make sure fields not processed by setDataInEntity
        unset($this->data->firstName);
        unset($this->data->surName);
        unset($this->data->login);
        unset($this->data->email);
        unset($this->data->gender);
        unset($this->data->parentIds);
        unset($this->data->passwd);

        $this->setDataInEntity($this->postBlacklist);

        if ($this->validate()) {
            $this->entity->save();
            $this->view->rows = $this->entity->getDataObject();
        }

        $this->credentialCleanup();
        if ($this->wasValid) {
            $this->csvToArray();
        }
    }

    /**
     * encapsulate a separate REST sub request for authenticated users only.
     * A authenticated user is allowed to get and change (PUT) himself, nothing more, nothing less.
     * @throws ZfExtended_BadMethodCallException
     */
    public function authenticatedAction()
    {
        if ($this->_request->getActionName() !== 'put') {
            throw new ZfExtended_BadMethodCallException();
        }

        $oldpwd = trim($this->getParam('oldpasswd'));

        if (empty($oldpwd)) {
            throw ZfExtended_UnprocessableEntity::createResponse('E1420', [
                'oldpasswd' => 'Old password is required',
            ]);
        }

        $auth = ZfExtended_Authentication::getInstance();

        if (! $auth->authenticate($auth->getLogin(), $oldpwd)) {
            throw ZfExtended_UnprocessableEntity::createResponse('E1421', [
                'oldpasswd' => 'Old password does not match',
            ]);
        }

        $this->decodePutData();

        try {
            $this->updatePassword($auth->getUser());
        } catch (ZfExtended_ValidateException $e) {
            $this->handleValidateException($e);
        }
    }

    /**
     * Loads a list of all users with role 'pm'. If 'pmRoles' is set,
     * all users with roles listed in 'pmRoles' will be loaded
     */
    public function pmAction()
    {
        $parentId = ZfExtended_Authentication::getInstance()->getUserId();
        //check if the user is allowed to see all users
        if ($this->isAllowed(SystemResource::ID, SystemResource::SEE_ALL_USERS)) {
            $parentId = -1;
        }

        $pmRoles = explode(',', $this->getParam('pmRoles', ''));
        $pmRoles[] = 'pm';
        $pmRoles = array_unique(array_filter($pmRoles));
        $this->view->rows = $this->entity->loadAllByRole($pmRoles, $parentId);
        $this->view->total = $this->entity->getTotalByRole($pmRoles, $parentId);
        $this->csvToArray();
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
            UserCustomerAssociationUpdateOperation::create()->updateAssociatedCustomersBy(
                $this->entity,
                $sentCustomerIds,
                $authUser
            );
        } catch (InexistentCustomerException $e) {
            throw ZfExtended_Models_Entity_Conflict::createResponse(
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
    private function initRoles(ZfExtended_Models_User $authUser): void
    {
        if (empty($this->data->roles)) {
            return;
        }

        $roles = explode(',', trim($this->data->roles, ','));

        try {
            UserInitRolesOperation::create()->initUserRolesBy($this->entity, $roles, $authUser);
        } catch (Exception $e) {
            throw $this->transformRolesException($e);
        }
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
            UserUpdateRolesOperation::create()->updateRolesBy($this->entity, $roles, $authUser);
        } catch (Exception $e) {
            throw $this->transformRolesException($e);
        }

        // make sure roles not processed by setDataInEntity
        unset($this->data->roles);
    }

    private function transformRolesException(Exception $e): ZfExtended_ErrorCodeException|Exception
    {
        return match ($e::class) {
            RolesetHasConflictingRolesException::class => ZfExtended_UnprocessableEntity::createResponse(
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
            ),
            RoleConflictWithRoleThatPopulatedToRolesetException::class => ZfExtended_UnprocessableEntity::createResponse(
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
            ),
            Zend_Acl_Exception::class => new ZfExtended_NoAccessException(previous: $e),
            UserIsNotAuthorisedToAssignRoleException::class => new ZfExtended_NoAccessException(previous: $e),
            UnableToAssignJobCoordinatorRoleToExistingUserException::class => throw ZfExtended_UnprocessableEntity::createResponse(
                'E1631',
                [
                    'roles' => [
                        'Die Rolle "Job-Koordinator" kann nur bei der Benutzererstellung oder für LSP-Benutzer definiert werden.',
                    ],
                ],
            ),
            default => $e,
        };
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

    /**
     * converts the source and target comma separated language ids to array.
     * Frontend/api use array, in the database we save comma separated values.
     */
    protected function csvToArray(): void
    {
        $callback = function ($row) {
            if ($row !== null && $row !== "") {
                $row = trim($row, ', ');
                $row = explode(',', $row);
            }

            return $row;
        };

        //if the row is an array, loop over its elements, and explode the source/target language
        if (is_array($this->view->rows)) {
            foreach ($this->view->rows as &$singleRow) {
                $singleRow['parentIds'] = $callback($singleRow['parentIds']);
            }

            return;
        }

        $this->view->rows->parentIds = $callback($this->view->rows->parentIds);
    }

    /**
     * remove password hashes and openid subject from output
     */
    protected function credentialCleanup(): void
    {
        if (is_object($this->view->rows)) {
            if (property_exists($this->view->rows, 'passwd')) {
                unset($this->view->rows->passwd);
            }
            if (property_exists($this->view->rows, 'openIdSubject')) {
                unset($this->view->rows->openIdSubject);
            }
            if (property_exists($this->view->rows, 'openIdIssuer')) {
                unset($this->view->rows->openIdIssuer);
            }
        }

        if (is_array($this->view->rows)) {
            if (isset($this->view->rows['passwd'])) {
                unset($this->view->rows['passwd']);
            }
            if (isset($this->view->rows['openIdSubject'])) {
                unset($this->view->rows['openIdSubject']);
            }
            if (isset($this->view->rows['openIdIssuer'])) {
                unset($this->view->rows['openIdIssuer']);
            }
        }
    }

    /**
     * @throws Zend_Exception
     */
    public function updatePassword(ZfExtended_Models_User $authUser): void
    {
        if (! isset($this->data->passwd)) {
            return;
        }

        $updateUserPasswordOperation = UserUpdatePasswordOperation::create();

        if (! empty($this->data->passwd)) {
            $updateUserPasswordOperation->updatePassword($authUser, $this->data->passwd);

            return;
        }

        $updateUserPasswordOperation->updatePassword($authUser, null);

        ResetPasswordEmail::create()->sendTo($authUser);
    }
}
