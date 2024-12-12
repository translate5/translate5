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

use editor_Models_Db_UserAssocDefault as DefaultUserJobTable;
use editor_Models_Task as Task;
use editor_Models_UserAssocDefault as DefaultUserJob;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Model\Db\DefaultLspJobTable;
use MittagQI\Translate5\DefaultJobAssignment\Exception\DefaultUserJobAlreadyExistsException;
use MittagQI\Translate5\DefaultJobAssignment\Exception\InexistentDefaultUserJobException;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use PDO;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;

class DefaultUserJobRepository
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly LspUserRepositoryInterface $lspUserRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            LspUserRepository::create(),
        );
    }

    public function find(int $id): ?DefaultUserJob
    {
        try {
            return $this->get($id);
        } catch (InexistentDefaultUserJobException) {
            return null;
        }
    }

    /**
     * @throws InexistentDefaultUserJobException
     */
    public function get(int $id): DefaultUserJob
    {
        try {
            $job = ZfExtended_Factory::get(DefaultUserJob::class);
            $job->load($id);

            return $job;
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new InexistentDefaultUserJobException((string) $id);
        }
    }

    public function delete(int $jobId): void
    {
        $this->db->delete(
            DefaultUserJobTable::TABLE_NAME,
            $this->db->quoteInto('id = ?', $jobId)
        );
    }

    /**
     * @throws DefaultUserJobAlreadyExistsException
     */
    public function save(DefaultUserJob $job): void
    {
        try {
            $job->save();
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            throw new DefaultUserJobAlreadyExistsException(previous: $e);
        }
    }

    /**
     * @return iterable<DefaultUserJob>
     */
    public function getDefaultUserJobsOfForCustomerAndWorkflow(int $customerId, string $workflow): iterable
    {
        $job = new DefaultUserJob();

        $select = $this->db
            ->select()
            ->from([
                'DefaultUserJob' => DefaultUserJobTable::TABLE_NAME,
            ])
            ->joinLeft(
                [
                    'DefaultLspJob' => DefaultLspJobTable::TABLE_NAME,
                ],
                'DefaultUserJob.id = DefaultLspJob.dataJobId',
                []
            )
            ->where('DefaultUserJob.customerId = ?', $customerId)
            ->where('DefaultUserJob.workflow = ?', $workflow)
            ->where('DefaultLspJob.dataJobId IS NULL')
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
     * @return iterable<DefaultUserJob>
     */
    public function getDefaultJobsOfCustomerForUsersOfLsp(int $customerId, int $lspId): iterable
    {
        $userGuids = $this->lspUserRepository->getUserGuids($lspId);

        $select = $this->db
            ->select()
            ->from(DefaultUserJobTable::TABLE_NAME)
            ->where('customerId = ?', $customerId)
            ->where('userGuid in (?)', $userGuids)
        ;

        $job = new DefaultUserJob();

        foreach ($this->db->fetchAll($select) as $row) {
            $job->init($row);

            yield $job;
        }
    }

    /**
     * @return iterable<DefaultUserJob>
     */
    public function getDefaultLspJobsForTask(Task $task): iterable
    {
        $select = $this->db
            ->select()
            ->from([
                'defaultUserJob' => DefaultUserJobTable::TABLE_NAME,
            ])
            ->joinLeft(
                [
                    'defaultLspJob' => DefaultLspJobTable::TABLE_NAME,
                ],
                'defaultUserJob.id = defaultLspJob.dataJobId',
                []
            )
            ->where('defaultUserJob.customerId = ?', $task->getCustomerId())
            ->where('defaultUserJob.sourceLang = ?', $task->getSourceLang())
            ->where('defaultUserJob.targetLang = ?', $task->getTargetLang())
            ->where('defaultLspJob.dataJobId IS NULL')
        ;

        $job = new DefaultUserJob();

        foreach ($this->db->fetchAll($select) as $row) {
            $job->init($row);

            yield $job;
        }
    }
}
