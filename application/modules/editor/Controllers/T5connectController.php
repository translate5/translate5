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

use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Task\DataProvider\TaskViewDataProvider;
use MittagQI\Translate5\User\Model\User;

/**
 * Controller to feed t5connect with structured endpoints
 * @property editor_Models_Task $entity
 */
final class editor_T5connectController extends ZfExtended_RestController
{
    /**
     * The default duration we retrieve data for
     */
    private const DEFAULT_LASTDAYS = '365';

    /**
     * max number of years we retrieve data in retrospect
     */
    private const MAX_YEARS = 5;

    /**
     * The workflow-steps that must be assigned & confirmed to have a task regarded as "confirmed"
     */
    private const RELEVANT_CONFIRMED_TYPES = [
        editor_Workflow_Default::ROLE_REVIEWER,
        editor_Workflow_Default::ROLE_TRANSLATORCHECK,
    ];

    /**
     * The user-states that do not count for a user being part of a confirmed task
     */
    private const EXCLUDED_CONFIRMED_USERSTATES = [
        editor_Workflow_Default::STATE_UNCONFIRMED,
        editor_Workflow_Default::STATE_WAITING,
    ];

    private const NEEDED_COLS = [
        // needed by T5
        'id',
        'taskGuid',
        'foreignId',
        'foreignName',
        'foreignState',
        'taskName',
        'sourceLang',
        'targetLang',
        'relaisLang',
        'state',
        'locked',
        // needed internally
        'workflowStepName',
        'lockingUser',
    ];

    protected $entityClass = editor_Models_Task::class;

    private string $t5foreignName;

    private User $authenticatedUser;

    private TaskViewDataProvider $taskViewDataProvider;

    public function init(): void
    {
        parent::init();
        $this->t5foreignName = Zend_Registry::get('config')->runtimeOptions->t5connect->foreignName;
        $this->taskViewDataProvider = TaskViewDataProvider::create();
        $this->authenticatedUser = (new UserRepository())->get(ZfExtended_Authentication::getInstance()->getUserId());
    }

    /**
     * Will retrieve the tasks for the specified filters. To prevent misuse, filters have a default
     * returns ALL t5connect tasks. Should only be needed while migration from old to new behaviour.
     * => /editor/t5connect/?[lastDays=365][foreignState=someState]
     * @throws ZfExtended_BadRequest
     */
    public function indexAction(): void
    {
        $this->fetchTasksAndCreateView($this->createBaseSelect());
    }

    /**
     * Retrieves all failed T5Connect tasks (that are in state error))
     * => /editor/t5connect/failed/?[lastDays=365][foreignState=someState]
     * @throws ZfExtended_BadRequest
     */
    public function failedAction(): void
    {
        $select = $this->createBaseSelect();
        $select->where('`state` = ?', editor_Models_Task::STATE_ERROR);
        $this->fetchTasksAndCreateView($select);
    }

    /**
     * Retrieves all tasks that are imported but not ended (optional) or erroneous nor locked
     * => /editor/t5connect/imported/?[includeEnded=<0|1>][lastDays=365][foreignState=someState]
     * @throws ZfExtended_BadRequest
     */
    public function importedAction(): void
    {
        $statesBlacklist = [
            editor_Models_Task::STATE_ERROR,
            editor_Models_Task::STATE_IMPORT,
        ];
        // if not explicitly wanted, we exclude ended states
        if ((int) $this->getRequest()->getParam('includeEnded', '0') !== 1) {
            $statesBlacklist[] = editor_Models_Task::STATE_END;
        }
        $select = $this->createBaseSelect();
        $select->where('`state` NOT IN (?)', $statesBlacklist);
        $this->fetchTasksAndCreateView($select);
    }

    /**
     * Get all confirmed tasks. "Confirmed" tasks are all T5Connect tasks, that have users of the relevant
     * workflow-steps (optionally definable) assigned, which have confirmed their job.
     * A confirmed job is a job not in state 'waiting' or 'unconfirmed'
     * The relevant workflow-steps default to ['reviewing', 'translatorCheck']
     * => /editor/t5connect/confirmed/?[workflowSteps=step1,step2][lastDays=365][foreignState=someState]
     * @throws ZfExtended_BadRequest
     */
    public function confirmedAction(): void
    {
        $relevantWorkflowTypes = $this->evaluateRelevantWorkflowTypes();
        $select = $this->createBaseSelect();
        $tasks = $this->entity->db->fetchAll($select)->toArray();
        $confirmedTasks = [];
        // filter confirmed-tasks by user-data. This is just too painful to do via SQL
        foreach ($tasks as &$task) {
            $task = $this->taskViewDataProvider->buildTaskView($task, $this->authenticatedUser);
            $assigned = 0;
            $confirmed = 0;
            foreach ($task['users'] as $user) {
                if (in_array($user['role'], $relevantWorkflowTypes)) {
                    $assigned++;
                    if (! in_array($user['state'], self::EXCLUDED_CONFIRMED_USERSTATES)) {
                        $confirmed++;
                    }
                }
            }
            if ($assigned > 0 && $assigned === $confirmed) {
                $confirmedTasks[] = $task;
            }
        }

        $this->view->total = count($confirmedTasks);
        $this->view->rows = $confirmedTasks;
    }

