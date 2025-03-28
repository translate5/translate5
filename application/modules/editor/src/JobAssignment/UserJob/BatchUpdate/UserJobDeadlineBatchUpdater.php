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

namespace MittagQI\Translate5\JobAssignment\UserJob\BatchUpdate;

use editor_Models_Db_TaskUserAssoc;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use Zend_Registry;
use ZfExtended_Logger;

class UserJobDeadlineBatchUpdater
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            Zend_Registry::get('logger')->cloneMe('userJob.batchSet.deadlineDate'),
        );
    }

    public function updateDeadlines(array $jobIds, string $newDeadlineDate): void
    {
        if (empty($jobIds)) {
            return;
        }

        $this->db->update(
            editor_Models_Db_TaskUserAssoc::TABLE_NAME,
            [
                'deadlineDate' => $newDeadlineDate,
            ],
            $this->db->quoteInto('id IN (?)', $jobIds)
        );

        $this->logger->info(
            'E1012',
            'Deadline date for user jobs updated to {deadlineDate}',
            [
                'jobs' => $jobIds,
                'deadlineDate' => $newDeadlineDate,
            ]
        );
    }
}
