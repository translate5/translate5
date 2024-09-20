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

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\Exception\InexistentCustomerException;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\LSP\ActionAssert\Permission\LspActionPermissionAssert;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Operations\WithAuthentication\LspCreateOperation;
use MittagQI\Translate5\LSP\Operations\WithAuthentication\LspDeleteOperation;
use MittagQI\Translate5\LSP\Operations\WithAuthentication\LspUpdateOperation;
use MittagQI\Translate5\LSP\ViewDataProvider;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\Repository\UserRepository;
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

    private ViewDataProvider $viewDataProvider;

    public function init()
    {
        parent::init();
        $this->viewDataProvider = ViewDataProvider::create();
    }

    public function getAction(): void
    {
        $userRepository = new UserRepository();
        $authUser = $userRepository->get(ZfExtended_Authentication::getInstance()->getUserId());

        $lsp = LspRepository::create()->get((int) $this->_getParam('id'));

        try {
            $this->view->rows = (object) $this->viewDataProvider->buildViewData($authUser, $lsp);
        } catch (PermissionExceptionInterface) {
            throw new ZfExtended_NoAccessException();
        }
    }

    public function indexAction(): void
    {
        $userRepository = new UserRepository();
        $authUser = $userRepository->get(ZfExtended_Authentication::getInstance()->getUserId());

        $this->view->rows = $this->viewDataProvider->getViewListFor($authUser); // @phpstan-ignore-line
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

        $userRepository = new UserRepository();
        $authUser = $userRepository->get(ZfExtended_Authentication::getInstance()->getUserId());

        $lsp = LspCreateOperation::create()->createLsp(
            $this->data['name'],
            $this->data['description'] ?? null,
        );

        $this->view->rows = (object) $this->viewDataProvider->buildViewData($authUser, $lsp);
    }

    public function putAction(): void
    {
        $this->decodePutData();

        $lspRepository = LspRepository::create();
        $lsp = $lspRepository->get((int) $this->_getParam('id'));

        ZfExtended_UnprocessableEntity::addCodes([
            'E2003' => 'Wrong value',
        ], 'editor.lsp');

        try {
            LspUpdateOperation::create()->updateLsp(
                $lsp,
                $this->data['name'],
                    $this->data['description'] ?? null
            );
        } catch (PermissionExceptionInterface) {
            throw new ZfExtended_NoAccessException();
        }

        $userRepository = new UserRepository();
        $authUser = $userRepository->get(ZfExtended_Authentication::getInstance()->getUserId());

        $this->view->rows = (object) $this->viewDataProvider->buildViewData($authUser, $lsp);
    }

    public function deleteAction(): void
    {
        $lspRepository = LspRepository::create();
        $lsp = $lspRepository->get((int) $this->_getParam('id'));

        $name = $this->getRequest()->getData(true)['name'] ?? null;
        if (!$name || $name !== $lsp->getName()) {
            throw new ZfExtended_NoAccessException($this->getTranslator()->_('Falscher Name'));
        }

        try {
            LspDeleteOperation::create()->deleteLsp($lsp);
        } catch (PermissionExceptionInterface) {
            throw new ZfExtended_NoAccessException();
        }
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

    private function getTranslator(): ZfExtended_Zendoverwrites_Translate
    {
        return ZfExtended_Zendoverwrites_Translate::getInstance();
    }
}
