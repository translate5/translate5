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

use editor_Models_UserAssocDefault as DefaultUserJob;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\Exception\CoordinatorDontBelongToLCoordinatorGroupException;
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\Customer\ActionAssert\CustomerActionPermissionAssert;
use MittagQI\Translate5\Customer\Exception\InexistentCustomerException;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Exception\NotCoordinatorGroupCustomerException;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Operation\DTO\NewDefaultCoordinatorGroupJobDto;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Operation\WithAuthentication\CreateDefaultCoordinatorGroupJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Operation\WithAuthentication\UpdateDefaultCoordinatorGroupJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\DefaultJobAssignmentViewDataProvider;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\DataProvider\DefaultUserJobViewDataProvider;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\DataProvider\UserProvider;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\DTO\NewDefaultUserJobDto;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\WithAuthentication\CreateDefaultUserJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\WithAuthentication\UpdateDefaultUserJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\Exception\InvalidWorkflowStepProvidedException;
use MittagQI\Translate5\DefaultJobAssignment\Factory\NewDefaultJobDtoFactory;
use MittagQI\Translate5\DefaultJobAssignment\Factory\UpdateDefaultJobDtoFactory;
use MittagQI\Translate5\DefaultJobAssignment\Operation\WithAuthentication\DeleteDefaultJobAssignmentOperation;
use MittagQI\Translate5\JobAssignment\Exception\InvalidTypeProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\DefaultCoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Exception\InexistentUserException;
use ZfExtended_UnprocessableEntity as UnprocessableEntity;

class Editor_UserassocdefaultController extends ZfExtended_RestController
{
    protected $entityClass = editor_Models_UserAssocDefault::class;

    /**
     * @var editor_Models_UserAssocDefault
     */
    protected $entity;

    /**
     * ignoring ID field for POST Requests
     * @var array
     */
    protected $postBlacklist = ['id'];

    private UserRepository $userRepository;

    private DefaultUserJobRepository $defaultUserJobRepository;

    private DefaultUserJobViewDataProvider $defaultUserJobViewDataProvider;

    private CustomerRepository $customerRepository;

    private CustomerActionPermissionAssert $customerPermissionAssert;

    private DefaultCoordinatorGroupJobRepository $defaultCoordinatorGroupJobRepository;

    public function init(): void
    {
        parent::init();

        UnprocessableEntity::addCodes([
            'E1682' => 'Multi Purpose Code logging in the context of Default jobs',
        ]);

        $this->userRepository = new UserRepository();
        $this->defaultUserJobRepository = DefaultUserJobRepository::create();
        $this->defaultCoordinatorGroupJobRepository = DefaultCoordinatorGroupJobRepository::create();
        $this->customerRepository = CustomerRepository::create();
        $this->defaultUserJobViewDataProvider = DefaultUserJobViewDataProvider::create();
        $this->customerPermissionAssert = CustomerActionPermissionAssert::create();
    }

    public function indexAction(): void
    {
        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());

