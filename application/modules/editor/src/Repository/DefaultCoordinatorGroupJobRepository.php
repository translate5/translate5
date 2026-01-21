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
use MittagQI\Translate5\CoordinatorGroup\JobCoordinator;
use MittagQI\Translate5\CoordinatorGroup\Model\Db\CoordinatorGroupTable;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Model\Db\DefaultCoordinatorGroupJobTable;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Model\DefaultCoordinatorGroupJob;
use MittagQI\Translate5\DefaultJobAssignment\Exception\DefaultCoordinatorGroupJobAlreadyExistsException;
use MittagQI\Translate5\DefaultJobAssignment\Exception\InexistentDefaultCoordinatorGroupJobException;
use PDO;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;

class DefaultCoordinatorGroupJobRepository
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
     * @throws InexistentDefaultCoordinatorGroupJobException
     */
    public function get(int $id): DefaultCoordinatorGroupJob
    {
        try {
            $job = ZfExtended_Factory::get(DefaultCoordinatorGroupJob::class);
            $job->load($id);

            return $job;
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new InexistentDefaultCoordinatorGroupJobException((string) $id);
        }
    }

    public function delete(int $jobId): void
    {
        $this->db->delete(
            DefaultCoordinatorGroupJobTable::TABLE_NAME,
            $this->db->quoteInto('id = ?', $jobId)
        );
    }

    /**
     * @throws DefaultCoordinatorGroupJobAlreadyExistsException
     */
    public function save(DefaultCoordinatorGroupJob $job): void
    {
        try {
            $job->save();
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            throw new DefaultCoordinatorGroupJobAlreadyExistsException(previous: $e);
        }
    }

    /**
     * @return iterable<DefaultCoordinatorGroupJob>
     */
    public function getDefaultCoordinatorGroupJobs(int $groupId): iterable
    {
        $job = new DefaultCoordinatorGroupJob();

        $select = $this->db
            ->select()
            ->from(DefaultCoordinatorGroupJobTable::TABLE_NAME)
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

    public function findDefaultCoordinatorGroupJobByDataJobId(int $dataJobId): ?DefaultCoordinatorGroupJob
    {
        $job = new DefaultCoordinatorGroupJob();

        $select = $this->db
            ->select()
            ->from(DefaultCoordinatorGroupJobTable::TABLE_NAME)
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
     * @return iterable<DefaultCoordinatorGroupJob>
     */
    public function getDefaultCoordinatorGroupJobsOfForCustomerAndWorkflow(int $customerId, string $workflow): iterable
    {
        $job = new DefaultCoordinatorGroupJob();

        $select = $this->db
            ->select()
            ->from([
                'DefaultGroupJob' => DefaultCoordinatorGroupJobTable::TABLE_NAME,
            ])
            ->where('DefaultGroupJob.customerId = ?', $customerId)
            ->where('DefaultGroupJob.workflow = ?', $workflow)
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
     * @return iterable<DefaultCoordinatorGroupJob>
     */
    public function getDefaultCoordinatorGroupJobsOfGroupForCustomer(int $groupId, int $customerId): iterable
    {
        $job = new DefaultCoordinatorGroupJob();

        $select = $this->db
            ->select()
            ->from([
                'DefaultGroupJob' => DefaultCoordinatorGroupJobTable::TABLE_NAME,
            ])
            ->where('DefaultGroupJob.groupId = ?', $groupId)
            ->where('DefaultGroupJob.customerId = ?', $customerId)
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

    public function coordinatorAssignedToDefaultCoordinatorGroupJobs(JobCoordinator $coordinator): bool
    {
        $select = $this->db
            ->select()
            ->from(
                [
                    'groupJob' => DefaultCoordinatorGroupJobTable::TABLE_NAME,
                ],
                'COUNT(*)'
            )
            ->join(
                [
                    'userJob' => DefaultUserJobTable::TABLE_NAME,
                ],
                'groupJob.dataJobId = userJob.id',
                []
            )
            ->where('userJob.userGuid = ?', $coordinator->user->getUserGuid())
        ;

        return (int) $this->db->fetchOne($select) > 0;
    }

    /**
     * @return iterable<DefaultCoordinatorGroupJob>
     */
    public function getDefaultCoordinatorGroupJobsOfTopRankGroupsForTask(Task $task): iterable
    {
        $job = new DefaultCoordinatorGroupJob();

        $select = $this->db
            ->select()
            ->from([
                'DefaultGroupJob' => DefaultCoordinatorGroupJobTable::TABLE_NAME,
            ])
            ->join(
                [
                    'group' => CoordinatorGroupTable::TABLE_NAME,
                ],
                'DefaultGroupJob.groupId = group.id',
                []
            )
            ->where('customerId = ?', $task->getCustomerId())
            ->where('sourceLang = ?', $task->getSourceLang())
            ->where('targetLang = ?', $task->getTargetLang())
            ->where('group.parentId IS NULL')
        ;

        // Checking if the task is importing because in case of import context,
        // we want all available associations not to be filtered by workflow because
        // the workflow can be changed in the import wizard. After this cleanup is triggered
        // and only the picked workflow associations will be left assigned.
        if (! $task->isImporting()) {
            $select->where('DefaultGroupJob.workflow = ?', $task->getWorkflow());
        }

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
     * @return iterable<DefaultCoordinatorGroupJob>
     */
    public function getDefaultCoordinatorGroupJobsOfSubGroupsForTask(Task $task, int ...$parentGroupIds): iterable
    {
        $job = new DefaultCoordinatorGroupJob();

        $select = $this->db
            ->select()
            ->from([
                'DefaultGroupJob' => DefaultCoordinatorGroupJobTable::TABLE_NAME,
            ])
            ->join(
                [
                    'group' => CoordinatorGroupTable::TABLE_NAME,
                ],
                'DefaultGroupJob.groupId = group.id',
                []
            )
            ->where('customerId = ?', $task->getCustomerId())
            ->where('sourceLang = ?', $task->getSourceLang())
            ->where('targetLang = ?', $task->getTargetLang())
            ->where('group.parentId in (?)', $parentGroupIds)
        ;

        // Checking if the task is importing because in case of import context,
        // we want all available associations not to be filtered by workflow because
        // the workflow can be changed in the import wizard. After this cleanup is triggered
        // and only the picked workflow associations will be left assigned.
        if (! $task->isImporting()) {
            $select->where('DefaultGroupJob.workflow = ?', $task->getWorkflow());
        }

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
