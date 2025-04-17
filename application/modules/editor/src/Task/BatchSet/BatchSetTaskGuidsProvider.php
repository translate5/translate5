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

namespace MittagQI\Translate5\Task\BatchSet;

use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Task\BatchSet\DTO\TaskGuidsQueryDto;
use MittagQI\Translate5\Task\DataProvider\TaskQuerySelectFactory;
use MittagQI\Translate5\Task\Filtering\TaskQueryFilterAndSort;
use Zend_Db_Table;
use ZfExtended_Authentication;

class BatchSetTaskGuidsProvider
{
    public function __construct(
        private readonly TaskQuerySelectFactory $taskQuerySelectFactory,
        private readonly UserRepository $userRepository,
        private readonly \ZfExtended_AuthenticationInterface $authentication,
        private readonly \Zend_Db_Adapter_Abstract $db,
        private readonly TaskQueryFilterAndSort $taskQueryFilterAndSort,
    ) {
    }

    public static function create(): self
    {
        return new self(
            TaskQuerySelectFactory::create(),
            new UserRepository(),
            ZfExtended_Authentication::getInstance(),
            Zend_Db_Table::getDefaultAdapter(),
            new TaskQueryFilterAndSort(),
        );
    }

    /**
     * @return int[]
     */
    public function getAllowedTaskIds(TaskGuidsQueryDto $query): array
    {
        $taskGuids = $this->getAllowedTaskGuids($query);

        return array_keys($taskGuids);
    }

    public function getAllowedTaskGuids(TaskGuidsQueryDto $query): array
    {
        if (null !== $query->projectAndTaskIds) {
            return $this->getTaskGuidsFromProjectsAndTasks($query->projectAndTaskIds);
        }

        return $this->getTaskGuidsFromFilteredProjects($query->filter);
    }

    /* Returns: taskId => taskGuid array
    Warning: Use carefully, for example the following filter is ignored (it returns all possible tasks)
    $jsonFilter = [{"operator":"in","value":[],"property":"projectId"}] */
    private function fetchAllowedTaskGuids(string $jsonFilter): array
    {
        $filter = $this->taskQueryFilterAndSort->getTaskFilter($jsonFilter);

        $viewer = $this->userRepository->get($this->authentication->getUserId());

        $taskSelect = $this->taskQuerySelectFactory->createTaskSelect($viewer, $filter);

        $rows = $this->db->fetchAll($taskSelect);

        return array_column($rows, 'taskGuid', 'id');
    }

    private function getTaskGuidsFromFilteredProjects(string $jsonFilter): array
    {
        $filter = $this->taskQueryFilterAndSort->getTaskFilter($jsonFilter);

        $viewer = $this->userRepository->get($this->authentication->getUserId());

        $taskSelect = $this->taskQuerySelectFactory->createProjectSelect($viewer, $filter);

        $results = $this->db->fetchAll($taskSelect);
        if (empty($results)) {
            return [];
        }

        return $this->fetchAllowedTaskGuids(
            json_encode([
                [
                    'operator' => 'in',
                    'value' => array_column($results, 'id'),
                    'property' => 'projectId',
                ],
            ])
        );
    }

    private function getTaskGuidsFromProjectsAndTasks(array $taskAndProjectIds): array
    {
        $taskGuids = empty($taskAndProjectIds) ? [] : $this->fetchAllowedTaskGuids(
            json_encode([
                [
                    'operator' => 'in',
                    'value' => $taskAndProjectIds,
                    'property' => 'id',
                ],
            ])
        );

        // Use "+" to preserve array indexes, taskId => taskGuid array is needed
        return $taskGuids +
            $this->getTaskGuidsFromFilteredProjects(
                json_encode([
                    [
                        'operator' => 'in',
                        'value' => $taskAndProjectIds,
                        'property' => 'id',
                    ],
                ])
            );
    }
}
