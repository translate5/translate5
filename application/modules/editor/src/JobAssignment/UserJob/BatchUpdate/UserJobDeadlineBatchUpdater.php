<?php

namespace MittagQI\Translate5\JobAssignment\UserJob\BatchUpdate;

use editor_Models_Db_TaskUserAssoc;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;

class UserJobDeadlineBatchUpdater
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
        );
    }

    /**
     * @param int[] $tuas
     */
    public function updateDeadlines(array $jobIds, string $newDeadlineDate): void
    {
        $this->db->update(
            editor_Models_Db_TaskUserAssoc::TABLE_NAME,
            ['deadlineDate' => $newDeadlineDate],
            $this->db->quoteInto('id IN (?)', $jobIds)
        );
    }
}
