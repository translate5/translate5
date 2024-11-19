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

use MittagQI\Translate5\Acl\Exception\ClientRestrictedAndNotRolesProvidedTogetherException;
use MittagQI\Translate5\Acl\Exception\RoleConflictWithRoleThatPopulatedToRolesetException;
use MittagQI\Translate5\Acl\Exception\RolesCannotBeSetForUserException;
use MittagQI\Translate5\Acl\Exception\RolesetHasConflictingRolesException;
use MittagQI\Translate5\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Customer\Exception\InexistentCustomerException;
use MittagQI\Translate5\LSP\Exception\CustomerCanNotBeUnAssignedFromCoordinatorAsItHasRelatedLspJobsException;
use MittagQI\Translate5\LSP\Exception\CustomerCanNotBeUnAssignedFromLspUserAsItHasRelatedJobsException;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\Exception\LspNotFoundException;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\CoordinatorHasAssignedLspJobException;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\LastCoordinatorException;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\PmInTaskException;
use MittagQI\Translate5\User\ActionAssert\Permission\Exception\ClientRestrictionException;
use MittagQI\Translate5\User\ActionAssert\Permission\Exception\NotAccessibleLspUserException;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Exception\AttemptToSetLspForNonJobCoordinatorException;
use MittagQI\Translate5\User\Exception\CantRemoveCoordinatorRoleFromUserException;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use MittagQI\Translate5\User\Exception\CustomerNotProvidedOnClientRestrictedUserCreationException;
use MittagQI\Translate5\User\Exception\GuidAlreadyInUseException;
use MittagQI\Translate5\User\Exception\LoginAlreadyInUseException;
use MittagQI\Translate5\User\Exception\LspMustBeProvidedInJobCoordinatorCreationProcessException;
use MittagQI\Translate5\User\Exception\LspUserExceptionInterface;
use MittagQI\Translate5\User\Exception\UnableToAssignJobCoordinatorRoleToExistingUserException;
use MittagQI\Translate5\User\Exception\UserIsNotAuthorisedToAssignRoleException;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\DTO\UpdateUserDto;
use MittagQI\Translate5\User\Operations\Factory\CreateUserDtoFactory;
use MittagQI\Translate5\User\Operations\UserUpdatePasswordOperation;
use MittagQI\Translate5\User\Operations\WithAuthentication\UpdateUserCustomersAssignmentsOperation;
use MittagQI\Translate5\User\Operations\WithAuthentication\UpdateUserRolesOperation;
use MittagQI\Translate5\User\Operations\WithAuthentication\UserDeleteOperation;
use MittagQI\Translate5\User\Operations\WithAuthentication\UserCreateOperation;
use MittagQI\Translate5\User\Operations\WithAuthentication\UserUpdateOperation;
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
            'E1635' => 'You cannot set {roles} for this user.',
            'E1638' => "Can't remove Coordinator role from user: {reason}",
        ], 'editor.user');

        ZfExtended_Models_Entity_Conflict::addCodes([
            'E2002' => 'No object of type "{0}" was found by key "{1}"',
            'E1048' => 'The user can not be deleted, he is PM in one or more tasks.',
            'E1626' => 'The user can not be deleted, he is last Job Coordinator of LSP "{lsp}".',
            'E1627' => 'Attempts to manipulate not accessible user.',
            'E1628' => 'Tried to manipulate a not editable user.',
            'E1636' => 'The user can not be deleted, he is Job Coordinator of some LSP jobs.',
        ], 'editor.user');
    }

    public function getAction(): void
    {
        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserId());
        $user = $this->userRepository->get($this->getParam('id'));

        if ($user->getLogin() == ZfExtended_Models_User::SYSTEM_LOGIN) {
            $e = new ZfExtended_Models_Entity_NotFoundException();
            $e->setMessage("System Benutzer wurde versucht zu erreichen", true);

            throw $e;
        }

        $this->view->rows = $user->getDataObject();

        try {
            $this->permissionAssert->assertGranted(
                UserAction::Read,
                $user,
                new PermissionAssertContext($authUser)
            );
        } catch (Throwable $e) {
            throw $this->transformException($e);
        }

        $lspUserRepo = LspUserRepository::create();

        // @phpstan-ignore-next-line
        $this->view->rows->lsp = $lspUserRepo->findByUser($user)?->lsp->getId();

        $this->credentialCleanup();
    }

    public function indexAction(): void
    {
        $rows = $this->entity->loadAll();
        $lspUserRepo = LspUserRepository::create();
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
                $this->permissionAssert->assertGranted(UserAction::Read, $userModel, $context);
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

            $rows[$key]['lsp'] = isset($userIdToLspIdMap[$row['id']]) ? (int) $userIdToLspIdMap[$row['id']] : null;
        }

        $this->view->rows = array_values($rows);
        $this->view->total = count($rows);
    }

    public function putAction(): void
    {
        $user = $this->userRepository->get($this->getParam('id'));

        $data = $this->getRequest()->getParam('data');
        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        $customers = isset($data['customers'])
            ? array_filter(
                array_map(
                    'intval',
                    explode(',', trim($data['customers'], ' ,'))
                )
            )
            : null;

        $roles = isset($data['roles'])
            ? explode(',', trim($data['roles'], ' ,'))
            : null;

        try {
            if (null !== $customers) {
                $operation = UpdateUserCustomersAssignmentsOperation::create();
                $operation->updateCustomers($user, $customers, $this->getRequest()->getParam('force', false));
            }

            if (null !== $roles) {
                $operation = UpdateUserRolesOperation::create();
                $operation->updateRoles($user, $roles);
            }

            $dto = UpdateUserDto::fromRequest($this->getRequest());

            UserUpdateOperation::create()->updateUser($user, $dto);
        } catch (ZfExtended_ValidateException $e) {
            $this->handleValidateException($e);
        } catch (Throwable $e) {
            throw $this->transformException($e);
        }

        $this->view->rows = $user->getDataObject();

        $this->credentialCleanup();
        $this->checkAndUpdateSession();
    }

    public function deleteAction(): void
    {
        $user = $this->userRepository->get($this->getParam('id'));
        $operation = UserDeleteOperation::create();

        try {
            $this->getRequest()->getParam('force', false)
                ? $operation->forceDelete($user)
                : $operation->delete($user)
            ;
        } catch (Throwable $e) {
            throw $this->transformException($e);
        }
    }

    public function postAction(): void
    {
        try {
            $dto = CreateUserDtoFactory::create()->fromRequest($this->getRequest());
            $user = UserCreateOperation::create()->createUser($dto);

            $this->view->rows = $user->getDataObject();
        } catch (ZfExtended_ValidateException $e) {
            $this->handleValidateException($e);
        } catch (Throwable $e) {
            throw $this->transformException($e);
        }

        $this->credentialCleanup();
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

            $user = $this->userRepository->get($auth->getUserId());

            UserUpdatePasswordOperation::create()->updatePassword($user, $this->data->passwd);
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
        $pmRoles = explode(',', $this->getParam('pmRoles', ''));
        $pmRoles[] = 'pm';
        $pmRoles = array_unique(array_filter($pmRoles));
        $this->view->rows = $this->entity->loadAllByRole($pmRoles);
        $this->view->total = $this->entity->getTotalByRole($pmRoles);
    }

    /**
     * @throws Throwable
     */
    private function transformException(Throwable $e): ZfExtended_ErrorCodeException|Throwable
    {
        if ($e instanceof FeasibilityExceptionInterface) {
            return $this->transformFeasibilityException($e);
        }

        if ($e instanceof LspUserExceptionInterface) {
            return $this->transformLspUserException($e);
        }

        return match ($e::class) {
            RolesCannotBeSetForUserException::class => UnprocessableEntity::createResponse(
                'E1635',
                [
                    'roles' => [
                        'Sie können für diesen Benutzer keine {Rollen} festlegen.',
                    ],
                ],
                [
                    'roles' => implode(', ', $e->roles),
                ]
            ),
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
            ClientRestrictedAndNotRolesProvidedTogetherException::class => UnprocessableEntity::createResponse(
                'E1630',
                [
                    'roles' => [
                        'Sie können die Rolle {role} nicht mit einer der folgenden Rollen festlegen: {roles}',
                    ],
                ],
                [
                    'role' => '"client restricted"',
                    'roles' => '"not client restricted"',
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
            UserIsNotAuthorisedToAssignRoleException::class => new ZfExtended_NoAccessException(previous: $e),
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
            ClientRestrictionException::class => new ZfExtended_NoAccessException(
                'Deletion of User is not allowed due to client-restriction'
            ),
            CustomerNotProvidedOnClientRestrictedUserCreationException::class => UnprocessableEntity::createResponse(
                'E2003',
                [
                    'customers' => [
                        'Kunde nicht angegeben',
                    ],
                ],
            ),
            default => $e,
        };
    }

    private function transformLspUserException(LspUserExceptionInterface $e): ZfExtended_ErrorCodeException|UnprocessableEntity
    {
        return match ($e::class) {
            UnableToAssignJobCoordinatorRoleToExistingUserException::class => UnprocessableEntity::createResponse(
                'E1631',
                [
                    'roles' => [
                        'Die Rolle "Job-Koordinator" kann nur bei der Benutzererstellung '
                        . 'oder für LSP-Benutzer definiert werden.',
                    ],
                ],
            ),
            LspMustBeProvidedInJobCoordinatorCreationProcessException::class => UnprocessableEntity::createResponse(
                'E2003',
                [
                    'lsp' => [
                        'Sprachdienstleister ist ein Pflichtfeld für die Rolle des Jobkoordinators.',
                    ],
                ],
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
            AttemptToSetLspForNonJobCoordinatorException::class => ZfExtended_Models_Entity_Conflict::createResponse(
                'E2003',
                [
                    'lsp' => [
                        'Für Benutzer, die nicht die Rolle des Jobkoordinators haben, wird der Sprachdienstleister automatisch eingestellt.',
                    ],
                ],
            ),
            CantRemoveCoordinatorRoleFromUserException::class => UnprocessableEntity::createResponse(
                'E1638',
                [
                    'roles' => [
                        'Die Koordinatorenrolle kann dem Benutzer nicht entzogen werden: {reason}',
                    ],
                ],
                [
                    'reason' => match ($e->getPrevious()::class) {
                        LastCoordinatorException::class => $this->view->translate(
                            'Benutzer ist letzter Job-Koordinator des LSP'
                        ),
                        CoordinatorHasAssignedLspJobException::class => $this->view->translate(
                            'Der Benutzer ist Job-Koordinator für einige LSP Aufträge.'
                        ),
                        default => $e->getPrevious()->getMessage(),
                    }
                ],
            ),
            CustomerCanNotBeUnAssignedFromCoordinatorAsItHasRelatedLspJobsException::class => ZfExtended_UnprocessableEntity::createResponse(
                'E2003',
                [
                    'customers' => [
                        'Der Kunde "{customer}" kann nicht aus dem Koordinator entfernt werden, da er einige LSP-Aufträge hat, die zu diesem Kunden gehören.',
                    ],
                ],
                [
                    'customer' => $e->customerName,
                    'user' => $e->userGuid,
                ]
            ),
            CustomerCanNotBeUnAssignedFromLspUserAsItHasRelatedJobsException::class => ZfExtended_UnprocessableEntity::createResponse(
                'E2003',
                [
                    'customers' => [
                        'Der Kunde "{customer}" kann nicht aus dem Koordinator entfernt werden, da er einige LSP-Aufträge hat, die zu diesem Kunden gehören.',
                    ],
                ],
                [
                    'customer' => $e->customerName,
                    'user' => $e->userGuid,
                ]
            ),
            default => $e,
        };
    }

    private function transformFeasibilityException(FeasibilityExceptionInterface $e): ZfExtended_ErrorCodeException
    {
        return match ($e::class) {
            PmInTaskException::class => ZfExtended_Models_Entity_Conflict::createResponse(
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
            ),
            LastCoordinatorException::class => ZfExtended_Models_Entity_Conflict::createResponse(
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
            ),
            CoordinatorHasAssignedLspJobException::class => ZfExtended_Models_Entity_Conflict::createResponse(
                'E1636',
                [
                    'Der Benutzer kann nicht gelöscht werden, da er Job-Koordinator von einigen LSP-Aufträgen ist.',
                ],
                [
                    'lsp' => $e->coordinator->lsp->getName(),
                    'lspId' => $e->coordinator->lsp->getId(),
                    'user' => $this->entity->getUserGuid(),
                    'userLogin' => $this->entity->getLogin(),
                    'userEmail' => $this->entity->getEmail(),
                ]
            ),
            default => ZfExtended_Models_Entity_Conflict::createResponse(
                'E1628',
                [
                    'Versucht, einen Benutzer zu manipulieren, der nicht bearbeitet werden kann.',
                ],
                [
                    'user' => $this->entity->getUserGuid(),
                    'userLogin' => $this->entity->getLogin(),
                    'userEmail' => $this->entity->getEmail(),
                ]
            )
        };
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
}
