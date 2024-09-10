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

use MittagQI\Translate5\Exception\InexistentCustomerException;
use MittagQI\Translate5\LSP\ActionAssert\Action;
use MittagQI\Translate5\LSP\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\LSP\ActionAssert\Permission\LspActionPermissionAssert;
use MittagQI\Translate5\LSP\ActionAssert\Permission\LspActionPermissionAssertInterface;
use MittagQI\Translate5\LSP\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Service\LspCreateService;
use MittagQI\Translate5\LSP\Service\LspCustomerAssociationUpdateService;
use MittagQI\Translate5\LSP\Service\LspDeleteService;
use MittagQI\Translate5\LSP\Service\LspUpdateService;
use MittagQI\Translate5\LSP\ViewDataProvider;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;

class editor_LspController extends ZfExtended_RestController
{
    /**
     * @var LanguageServiceProvider
     */
    protected $entity;

    protected $entityClass = LanguageServiceProvider::class;

    protected $postBlacklist = ['id'];

    protected bool $decodePutAssociative = true;

    private JobCoordinatorRepository $coordinatorRepository;

    private ViewDataProvider $viewDataProvider;

    private LspActionPermissionAssertInterface $permissionAssert;

    private LspCustomerAssociationUpdateService $lspCustomerAssocUpdateService;

    public function init()
    {
        parent::init();
        $this->lspCustomerAssocUpdateService = LspCustomerAssociationUpdateService::create();
        $this->coordinatorRepository = JobCoordinatorRepository::create();
        $this->permissionAssert = LspActionPermissionAssert::create($this->coordinatorRepository);
        $this->viewDataProvider = ViewDataProvider::create(
            $this->coordinatorRepository,
            $this->permissionAssert,
        );
    }

    public function getAction(): void
    {
        $authUser = ZfExtended_Authentication::getInstance()->getUser();

        $lsp = LspRepository::create()->get((int) $this->_getParam('id'));

        try {
            $this->permissionAssert->assertGranted(Action::READ, $lsp, new PermissionAssertContext($authUser));
        } catch (PermissionExceptionInterface) {
            throw new ZfExtended_NoAccessException();
        }

        $this->view->rows = (object) $this->viewDataProvider->buildViewData($authUser, $lsp);
    }

    public function indexAction(): void
    {
        $user = ZfExtended_Authentication::getInstance()->getUser();

        $this->view->rows = $this->viewDataProvider->getViewListFor($user); // @phpstan-ignore-line
        $this->view->total = count($this->view->rows);
    }

    public function postAction(): void
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

        $lsp = LspCreateService::create()->createLsp(
            $this->data['name'],
            $this->data['description'] ?? null,
            $coordinator,
        );

        $this->runWithExceptionHandlerWrapping(
            fn () => $this->lspCustomerAssocUpdateService->updateCustomersBy($lsp, $this->data['customerIds'], $user)
        );

        $this->view->rows = (object) $this->viewDataProvider->buildViewData($user, $lsp);
    }

    public function putAction(): void
    {
        $this->decodePutData();

        $authUser = ZfExtended_Authentication::getInstance()->getUser();

        $lspRepository = LspRepository::create();
        $lsp = $lspRepository->get((int) $this->_getParam('id'));

        try {
            $this->permissionAssert->assertGranted(Action::UPDATE, $lsp, new PermissionAssertContext($authUser));
        } catch (PermissionExceptionInterface) {
            throw new ZfExtended_NoAccessException();
        }

        ZfExtended_UnprocessableEntity::addCodes([
            'E2003' => 'Wrong value',
        ], 'editor.lsp');

        LspUpdateService::create($lspRepository)->updateInfoFields(
            $lsp,
            $this->data['name'],
                $this->data['description'] ?? null
        );

        $this->runWithExceptionHandlerWrapping(
            fn () => $this->lspCustomerAssocUpdateService->updateCustomersBy($lsp, $this->data['customerIds'], $authUser)
        );

        $this->view->rows = (object) $this->viewDataProvider->buildViewData($authUser, $lsp);
    }

    public function deleteAction(): void
    {
        $authUser = ZfExtended_Authentication::getInstance()->getUser();

        $lspRepository = LspRepository::create();
        $lsp = LspRepository::create()->get((int) $this->_getParam('id'));

        try {
            $this->permissionAssert->assertGranted(Action::DELETE, $lsp, new PermissionAssertContext($authUser));
        } catch (PermissionExceptionInterface) {
            throw new ZfExtended_NoAccessException();
        }

        LspDeleteService::create($lspRepository)->deleteLsp($lsp);
    }

    private function runWithExceptionHandlerWrapping(callable $update): void
    {
        try {
            $update();
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
        } catch (CustomerDoesNotBelongToUserException $e) {
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
}
