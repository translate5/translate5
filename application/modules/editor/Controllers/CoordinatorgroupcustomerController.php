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

use MittagQI\Translate5\ActionAssert\Permission\Exception\NoAccessException;
use MittagQI\Translate5\CoordinatorGroup\Exception\CoordinatorGroupHasUnDeletableJobException;
use MittagQI\Translate5\CoordinatorGroup\Exception\CustomerDoesNotBelongToCoordinatorGroupException;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroupCustomer;
use MittagQI\Translate5\CoordinatorGroup\Operations\WithAuthentication\CoordinatorGroupAssignCustomerOperation;
use MittagQI\Translate5\CoordinatorGroup\Operations\WithAuthentication\CoordinatorGroupUnassignCustomerOperation;
use MittagQI\Translate5\Customer\Exception\InexistentCustomerException;
use MittagQI\Translate5\Repository\CoordinatorGroupRepository;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use MittagQI\ZfExtended\Localization;
use ZfExtended_Models_Entity_Conflict as EntityConflictException;

class editor_CoordinatorgroupcustomerController extends ZfExtended_RestController
{
    /**
     * @var CoordinatorGroupCustomer
     */
    protected $entity;

    protected $entityClass = CoordinatorGroupCustomer::class;

    protected $postBlacklist = ['id'];

    protected bool $decodePutAssociative = true;

    private CoordinatorGroupRepository $coordinatorGroupRepository;

    private CustomerRepository $customerRepository;

    private CoordinatorGroupAssignCustomerOperation $coordinatorGroupAssignCustomerOperation;

    private CoordinatorGroupUnassignCustomerOperation $coordinatorGroupUnassignCustomerOperation;

    public function init()
    {
        parent::init();
        $this->coordinatorGroupRepository = CoordinatorGroupRepository::create();
        $this->customerRepository = new CustomerRepository();
        $this->coordinatorGroupAssignCustomerOperation = CoordinatorGroupAssignCustomerOperation::create();
        $this->coordinatorGroupUnassignCustomerOperation = CoordinatorGroupUnassignCustomerOperation::create();

        ZfExtended_UnprocessableEntity::addCodes([
            'E2000' => 'Param "{0}" - is not given',
            'E2003' => 'Wrong value',
        ], 'editor.coordinatorGroup.customer');

        EntityConflictException::addCodes([
            'E2002' => 'No object of type "{0}" was found by key "{1}"',
            'E1676' => 'Coordinator group has un-deletable job of customer',
        ], 'editor.coordinatorGroup.customer');
    }

    public function getAction(): void
    {
        throw new ZfExtended_NotFoundException('Action not found');
    }

    public function indexAction(): void
    {
        throw new ZfExtended_NotFoundException('Action not found');
    }

    public function postAction(): void
    {
        $group = $this->coordinatorGroupRepository->get((int) $this->getRequest()->getParam('groupId'));

        $this->decodePutData();

        if (empty($this->data['customer'])) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E2000',
                [
                    'customer' => [
                        Localization::trans('Kunde nicht angegeben'),
                    ],
                ],
                [
                    'customer',
                ]
            );
        }

        try {
            $customer = $this->customerRepository->get((int) $this->data['customer']);

            $this->coordinatorGroupAssignCustomerOperation->assignCustomer($group, $customer);
        } catch (NoAccessException $e) {
            throw new ZfExtended_NoAccessException(previous: $e);
        } catch (InexistentCustomerException $e) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E2002',
                [
                    'customer' => [
                        Localization::trans('Der referenzierte Kunde existiert nicht (mehr).'),
                    ],
                ],
                [
                    editor_Models_Customer_Customer::class,
                    $e->customerId,
                ]
            );
        } catch (CustomerDoesNotBelongToUserException $e) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E2003',
                [
                    'customer' => [
                        Localization::trans('Sie können den Kunden "{id}" hier nicht angeben'),
                    ],
                ],
                [
                    'id' => $e->customerId,
                ]
            );
        } catch (CustomerDoesNotBelongToCoordinatorGroupException $e) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E2003',
                [
                    'customer' => [
                        Localization::trans('Sie können den Kunden "{id}" hier nicht angeben'),
                    ],
                ],
                [
                    'id' => $e->customerId,
                ]
            );
        }

        $this->view->rows = [];
    }

    public function deleteAction(): void
    {
        $group = $this->coordinatorGroupRepository->get((int) $this->getRequest()->getParam('groupId'));
        $customer = $this->customerRepository->get((int) $this->getRequest()->getParam('id'));

        try {
            if ($this->getRequest()->getParam('force')) {
                $this->coordinatorGroupUnassignCustomerOperation->forceUnassignCustomer($group, $customer);
            } else {
                $this->coordinatorGroupUnassignCustomerOperation->unassignCustomer($group, $customer);
            }
        } catch (Throwable $e) {
            throw $this->transformException($e);
        }
    }

    private function transformException(Throwable $e): Throwable
    {
        return match ($e::class) {
            CoordinatorGroupHasUnDeletableJobException::class => EntityConflictException::createResponse(
                'E1676',
                [
                    'id' => [
                        Localization::trans('job-assignment.delete.there-is-un-deletable-bound-job'),
                    ],
                ],
            ),
            default => $e,
        };
    }
}
