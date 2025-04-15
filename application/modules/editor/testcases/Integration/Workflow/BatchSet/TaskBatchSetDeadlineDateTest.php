<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Test\Integration\Workflow\BatchSet;

use editor_Models_Customer_Customer;
use editor_Models_Db_TaskUserAssoc;
use editor_Models_Task;
use editor_Models_Task_Remover;
use MittagQI\Translate5\JobAssignment\UserJob\BatchUpdate\UserJobDeadlineBatchUpdater;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\BatchSet\BatchSetTaskGuidsProvider;
use MittagQI\Translate5\Task\BatchSet\Setter\TaskBatchSetDeadlineDate;
use MittagQI\Translate5\Test\Fixtures\CustomerFixtures;
use MittagQI\Translate5\Test\Fixtures\JobFixtures;
use MittagQI\Translate5\Test\Fixtures\TaskFixtures;
use MittagQI\Translate5\Test\Fixtures\UserFixtures;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\TestCase;
use REST_Controller_Request_Http as Request;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use ZfExtended_Authentication;
use ZfExtended_Factory;
use ZfExtended_Logger;

class TaskBatchSetDeadlineDateTest extends TestCase
{
    private const workflowStepName = 'translation';

    private const workflow = 'default';

    private const deadlineDate = '2025-03-05T09:43:00';

    private const deadlineDateDb = '2025-03-05 09:43:00';

    private const projectsCount = 3;

    private const tasksPerProject = 3;

    private Zend_Db_Adapter_Abstract $db;

    private Request $request;

    private TaskBatchSetDeadlineDate $taskBatchSetDeadlineDate;

    /**
     * @var editor_Models_Task[]
     */
    private static array $projects = [];

    /**
     * @var editor_Models_Task[][]
     */
    private static array $tasks = [];

    /**
     * @var User[]
     */
    private static array $users = [];

    /**
     * @var editor_Models_Customer_Customer[]
     */
    private static array $customers = [];

