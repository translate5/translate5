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
use MittagQI\Translate5\Customer\Exception\InexistentCustomerException;
use MittagQI\Translate5\JobAssignment\LspJob\ActionAssert\Feasibility\Exception\ThereIsUnDeletableBoundJobException;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\Exception\LspHasUnDeletableJobException;
use MittagQI\Translate5\LSP\Model\LanguageServiceProviderCustomer;
use MittagQI\Translate5\LSP\Operations\WithAuthentication\LspAssignCustomerOperation;
use MittagQI\Translate5\LSP\Operations\WithAuthentication\LspUnassignCustomerOperation;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use ZfExtended_Models_Entity_Conflict as EntityConflictException;

class editor_LspcustomerController extends ZfExtended_RestController
{
    /**
     * @var LanguageServiceProviderCustomer
     */
    protected $entity;

    protected $entityClass = LanguageServiceProviderCustomer::class;

    protected $postBlacklist = ['id'];

    protected bool $decodePutAssociative = true;

    private LspRepository $lspRepository;

    private CustomerRepository $customerRepository;

    private LspAssignCustomerOperation $lspAssignCustomerOperation;

    private LspUnassignCustomerOperation $lspUnassignCustomerOperation;

    public function init()
    {
        parent::init();
        $this->lspRepository = LspRepository::create();
        $this->customerRepository = new CustomerRepository();
        $this->lspAssignCustomerOperation = LspAssignCustomerOperation::create();
        $this->lspUnassignCustomerOperation = LspUnassignCustomerOperation::create();

        ZfExtended_UnprocessableEntity::addCodes([
            'E2000' => 'Param "{0}" - is not given',
            'E2003' => 'Wrong value',
        ], 'editor.lsp.customer');

        EntityConflictException::addCodes([
            'E2002' => 'No object of type "{0}" was found by key "{1}"',
            'E1676' => 'LSP has un-deletable job of customer',
        ], 'editor.lsp.customer');
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
        $lsp = $this->lspRepository->get((int) $this->getRequest()->getParam('lspId'));

        $this->decodePutData();

        if (empty($this->data['customer'])) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E2000',
                [
                    'customer' => [
                        'Kunde nicht angegeben',
                    ],
                ],
                [
                    'customer',
                ]
            );
        }

        try {
            $customer = $this->customerRepository->get((int) $this->data['customer']);

            $this->lspAssignCustomerOperation->assignCustomer($lsp, $customer);
        } catch (NoAccessException $e) {
            throw new ZfExtended_NoAccessException(previous: $e);
        } catch (InexistentCustomerException $e) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E2002',
                [
                    'customer' => [
                        'Der referenzierte Kunde existiert nicht (mehr).',
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
                        'Sie können den Kunden "{id}" hier nicht angeben',
                    ],
                ],
                [
                    'id' => $e->customerId,
                ]
            );
        } catch (CustomerDoesNotBelongToLspException $e) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E2003',
                [
                    'customer' => [
                        'Sie können den Kunden "{id}" hier nicht angeben',
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
        $lsp = $this->lspRepository->get((int) $this->getRequest()->getParam('lspId'));
        $customer = $this->customerRepository->get((int) $this->getRequest()->getParam('id'));

        try {
            if ($this->getRequest()->getParam('force')) {
                $this->lspUnassignCustomerOperation->forceUnassignCustomer($lsp, $customer);
            } else {
                $this->lspUnassignCustomerOperation->unassignCustomer($lsp, $customer);
            }
        } catch (Throwable $e) {
            throw $this->transformException($e);
        }
    }

    private function transformException(Throwable $e): Throwable
    {
        return match ($e::class) {
            LspHasUnDeletableJobException::class => EntityConflictException::createResponse(
                'E1676',
                [
                    'id' => [
                        'LSP-Auftrag hat verwandte Aufträge, die nicht gelöscht werden können.',
                    ],
                ],
            ),
            default => $e,
        };
    }
}
