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
use editor_Models_TaskUserAssoc as UserJob;
use editor_Models_Db_TaskUserAssoc as UserJobTable;
use editor_Models_TaskUserAssoc_Segmentrange;
use editor_Task_Type;
use editor_Workflow_Default as Workflow;
use MittagQI\Translate5\UserJob\TypeEnum;
use PDO;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Expr;
use Zend_Db_Table;
use Zend_Db_Table_Row;
use ZfExtended_Factory;
use ZfExtended_Models_Db_User as UserTable;

class UserJobRepository
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
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
            editor_Task_Type::getInstance(),
        );
    }

    public function getEmptyModel(): UserJob
    {
        return ZfExtended_Factory::get(UserJob::class);
    }

    /**
     * @throws \ZfExtended_Models_Entity_NotFoundException
     */
    public function get(int $id): UserJob
    {
        $job = ZfExtended_Factory::get(UserJob::class);
        $job->load($id);

        return $job;
    }

    public function delete(int $jobId): void
    {
        $this->db->delete(
            UserJobTable::TABLE_NAME,
            $this->db->quoteInto('id = ?', $jobId)
        );
    }

    public function userHasJobsInTask(string $userGuid, string $taskGuid): bool
    {
        $select = $this->db
            ->select()
            ->from(UserJobTable::TABLE_NAME, 'COUNT(*)')
            ->where('taskGuid = ?', $taskGuid)
            ->where('userGuid = ?', $userGuid)
            ->where('type != ?', TypeEnum::Lsp->value)
        ;

        return (int) $this->db->fetchOne($select) > 0;
    }

    /**
     * @return iterable<UserJob>
     */
    public function getJobsByTaskAndStep(string $taskGuid, string $workflowStepName): iterable
    {
        $job = new UserJob();

        $select = $this->db
            ->select()
            ->from(UserJobTable::TABLE_NAME)
            ->where('taskGuid = ?', $taskGuid)
            ->where('workflowStepName = ?', $workflowStepName)
            ->where('type != ?', TypeEnum::Lsp->value)
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
     * @return iterable<UserJob>
     */
    public function getUserJobsOfCustomer(string $userGuid, int $customerId): iterable
    {
        $job = new UserJob();

        $select = $this->db
            ->select()
            ->from([
                'job' => UserJobTable::TABLE_NAME,
            ])
            ->join(
                [
                    'task' => TaskTable::TABLE_NAME,
                ],
                'job.taskGuid = task.taskGuid',
                []
            )
            ->where('job.userGuid = ?', $userGuid)
            ->where('job.type != ?', TypeEnum::Lsp->value)
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

    /**
     * @return iterable<UserJob>
     */
    public function getUserJobsByLspJob(int $lspJobId): iterable
    {
        $job = new UserJob();
        $select = $this->db
            ->select()
            ->from(UserJobTable::TABLE_NAME)
            ->where('lspJobId = ?', $lspJobId);

        foreach ($this->db->fetchAll($select) as $jobData) {
            $job->init(
                new Zend_Db_Table_Row(
                    [
                        'table' => $job->db,
                        'data' => $jobData,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            if ($job->isLspJob()) {
                continue;
            }

            yield clone $job;
        }
    }

    /**
     * Returns bound UserJob with type LSP of LspJob
     */
    public function getDataJobByLspJob(int $lspJobId): UserJob
    {
        $job = new UserJob();
        $select = $this->db
            ->select()
            ->from(UserJobTable::TABLE_NAME)
            ->where('type = ?', TypeEnum::Lsp->value)
            ->where('lspJobId = ?', $lspJobId);

        $job->init(
            new Zend_Db_Table_Row(
                [
                    'table' => $job->db,
                    'data' => $this->db->fetchRow($select, [], PDO::FETCH_ASSOC),
                    'stored' => true,
                    'readOnly' => false,
                ]
            )
        );

        return $job;
    }

    public function taskHasConfirmedJob(string $taskGuid, string $workflow, string $workflowStepName): bool
    {
        $select = $this->db
            ->select()
            ->from(UserJobTable::TABLE_NAME, 'count(*)')
            ->where('taskGuid = ?', $taskGuid)
            ->where('workflow = ?', $workflow)
            ->where('workflowStepName = ?', $workflowStepName)
            ->where('state in (?)', [Workflow::STATE_WAITING, Workflow::STATE_OPEN, Workflow::STATE_EDIT])
        ;

        return (int) $this->db->fetchOne($select) > 0;
    }

    /**
     * @return iterable<UserJob>
     */
    public function getJobsByUserGuid(string $userGuid): iterable
    {
        $tua = new UserJob();
        $jobs = $tua->loadByUserGuid($userGuid);

        foreach ($jobs as $job) {
            $tua->init(
                new Zend_Db_Table_Row(
                    [
                        'table' => $tua->db,
                        'data' => $job,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            yield clone $tua;
        }
    }

    public function findUserJobInTask(string $userGuid, string $taskGuid, string $workflowStepName): ?UserJob
    {
        //order first by matching role, then by the states as defined
        $order = $this->db->quoteInto(
            'workflowStepName = ? DESC,'
            . 'state="edit" DESC,'
            . 'state="view" DESC,'
            . 'state="unconfirmed" DESC,'
            . 'state="open" DESC,'
            . 'state="waiting" DESC,'
            . 'state="finished" DESC',
            $workflowStepName
        );

        $s = $this->db->select()
            ->from(UserJobTable::TABLE_NAME)
            ->where('userGuid = ?', $userGuid)
            ->where('taskGuid = ?', $taskGuid)
            ->where('type != ?', TypeEnum::Lsp->value)
            ->order(new Zend_Db_Expr($order));

        $row = $this->db->fetchRow($s);

        if (empty($row)) {
            return null;
        }

        $job = new UserJob();
        $job->init(
            new Zend_Db_Table_Row(
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

    public function save(UserJob $job): void
    {
        $job->save();
    }

    /**
     * @return iterable<UserJob>
     */
    public function getTaskJobs(string $taskGuid, bool $excludePmOverride = false): iterable
    {
        $job = ZfExtended_Factory::get(UserJob::class);

        $jobs = $job->loadByTaskGuidList([$taskGuid]);

        foreach ($jobs as $jobData) {
            $job->init(
                new Zend_Db_Table_Row(
                    [
                        'table' => $job->db,
                        'data' => $jobData,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            if ($excludePmOverride && $job->getIsPmOverride()) {
                continue;
            }

            yield clone $job;
        }
    }

    /**
     * @return iterable<UserJob>
     */
    public function getProjectJobs(int $projectId, ?string $workflow = null): iterable
    {
        $job = new UserJob();

        $s = $this->db
            ->select()
            ->from([
                'tua' => UserJobTable::TABLE_NAME,
            ])
            ->join(
                [
                    't' => TaskTable::TABLE_NAME,
                ],
                't.taskGuid = tua.taskGuid',
            )
            ->where('tua.isPmOverride = 0')
            ->where('t.projectId = ?', $projectId)
            ->where('t.taskType not in (?)', $this->taskType->getProjectTypes(true));

        if (null !== $workflow) {
            $s->where('tua.workflow = ?', $workflow);
        }

        $jobs = $this->db->fetchAll($s, [], PDO::FETCH_ASSOC);

        foreach ($jobs as $jobData) {
            $job->init(
                new Zend_Db_Table_Row(
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
     * Get all assigned segments for task and workflow step name but exclude the given user from the select
     *
     * @return int[]
     */
    public function getAssignedSegmentsExceptForUser(
        string $exceptUserGuid,
        string $taskGuid,
        string $workflowStepName,
    ): array {
        $s = $this->db
            ->select()
            ->from(UserJobTable::TABLE_NAME)
            ->where('taskGuid = ?', $taskGuid)
            ->where('workflowStepName = ?', $workflowStepName)
            ->where('userGuid != ?', $exceptUserGuid)
            ->where('segmentrange IS NOT NULL');

        $tuaRows = $this->db->fetchAll($s, [], PDO::FETCH_ASSOC);

        return editor_Models_TaskUserAssoc_Segmentrange::getSegmentNumbersFromRows($tuaRows);
    }

    /**
     * returns all users to the taskGuid and role of the given UserJob
     *
     * @param array $assocFields optional, column names of the assoc table to be added in the result set
     * @param string $state string or null, additional filter for state of the job
     */
    public function loadUsersOfTaskWithStep(
        string $taskGuid,
        ?string $workflowStepName,
        array $assocFields = [],
        ?string $state = null
    ): array {
        $s = $this->db->select()
            ->from([
                'user' => UserTable::TABLE_NAME,
            ])
            ->join(
                [
                    'job' => UserJobTable::TABLE_NAME,
                ],
                'job.userGuid = user.userGuid',
                $assocFields
            )
            ->where('job.isPmOverride = 0')
            ->where('job.taskGuid = ?', $taskGuid);

        if (! empty($workflowStepName)) {
            $s->where('job.workflowStepName = ?', $workflowStepName);
        }

        if (! empty($state)) {
            $s->where('job.state = ?', $state);
        }

        return $this->db->fetchAll($s);
    }
}
