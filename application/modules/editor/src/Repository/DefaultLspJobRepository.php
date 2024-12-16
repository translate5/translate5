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

use editor_Models_Db_TaskUserAssoc as DefaultUserJobTable;
use editor_Models_Task as Task;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Model\Db\DefaultLspJobTable;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Model\DefaultLspJob;
use MittagQI\Translate5\DefaultJobAssignment\Exception\DefaultLspJobAlreadyExistsException;
use MittagQI\Translate5\DefaultJobAssignment\Exception\InexistentDefaultLspJobException;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\Model\Db\LanguageServiceProviderTable;
use PDO;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;

class DefaultLspJobRepository
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

    /**
     * @throws InexistentDefaultLspJobException
     */
    public function get(int $id): DefaultLspJob
    {
        try {
            $job = ZfExtended_Factory::get(DefaultLspJob::class);
            $job->load($id);

            return $job;
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new InexistentDefaultLspJobException((string) $id);
        }
    }

    public function delete(int $jobId): void
    {
        $this->db->delete(
            DefaultLspJobTable::TABLE_NAME,
            $this->db->quoteInto('id = ?', $jobId)
        );
    }

    /**
     * @throws DefaultLspJobAlreadyExistsException
     */
    public function save(DefaultLspJob $job): void
    {
        try {
            $job->save();
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            throw new DefaultLspJobAlreadyExistsException(previous: $e);
        }
    }

    /**
     * @return iterable<DefaultLspJob>
     */
    public function getDefaultLspJobs(int $lspId): iterable
    {
        $job = new DefaultLspJob();

        $select = $this->db
            ->select()
            ->from(DefaultLspJobTable::TABLE_NAME)
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

    public function findDefaultLspJobByDataJobId(int $dataJobId): ?DefaultLspJob
    {
        $job = new DefaultLspJob();

        $select = $this->db
            ->select()
            ->from(DefaultLspJobTable::TABLE_NAME)
            ->where('dataJobId = ?', $dataJobId)
        ;

        $stmt = $this->db->query($select);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

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

    /**
     * @return iterable<DefaultLspJob>
     */
    public function getDefaultLspJobsOfForCustomerAndWorkflow(int $customerId, string $workflow): iterable
    {
        $job = new DefaultLspJob();

        $select = $this->db
            ->select()
            ->from([
                'DefaultLspJob' => DefaultLspJobTable::TABLE_NAME,
            ])
            ->where('DefaultLspJob.customerId = ?', $customerId)
            ->where('DefaultLspJob.workflow = ?', $workflow)
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
     * @return iterable<DefaultLspJob>
     */
    public function getDefaultLspJobsOfLspForCustomer(int $lspId, int $customerId): iterable
    {
        $job = new DefaultLspJob();

        $select = $this->db
            ->select()
            ->from([
                'DefaultLspJob' => DefaultLspJobTable::TABLE_NAME,
            ])
            ->where('DefaultLspJob.lspId = ?', $lspId)
            ->where('DefaultLspJob.customerId = ?', $customerId)
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

    public function coordinatorAssignedToDefaultLspJobs(JobCoordinator $coordinator): bool
    {
        $select = $this->db
            ->select()
            ->from(
                [
                    'lspJob' => DefaultLspJobTable::TABLE_NAME,
                ],
                'COUNT(*)'
            )
            ->join(
                [
                    'userJob' => DefaultUserJobTable::TABLE_NAME,
                ],
                'lspJob.dataJobId = userJob.id',
                []
            )
            ->where('userJob.userGuid = ?', $coordinator->user->getUserGuid())
        ;

        return (int) $this->db->fetchOne($select) > 0;
    }

    /**
     * @return iterable<DefaultLspJob>
     */
    public function getDefaultLspJobsOfDirectLspsForTask(Task $task): iterable
    {
        $job = new DefaultLspJob();

        $select = $this->db
            ->select()
            ->from([
                'DefaultLspJob' => DefaultLspJobTable::TABLE_NAME
            ])
            ->join(
                [
                    'lsp' => LanguageServiceProviderTable::TABLE_NAME,
                ],
                'DefaultLspJob.lspId = lsp.id',
                []
            )
            ->where('customerId = ?', $task->getCustomerId())
            ->where('sourceLang = ?', $task->getSourceLang())
            ->where('targetLang = ?', $task->getTargetLang())
            ->where('lsp.parentId IS NULL')
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
     * @return iterable<DefaultLspJob>
     */
    public function getDefaultLspJobsOfSubLspsForTask(Task $task, int ...$parentLspIds): iterable
    {
        $job = new DefaultLspJob();

        $select = $this->db
            ->select()
            ->from([
                'DefaultLspJob' => DefaultLspJobTable::TABLE_NAME
            ])
            ->join(
                [
                    'lsp' => LanguageServiceProviderTable::TABLE_NAME,
                ],
                'DefaultLspJob.lspId = lsp.id',
                []
            )
            ->where('customerId = ?', $task->getCustomerId())
            ->where('sourceLang = ?', $task->getSourceLang())
            ->where('targetLang = ?', $task->getTargetLang())
            ->where('lsp.parentId in (?)', $parentLspIds)
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
}
