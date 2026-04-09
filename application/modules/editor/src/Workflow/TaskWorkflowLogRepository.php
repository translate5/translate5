<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2026 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Workflow;

use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;

class TaskWorkflowLogRepository
{
    public const string TABLE_NAME = 'LEK_task_workflow_log';

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

    public function add(
        string $taskGuid,
        string $workflowName,
        string $workflowStepName,
        string $userGuid,
    ): void {
        $this->db->insert(self::TABLE_NAME, [
            'taskGuid' => $taskGuid,
            'workflowName' => $workflowName,
            'workflowStepName' => $workflowStepName,
            'userGuid' => $userGuid,
        ]);
    }

    /**
     * @return string[]
     */
    public function getDistinctStepsInOrder(string $taskGuid): array
    {
        $rows = $this->db->fetchAll(
            'SELECT workflowStepName
             FROM ' . self::TABLE_NAME . '
             WHERE taskGuid = :taskGuid
             GROUP BY workflowStepName
             ORDER BY MIN(id) ASC',
            [
                'taskGuid' => $taskGuid,
            ]
        );

        if (empty($rows)) {
            return [];
        }

        return array_values(array_filter(array_column($rows, 'workflowStepName')));
    }
}
