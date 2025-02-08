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

use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\Customer\ActionAssert\CustomerActionPermissionAssert;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\DataProvider\CoordinatorProvider;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Model\DefaultCoordinatorGroupJob;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\DefaultCoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\UserRepository;

class Editor_DefaultcoordinatorgroupjobController extends ZfExtended_RestController
{
    protected $entityClass = DefaultCoordinatorGroupJob::class;

    private UserRepository $userRepository;

    private DefaultCoordinatorGroupJobRepository $defaultLspJobRepository;

    private CoordinatorProvider $coordinatorProvider;

    private CustomerRepository $customerRepository;

    private CustomerActionPermissionAssert $customerPermissionAssert;

    public function init(): void
    {
        parent::init();

        $this->userRepository = new UserRepository();
        $this->defaultLspJobRepository = DefaultCoordinatorGroupJobRepository::create();
        $this->coordinatorProvider = CoordinatorProvider::create();
        $this->customerRepository = CustomerRepository::create();
        $this->customerPermissionAssert = CustomerActionPermissionAssert::create();
    }

    public function coordinatorupdatecomboAction(): void
    {
        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
        $context = new PermissionAssertContext($authUser);
        $customer = $this->customerRepository->get((int) $this->getRequest()->getParam('customerId'));

        if (! $this->customerPermissionAssert->isGranted(CustomerAction::DefaultJob, $customer, $context)) {
            throw new ZfExtended_NotFoundException('Customer not found');
        }

        $defaultLspJob = $this->defaultLspJobRepository->findDefaultCoordinatorGroupJobByDataJobId(
            (int) $this->getRequest()->getParam('jobId')
        );

        if ($defaultLspJob === null) {
            throw new ZfExtended_NotFoundException('Job not found');
        }

        $this->assertJobBelongsToCustomer($defaultLspJob);

        // @phpstan-ignore-next-line
        $this->view->rows = $this->coordinatorProvider->getCoordinatorsOfGroup(
            (int) $defaultLspJob->getGroupId(),
            $authUser
        );
    }

    public function coordinatorscomboAction(): void
    {
        try {
            $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
            $context = new PermissionAssertContext($authUser);
            $customer = $this->customerRepository->get((int) $this->getRequest()->getParam('customerId'));

            if (! $this->customerPermissionAssert->isGranted(CustomerAction::DefaultJob, $customer, $context)) {
                throw new ZfExtended_NotFoundException('Customer not found');
            }

            // @phpstan-ignore-next-line
            $this->view->rows = $this->coordinatorProvider->getPossibleCoordinators(
                (int) $this->getRequest()->getParam('customerId'),
                $authUser
            );
        } catch (Throwable $e) {
            $this->log->exception($e);

            $this->view->rows = [];
        }
    }

    /**
     * @throws ZfExtended_NotFoundException
     */
    public function assertJobBelongsToCustomer(DefaultCoordinatorGroupJob $job): void
    {
        if ((int) $job->getCustomerId() !== (int) $this->getRequest()->getParam('customerId')) {
            throw new ZfExtended_NotFoundException('Job not found');
        }
    }
}
