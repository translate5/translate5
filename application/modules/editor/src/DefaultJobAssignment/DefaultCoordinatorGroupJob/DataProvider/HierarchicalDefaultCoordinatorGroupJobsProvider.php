<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\DataProvider;

use editor_Models_Task as Task;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Model\DefaultCoordinatorGroupJob;
use MittagQI\Translate5\Repository\DefaultCoordinatorGroupJobRepository;

class HierarchicalDefaultCoordinatorGroupJobsProvider
{
    public function __construct(
        private readonly DefaultCoordinatorGroupJobRepository $defaultCoordinatorGroupJobRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            DefaultCoordinatorGroupJobRepository::create(),
        );
    }

    /**
     * Fetches default job assignments hierarchically.
     * Jobs of direct Coordinator Groups will be fetched first, then jobs of their sub-Groups and so on
     *
     * @return iterable<DefaultCoordinatorGroupJob>
     */
    public function getHierarchicallyFor(Task $task): iterable
    {
        $parentGroupIds = [];

        foreach ($this->defaultCoordinatorGroupJobRepository->getDefaultCoordinatorGroupJobsOfTopRankGroupsForTask($task) as $job) {
            $parentGroupIds[] = (int) $job->getGroupId();

            yield $job;
        }

        if (! empty($parentGroupIds)) {
            yield from $this->fetchSubGroupsJobsBy($task, ...array_unique($parentGroupIds));
        }
    }

    /**
     * @return iterable<DefaultCoordinatorGroupJob>
     */
    private function fetchSubGroupsJobsBy(Task $task, int ...$parentGroupIds): iterable
    {
        $groupIds = [];

        foreach ($this->defaultCoordinatorGroupJobRepository->getDefaultCoordinatorGroupJobsOfSubGroupsForTask($task, ...$parentGroupIds) as $job) {
            $groupIds[] = (int) $job->getGroupId();

            yield $job;
        }

        if (! empty($groupIds)) {
            yield from $this->fetchSubGroupsJobsBy($task, ...array_unique($groupIds));
        }
    }
}
