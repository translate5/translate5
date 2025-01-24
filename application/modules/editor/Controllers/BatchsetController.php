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

use MittagQI\Translate5\Acl\Rights;

/**
 * Controller for Batch Updates
 */
class Editor_BatchsetController extends ZfExtended_RestController
{
    protected $entityClass = editor_Models_Task::class;

    /**
     * @var editor_Models_Task
     */
    protected $entity;

    /**
     * ignoring ID field for POST Requests
     * @var array
     */
    protected $postBlacklist = ['id'];

    public function indexAction(): void
    {
        if ($this->getParam('countTasks')) {
            $this->view->total = count($this->getTaskGuidsFromFilteredProjects($this->getRequest()->getRawParam('filter')));

            return;
        }

        $batchSet = new MittagQI\Translate5\Task\BatchSet\Strategy($this->getRequest());
        if (! $batchSet->validate()) {
            return;
        }

        $taskGuids = $this->prepareAllowedTaskGuids($this->getParam('projectsAndTasks'));
        if (empty($taskGuids)) {
            return;
        }

        $batchSet->update($taskGuids);
    }

    private function prepareAllowedTaskGuids(?string $projectsAndTaskIdsCsv): array
    {
        if (! empty($projectsAndTaskIdsCsv)) {
            return $this->getTaskGuidsFromProjectsAndTasks(explode(',', $projectsAndTaskIdsCsv));
        }

        return $this->getTaskGuidsFromFilteredProjects($this->getRequest()->getRawParam('filter'));
    }

    private function loadAllowedTasks(string $jsonFilter): array
    {
        // handle filtered projects like in TaskController
        $filter = new editor_Models_Filter_TaskSpecific($this->entity, $jsonFilter);
        $this->entity->filterAndSort($filter);

        // here no check for pmGuid, since this is done in task::loadListByUserAssoc
        $isAllowedToLoadAll = $this->isAllowed(Rights::ID, Rights::LOAD_ALL_TASKS);
        if ($isAllowedToLoadAll) {
            return $this->entity->loadAll();
        }
        $authenticatedUser = ZfExtended_Authentication::getInstance()->getUser();

        return $this->entity->loadListByUserAssoc($authenticatedUser->getUserGuid());
    }

    private function getTaskGuidsFromFilteredProjects(string $jsonFilter): array
    {
        $rows = $this->loadAllowedTasks($jsonFilter);
        if (empty($rows)) {
            return [];
        }
        $projectIds = array_column(array_filter($rows, fn ($row) => $row['id'] === $row['projectId']), 'id');
        if (empty($projectIds)) {
            return [];
        }

        $rows = $this->loadAllowedTasks(json_encode([[
            'operator' => 'in',
            'value' => $projectIds,
            'property' => 'projectId',
        ], [
            'operator' => 'in',
            'value' => editor_Task_Type::getInstance()->getNonInternalTaskTypes(),
            'property' => 'taskType',
        ]]));

        return array_column($rows, 'taskGuid');
    }

    private function getTaskGuidsFromProjectsAndTasks(array $taskAndProjectIds): array
    {
        $rows = $this->loadAllowedTasks(json_encode([[
            'operator' => 'in',
            'value' => $taskAndProjectIds,
            'property' => 'id',
        ]]));

        $projectIds = $taskGuids = [];
        foreach ($rows as $row) {
            if ($row['id'] === $row['projectId']) {
                $projectIds[] = $row['id'];
            } else {
                $taskGuids[] = $row['taskGuid'];
            }
        }

        if (! empty($projectIds)) {
            $taskGuids = array_merge($taskGuids, $this->getTaskGuidsFromFilteredProjects(json_encode([[
                'operator' => 'in',
                'value' => $projectIds,
                'property' => 'projectId',
            ]])));
        }

        return $taskGuids;
    }
}