    /**
     * Get all finished tasks. "Finished" tasks are all T5Connect tasks, where all assigned users of the relevant
     * workflow-steps (optionally definable) have their work in state "finished".
     * The relevant workflow-steps default to ['reviewing', 'translatorCheck']
     * => /editor/t5connect/finished/?[workflowSteps=step1,step2][lastDays=365][foreignState=someState]
     * @throws ZfExtended_BadRequest
     */
    public function finishedAction(): void
    {
        $relevantWorkflowTypes = $this->evaluateRelevantWorkflowTypes();
        $select = $this->createBaseSelect();
        $tasks = $this->entity->db->fetchAll($select)->toArray();
        $finishedTasks = [];
        // filter confirmed-tasks by user-data. This is just too painful to do via SQL
        foreach ($tasks as &$task) {
            $task = $this->taskViewDataProvider->buildTaskView($task, $this->authenticatedUser);
            $assigned = 0;
            $finished = 0;
            foreach ($task['users'] as $user) {
                if (in_array($user['role'], $relevantWorkflowTypes)) {
                    $assigned++;
                    if ($user['state'] === editor_Workflow_Default::STATE_FINISH ||
                        $user['state'] === editor_Workflow_Default::STATE_AUTO_FINISH
                    ) {
                        $finished++;
                    }
                }
            }
            if ($assigned > 0 && $assigned === $finished) {
                $finishedTasks[] = $task;
            }
        }

        $this->view->total = count($finishedTasks);
        $this->view->rows = $finishedTasks;
    }

    /**
     * Get a task by foreign-id
     * Be aware, that this does not return a full task like with the task/get endpoint but
     * the same result-format as the other endpoints in this class with one or zero rows
     * => /editor/t5connect/byforeignid?foreignId=<foreignId>
     * @throws ZfExtended_BadRequest
     */
    public function byforeignidAction(): void
    {
        $foreignId = $this->getRequest()->getParam('foreignId', 0);
        if (empty($foreignId)) {
            throw new ZfExtended_BadRequest(
                'E1559',
                [
                    'details' => 'foreignId must be given',
                ]
            );
        } else {
            $select = $this->entity->db->select()->from($this->entity->getTableName(), self::NEEDED_COLS)
                ->where('`foreignId` = ?', $foreignId);
            $this->fetchTasksAndCreateView($select);
        }
    }

    /**
     * Sets the foreignState for a single task
     * => /editor/t5connect/setforeignstate?taskId=<taskId>&foreignState=<foreignState>
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_BadRequest
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function setforeignstateAction(): void
    {
        $taskId = (int) $this->getRequest()->getParam('taskId', 0);
        $foreignState = $this->getRequest()->getParam('foreignState', null);
        if (empty($taskId) || empty($foreignState)) {
            throw new ZfExtended_BadRequest(
                'E1559',
                [
                    'details' => 'taskId and foreignState must be given',
                ]
            );
        }
        $this->entity->load($taskId);
        $this->entity->setForeignState($foreignState);
        $this->entity->save();

        $this->view->success = 1;
    }

    public function putAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->put');
    }

    public function getAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->get');
    }

    public function deleteAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->delete');
    }

    public function postAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->post');
    }

    /**
     * Filters for params valid for all endpoints
     */
    private function createBaseSelect(): Zend_Db_Table_Select
    {
        $lastDaysFilter = (int) $this->getRequest()->getParam('lastDays', self::DEFAULT_LASTDAYS);
        $foreignState = $this->getRequest()->getParam('foreignState');
        if ($lastDaysFilter > (365 * self::MAX_YEARS)) {
            throw new ZfExtended_BadRequest(
                'E1559',
                [
                    'details' => 'LastDays must be below ' . (365 * self::MAX_YEARS),
                ]
            );
        }

        $select = $this->entity->db->select()->from($this->entity->getTableName(), self::NEEDED_COLS)
            ->where('`foreignName` = ?', $this->t5foreignName)
            ->where('`orderDate` >= (CURRENT_DATE - INTERVAL ? DAY)', $lastDaysFilter);
        if (! empty($foreignState)) {
            $select->where('`foreignState` = ?', $foreignState);
        }

        return $select;
    }

    /**
     * @return string[]
     */
    private function evaluateRelevantWorkflowTypes(): array
    {
        return explode(
            ',',
            $this->getRequest()->getParam(
                'workflowSteps',
                implode(',', self::RELEVANT_CONFIRMED_TYPES)
            )
        );
    }

    /**
     * Creates the view
     */
    private function fetchTasksAndCreateView(Zend_Db_Table_Select $select): void
    {
        $tasks = $this->entity->db->fetchAll($select)->toArray();

        foreach ($tasks as &$task) {
            $task = $this->taskViewDataProvider->buildTaskView($task, $this->authenticatedUser);
        }

        $this->view->total = count($tasks);
        $this->view->rows = $tasks;
    }
}
