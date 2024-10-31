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
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LspJob\Exception\InexistentLspJobException;
use MittagQI\Translate5\LspJob\Exception\LspJobAlreadyExistsException;
use MittagQI\Translate5\LspJob\Exception\NotFoundLspJobException;
use MittagQI\Translate5\LspJob\Model\LspJobAssociation;
use PDO;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use ZfExtended_Factory;
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

    public function getEmptyModel(): LspJobAssociation
    {
        return ZfExtended_Factory::get(LspJobAssociation::class);
    }

    /**
     * @throws InexistentLspJobException
     */
    public function get(int $id): LspJobAssociation
    {
        try {
            $job = ZfExtended_Factory::get(LspJobAssociation::class);
            $job->load($id);

            return $job;
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new InexistentLspJobException((string) $id);
        }
    }

    public function delete(LspJobAssociation $job): void
    {
        $job->delete();
    }

    /**
     * @throws LspJobAlreadyExistsException
     */
    public function save(LspJobAssociation $job): void
    {
        try {
            $job->save();
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            throw new LspJobAlreadyExistsException(previous: $e);
        }
    }

    public function lspHasJobInTask(int $lspId, string $taskGuid): bool
    {
        $job = ZfExtended_Factory::get(LspJobAssociation::class);

        $select = $this->db
            ->select()
            ->from($job->db->info($job->db::NAME), 'COUNT(*)')
            ->where('taskGuid = ?', $taskGuid)
            ->where('lspId = ?', $lspId);

        return (int) $this->db->fetchOne($select) > 0;
    }

    public function coordinatorAssignedToLspJobs(JobCoordinator $coordinator): bool
    {
        $lspJob = ZfExtended_Factory::get(LspJobAssociation::class);
        $userJob = ZfExtended_Factory::get(UserJob::class);

        $select = $this->db
            ->select()
            ->from($lspJob->db->info($lspJob->db::NAME), 'COUNT(*)')
            ->join(
                [
                    'userJob' => $userJob->db->info($userJob->db::NAME),
                ],
                'userJob.lspJobId = lspJob.Id',
                []
            )
            ->where('userGuid = ?', $coordinator->user->getUserGuid());

        return (int) $this->db->fetchOne($select) > 0;
    }

    /**
     * @return iterable<LspJobAssociation>
     */
    public function getLspJobs(int $lspId): iterable
    {
        $job = ZfExtended_Factory::get(LspJobAssociation::class);

        $select = $this->db
            ->select()
            ->from($job->db->info($job->db::NAME))
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
     * @return iterable<LspJobAssociation>
     */
    public function getLspJobsOfCustomer(int $lspId, int $customerId): iterable
    {
        $job = ZfExtended_Factory::get(LspJobAssociation::class);
        $taskDb = ZfExtended_Factory::get(Task::class)->db;

        $select = $this->db
            ->select()
            ->from(['lspJob' => $job->db->info($job->db::NAME)])
            ->join(
                [
                    'task' => $taskDb->info($taskDb::NAME),
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
        $job = ZfExtended_Factory::get(LspJobAssociation::class);
        $userJobDb = ZfExtended_Factory::get(UserJob::class)->db;
        $taskDb = ZfExtended_Factory::get(Task::class)->db;

        $select = $this->db
            ->select()
            ->from(['lspJob' => $job->db->info($job->db::NAME)], 'COUNT(*)')
            ->join(
                [
                    'userJob' => $userJobDb->info($userJobDb::NAME),
                ],
                'lspJob.id = userJob.lspJobId',
                []
            )
            ->join(
                [
                    'task' => $taskDb->info($taskDb::NAME),
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
     * @return iterable<LspJobAssociation>
     */
    public function getSubLspJobs(LspJobAssociation $lspJob): iterable
    {
        $job = ZfExtended_Factory::get(LspJobAssociation::class);
        $lsp = ZfExtended_Factory::get(LanguageServiceProvider::class);

        $select = $this->db
            ->select()
            ->from(['lspJob' => $job->db->info($job->db::NAME)])
            ->join(
                [
                    'lsp' => $lsp->db->info($lsp->db::NAME),
                ],
                'lsp.Id = lspJob.lspId',
                []
            )
            ->where('lsp.parentId = ?', $lspJob->getId())
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
            $this->getByTaskGuidAndWorkflow($lspId, $taskGuid, $workflow, $workflowStepName);

            return true;
        } catch (NotFoundLspJobException) {
            return false;
        }
    }

    /**
     * @throws NotFoundLspJobException
     */
    public function getByTaskGuidAndWorkflow(
        int $lspId,
        string $taskGuid,
        string $workflow,
        string $workflowStepName,
    ): LspJobAssociation {
        $lspJob = ZfExtended_Factory::get(LspJobAssociation::class);

        $select = $this->db
            ->select()
            ->from([
                'lspJob' => $lspJob->db->info($lspJob->db::NAME),
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
}
