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

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\LSP\DTO\UpdateData;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToJobCoordinatorException;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\LSPService;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Exception\InexistentCustomerException;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\Repository\CustomerRepository;

class editor_LspController extends ZfExtended_RestController
{
    /**
     * @var LanguageServiceProvider
     */
    protected $entity;

    protected $entityClass = LanguageServiceProvider::class;

    protected $postBlacklist = ['id'];

    protected bool $decodePutAssociative = true;

    private LSPService $lspService;

    private CustomerRepository $customerRepository;

    private JobCoordinatorRepository $coordinatorRepository;

    public function init()
    {
        parent::init();
        $this->lspService = LSPService::create();
        $this->customerRepository = new CustomerRepository();
        $this->coordinatorRepository = new JobCoordinatorRepository();
    }

    public function indexAction()
    {
        $user = ZfExtended_Authentication::getInstance()->getUser();

        $this->view->rows = $this->lspService->getViewListFor($user); // @phpstan-ignore-line
        $this->view->total = count($this->view->rows);
    }

    public function postAction()
    {
        $this->decodePutData();

        if (empty($this->data['name'])) {
            ZfExtended_UnprocessableEntity::addCodes([
                'E2000' => 'Param "{0}" - is not given',
            ], 'editor.lsp');

            throw ZfExtended_UnprocessableEntity::createResponse(
                'E2000',
                [
                    'name' => [
                        'Name (Angabe notwendig)',
                    ],
                ],
                [
                    'name',
                ]
            );
        }

        $user = ZfExtended_Authentication::getInstance()->getUser();
        $coordinator = $this->coordinatorRepository->findByUser($user);

        $customers = $this->getNewCustomers($coordinator);

        $lsp = $this->lspService->createLsp(
            $this->data['name'],
            $this->data['description'] ?? null,
            $coordinator,
        );

        foreach ($customers as $customer) {
            $this->lspService->assignCustomer($lsp, $customer);
        }

        $this->view->rows = (object) $this->lspService->buildViewData($lsp);
    }

    public function putAction()
    {
        $this->decodePutData();

        $user = ZfExtended_Authentication::getInstance()->getUser();

        $roles = $user->getRoles();

        $lsp = $this->lspService->getLsp((int) $this->_getParam('id'));

        $coordinator = $this->coordinatorRepository->findByUser($user);

        $userCanUpdateLsp = array_intersect([Roles::ADMIN, Roles::SYSTEMADMIN], $roles)
            || (in_array(Roles::PM, $roles) && $lsp->isDirectLsp())
            || (null !== $coordinator && $lsp->isSubLspOf($coordinator->lsp))
        ;

        if (! $userCanUpdateLsp) {
            throw new ZfExtended_NoAccessException();
        }

        ZfExtended_UnprocessableEntity::addCodes([
            'E2003' => 'Wrong value',
        ], 'editor.lsp');

        $customers = $this->getNewCustomers($coordinator);

        try {
            $this->lspService->updateLsp(
                $lsp,
                new UpdateData(
                    $this->data['name'],
                    $this->data['description'] ?? null,
                    $customers,
                ),
            );
        } catch (CustomerDoesNotBelongToLspException $e) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E2003',
                [
                    'customerIds' => [
                        'Sie können den Kunden "{id}" hier nicht angeben',
                    ],
                ],
                [
                    'id' => $e->customerId,
                ]
            );
        }
    }

    public function deleteAction()
    {
        $lsp = $this->lspService->getLsp((int) $this->_getParam('id'));

        $this->lspService->deleteLsp($lsp);
    }

    public function getNewCustomers(?JobCoordinator $coordinator): array
    {
        if (empty($this->data['customerIds'])) {
            return [];
        }

        try {
            $customers = $this->customerRepository->getList(...$this->data['customerIds']);

            if (null !== $coordinator) {
                $this->lspService->validateCustomersAreSubsetForCoordinator($coordinator, $customers);
            }
        } catch (InexistentCustomerException $e) {
            ZfExtended_UnprocessableEntity::addCodes([
                'E2002' => 'No object of type "{0}" was found by key "{1}"',
            ], 'editor.lsp');

            throw ZfExtended_UnprocessableEntity::createResponse(
                'E2002',
                [
                    'customerIds' => [
                        'Der referenzierte Kunde existiert nicht (mehr).',
                    ],
                ],
                [
                    editor_Models_Customer_Customer::class,
                    $e->customerId,
                ]
            );
        } catch (CustomerDoesNotBelongToJobCoordinatorException $e) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E2003',
                [
                    'customerIds' => [
                        'Sie können den Kunden "{id}" hier nicht angeben',
                    ],
                ],
                [
                    'id' => $e->customerId,
                ]
            );
        }

        return $customers;
    }
}
