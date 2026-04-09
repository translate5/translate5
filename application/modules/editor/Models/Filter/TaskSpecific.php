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

use MittagQI\Translate5\Statistics\Dto\StatisticFilterDTO;
use MittagQI\ZfExtended\Models\Filter\FilterJoinDTO;

/**
 * Set the task specific filter when special filter is active
 */
class editor_Models_Filter_TaskSpecific extends ZfExtended_Models_Filter_ExtJs6
{
    /**
     * Used in the advanced filter window, values for them are filtered
     * for performance reasons in columnar based segment_statistics DB
     */
    private const array ADVANCED_FILTERS = [
        'matchRateMin',
        'matchRateMax',
        'langResource',
        'langResourceType',
        'qualityScoreMin',
        'qualityScoreMax',
    ];

    private ?StatisticFilterDTO $advancedFilter = null;

    public function __construct(ZfExtended_Models_Entity_Abstract $entity = null, $filter = null)
    {
        parent::__construct($entity, $filter);
        $advancedFilter = [];
        foreach ($this->filter as $idx => $oneFilter) {
            if (StatisticFilterDTO::isSupported($oneFilter->field)) {
                $advancedFilter[$oneFilter->field] = $oneFilter->value;
            }
            //advanced filters are currently only processed via statistics
            if (in_array($oneFilter->field, self::ADVANCED_FILTERS)) {
                unset($this->filter[$idx]);
            }
        }
        if (! empty($advancedFilter)) {
            $this->advancedFilter = StatisticFilterDTO::fromAssocArray($advancedFilter);
        }
    }

    public function getStatisticFilter(): ?StatisticFilterDTO
    {
        return $this->advancedFilter;
    }

    /**
     * sets several field mappings (field name in frontend differs from that in backend)
     * should be called after setDefaultFilter
     * @param array|null $sortColMap
     * @param array|null $filterTypeMap
     * @throws Zend_Db_Table_Exception
     */
    public function setMappings($sortColMap = null, $filterTypeMap = null): void
    {
        //HERE wenn step gefiltert, dann splitten in zwei Filter. Workflow und WfStep. Wobei Workflow mit dem gegeben
        // Worekflwo gemerged werden muss!

        parent::setMappings($sortColMap, $filterTypeMap);

        $this->addLockedToStateFilter();
        $this->addAuthUserToUserStateFilter();
        $this->processWorkflowStepFilter();
    }

    /**
     * @throws Zend_Db_Table_Exception
     */
    private function addLockedToStateFilter(): void
    {
        //if the task state filter is set, set the filter table
        $taskState = null;
        if (! $this->hasFilter('state', $taskState)) {
            return;
        }
        $db = $this->entity->db;
        $taskTable = $db->info($db::NAME);
        $taskStateValues = $taskState->value;

        //set the task table
        $taskState->table = $taskTable;

        //is state locked active
        $locked = ! empty($taskStateValues) && in_array('locked', $taskStateValues);

        //if locked filter state is active, add the "or" filter
        if (! $locked) {
            return;
        }
        //remove the user task state filter
        $this->deleteFilter('state');

        $orFilter = new stdClass();
        $orFilter->type = 'orExpression';
        $orFilter->field = '';
        $orFilter->value = [];

        //add the locked filter
        $filter = new stdClass();
        $filter->field = 'locked';
        $filter->type = 'notIsNull';
        $filter->value = '';
        $filter->table = $taskTable;
        $orFilter->value[] = $filter;

        //remove state locked from the state values
        if (($key = array_search('locked', $taskStateValues)) !== false) {
            unset($taskStateValues[$key]);
        }

        if (! empty($taskStateValues)) {
            //add all other state filter values
            $filter = new stdClass();
            $filter->field = 'state';
            $filter->type = 'list';
            $filter->value = $taskStateValues;
            $filter->table = $taskTable;
            $orFilter->value[] = $filter;
        }

        $this->addFilter($orFilter);
    }

    private function addAuthUserToUserStateFilter(): void
    {
        //check if one of the set filters is userState filter
        $userStateFilter = null;
        if (! $this->hasFilter('userState', $userStateFilter)) {
            return;
        }
        //if the user filter is used, apply the current user as additional TaskAssocFilter
        $filter = new stdClass();
        $filter->field = 'userGuid';
        $filter->type = 'string';
        $filter->comparison = 'eq';
        $filter->value = ZfExtended_Authentication::getInstance()->getUserGuid();
        $filter->table = $userStateFilter->type->getTable();
        $this->addFilter($filter);
    }

    private function processWorkflowStepFilter(): void
    {
        if (! $this->hasFilter('workflowStep')) {
            return;
        }
        $this->deleteFilter('workflowStep');

        // the advanced filter instance are filled with the steps already
        $stepsByWorkflow = $this->advancedFilter->getGroupWorkflowStepsByWorkflow();

        $orFilter = new stdClass();
        $orFilter->type = 'orExpression';
        $orFilter->field = '';
        $orFilter->value = [];

        foreach ($stepsByWorkflow as $workflow => $steps) {
            $orFilter->value[] = $this->createStepAndWorkflowFilter($workflow, $steps);
        }

        $this->addFilter($orFilter);
        $this->addJoinedTable(new FilterJoinDTO(
            editor_Models_Db_TaskUserAssoc::TABLE_NAME,
            'taskGuid',
            'taskGuid'
        ));
    }

    private function createStepAndWorkflowFilter(string $workflow, array $steps): stdClass
    {
        //add the locked filter
        $stepFilter = new stdClass();
        $stepFilter->field = 'workflowStepName';
        $stepFilter->type = 'list';
        $stepFilter->value = $steps;
        $stepFilter->table = editor_Models_Db_TaskUserAssoc::TABLE_NAME;

        $workflowFilter = new stdClass();
        $workflowFilter->field = 'workflow';
        $workflowFilter->table = editor_Models_Db_TaskUserAssoc::TABLE_NAME;
        $workflowFilter->value = $workflow;
        $workflowFilter->comparison = 'eq';
        $workflowFilter->type = 'numeric';

        $andFilter = new stdClass();
        $andFilter->type = 'andExpression';
        $andFilter->field = '';
        $andFilter->value = [$stepFilter, $workflowFilter];

        return $andFilter;
    }
}
