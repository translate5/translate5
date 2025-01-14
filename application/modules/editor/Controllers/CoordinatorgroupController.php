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

use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\CoordinatorGroup\CoordinatorGroupViewDataProvider;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\CoordinatorGroup\Operations\Fabric\UpdateCoordinatorGroupDtoFactory;
use MittagQI\Translate5\CoordinatorGroup\Operations\WithAuthentication\CoordinatorGroupCreateOperation;
use MittagQI\Translate5\CoordinatorGroup\Operations\WithAuthentication\CoordinatorGroupDeleteOperation;
use MittagQI\Translate5\CoordinatorGroup\Operations\WithAuthentication\CoordinatorGroupUpdateOperation;
use MittagQI\Translate5\Repository\CoordinatorGroupRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Exception\InexistentUserException;

class editor_CoordinatorgroupController extends ZfExtended_RestController
{
    /**
     * @var CoordinatorGroup
     */
    protected $entity;

    protected $entityClass = CoordinatorGroup::class;

    protected $postBlacklist = ['id'];

    protected bool $decodePutAssociative = true;

    private CoordinatorGroupViewDataProvider $viewDataProvider;

    public function init()
    {
        parent::init();
        $this->viewDataProvider = CoordinatorGroupViewDataProvider::create();

        ZfExtended_UnprocessableEntity::addCodes([
            'E2000' => 'Param "{0}" - is not given',
            'E2003' => 'Wrong value',
        ], 'editor.lsp');
    }

    public function getAction(): void
    {
        $userRepository = new UserRepository();
        $authUser = $userRepository->get(ZfExtended_Authentication::getInstance()->getUserId());

        $group = CoordinatorGroupRepository::create()->get((int) $this->_getParam('id'));

        try {
            $this->view->rows = (object) $this->viewDataProvider->buildViewData($authUser, $group);
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

        $group = CoordinatorGroupCreateOperation::create()->createCoordinatorGroup(
            $this->data['name'],
            $this->data['description'] ?? null,
        );

        $this->view->rows = (object) $this->viewDataProvider->buildViewData($authUser, $group);
    }

    public function putAction(): void
    {
        $groupRepository = CoordinatorGroupRepository::create();
        $group = $groupRepository->get((int) $this->_getParam('id'));

        try {
            $dto = UpdateCoordinatorGroupDtoFactory::create()->fromRequest($this->getRequest());

            CoordinatorGroupUpdateOperation::create()->updateCoordinatorGroup($group, $dto);
        } catch (PermissionExceptionInterface|InexistentUserException) {
            throw new ZfExtended_NoAccessException();
        }

        $userRepository = new UserRepository();
        $authUser = $userRepository->get(ZfExtended_Authentication::getInstance()->getUserId());

        $this->view->rows = (object) $this->viewDataProvider->buildViewData($authUser, $group);
    }

    public function deleteAction(): void
    {
        $groupRepository = CoordinatorGroupRepository::create();
        $group = $groupRepository->get((int) $this->_getParam('id'));

        $name = $this->getRequest()->getData(true)['name'] ?? null;
        if (! $name || $name !== $group->getName()) {
            throw new ZfExtended_NoAccessException($this->getTranslator()->_('Falscher Name'));
        }

        try {
            CoordinatorGroupDeleteOperation::create()->deleteCoordinatorGroup($group);
        } catch (PermissionExceptionInterface) {
            throw new ZfExtended_NoAccessException();
        }
    }

    private function getTranslator(): ZfExtended_Zendoverwrites_Translate
    {
        return ZfExtended_Zendoverwrites_Translate::getInstance();
    }
}