        /** @deprecated App logic should not tolerate requests without customer in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'userassocdefault')) {
//            Zend_Registry::get('logger')->warn(
//                'E1681',
//                'Route /editor/userassocdefault deprecated, use editor/customers/:customerId/workflow/:workflow/default-job instead',
//            );

            $rows = $this->defaultUserJobViewDataProvider->buildViewForList($this->entity->loadAll(), $authUser);

            // @phpstan-ignore-next-line
            $this->view->rows = $rows;
            $this->view->total = count($rows);

            return;
        }

        $viewDataProvider = DefaultJobAssignmentViewDataProvider::create();

        $rows = $viewDataProvider->getListFor(
            (int) $this->getRequest()->getParam('customerId'),
            $this->getRequest()->getParam('workflow'),
            $authUser,
        );

        // @phpstan-ignore-next-line
        $this->view->rows = $rows;
        $this->view->total = count($rows);
    }

    public function userscomboAction(): void
    {
        try {
            $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
            $context = new PermissionAssertContext($authUser);
            $customer = $this->customerRepository->get((int) $this->getRequest()->getParam('customerId'));

            if (! $this->customerPermissionAssert->isGranted(CustomerAction::DefaultJob, $customer, $context)) {
                throw new ZfExtended_NotFoundException('Customer not found');
            }

            // @phpstan-ignore-next-line
            $this->view->rows = UserProvider::create()->getPossibleUsers(
                (int) $this->getRequest()->getParam('customerId'),
                $authUser
            );
        } catch (Throwable $e) {
            $this->log->exception($e);

            throw $this->transformException($e);
        }
    }

    public function postAction(): void
    {
        /** @deprecated App logic should not tolerate requests without customer in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'userassocdefault')) {
//            Zend_Registry::get('logger')->warn(
//                'E1681',
//                'Route /editor/userassocdefault deprecated, use editor/customers/:customerId/workflow/:workflow/default-job instead',
//            );
        }

        try {
            $dto = NewDefaultJobDtoFactory::create()->fromRequest($this->getRequest());

            if (TypeEnum::Coordinator === $dto->type) {
                $coordinatorGroupJob = CreateDefaultCoordinatorGroupJobOperation::create()->assignJob(
                    NewDefaultCoordinatorGroupJobDto::fromDefaultJobDto($dto)
                );
                $userJob = $this->defaultUserJobRepository->get((int) $coordinatorGroupJob->getDataJobId());
            } else {
                $userJob = CreateDefaultUserJobOperation::create()->assignJob(
                    NewDefaultUserJobDto::fromDefaultJobDto($dto)
                );
            }

            $this->view->rows = (object) $this->defaultUserJobViewDataProvider->buildJobView($userJob);
        } catch (Throwable $e) {
            $this->log->exception($e);

            throw $this->transformException($e);
        }
    }

    public function putAction(): void
    {
        /** @deprecated App logic should not tolerate requests without customer in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'userassocdefault')) {
//            Zend_Registry::get('logger')->warn(
//                'E1681',
//                'Route /editor/userassocdefault deprecated, use editor/customers/:customerId/workflow/:workflow/default-job instead',
//            );
        }

        $job = $this->defaultUserJobRepository->get((int) $this->getRequest()->getParam('id'));

        $this->assertJobBelongsToCustomer($job);

        try {
            $dto = UpdateDefaultJobDtoFactory::create()->fromRequest($this->getRequest());

            $groupJob = $this->defaultCoordinatorGroupJobRepository
                ->findDefaultCoordinatorGroupJobByDataJobId((int) $job->getId());

            if (null !== $groupJob) {
                UpdateDefaultCoordinatorGroupJobOperation::create()->updateJob($groupJob, $dto);
            } else {
                UpdateDefaultUserJobOperation::create()->updateJob($job, $dto);
            }

            $job->refresh();

            $this->view->rows = (object) $this->defaultUserJobViewDataProvider->buildJobView($job);
        } catch (Throwable $e) {
            $this->log->exception($e);

            throw $this->transformException($e);
        }
    }

    public function deleteAction(): void
    {
        /** @deprecated App logic should not tolerate requests without customer in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'userassocdefault')) {
//            Zend_Registry::get('logger')->warn(
//                'E1681',
//                'Route /editor/userassocdefault deprecated, use editor/customers/:customerId/workflow/:workflow/default-job instead',
//            );
        }

        $job = $this->defaultUserJobRepository->find((int) $this->getRequest()->getParam('id'));

        if (! $job) {
            return;
        }

        $this->assertJobBelongsToCustomer($job);

        try {
            $operation = DeleteDefaultJobAssignmentOperation::create();
            $operation->delete((int) $job->getId());
        } catch (Throwable $e) {
            $this->log->exception($e);

            throw $this->transformException($e);
        }
    }

    /**
     * @throws ZfExtended_NotFoundException
     */
    public function assertJobBelongsToCustomer(DefaultUserJob $job): void
    {
        if (! $this->hasParam('customerId')) {
            return;
        }

        if ((int) $job->getCustomerId() !== (int) $this->getParam('customerId')) {
            throw new ZfExtended_NotFoundException('Job not found');
        }
    }

    /**
     * @throws Throwable
     */
    private function transformException(Throwable $e): ZfExtended_ErrorCodeException|Throwable
    {
        $invalidValueProvidedMessage = 'Ungültiger Wert bereitgestellt';

        return match ($e::class) {
            InvalidTypeProvidedException::class => UnprocessableEntity::createResponse(
                'E1682',
                [
                    'type' => [
                        $invalidValueProvidedMessage,
                    ],
                ],
            ),
            InexistentUserException::class => UnprocessableEntity::createResponse(
                'E1682',
                [
                    'userGuid' => [
                        $invalidValueProvidedMessage,
                    ],
                ],
            ),
            InexistentCustomerException::class => UnprocessableEntity::createResponse(
                'E1682',
                [
                    'customerId' => [
                        $invalidValueProvidedMessage,
                    ],
                ],
            ),
            OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException::class => UnprocessableEntity::createResponse(
                'E1682',
                [
                    'userGuid' => [
                        'Nur Koordinator kann dem Auftrag der Gruppe Koordinator zugewiesen werden',
                    ],
                ],
            ),
            NotCoordinatorGroupCustomerException::class => UnprocessableEntity::createResponse(
                'E1682',
                [
                    'userGuid' => [
                        'Kunde ist nicht mit der Benutzergruppe Koordinator verbunden',
                    ],
                ],
            ),
            CoordinatorDontBelongToLCoordinatorGroupException::class => UnprocessableEntity::createResponse(
                'E1682',
                [
                    'userGuid' => [
                        'coordinator-dont-belong-to-coordinator-group',
                    ],
                ],
            ),
            InvalidWorkflowStepProvidedException::class => UnprocessableEntity::createResponse(
                'E1682',
                [
                    'workflowStepName' => [
                        $invalidValueProvidedMessage,
                    ],
                ],
            ),
            default => $e,
        };
    }
}
