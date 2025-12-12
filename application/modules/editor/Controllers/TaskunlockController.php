<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Task\ActionAssert\Permission\TaskUnlockPermissionAssert;
use MittagQI\Translate5\Task\ActionAssert\TaskAction;
use MittagQI\Translate5\Task\Unlock\TaskUnlockService;
use ZfExtended_Authentication as Auth;

/**
 * Handles unlocking tasks without processing the rest of TaskController::putAction logic.
 */
class editor_TaskunlockController extends ZfExtended_RestController
{
    protected $entityClass = editor_Models_Task::class;

    /**
     * @var editor_Models_Task
     */
    protected $entity;

    public function init(): void
    {
        parent::init();

        $this->entity = ZfExtended_Factory::get($this->entityClass);

        $this->log = ZfExtended_Factory::get('editor_Logger_Workflow', [$this->entity]);
    }

    /**
     * Unlocks a task without processing the rest of the PUT logic.
     * Used by the sendBeacon call for unlocking a task when user closes the browser.
     *
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_Conflict
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NoAccessException
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ZfExtended_ValidateException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws editor_Models_Segment_Exception
     */
    public function unlockAction(): void
    {
        if (! ZfExtended_Authentication::getInstance()->isAuthenticated()) {
            return;
        }

        $this->entity->load($this->_getParam('id'));
        $this->decodePutData();

        $taskUnlockService = TaskUnlockService::create();
        $taskActionPermissionAssert = TaskUnlockPermissionAssert::create();

        $userRepository = new UserRepository();

        $authenticatedUser = $userRepository->get(
            intval(
                Auth::getInstance()->getUser()->getId()
            )
        );

        $isEditAllTasks = $this->isAllowed(
            MittagQI\Translate5\Acl\Rights::ID,
            MittagQI\Translate5\Acl\Rights::EDIT_ALL_TASKS
        )
            || $authenticatedUser->getUserGuid() === $this->entity->getPmGuid()
            || (
                $authenticatedUser->isCoordinator()
                && $taskActionPermissionAssert->isGranted(
                    TaskAction::Edit,
                    $this->entity,
                    new PermissionAssertContext($authenticatedUser)
                )
            );

        $taskUnlockService->unlock(
            $this->entity,
            (object) $this->data,
            $authenticatedUser,
            $this->log,
            $this->events,
            $isEditAllTasks,
        );
    }
}