    public static function setUpBeforeClass(): void
    {
        self::$users = UserFixtures::create()->createUsers(1);
        $userGuid = self::$users[0]->getUserGuid();
        (ZfExtended_Authentication::getInstance())->authenticateByLogin(self::$users[0]->getLogin());

        $taskFixtures = TaskFixtures::create();
        $jobFixtures = JobFixtures::create();
        self::$customers = CustomerFixtures::create()->createCustomers(2);

        for ($projectIdx = 0; $projectIdx < self::projectsCount; $projectIdx++) {
            $customerId = (int) self::$customers[$projectIdx === 0 ? 0 : 1]->getId();
            self::$projects[$projectIdx] = $taskFixtures->createProject($customerId, $userGuid);
            $projectId = (int) self::$projects[$projectIdx]->getId();

            for ($taskIdx = 0; $taskIdx < self::tasksPerProject; $taskIdx++) {
                $task = $taskFixtures->createTask(
                    $customerId,
                    self::workflow,
                    self::workflowStepName,
                    0,
                    0,
                    editor_Models_Task::STATE_OPEN,
                    $projectId
                );

                $jobFixtures->createJob($task->getTaskGuid(), $userGuid, self::workflowStepName);

                self::$tasks[$projectIdx][$taskIdx] = $task;
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        for ($projectIdx = 0; $projectIdx < self::projectsCount; $projectIdx++) {
            $projectId = (int) self::$projects[$projectIdx]->getId();
            $entity = new editor_Models_Task();
            $entity->load($projectId);
            $remover = ZfExtended_Factory::get(editor_Models_Task_Remover::class, [$entity]);
            $remover->remove(true);
        }
        foreach (self::$users as $user) {
            $user->delete();
        }
        foreach (self::$customers as $customer) {
            $customer->delete();
        }
    }

    protected function setUp(): void
    {
        $logger = $this->createMock(ZfExtended_Logger::class);
        $this->db = Zend_Db_Table::getDefaultAdapter();
        $userJobDeadlineBatchUpdater = new UserJobDeadlineBatchUpdater($this->db, $logger);
        $userJobRepository = UserJobRepository::create();
        $taskGuidsProvider = BatchSetTaskGuidsProvider::create();
        $this->taskBatchSetDeadlineDate = new TaskBatchSetDeadlineDate(
            $logger,
            $userJobRepository,
            $userJobDeadlineBatchUpdater,
            $taskGuidsProvider
        );

        $this->resetJobs();
        $this->request = self::getRequest();
    }

    public function testUpdateOneJobByTaskId(): void
    {
        self::assertEquals(self::projectsCount, count(self::$projects));
        for ($projectIdx = 0; $projectIdx < self::projectsCount; $projectIdx++) {
            self::assertEquals(self::tasksPerProject, count(self::$tasks[$projectIdx]));
        }
        $task = self::$tasks[0][0];
        self::assertEquals(self::workflowStepName, $task->getWorkflowStepName());

        // update one task (one job)
        $this->request->setParam('projectsAndTasks', $task->getId());
        $this->assertJobsUpdate(1);
    }

    public function testUpdateJobsByTaskIds(): void
    {
        $tasksLimit = self::tasksPerProject - 1;
        // update tasks (one job each)
        $taskIds = [];
        for ($i = 1; $i <= $tasksLimit; $i++) {
            $taskIds[] = self::$tasks[0][$i]->getId();
        }
        $this->request->setParam('projectsAndTasks', implode(',', $taskIds));
        $this->assertJobsUpdate($tasksLimit);
    }

    public function testUpdateJobsByProjectIds(): void
    {
        $projectsLimit = self::projectsCount - 1;
        $projectIds = [];
        $tasksCount = 0;
        foreach (self::$projects as $projectIdx => $project) {
            if ($projectIdx === $projectsLimit) {
                break;
            }
            $projectIds[] = $project->getId();
            $tasksCount += count(self::$tasks[$projectIdx]);
        }
        $this->request->setParam('projectsAndTasks', implode(',', $projectIds));
        $this->assertJobsUpdate($tasksCount);
    }

    public function testUpdateJobsByProjectsFilter(): void
    {
        $projectsLimit = self::projectsCount - 1;
        $minProjectId = (int) self::$projects[0]->getId();
        $this->request->setParam('filter', '[{"operator":"lt","value":' . ($projectsLimit * (self::tasksPerProject + 1) + $minProjectId) . ',"property":"id"}]');
        $this->assertJobsUpdate($projectsLimit * self::tasksPerProject);
    }

    public function testUpdateNoJobsByProjectsFilter(): void
    {
        $lastProjectId = self::$projects[count(self::$projects) - 1]->getId();
        $this->request->setParam('filter', '[{"operator":"gt","value":' . $lastProjectId . ',"property":"id"}]');
        $this->assertJobsUpdate(0);
    }

    public function testUpdateJobsByCustomerFilter(): void
    {
        if (self::$customers[0]->getName() === self::$customers[1]->getName()) {
            self::markTestSkipped('Customer names should be different to run this test');
        }
        $this->request->setParam('filter', json_encode([[
            'operator' => 'like',
            'value' => self::$customers[0]->getName(),
            'property' => 'customerId',
        ]]));
        $this->assertJobsUpdate(self::tasksPerProject);
    }

    private function resetJobs(): void
    {
        $this->db->query('UPDATE ' . editor_Models_Db_TaskUserAssoc::TABLE_NAME . ' SET deadlineDate = NULL');
    }

    private function assertJobsUpdate(int $expectedUpdatedCount): void
    {
        $this->taskBatchSetDeadlineDate->process($this->request);

        $updatedCount = $this->db->fetchOne(
            'SELECT COUNT(*) FROM ' . editor_Models_Db_TaskUserAssoc::TABLE_NAME . ' WHERE deadlineDate = "' . self::deadlineDateDb . '"'
        );
        self::assertEquals($expectedUpdatedCount, $updatedCount);
    }

    private static function getRequest(): Request
    {
        $request = new Request();
        $request->setParam('updateType', 'deadlineDate');
        $request->setParam('batchWorkflow', self::workflow);
        $request->setParam('batchWorkflowStep', self::workflowStepName);
        $request->setParam('deadlineDate', self::deadlineDate);

        return $request;
    }
}
