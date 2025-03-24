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

namespace MittagQI\Translate5\Task\Filtering;

use editor_Models_Db_Customer;
use editor_Models_Db_TaskUserAssoc;
use editor_Models_Filter_TaskSpecific;
use editor_Models_Task as Task;
use ZfExtended_Models_Filter_Join as Join;

class TaskQueryFilterAndSort
{
    public function getFilterTypeMap(): array
    {
        return [
            'customerId' => [
                'string' => new Join(editor_Models_Db_Customer::TABLE_NAME, 'name', 'id', 'customerId'),
            ],
            'workflowState' => [
                'list' => new Join(editor_Models_Db_TaskUserAssoc::TABLE_NAME, 'state', 'taskGuid', 'taskGuid'),
            ],
            'workflowStep' => [
                'list' => new Join(
                    editor_Models_Db_TaskUserAssoc::TABLE_NAME,
                    'workflowStepName',
                    'taskGuid',
                    'taskGuid'
                ),
            ],
            'workflowUserRole' => [
                'list' => new Join(editor_Models_Db_TaskUserAssoc::TABLE_NAME, 'role', 'taskGuid', 'taskGuid'),
            ],
            'userName' => [
                'list' => new Join(editor_Models_Db_TaskUserAssoc::TABLE_NAME, 'userGuid', 'taskGuid', 'taskGuid'),
            ],
            'userAssocDeadline' => [
                'date' => new Join(
                    editor_Models_Db_TaskUserAssoc::TABLE_NAME,
                    'deadlineDate',
                    'taskGuid',
                    'taskGuid',
                    'date'
                ),
            ],
            'segmentFinishCount' => [
                'numeric' => 'percent',
                'totalField' => 'segmentEditableCount',
            ],
            'userState' => [
                'list' => new Join(editor_Models_Db_TaskUserAssoc::TABLE_NAME, 'state', 'taskGuid', 'taskGuid'),
            ],
            'orderdate' => [
                'numeric' => 'date',
            ],
            'assignmentDate' => [
                'numeric' => new Join(
                    editor_Models_Db_TaskUserAssoc::TABLE_NAME,
                    'assignmentDate',
                    'taskGuid',
                    'taskGuid',
                    'date'
                ),
            ],
            'finishedDate' => [
                'numeric' => new Join(
                    editor_Models_Db_TaskUserAssoc::TABLE_NAME,
                    'finishedDate',
                    'taskGuid',
                    'taskGuid',
                    'date'
                ),
            ],
            'deadlineDate' => [
                'numeric' => new Join(
                    editor_Models_Db_TaskUserAssoc::TABLE_NAME,
                    'deadlineDate',
                    'taskGuid',
                    'taskGuid',
                    'date'
                ),
            ],
        ];
    }

    public function getSortColMap(): array
    {
        $filterTypeMap = $this->getFilterTypeMap();
        $sortColMap = [];

        $sortColMap['customerId'] = $filterTypeMap['customerId']['string'];
        $sortColMap['userAssocDeadline'] = $filterTypeMap['userAssocDeadline']['date'];

        return $sortColMap;
    }

    public function getTaskFilter(string $jsonFilter, ?Task $task = null): editor_Models_Filter_TaskSpecific
    {
        $task = $task ?: new Task();

        $filter = new editor_Models_Filter_TaskSpecific($task, $jsonFilter);
        $filter->setMappings($this->getSortColMap(), $this->getFilterTypeMap());

        return $filter;
    }
}
