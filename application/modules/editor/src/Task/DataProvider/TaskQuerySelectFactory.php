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

namespace MittagQI\Translate5\Task\DataProvider;

use editor_Models_Db_Task as TaskDb;
use editor_Models_Db_TaskUserAssoc as UserJobDb;
use editor_Task_Type;
use MittagQI\Translate5\Acl\Rights;
use MittagQI\Translate5\LspJob\Model\Db\LspJobAssociationTable;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\User\Model\User;
use Zend_Acl_Exception;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Select;
use Zend_Db_Table;
use ZfExtended_Acl;
use ZfExtended_Models_Filter;

class TaskQuerySelectFactory
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly ZfExtended_Acl $acl,
        private readonly LspUserRepositoryInterface $lspUserRepository,
        private readonly editor_Task_Type $taskType,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            ZfExtended_Acl::getInstance(),
            LspUserRepository::create(),
            editor_Task_Type::getInstance(),
        );
    }

    public function createTotalTaskCountSelect(
        User $viewer,
        ?ZfExtended_Models_Filter $filter,
    ): Zend_Db_Select {
        return $this->getBaseTaskSelect($viewer, $filter, 'COUNT(distinct(task.id)) as count', false);
    }

    public function createTaskSelect(
        User $viewer,
        ?ZfExtended_Models_Filter $filter,
        int $offset = 0,
        int $limit = 0,
    ): Zend_Db_Select {
        $select = $this->getBaseTaskSelect($viewer, $filter);

        if (0 !== $offset || 0 !== $limit) {
            $select->limit($limit, $offset);
        }

        return $select;
    }

    public function createTotalProjectCountSelect(
        User $viewer,
        ?ZfExtended_Models_Filter $filter,
    ): Zend_Db_Select {
        return $this->getBaseProjectSelect($viewer, $filter, 'COUNT(distinct(project.id)) as count', false);
    }

    public function createProjectSelect(
        User $viewer,
        ?ZfExtended_Models_Filter $filter,
        int $offset = 0,
        int $limit = 0,
    ): Zend_Db_Select {
        $select = $this->getBaseProjectSelect($viewer, $filter)->distinct();

        if (0 !== $offset || 0 !== $limit) {
            $select->limit($limit, $offset);
        }

        return $select;
    }

    private function getBaseTaskSelect(
        User $viewer,
        ?ZfExtended_Models_Filter $filter,
        array|string $columns = '*',
        bool $applySort = true,
    ): Zend_Db_Select {
        $select = $this->db
            ->select()
            ->from(
                [
                    'task' => TaskDb::TABLE_NAME,
                ],
                $columns
            )
            ->where('task.taskType in (?)', $this->taskType->getNonInternalTaskTypes())
        ;

        if (! $this->canLoadAllTasks($viewer)) {
            $this->restrictSelect($select, $viewer);
        }

        if (null !== $filter) {
            $filter->applyToSelect($select, $applySort);
        }

        return $select;
    }

    private function getBaseProjectSelect(
        User $viewer,
        ?ZfExtended_Models_Filter $filter,
        array|string $columns = '*',
        bool $applySort = true,
    ): Zend_Db_Select {
        $select = $this->db
            ->select()
            ->from(
                [
                    'project' => TaskDb::TABLE_NAME,
                ],
                $columns
            )
            ->join(
                [
                    'task' => TaskDb::TABLE_NAME,
                ],
                'task.projectId = project.id',
                []
            )
            ->where('project.taskType in (?)', $this->taskType->getProjectTypes())
        ;

        if (! $this->canLoadAllTasks($viewer)) {
            $this->restrictSelect($select, $viewer);
        }

        if (null !== $filter) {
            $filter->applyToSelect($select, $applySort);
        }

        return $select;
    }

    private function restrictSelect(Zend_Db_Select $select, User $viewer): void
    {
        if ($viewer->isClientPm()) {
            $select
                ->joinLeft(
                    [
                        'userJob' => UserJobDb::TABLE_NAME,
                    ],
                    'userJob.taskGuid = task.taskGuid',
                    []
                )
                ->where('userJob.userGuid = ?', $viewer->getUserGuid())
                ->orWhere('task.pmGuid = ?', $viewer->getUserGuid())
                ->orWhere('task.customerId in (?)', $viewer->getCustomersArray())
            ;

            return;
        }

        $lspUser = $this->lspUserRepository->findByUser($viewer);

        if (null === $lspUser) {
            $select
                ->joinLeft(
                    [
                        'userJob' => UserJobDb::TABLE_NAME,
                    ],
                    'userJob.taskGuid = task.taskGuid',
                    []
                )
                ->where('userJob.userGuid = ?', $viewer->getUserGuid())
                ->orWhere('task.pmGuid = ?', $viewer->getUserGuid())
            ;

            return;
        }

        $select
            ->join(
                [
                    'lspJob' => LspJobAssociationTable::TABLE_NAME,
                ],
                'lspJob.taskGuid = task.taskGuid',
                []
            )
            ->where('lspJob.lspId = ?', $lspUser->lsp->getId())
        ;

        if ($viewer->isCoordinator()) {
            return;
        }

        $select
            ->join(
                [
                    'userJob' => UserJobDb::TABLE_NAME,
                ],
                'userJob.taskGuid = task.taskGuid',
                []
            )
            ->where('userJob.userGuid = ?', $viewer->getUserGuid())
        ;
    }

    private function canLoadAllTasks(User $viewer): bool
    {
        try {
            return $this->acl->isInAllowedRoles($viewer->getRoles(), Rights::ID, Rights::LOAD_ALL_TASKS);
        } catch (Zend_Acl_Exception) {
            return false;
        }
    }
}
