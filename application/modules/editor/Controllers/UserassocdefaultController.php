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
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\Customer\ActionAssert\CustomerActionPermissionAssert;
use MittagQI\Translate5\Customer\Exception\InexistentCustomerException;
use MittagQI\Translate5\DefaultJobAssignment\DefaultJobAssignmentViewDataProvider;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Exception\NotLspCustomerException;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation\DTO\NewDefaultLspJobDto;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation\WithAuthentication\CreateDefaultLspJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation\WithAuthentication\UpdateDefaultLspJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\DataProvider\UserProvider;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\DefaultUserJobViewDataProvider;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\DTO\NewDefaultUserJobDto;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\WithAuthentication\CreateDefaultUserJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\WithAuthentication\UpdateDefaultUserJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\Exception\InvalidWorkflowStepProvidedException;
use MittagQI\Translate5\DefaultJobAssignment\Factory\NewDefaultJobDtoFactory;
use MittagQI\Translate5\DefaultJobAssignment\Factory\UpdateDefaultJobDtoFactory;
use MittagQI\Translate5\DefaultJobAssignment\Operation\WithAuthentication\DeleteDefaultJobAssignmentOperation;
use MittagQI\Translate5\JobAssignment\Exception\InvalidTypeProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToLspJobException;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\Translate5\LSP\Exception\CoordinatorDontBelongToLspException;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\DefaultLspJobRepository;
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

    private DefaultLspJobRepository $defaultLspJobRepository;

    public function init(): void
    {
        parent::init();

        UnprocessableEntity::addCodes([
            'E1682' => 'Multi Purpose Code logging in the context of Default jobs',
        ]);

        $this->userRepository = new UserRepository();
        $this->defaultUserJobRepository = DefaultUserJobRepository::create();
        $this->defaultLspJobRepository = DefaultLspJobRepository::create();
        $this->customerRepository = CustomerRepository::create();
        $this->defaultUserJobViewDataProvider = DefaultUserJobViewDataProvider::create();
        $this->customerPermissionAssert = CustomerActionPermissionAssert::create();
    }

    public function indexAction(): void
    {
        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());

        /** @deprecated App logic should not tolerate requests without customer in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'userassocdefault')) {
            Zend_Registry::get('logger')->warn(
                'E1681',
                'Route /editor/userassocdefault deprecated, use editor/customer/:customerId/workflow/:workflow/default-job instead',
            );

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
        try {
            $dto = NewDefaultJobDtoFactory::create()->fromRequest($this->getRequest());

            if (TypeEnum::Lsp === $dto->type) {
                $lspJob = CreateDefaultLspJobOperation::create()->assignJob(
                    NewDefaultLspJobDto::fromDefaultJobDto($dto)
                );
                $userJob = $this->defaultUserJobRepository->get((int) $lspJob->getDataJobId());
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
            Zend_Registry::get('logger')->warn(
                'E1681',
                'Route /editor/userassocdefault deprecated, use editor/customer/:customerId/workflow/:workflow/default-job instead',
            );
        }

        $job = $this->defaultUserJobRepository->get((int) $this->getRequest()->getParam('id'));

        $this->assertJobBelongsToCustomer($job);

        try {
            $dto = UpdateDefaultJobDtoFactory::create()->fromRequest($this->getRequest());

            $lspJob = $this->defaultLspJobRepository->findDefaultLspJobByDataJobId((int)$job->getId());

            if (null !== $lspJob) {
                UpdateDefaultLspJobOperation::create()->updateJob($lspJob, $dto);
            } else {
                UpdateDefaultUserJobOperation::create()->updateJob($job, $dto);
            }

            $job->refresh();

            $this->view->rows = (object)$this->defaultUserJobViewDataProvider->buildJobView($job);
        } catch (Throwable $e) {
            $this->log->exception($e);

            throw $this->transformException($e);
        }
    }

    public function deleteAction(): void
    {
        /** @deprecated App logic should not tolerate requests without customer in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'userassocdefault')) {
            Zend_Registry::get('logger')->warn(
                'E1681',
                'Route /editor/userassocdefault deprecated, use editor/customer/:customerId/workflow/:workflow/default-job instead',
            );
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

        if ((int) $job->getCustomerId() !== (int) $this->hasParam('customerId')) {
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
            OnlyCoordinatorCanBeAssignedToLspJobException::class => UnprocessableEntity::createResponse(
                'E1682',
                [
                    'userGuid' => [
                        'Nur der Koordinator kann einem LSP-Auftrag zugewiesen werden',
                    ],
                ],
            ),
            NotLspCustomerException::class => UnprocessableEntity::createResponse(
                'E1682',
                [
                    'userGuid' => [
                        'Kunde ist nicht mit LSP des Nutzers verbunden',
                    ],
                ],
            ),
            CoordinatorDontBelongToLspException::class => UnprocessableEntity::createResponse(
                'E1682',
                [
                    'userGuid' => [
                        'Der Koordinator gehört nicht zum LSP',
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
