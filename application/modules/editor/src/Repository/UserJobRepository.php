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

use editor_Models_Task as Task;
use editor_Models_TaskUserAssoc as UserJob;
use editor_Models_TaskUserAssoc_Segmentrange;
use editor_Task_Type;
use MittagQI\Translate5\LspJob\Model\LspJobAssociation;
use MittagQI\Translate5\UserJob\TypeEnum;
use PDO;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use Zend_Db_Table_Row;
use ZfExtended_Factory;

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

    public function delete(UserJob $job): void
    {
        $job->delete();
    }

    public function userHasJobsInTask(string $userGuid, string $taskGuid): bool
    {
        $job = ZfExtended_Factory::get(UserJob::class);
        $select = $this->db
            ->select()
            ->from($job->db->info($job->db::NAME), 'COUNT(*)')
            ->where('taskGuid = ?', $taskGuid)
            ->where('userGuid = ?', $userGuid)
            ->where('type != ?', TypeEnum::Lsp->value)
        ;

        return (int) $this->db->fetchOne($select) > 0;
    }

    /**
     * @return iterable<UserJob>
     */
    public function getUserJobsOfCustomer(string $userGuid, int $customerId): iterable
    {
        $job = ZfExtended_Factory::get(UserJob::class);
        $taskDb = ZfExtended_Factory::get(Task::class)->db;

        $select = $this->db
            ->select()
            ->from([
                'job' => $job->db->info($job->db::NAME),
            ])
            ->join(
                [
                    'task' => $taskDb->info($taskDb::NAME),
                ],
                'job.taskGuid = task.taskGuid',
                []
            )
            ->where('job.type != ?', TypeEnum::Lsp->value)
            ->where('job.userGuid = ?', $userGuid)
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
        $job = ZfExtended_Factory::get(UserJob::class);
        $select = $this->db
            ->select()
            ->from($job->db->info($job->db::NAME))
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
    public function getDataJobByLspJob(LspJobAssociation $lspJob): UserJob
    {
        $job = ZfExtended_Factory::get(UserJob::class);
        $select = $this->db
            ->select()
            ->from($job->db->info($job->db::NAME))
            ->where('type = ?', TypeEnum::Lsp->value)
            ->where('lspJobId = ?', $lspJob->getId());

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

    /**
     * @return iterable<UserJob>
     */
    public function getJobsByUserGuid(string $userGuid): iterable
    {
        $tua = ZfExtended_Factory::get(UserJob::class);
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

    public function save(UserJob $job): void
    {
        $job->save();
    }

    /**
     * @return iterable<UserJob>
     */
    public function getTaskJobs(Task $task, bool $excludePmOverride = false): iterable
    {
        $job = ZfExtended_Factory::get(UserJob::class);

        $jobs = $job->loadByTaskGuidList([$task->getTaskGuid()]);

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
        $job = ZfExtended_Factory::get(UserJob::class);
        $task = ZfExtended_Factory::get(Task::class);

        $s = $this->db
            ->select()
            ->from([
                'tua' => $job->db->info($job->db::NAME),
            ])
            ->join(
                [
                    't' => $task->db->info($task->db::NAME),
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
        $job = ZfExtended_Factory::get(UserJob::class);

        $s = $this->db
            ->select()
            ->from($job->db->info($job->db::NAME))
            ->where('taskGuid = ?', $taskGuid)
            ->where('workflowStepName = ?', $workflowStepName)
            ->where('userGuid != ?', $exceptUserGuid)
            ->where('segmentrange IS NOT NULL');

        $tuaRows = $this->db->fetchAll($s, [], PDO::FETCH_ASSOC);

        return editor_Models_TaskUserAssoc_Segmentrange::getSegmentNumbersFromRows($tuaRows);
    }
}
