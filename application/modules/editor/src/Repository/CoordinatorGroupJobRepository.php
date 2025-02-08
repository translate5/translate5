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

namespace MittagQI\Translate5\Repository;

use editor_Models_Db_Task as TaskTable;
use editor_Models_Db_TaskUserAssoc as UserJobTable;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinator;
use MittagQI\Translate5\CoordinatorGroup\Model\Db\CoordinatorGroupTable;
use MittagQI\Translate5\CoordinatorGroup\Model\Db\CoordinatorGroupUserTable;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Exception\CoordinatorGroupJobAlreadyExistsException;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Exception\InexistentCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Exception\NotFoundCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Model\CoordinatorGroupJob;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Model\Db\CoordinatorGroupJobTable;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use PDO;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Expr;
use Zend_Db_Table;
use ZfExtended_Factory;
use ZfExtended_Models_Db_User;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;

class CoordinatorGroupJobRepository
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
        );
    }

    public function getEmptyModel(): CoordinatorGroupJob
    {
        return ZfExtended_Factory::get(CoordinatorGroupJob::class);
    }

    /**
     * @throws InexistentCoordinatorGroupJobException
     */
    public function get(int $id): CoordinatorGroupJob
    {
        try {
            $job = ZfExtended_Factory::get(CoordinatorGroupJob::class);
            $job->load($id);

            return $job;
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new InexistentCoordinatorGroupJobException((string) $id);
        }
    }

    public function delete(int $jobId): void
    {
        $this->db->delete(
            CoordinatorGroupJobTable::TABLE_NAME,
            $this->db->quoteInto('id = ?', $jobId)
        );
    }

    /**
     * @throws CoordinatorGroupJobAlreadyExistsException
     */
    public function save(CoordinatorGroupJob $job): void
    {
        try {
            $job->save();
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            throw new CoordinatorGroupJobAlreadyExistsException(previous: $e);
        }
    }

    public function coordinatorGroupHasJobInTask(int $groupId, string $taskGuid): bool
    {
        $job = ZfExtended_Factory::get(CoordinatorGroupJob::class);

        $select = $this->db
            ->select()
            ->from($job->db->info($job->db::NAME), 'COUNT(*)')
            ->where('taskGuid = ?', $taskGuid)
            ->where('groupId = ?', $groupId);

        return (int) $this->db->fetchOne($select) > 0;
    }

    public function coordinatorGroupOfCoordinatorHasJobForTaskWorkflowStep(string $userGuid, string $taskGuid): bool
    {
        $select = $this->db
            ->select()
            ->from(
                [
                    'groupJob' => CoordinatorGroupJobTable::TABLE_NAME,
                ],
                'COUNT(*)'
            )
            ->join(
                [
                    'task' => TaskTable::TABLE_NAME,
                ],
                'groupJob.taskGuid = task.taskGuid AND (task.workflowStepName = groupJob.workflowStepName OR task.workflowStepName = \'no workflow\')',
                []
            )
            ->join(
                [
                    'groupUser' => CoordinatorGroupUserTable::TABLE_NAME,
                ],
                'groupUser.groupId = groupJob.groupId',
                []
            )
            ->join(
                [
                    'user' => ZfExtended_Models_Db_User::TABLE_NAME,
                ],
                'groupUser.userId = user.id',
                []
            )
            ->where('groupJob.taskGuid = ?', $taskGuid)
            ->where('user.userGuid = ?', $userGuid)
        ;

        return (int) $this->db->fetchOne($select) > 0;
    }

    public function findCurrentCoordinatorGroupJobOfCoordinatorInTask(
        string $userGuid,
        string $taskGuid,
        string $workflowStepName
    ): ?CoordinatorGroupJob {
        //order first by matching role, then by the states as defined
        $order = $this->db->quoteInto(
            'userJob.workflowStepName = ? DESC,'
            . 'userJob.state="edit" DESC,'
            . 'userJob.state="view" DESC,'
            . 'userJob.state="unconfirmed" DESC,'
            . 'userJob.state="open" DESC,'
            . 'userJob.state="waiting" DESC,'
            . 'userJob.state="finished" DESC',
            $workflowStepName
        );

        $select = $this->db
            ->select()
            ->from([
                'groupJob' => CoordinatorGroupJobTable::TABLE_NAME,
            ])
            ->join(
                [
                    'groupUser' => CoordinatorGroupUserTable::TABLE_NAME,
                ],
                'groupUser.groupId = groupJob.groupId',
                []
            )
            ->join(
                [
                    'user' => ZfExtended_Models_Db_User::TABLE_NAME,
                ],
                'groupUser.userId = user.id',
                []
            )
            ->join(
                [
                    'userJob' => UserJobTable::TABLE_NAME,
                ],
                'userJob.coordinatorGroupJobId = groupJob.id',
                []
            )
            ->where('groupJob.taskGuid = ?', $taskGuid)
            ->where('userJob.type = ?', TypeEnum::Coordinator->value)
            ->where('user.userGuid = ?', $userGuid)
            ->order(new Zend_Db_Expr($order))
        ;

        $row = $this->db->fetchRow($select);

        if (empty($row)) {
            return null;
        }

        $job = new CoordinatorGroupJob();
        $job->init(
            new \Zend_Db_Table_Row(
                [
                    'table' => $job->db,
                    'data' => $row,
                    'stored' => true,
                    'readOnly' => false,
                ]
            )
        );

        return $job;
    }

    public function coordinatorAssignedToCoordinatorGroupJobs(JobCoordinator $coordinator): bool
    {
        $select = $this->db
            ->select()
            ->from(
                [
                    'groupJob' => CoordinatorGroupJobTable::TABLE_NAME,
                ],
                'COUNT(*)'
            )
            ->join(
                [
                    'userJob' => UserJobTable::TABLE_NAME,
                ],
                'userJob.coordinatorGroupJobId = groupJob.id',
                []
            )
            ->where('userJob.userGuid = ?', $coordinator->user->getUserGuid())
            ->where('userJob.type = ?', TypeEnum::Coordinator->value)
        ;

        return (int) $this->db->fetchOne($select) > 0;
    }

    /**
     * @return iterable<CoordinatorGroupJob>
     */
    public function getCoordinatorGroupJobs(int $groupId): iterable
    {
        $job = new CoordinatorGroupJob();

        $select = $this->db
            ->select()
            ->from(CoordinatorGroupJobTable::TABLE_NAME)
            ->where('groupId = ?', $groupId)
        ;

        $stmt = $this->db->query($select);

        while ($jobData = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $job->init(
                new \Zend_Db_Table_Row(
                    [
                        'table' => $job->db,
                        'data' => $jobData,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            yield clone $job;
        }
    }

    /**
     * @return iterable<CoordinatorGroupJob>
     */
    public function getCoordinatorGroupJobsOfCustomer(int $groupId, int $customerId): iterable
    {
        $job = new CoordinatorGroupJob();

        $select = $this->db
            ->select()
            ->from([
                'groupJob' => CoordinatorGroupJobTable::TABLE_NAME,
            ])
            ->join(
                [
                    'task' => TaskTable::TABLE_NAME,
                ],
                'groupJob.taskGuid = task.taskGuid',
                []
            )
            ->where('groupJob.groupId = ?', $groupId)
            ->where('task.customerId = ?', $customerId)
        ;

        $stmt = $this->db->query($select);

        while ($jobData = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $job->init(
                new \Zend_Db_Table_Row(
                    [
                        'table' => $job->db,
                        'data' => $jobData,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            yield clone $job;
        }
    }

    public function coordinatorHasGroupJobsOfCustomer(string $userGuid, int $customerId): bool
    {
        $select = $this->db
            ->select()
            ->from(
                [
                    'groupJob' => CoordinatorGroupJobTable::TABLE_NAME,
                ],
                'COUNT(*)'
            )
            ->join(
                [
                    'userJob' => UserJobTable::TABLE_NAME,
                ],
                'groupJob.id = userJob.coordinatorGroupJobId',
                []
            )
            ->join(
                [
                    'task' => TaskTable::TABLE_NAME,
                ],
                'groupJob.taskGuid = task.taskGuid',
                []
            )
            ->where('userJob.userGuid = ?', $userGuid)
            ->where('task.customerId = ?', $customerId)
        ;

        return (int) $this->db->fetchOne($select) > 0;
    }

    /**
     * @return iterable<CoordinatorGroupJob>
     */
    public function getSubGroupJobsOf(int $groupJobId): iterable
    {
        $job = ZfExtended_Factory::get(CoordinatorGroupJob::class);

        $select = $this->db
            ->select()
            ->from([
                'groupJob' => CoordinatorGroupJobTable::TABLE_NAME,
            ])
            ->join(
                [
                    'group' => CoordinatorGroupTable::TABLE_NAME,
                ],
                'groupJob.groupId = group.id',
                []
            )
            ->join(
                [
                    'parentGroupJob' => CoordinatorGroupJobTable::TABLE_NAME,
                ],
                implode(
                    ' AND ',
                    [
                        'group.parentId = parentGroupJob.groupId',
                        'groupJob.taskGuid = parentGroupJob.taskGuid',
                        'groupJob.workflowStepName = parentGroupJob.workflowStepName',
                    ]
                ),
                []
            )
            ->where('parentGroupJob.id = ?', $groupJobId)
        ;

        foreach ($this->db->fetchAll($select) as $jobData) {
            $job->init(
                new \Zend_Db_Table_Row(
                    [
                        'table' => $job->db,
                        'data' => $jobData,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            yield clone $job;
        }
    }

    public function hasJobInTaskGuidAndWorkflow(
        int $groupId,
        string $taskGuid,
        string $workflow,
        string $workflowStepName,
    ): bool {
        try {
            $this->getByCoordinatorGroupIdTaskGuidAndWorkflow($groupId, $taskGuid, $workflow, $workflowStepName);

            return true;
        } catch (NotFoundCoordinatorGroupJobException) {
            return false;
        }
    }

    /**
     * @throws NotFoundCoordinatorGroupJobException
     */
    public function getByCoordinatorGroupIdTaskGuidAndWorkflow(
        int $groupId,
        string $taskGuid,
        string $workflow,
        string $workflowStepName,
    ): CoordinatorGroupJob {
        $groupJob = new CoordinatorGroupJob();

        $select = $this->db
            ->select()
            ->from([
                'groupJob' => CoordinatorGroupJobTable::TABLE_NAME,
            ])
            ->where('groupJob.groupId = ?', $groupId)
            ->where('groupJob.taskGuid = ?', $taskGuid)
            ->where('groupJob.workflow = ?', $workflow)
            ->where('groupJob.workflowStepName = ?', $workflowStepName)
        ;

        $row = $this->db->fetchRow($select);

        if (empty($row)) {
            throw new NotFoundCoordinatorGroupJobException($groupId, $taskGuid, $workflow, $workflowStepName);
        }

        $groupJob->init(
            new \Zend_Db_Table_Row(
                [
                    'table' => $groupJob->db,
                    'data' => $row,
                    'stored' => true,
                    'readOnly' => false,
                ]
            )
        );

        return $groupJob;
    }

    /**
     * @return iterable<CoordinatorGroupJob>
     */
    public function getByTaskGuidAndWorkflow(
        string $taskGuid,
        string $workflow,
        string $workflowStepName,
    ): iterable {
        $job = new CoordinatorGroupJob();

        $select = $this->db
            ->select()
            ->from([
                'groupJob' => CoordinatorGroupJobTable::TABLE_NAME,
            ])
            ->where('groupJob.taskGuid = ?', $taskGuid)
            ->where('groupJob.workflow = ?', $workflow)
            ->where('groupJob.workflowStepName = ?', $workflowStepName)
        ;

        foreach ($this->db->fetchAll($select) as $jobData) {
            $job->init(
                new \Zend_Db_Table_Row(
                    [
                        'table' => $job->db,
                        'data' => $jobData,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            yield clone $job;
        }
    }

    public function getTaskCoordinatorGroupJobs(string $taskGuid): iterable
    {
        $groupJob = new CoordinatorGroupJob();

        $select = $this->db
            ->select()
            ->from([
                'groupJob' => CoordinatorGroupJobTable::TABLE_NAME,
            ])
            ->where('groupJob.taskGuid = ?', $taskGuid)
        ;

        foreach ($this->db->fetchAll($select) as $jobData) {
            $groupJob->init(
                new \Zend_Db_Table_Row(
                    [
                        'table' => $groupJob->db,
                        'data' => $jobData,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            yield clone $groupJob;
        }
    }

    public function getTaskJobsOfTopRankCoordinatorGroups(string $taskGuid): iterable
    {
        $groupJob = new CoordinatorGroupJob();

        $select = $this->db
            ->select()
            ->from([
                'groupJob' => CoordinatorGroupJobTable::TABLE_NAME,
            ])
            ->join(
                [
                    'group' => CoordinatorGroupTable::TABLE_NAME,
                ],
                'groupJob.groupId = group.id',
                []
            )
            ->where('groupJob.taskGuid = ?', $taskGuid)
            ->where('group.parentId IS NULL')
        ;

        foreach ($this->db->fetchAll($select) as $jobData) {
            $groupJob->init(
                new \Zend_Db_Table_Row(
                    [
                        'table' => $groupJob->db,
                        'data' => $jobData,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            yield clone $groupJob;
        }
    }
}
