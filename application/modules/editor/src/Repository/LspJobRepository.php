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
use MittagQI\Translate5\JobAssignment\LspJob\Exception\InexistentLspJobException;
use MittagQI\Translate5\JobAssignment\LspJob\Exception\LspJobAlreadyExistsException;
use MittagQI\Translate5\JobAssignment\LspJob\Exception\NotFoundLspJobException;
use MittagQI\Translate5\JobAssignment\LspJob\Model\Db\LspJobTable;
use MittagQI\Translate5\JobAssignment\LspJob\Model\LspJob;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\Model\Db\LanguageServiceProviderTable;
use MittagQI\Translate5\LSP\Model\Db\LanguageServiceProviderUserTable;
use PDO;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Expr;
use Zend_Db_Table;
use ZfExtended_Factory;
use ZfExtended_Models_Db_User;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;

class LspJobRepository
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

    public function getEmptyModel(): LspJob
    {
        return ZfExtended_Factory::get(LspJob::class);
    }

    /**
     * @throws InexistentLspJobException
     */
    public function get(int $id): LspJob
    {
        try {
            $job = ZfExtended_Factory::get(LspJob::class);
            $job->load($id);

            return $job;
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new InexistentLspJobException((string) $id);
        }
    }

    public function delete(int $jobId): void
    {
        $this->db->delete(
            LspJobTable::TABLE_NAME,
            $this->db->quoteInto('id = ?', $jobId)
        );
    }

    /**
     * @throws LspJobAlreadyExistsException
     */
    public function save(LspJob $job): void
    {
        try {
            $job->save();
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            throw new LspJobAlreadyExistsException(previous: $e);
        }
    }

    public function lspHasJobInTask(int $lspId, string $taskGuid): bool
    {
        $job = ZfExtended_Factory::get(LspJob::class);

        $select = $this->db
            ->select()
            ->from($job->db->info($job->db::NAME), 'COUNT(*)')
            ->where('taskGuid = ?', $taskGuid)
            ->where('lspId = ?', $lspId);

        return (int) $this->db->fetchOne($select) > 0;
    }

    public function lspOfCoordinatorHasJobForTaskWorkflowStep(string $userGuid, string $taskGuid): bool
    {
        $select = $this->db
            ->select()
            ->from(
                [
                    'lspJob' => LspJobTable::TABLE_NAME,
                ],
                'COUNT(*)'
            )
            ->join(
                [
                    'task' => TaskTable::TABLE_NAME,
                ],
                'lspJob.taskGuid = task.taskGuid AND (task.workflowStepName = lspJob.workflowStepName OR task.workflowStepName = \'no workflow\')',
                []
            )
            ->join(
                [
                    'lspUser' => LanguageServiceProviderUserTable::TABLE_NAME,
                ],
                'lspUser.lspId = lspJob.lspId',
                []
            )
            ->join(
                [
                    'user' => ZfExtended_Models_Db_User::TABLE_NAME,
                ],
                'lspUser.userId = user.id',
                []
            )
            ->where('lspJob.taskGuid = ?', $taskGuid)
            ->where('user.userGuid = ?', $userGuid)
        ;

        return (int) $this->db->fetchOne($select) > 0;
    }

    public function findCurrentLspJobOfCoordinatorInTask(
        string $userGuid,
        string $taskGuid,
        string $workflowStepName
    ): ?LspJob {
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
                'lspJob' => LspJobTable::TABLE_NAME,
            ])
            ->join(
                [
                    'lspUser' => LanguageServiceProviderUserTable::TABLE_NAME,
                ],
                'lspUser.lspId = lspJob.lspId',
                []
            )
            ->join(
                [
                    'user' => ZfExtended_Models_Db_User::TABLE_NAME,
                ],
                'lspUser.userId = user.id',
                []
            )
            ->join(
                [
                    'userJob' => UserJobTable::TABLE_NAME,
                ],
                'userJob.lspJobId = lspJob.id',
                []
            )
            ->where('lspJob.taskGuid = ?', $taskGuid)
            ->where('userJob.type = ?', TypeEnum::Lsp->value)
            ->where('user.userGuid = ?', $userGuid)
            ->order(new Zend_Db_Expr($order))
        ;

        $row = $this->db->fetchRow($select);

        if (empty($row)) {
            return null;
        }

        $job = new LspJob();
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

    public function coordinatorAssignedToLspJobs(JobCoordinator $coordinator): bool
    {
        $select = $this->db
            ->select()
            ->from(
                [
                    'lspJob' => LspJobTable::TABLE_NAME,
                ],
                'COUNT(*)'
            )
            ->join(
                [
                    'userJob' => UserJobTable::TABLE_NAME,
                ],
                'userJob.lspJobId = lspJob.id',
                []
            )
            ->where('userJob.userGuid = ?', $coordinator->user->getUserGuid())
            ->where('userJob.type = ?', TypeEnum::Lsp->value)
        ;

        return (int) $this->db->fetchOne($select) > 0;
    }

    /**
     * @return iterable<LspJob>
     */
    public function getLspJobs(int $lspId): iterable
    {
        $job = new LspJob();

        $select = $this->db
            ->select()
            ->from(LspJobTable::TABLE_NAME)
            ->where('lspId = ?', $lspId)
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
     * @return iterable<LspJob>
     */
    public function getLspJobsOfCustomer(int $lspId, int $customerId): iterable
    {
        $job = new LspJob();

        $select = $this->db
            ->select()
            ->from([
                'lspJob' => LspJobTable::TABLE_NAME,
            ])
            ->join(
                [
                    'task' => TaskTable::TABLE_NAME,
                ],
                'lspJob.taskGuid = task.taskGuid',
                []
            )
            ->where('lspJob.lspId = ?', $lspId)
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

    public function coordinatorHasLspJobsOfCustomer(string $userGuid, int $customerId): bool
    {
        $select = $this->db
            ->select()
            ->from(
                [
                    'lspJob' => LspJobTable::TABLE_NAME,
                ],
                'COUNT(*)'
            )
            ->join(
                [
                    'userJob' => UserJobTable::TABLE_NAME,
                ],
                'lspJob.id = userJob.lspJobId',
                []
            )
            ->join(
                [
                    'task' => TaskTable::TABLE_NAME,
                ],
                'lspJob.taskGuid = task.taskGuid',
                []
            )
            ->where('userJob.userGuid = ?', $userGuid)
            ->where('task.customerId = ?', $customerId)
        ;

        return (int) $this->db->fetchOne($select) > 0;
    }

    /**
     * @return iterable<LspJob>
     */
    public function getSubLspJobsOf(int $lspJobId): iterable
    {
        $job = ZfExtended_Factory::get(LspJob::class);

        $select = $this->db
            ->select()
            ->from([
                'lspJob' => LspJobTable::TABLE_NAME,
            ])
            ->join(
                [
                    'lsp' => LanguageServiceProviderTable::TABLE_NAME,
                ],
                'lspJob.lspId = lsp.id',
                []
            )
            ->join(
                [
                    'parentLspJob' => LspJobTable::TABLE_NAME,
                ],
                implode(
                    ' AND ',
                    [
                        'lsp.parentId = parentLspJob.lspId',
                        'lspJob.taskGuid = parentLspJob.taskGuid',
                        'lspJob.workflowStepName = parentLspJob.workflowStepName',
                    ]
                ),
                []
            )
            ->where('parentLspJob.id = ?', $lspJobId)
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
        int $lspId,
        string $taskGuid,
        string $workflow,
        string $workflowStepName,
    ): bool {
        try {
            $this->getByLspIdTaskGuidAndWorkflow($lspId, $taskGuid, $workflow, $workflowStepName);

            return true;
        } catch (NotFoundLspJobException) {
            return false;
        }
    }

    /**
     * @throws NotFoundLspJobException
     */
    public function getByLspIdTaskGuidAndWorkflow(
        int $lspId,
        string $taskGuid,
        string $workflow,
        string $workflowStepName,
    ): LspJob {
        $lspJob = new LspJob();

        $select = $this->db
            ->select()
            ->from([
                'lspJob' => LspJobTable::TABLE_NAME,
            ])
            ->where('lspJob.lspId = ?', $lspId)
            ->where('lspJob.taskGuid = ?', $taskGuid)
            ->where('lspJob.workflow = ?', $workflow)
            ->where('lspJob.workflowStepName = ?', $workflowStepName)
        ;

        $row = $this->db->fetchRow($select);

        if (empty($row)) {
            throw new NotFoundLspJobException($lspId, $taskGuid, $workflow, $workflowStepName);
        }

        $lspJob->init(
            new \Zend_Db_Table_Row(
                [
                    'table' => $lspJob->db,
                    'data' => $row,
                    'stored' => true,
                    'readOnly' => false,
                ]
            )
        );

        return $lspJob;
    }

    /**
     * @return iterable<LspJob>
     */
    public function getByTaskGuidAndWorkflow(
        string $taskGuid,
        string $workflow,
        string $workflowStepName,
    ): iterable {
        $job = new LspJob();

        $select = $this->db
            ->select()
            ->from([
                'lspJob' => LspJobTable::TABLE_NAME,
            ])
            ->where('lspJob.taskGuid = ?', $taskGuid)
            ->where('lspJob.workflow = ?', $workflow)
            ->where('lspJob.workflowStepName = ?', $workflowStepName)
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

    public function getTaskLspJobs(string $taskGuid): iterable
    {
        $lspJob = new LspJob();

        $select = $this->db
            ->select()
            ->from([
                'lspJob' => LspJobTable::TABLE_NAME,
            ])
            ->where('lspJob.taskGuid = ?', $taskGuid)
        ;

        foreach ($this->db->fetchAll($select) as $jobData) {
            $lspJob->init(
                new \Zend_Db_Table_Row(
                    [
                        'table' => $lspJob->db,
                        'data' => $jobData,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            yield clone $lspJob;
        }
    }
}
