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
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Exception\InexistentCustomerException;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\Exception\LspNotFoundException;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\LastCoordinatorException;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\PmInTaskException;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\UserIsNotEditableException;
use MittagQI\Translate5\User\ActionAssert\Permission\Exception\ClientRestrictionException;
use MittagQI\Translate5\User\ActionAssert\Permission\Exception\NotAccessibleLspUserException;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use MittagQI\Translate5\User\DTO\CreateUserDto;
use MittagQI\Translate5\User\DTO\UpdateUserDto;
use MittagQI\Translate5\User\Exception\AttemptToSetLspForNonJobCoordinatorException;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use MittagQI\Translate5\User\Exception\GuidAlreadyInUseException;
use MittagQI\Translate5\User\Exception\InvalidParentUserProvidedForJobCoordinatorException;
use MittagQI\Translate5\User\Exception\InvalidParentUserProvidedForLspUserException;
use MittagQI\Translate5\User\Exception\LoginAlreadyInUseException;
use MittagQI\Translate5\User\Exception\LspMustBeProvidedInJobCoordinatorCreationProcessException;
use MittagQI\Translate5\User\Exception\ProvidedParentIdCannotBeEvaluatedToUserException;
use MittagQI\Translate5\User\Exception\RoleConflictWithRoleThatPopulatedToRolesetException;
use MittagQI\Translate5\User\Exception\RolesetHasConflictingRolesException;
use MittagQI\Translate5\User\Exception\UnableToAssignJobCoordinatorRoleToExistingUserException;
use MittagQI\Translate5\User\Exception\UserExceptionInterface;
use MittagQI\Translate5\User\Exception\UserIsNotAuthorisedToAssignRoleException;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\UserUpdatePasswordOperation;
use MittagQI\Translate5\User\Operations\WithAuthentication\UserCreateOperation;
use MittagQI\Translate5\User\Operations\WithAuthentication\UserDeleteOperation;
use MittagQI\Translate5\User\Operations\WithAuthentication\UserUpdateOperation;
use MittagQI\Translate5\User\Validation\ParentUserValidator;
use MittagQI\ZfExtended\Acl\SystemResource;
use ZfExtended_UnprocessableEntity as UnprocessableEntity;

class Editor_UserController extends ZfExtended_RestController
{
    protected $entityClass = ZfExtended_Models_User::class;

    /**
     * @var ZfExtended_Models_User
     */
    protected $entity;

    private UserActionPermissionAssert $permissionAssert;

    private UserRepository $userRepository;

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
        $this->userRepository = new UserRepository();

        ZfExtended_UnprocessableEntity::addCodes([
            'E1420' => 'Old password is required',
            'E1421' => 'Old password does not match',
            'E2003' => 'Wrong value',
            'E1630' => 'You can not set role {role} with one of the following roles: {roles}',
            'E1094' => 'User can not be saved: the chosen login does already exist.',
            'E1095' => 'User can not be saved: the chosen userGuid does already exist.',
            'E1631' => 'Role "Job Coordinator" can be set only on User creation process or to LSP User',
        ], 'editor.user');

