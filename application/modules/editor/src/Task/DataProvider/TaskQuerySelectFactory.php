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
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Model\Db\CoordinatorGroupJobTable;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\Repository\CoordinatorGroupUserRepository;
use MittagQI\Translate5\User\Model\User;
use MittagQI\ZfExtended\Models\Filter\FilterJoinDTO;
use Zend_Acl_Exception;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Select;
use Zend_Db_Table;
use ZfExtended_Acl;
use ZfExtended_Models_Filter;

class TaskQuerySelectFactory
{
    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            ZfExtended_Acl::getInstance(),
            CoordinatorGroupUserRepository::create(),
            editor_Task_Type::getInstance(),
        );
    }

    private bool $doDebug = true;

    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly ZfExtended_Acl $acl,
        private readonly CoordinatorGroupUserRepositoryInterface $coordinatorGroupUserRepository,
        private readonly editor_Task_Type $taskType,
    ) {
    }

    public function createTotalTaskCountSelect(
        User $viewer,
        ?ZfExtended_Models_Filter $filter,
    ): Zend_Db_Select {
        $cols = 'COUNT(distinct(' . TaskDb::TABLE_NAME . '.id)) as count';
        $select = $this->getBaseTaskSelect($viewer, $filter, $cols, false);

        if ($this->doDebug) {
            error_log("TASK QUERY SELECT createTotalTaskCountSelect:\n ---\n" . $select->assemble() . "\n\n");
        }

        return $select;
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
        if ($this->doDebug) {
            error_log("TASK QUERY SELECT createTaskSelect:\n ---\n" . $select->assemble() . "\n\n");
        }

        return $select;
    }

    public function createProjectIdsSelect(
        User $viewer,
        ?ZfExtended_Models_Filter $filter,
    ): Zend_Db_Select {
        $select = $this->getBaseProjectSelect($viewer, $filter, 'project.id');
        $select->group('project.id');

        if ($this->doDebug) {
            error_log("TASK QUERY SELECT createProjectIdsSelect:\n ---\n" . $select->assemble() . "\n\n");
        }

        return $select;
    }

    public function createTotalProjectCountSelect(
        User $viewer,
        ?ZfExtended_Models_Filter $filter,
    ): Zend_Db_Select {
        $cols = 'COUNT(distinct(project.id)) as count';
        $select = $this->getBaseProjectSelect($viewer, $filter, $cols, false);

        if ($this->doDebug) {
            error_log("TASK QUERY SELECT createTotalProjectCountSelect:\n ---\n" . $select->assemble() . "\n\n");
        }

        return $select;
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
        if ($this->doDebug) {
            error_log("TASK QUERY SELECT createProjectSelect:\n ---\n" . $select->assemble() . "\n\n");
        }

        return $select;
    }

    private function getBaseTaskSelect(
        User $viewer,
        ?ZfExtended_Models_Filter $filter,
        array|string $columns = '*',
        bool $applySort = true,
    ): Zend_Db_Select {
        $filter?->setDefaultTable(TaskDb::TABLE_NAME);
        $select = $this->db
            ->select()
            ->from(
                TaskDb::TABLE_NAME,
                $columns
            )
            ->where(TaskDb::TABLE_NAME . '.taskType in (?)', $this->taskType->getNonInternalTaskTypes())
        ;

        if ($this->hasRestrictedAccess($viewer)) {
            $this->restrictSelect($select, $viewer, $filter, $applySort);
        }

        $filter?->applyToSelect($select, $applySort);

        return $select;
    }

    private function getBaseProjectSelect(
        User $viewer,
        ?ZfExtended_Models_Filter $filter,
        array|string $columns = '*',
        bool $applySort = true,
    ): Zend_Db_Select {
        $filter?->setDefaultTable('project');
        $select = $this->db
            ->select()
            ->from(
                [
                    'project' => TaskDb::TABLE_NAME,
                ],
                $columns
            )
            ->join(
                TaskDb::TABLE_NAME,
                TaskDb::TABLE_NAME . '.projectId = project.id',
                []
            )
            ->where('project.taskType in (?)', $this->taskType->getProjectTypes())
        ;

        if ($this->hasRestrictedAccess($viewer)) {
            $this->restrictSelect($select, $viewer, $filter, $applySort, false);
        }

        $filter?->applyToSelect($select, $applySort);

        return $select;
    }

    private function restrictSelect(
        Zend_Db_Select $select,
        User $viewer,
        ?ZfExtended_Models_Filter $filter,
        bool $applySort,
        bool $isTaskSelect = true,
    ): void {
        $userJobsJoin = new JoinCondition(
            TaskDb::TABLE_NAME,
            'taskGuid',
            UserJobDb::TABLE_NAME,
            'taskGuid'
        );

        if ($viewer->isClientPm()) {
            $where = [];
            $where[] = $this->db->quoteInto(TaskDb::TABLE_NAME . '.pmGuid = ?', $viewer->getUserGuid());
            $where[] = $this->db->quoteInto(TaskDb::TABLE_NAME . '.customerId in (?)', $viewer->getCustomersArray());

            if ($isTaskSelect) {
                $this->joinWithFilter(
                    $select,
                    $filter,
                    $userJobsJoin,
                    $applySort,
                    [],
                    Zend_Db_Select::LEFT_JOIN
                );

                $where[] = $this->db->quoteInto(UserJobDb::TABLE_NAME . '.userGuid = ?', $viewer->getUserGuid());
            }

            $select->where(implode(' OR ', $where));

            return;
        }

        $groupUser = $viewer->isPmLight() ? null : $this->coordinatorGroupUserRepository->findByUser($viewer);

        if (null === $groupUser) {
            $where = [];
            $where[] = $this->db->quoteInto(TaskDb::TABLE_NAME . '.pmGuid = ?', $viewer->getUserGuid());

            if ($isTaskSelect) {
                $this->joinWithFilter(
                    $select,
                    $filter,
                    $userJobsJoin,
                    $applySort,
                    [],
                    Zend_Db_Select::LEFT_JOIN
                );

                $where[] = $this->db->quoteInto(UserJobDb::TABLE_NAME . '.userGuid = ?', $viewer->getUserGuid());
            }

            $select->where(implode(' OR ', $where));

            return;
        }

        $groupJobJoin = new JoinCondition(
            TaskDb::TABLE_NAME,
            'taskGuid',
            CoordinatorGroupJobTable::TABLE_NAME,
            'taskGuid'
        );
        $this->joinWithFilter($select, $filter, $groupJobJoin, $applySort);
        $select->where(CoordinatorGroupJobTable::TABLE_NAME . '.groupId = ?', $groupUser->group->getId());

        if ($viewer->isCoordinator()) {
            return;
        }

        $this->joinWithFilter($select, $filter, $userJobsJoin, $applySort);
        $select->where(UserJobDb::TABLE_NAME . '.userGuid = ?', $viewer->getUserGuid());
    }

    /**
     * This function is a very hacky solution, that inner joins encapsulated in the $filter
     * interfere with joins in the $select (that weill be merged llater on)
     * We solve it by adding the join to the filter instead to the select if the filter exists
     * and has the table in question joined already
     * TODO FIXME: Extend the filter-classes so we can build the select purely out of filters ...
     */
    private function joinWithFilter(
        Zend_Db_Select $select,
        ?ZfExtended_Models_Filter $filter,
        JoinCondition $condition,
        bool $applySort,
        array $columns = [],
        string $joinType = Zend_Db_Select::RIGHT_JOIN,
    ): void {
        if (null !== $filter && $filter->hasJoinedTable($condition->foreignTable, $applySort)) {
            $dto = new FilterJoinDTO(
                $condition->foreignTable,
                $condition->localKey,
                $condition->foreignKey,
                $columns,
                null,
                $joinType
            );
            $filter->overrideJoinedTable($dto);

            return;
        }

        switch ($joinType) {
            case Zend_Db_Select::LEFT_JOIN:
                $select->joinLeft($condition->foreignTable, (string) $condition, $columns);

                break;
            case Zend_Db_Select::RIGHT_JOIN:
                $select->joinRight($condition->foreignTable, (string) $condition, $columns);

                break;
            default:
                $select->joinInner($condition->foreignTable, (string) $condition, $columns);

                break;
        }
    }

    private function hasRestrictedAccess(User $viewer): bool
    {
        if ($viewer->isClientPm() || $viewer->isPmLight()) {
            return true;
        }

        try {
            return ! $this->acl->isInAllowedRoles($viewer->getRoles(), Rights::ID, Rights::LOAD_ALL_TASKS);
        } catch (Zend_Acl_Exception) {
            return true;
        }
    }
}
