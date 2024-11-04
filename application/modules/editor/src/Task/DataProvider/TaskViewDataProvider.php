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

namespace MittagQI\Translate5\Task\DataProvider;

use MittagQI\Translate5\User\Model\User;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use ZfExtended_Models_Filter;

class TaskViewDataProvider
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly TaskQuerySelectFactory $taskQuerySelectFactory,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            TaskQuerySelectFactory::create(),
        );
    }

    public function getProjectList(
        User $viewer,
        ?ZfExtended_Models_Filter $filter,
        int $offset = 0,
        int $limit = 0,
    ): array {
        $totalSelect = $this->taskQuerySelectFactory->createTotalProjectCountSelect($viewer, $filter);
        $select = $this->taskQuerySelectFactory->createProjectSelect($viewer, $filter, $offset, $limit);

        $totalCount = $this->db->fetchOne($totalSelect);
        $tasks = $this->db->fetchAll($select);

        // TODO: extract logic from TaskController to here
        return [
            'totalCount' => $totalCount,
            'rows' => $tasks,
        ];
    }

    public function getTaskList(
        User $viewer,
        ?ZfExtended_Models_Filter $filter,
        int $offset = 0,
        int $limit = 0,
    ): array {
        $totalSelect = $this->taskQuerySelectFactory->createTotalTaskCountSelect($viewer, $filter);
        $select = $this->taskQuerySelectFactory->createTaskSelect($viewer, $filter, $offset, $limit);

        $totalCount = $this->db->fetchOne($totalSelect);
        $tasks = $this->db->fetchAll($select);

        // TODO: extract logic from TaskController to here
        return [
            'totalCount' => $totalCount,
            'rows' => $tasks,
        ];
    }
}