        ZfExtended_Models_Entity_Conflict::addCodes([
            'E2002' => 'No object of type "{0}" was found by key "{1}"',
            'E1048' => 'The user can not be deleted, he is PM in one or more tasks.',
            'E1626' => 'The user can not be deleted, he is last Job Coordinator of LSP "{lsp}".',
            'E1627' => 'Attempts to manipulate not accessible user.',
            'E1628' => 'Tried to manipulate a not editable user.',
        ], 'editor.user');
    }

    public function getAction(): void
    {
        $user = $this->userRepository->get($this->getParam('id'));

        if ($user->getLogin() == ZfExtended_Models_User::SYSTEM_LOGIN) {
            $e = new ZfExtended_Models_Entity_NotFoundException();
            $e->setMessage("System Benutzer wurde versucht zu erreichen", true);

            throw $e;
        }

        $this->view->rows = $user->getDataObject();

        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());

        $this->permissionAssert->assertGranted(
            Action::READ,
            $user,
            new PermissionAssertContext($authUser)
        );

        $lspUserRepo = new LspUserRepository();

        // @phpstan-ignore-next-line
        $this->view->rows->lsp = $lspUserRepo->findByUser($user)?->lsp->getId();

        $this->csvToArray();
        $this->credentialCleanup();
    }

    public function indexAction(): void
    {
        $rows = $this->entity->loadAll();
        $lspUserRepo = new LspUserRepository();
        $userIdToLspIdMap = $lspUserRepo->getUserIdToLspIdMap();

        $userModel = ZfExtended_Factory::get(User::class);
        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
        $editableRoles = $authUser->getSetableRoles();
        $context = new PermissionAssertContext($authUser);

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
                $this->permissionAssert->assertGranted(Action::READ, $userModel, $context);
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

        $this->view->rows = array_values($rows);
        $this->view->total = count($rows);

        $this->csvToArray();
    }

    public function putAction(): void
    {
        $this->decodePutData();

        if (! empty($this->getDataField('lsp'))) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E2003',
                [
                    'lsp' => [
                        'Ein Wechsel des Sprachdienstleisters ist nicht zulässig.',
                    ],
                ],
            );
        }

        $user = $this->userRepository->get($this->getParam('id'));

        try {
            UserUpdateOperation::create()->updateUser(
                $user,
                UpdateUserDto::fromRequestData((array) $this->data),
            );
        } catch (ZfExtended_ValidateException $e) {
            $this->handleValidateException($e);
        } catch (Exception $e) {
            throw $this->transformException($e);
        }

        $this->view->rows = $user->getDataObject();

        $this->credentialCleanup();
        $this->csvToArray();
        $this->checkAndUpdateSession();
    }

    public function deleteAction(): void
    {
        $user = $this->userRepository->get($this->getParam('id'));

        try {
            UserDeleteOperation::create()->delete($user);
        } catch (PmInTaskException $e) {
            throw ZfExtended_Models_Entity_Conflict::createResponse(
                'E1048',
                [
                    'Der Benutzer kann nicht gelöscht werden, er ist PM in einer oder mehreren Aufgaben.',
                ],
                [
                    'tasks' => implode(', ', $e->taskGuids),
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

    public function postAction(): void
    {
        $this->decodePutData();

        try {
            $user = UserCreateOperation::create()->createUser(
                CreateUserDto::fromRequestData(
                    ZfExtended_Utils::guid(true),
                    (array) $this->data
                ),
            );

            $this->view->rows = $user->getDataObject();
        } catch (ZfExtended_ValidateException $e) {
            $this->handleValidateException($e);
        } catch (Exception $e) {
            throw $this->transformException($e);
        }

        $this->credentialCleanup();
        $this->csvToArray();
    }

    /**
     * encapsulate a separate REST sub request for authenticated users only.
     * A authenticated user is allowed to get and change (PUT) himself, nothing more, nothing less.
     * @throws ZfExtended_BadMethodCallException
     */
    public function authenticatedAction(): void
    {
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
            if (! property_exists($this->data->passwd, 'passwd')) {
                return;
            }

            UserUpdatePasswordOperation::create()->updatePassword($auth->getUser(), $this->data->passwd);
        } catch (ZfExtended_ValidateException $e) {
            $this->handleValidateException($e);
        }
    }

    /**
     * Loads a list of all users with role 'pm'. If 'pmRoles' is set,
     * all users with roles listed in 'pmRoles' will be loaded
     */
    public function pmAction(): void
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

    public function allowedparentusersAction(): void
    {
        $rows = $this->entity->loadAll();

        if ($this->_getParam('id')) {
            $rows = $this->filterParentUsersForExistingUser($rows);
        } else {
            $rows = $this->filterParentUsersForCreate($rows);
        }

        $this->view->rows = array_values($rows);
        $this->view->total = count($rows);
    }

    /**
     * @throws Zend_Exception
     */
    private function transformException(Exception $e): ZfExtended_ErrorCodeException|Exception
    {
        return match ($e::class) {
            RolesetHasConflictingRolesException::class => UnprocessableEntity::createResponse(
                'E1630',
                [
                    'roles' => [
                        'Sie können die Rolle {role} nicht mit einer der folgenden Rollen festlegen: {roles}',
                    ],
                ],
                [
                    'role' => $e->role,
                    'roles' => implode(', ', $e->conflictsWith),
                ]
            ),
            RoleConflictWithRoleThatPopulatedToRolesetException::class => UnprocessableEntity::createResponse(
                'E1630',
                [
                    'roles' => [
                        'Sie können die Rolle {role} nicht mit einer der folgenden Rollen festlegen: {roles}',
                    ],
                ],
                [
                    'role' => $e->role,
                    'roles' => implode(', ', array_merge([$e->conflictsWith], $e->becauseOf)),
                    'conflictsWith' => $e->conflictsWith,
                    'becauseOf' => $e->becauseOf,
                ]
            ),
            Zend_Acl_Exception::class,
            UserIsNotAuthorisedToAssignRoleException::class => new ZfExtended_NoAccessException(previous: $e),
            UnableToAssignJobCoordinatorRoleToExistingUserException::class => UnprocessableEntity::createResponse(
                'E1631',
                [
                    'roles' => [
                        'Die Rolle "Job-Koordinator" kann nur bei der Benutzererstellung '
                        . 'oder für LSP-Benutzer definiert werden.',
                    ],
                ],
            ),
            InexistentCustomerException::class => ZfExtended_Models_Entity_Conflict::createResponse(
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
            ),
            CustomerDoesNotBelongToUserException::class => UnprocessableEntity::createResponse(
                'E2003',
                [
                    'customers' => [
                        'Sie können den Kunden "{id}" hier nicht angeben',
                    ],
                ],
                [
                    'id' => $e->customerId,
                ]
            ),
            CustomerDoesNotBelongToLspException::class => UnprocessableEntity::createResponse(
                'E2003',
                [
                    'customers' => [
                        'Sie können den Kunden "{id}" hier nicht angeben',
                    ],
                ],
                [
                    'id' => $e->customerId,
                ]
            ),
            LoginAlreadyInUseException::class => UnprocessableEntity::createResponse('E1094', [
                'login' => [
                    'duplicateLogin' => 'Dieser Anmeldename wird bereits verwendet.',
                ],
            ]),
            GuidAlreadyInUseException::class => UnprocessableEntity::createResponse('E1095', [
                'login' => [
                    'duplicateUserGuid' => 'Diese UserGuid wird bereits verwendet.',
                ],
            ]),
            LspMustBeProvidedInJobCoordinatorCreationProcessException::class => UnprocessableEntity::createResponse(
        'E2003',
                [
                    'lsp' => [
                        'Sprachdienstleister ist ein Pflichtfeld für die Rolle des Jobkoordinators.',
                    ],
                ],
            ),
            ProvidedParentIdCannotBeEvaluatedToUserException::class => UnprocessableEntity::createResponse(
                'E2003',
                [
                    'parentIds' => [
                        'Gegebener parentIds-Wert kann für keinen Benutzer ausgewertet werden!',
                    ],
                ],
            ),
            FeasibilityExceptionInterface::class => ZfExtended_Models_Entity_Conflict::createResponse(
                'E1628',
                [
                    'Versucht, einen Benutzer zu manipulieren, der nicht bearbeitet werden kann.',
                ],
                [
                    'user' => $this->entity->getUserGuid(),
                    'userLogin' => $this->entity->getLogin(),
                    'userEmail' => $this->entity->getEmail(),
                ]
            ),
            NotAccessibleLspUserException::class => ZfExtended_Models_Entity_Conflict::createResponse(
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
            ),
            LspNotFoundException::class => ZfExtended_Models_Entity_Conflict::createResponse(
                'E2002',
                [
                    'lsp' => [
                        'Der referenzierte LSP existiert nicht (mehr).',
                    ],
                ],
                [
                    LanguageServiceProvider::class,
                    $e->lspId,
                ]
            ),
            InvalidParentUserProvidedForJobCoordinatorException::class => UnprocessableEntity::createResponse(
                'E2003',
                [
                    'parentIds' => [
                        'Der übergeordnete Benutzer des Job-Koordinators kann nur der PM, '
                        .'Admin und Job-Koordinator des eigenen oder übergeordneten LSP sein',
                    ],
                ],
            ),
            InvalidParentUserProvidedForLspUserException::class => UnprocessableEntity::createResponse(
                'E2003',
                [
                    'parentIds' => [
                        'Der übergeordnete Benutzer eines LSP-Benutzers kann nur der Jobkoordinator '
                        .'des eigenen Sprachdienstleisters (LSP) sein.',
                    ],
                ],
            ),
            AttemptToSetLspForNonJobCoordinatorException::class => ZfExtended_UnprocessableEntity::createResponse(
                'E2003',
                [
                    'lsp' => [
                        'Für Benutzer, die nicht die Rolle des Jobkoordinators haben, wird der Sprachdienstleister automatisch eingestellt.',
                    ],
                ],
            ),
            default => $e,
        };
    }

    /**
     * converts the source and target comma separated language ids to array.
     * Frontend/api use array, in the database we save comma separated values.
     */
    private function csvToArray(): void
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
    private function credentialCleanup(): void
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
     * Check and update user session if the current modified user is the one in the session
     */
    private function checkAndUpdateSession()
    {
        $userSession = new Zend_Session_Namespace('user');
        //ignore the check if session user or the data user is not set
        if (! isset($userSession->data->id) || ! isset($this->data->id)) {
            return;
        }
        if ($userSession->data->id == $this->data->id) {
            ZfExtended_Authentication::getInstance()->authenticateBySessionData($userSession->data);
        }
    }

    private function filterParentUsersForCreate(array $rows): array
    {
        $roles = explode(',', $this->_getParam('roles'));
        $roles = array_filter(array_map('trim', $roles));
        $childIsJobCoordinator = in_array(Roles::JOB_COORDINATOR, $roles, true);
        $childLsp = LspRepository::create()->find((int) $this->_getParam('lspId'));
        $lspUserRepo = new LspUserRepository();
        $userIdToLspIdMap = $lspUserRepo->getUserIdToLspIdMap();

        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
        $context = new PermissionAssertContext($authUser);
        $parentUser = ZfExtended_Factory::get(User::class);
        $parentUserValidator = ParentUserValidator::create();

        foreach ($rows as $key => $row) {
            $parentUser->init(
                new Zend_Db_Table_Row(
                    [
                        'table' => $parentUser->db,
                        'data' => $row,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            try {
                $this->permissionAssert->assertGranted(Action::READ, $parentUser, $context);
            } catch (PermissionExceptionInterface) {
                unset($rows[$key]);

                continue;
            }

            $parentIsLspUser = isset($userIdToLspIdMap[$row['id']]);

            if (! $childLsp && ! $childIsJobCoordinator && ! $parentIsLspUser) {
                continue;
            }

            if ($childLsp) {
                try {
                    $parentUserValidator->assertIsSuitableParentForLspUser(
                        $parentUser,
                        $childIsJobCoordinator,
                        $childLsp
                    );
                    continue;
                } catch (UserExceptionInterface) {
                }
            }

            unset($rows[$key]);
        }

        return $rows;
    }

    private function filterParentUsersForExistingUser(array $rows): array
    {
        $childUser = $this->userRepository->get($this->getParam('id'));
        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
        $context = new PermissionAssertContext($authUser);
        $parentUser = ZfExtended_Factory::get(User::class);
        $parentUserValidator = ParentUserValidator::create();

        foreach ($rows as $key => $row) {
            $parentUser->init(
                new Zend_Db_Table_Row(
                    [
                        'table' => $parentUser->db,
                        'data' => $row,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            try {
                $this->permissionAssert->assertGranted(Action::READ, $parentUser, $context);
                $parentUserValidator->assertUserCanBeSetAsParentTo($parentUser, $childUser);
            } catch (PermissionExceptionInterface|UserExceptionInterface) {
                unset($rows[$key]);
            }
        }

        return $rows;
    }
}
