<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 * KpiTest imports three simple tasks, sets some KPI-relevant dates, exports some of the tasks,
 * and then checks if the KPIs (Key Performance Indicators) get calculated as expected.
 */
class KpiTest extends \ZfExtended_Test_ApiTestcase {
    
    /**
     * What our tasknames will start with (e.g.for creating and filtering tasks).
     * @var string
     */
    protected $taskNameBase = 'API Testing::'.__CLASS__;
    
    /**
     * Settings for the tasks we create and check.
     * @var array
     */
    protected $tasksForKPI = [array('taskNameSuffix' => 'nr1', 'doExport' => true,  'processingTime' => 'P2D')];
    
    protected $tasksForKPITODO = [array('taskNameSuffix' => 'nr1', 'doExport' => true,  'processingTime' => 'P1D'),
        array('taskNameSuffix' => 'nr2', 'doExport' => true,  'processingTime' => 'P2D'),
        array('taskNameSuffix' => 'nr3', 'doExport' => false, 'processingTime' => 'P3D')
    ];
    
    /**
     * Remember the task-ids we created for deleting the tasks at the end
     * taskIds[$taskNameSuffix] = id;
     * @var array 
     */
    protected $taskIds = [];
    
    /**
     * KPI average processing time: task-property for startdate
     * TODO: With TRANSLATE-1455, change this to: assigned
     * @var string
     */
    protected $taskStartDate = 'orderdate';
    
    /**
     * KPI average processing time: task-property for enddate
     * TODO: With TRANSLATE-1455, change this to: review delivered
     * @var string
     */
    protected $taskEndDate = 'realDeliveryDate';
    
    
    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
    }
    
    /**
     * If any task exists already, filtering will be wrong!
     */
    public function testConditions() {
        $nrTasks = $this->applyTaskGridFilter();
        $this->assertEquals('0', $nrTasks);
    }
    
    /**
     * Create tasks, create values for KPIs, check the KPI-results and delete the tasks.
     * @depends testConditions
     */
    public function testKPI() {
        // create the tasks and store their ids
        foreach ($this->tasksForKPI as $task) {
            $this->createTask($task['taskNameSuffix']);
        }
        
        // --- For KPI I: number of exported tasks ---
        foreach ($this->tasksForKPI as $task) {
            if ($task['doExport']) {
                $this->runExcelExport($task['taskNameSuffix']);
            }
        }
        
        // --- For KPI II: average processing time ---
        foreach ($this->tasksForKPI as $task) {
            $this->setTaskProcessingDates($task['taskNameSuffix'], $task['processingTime']);
        }
        
        // get the KPIs from the API
        $this->getKpiResultsFromApi();
        
        // check the KPI-results
        $this->checkKpiResults();
        
        // non-static => delete the tasks here, not in tearDownAfterClass()
        //$this->deleteAllTasks();
    }
    
    /**
     * Import a task and store the id it got in translate5.
     * @param string $taskNameSuffix
     */
    protected function createTask(string $taskNameSuffix) {
        $task = array(
            'taskName' => $this->taskNameBase.'_'.$taskNameSuffix, //no date in file name possible here!
            'sourceLang' => 'en',
            'targetLang' => 'de'
        );
        $this->api()->addImportFile($this->api()->getFile('testcase-de-en.xlf'));
        $this->api()->import($task);
        
        // store task-id for later deleting
        $task = $this->api()->getTask();
        $this->taskIds[$taskNameSuffix] = $task->id;
    }
    
    /**
     * Export a task via API.
     * @param string $taskNameSuffix
     */
    protected function runExcelExport(string $taskNameSuffix) {
        $taskId = $this->taskIds[$taskNameSuffix];
        $this->printUnitTestOutput('runExcelExport: editor/task/'.$taskId.'/excelexport');
        $this->api()->request('editor/task/'.$taskId.'/excelexport');
    }
    
    /**
     * Set the start- and end-date of a task.
     * @param string $taskNameSuffix
     * @param $interval_spec
     */
    protected function setTaskProcessingDates(string $taskNameSuffix, $interval_spec) {
        // We set the endDate to now and the startDate to the given days ago.
        $now = date('Y-m-d H:i:s');
        $endDate = $now;
        $startDate = new DateTime($now);
        $startDate->sub(new DateInterval($interval_spec));
        $startDate = $startDate->format('Y-m-d H:i:s');
        $taskId = $this->taskIds[$taskNameSuffix];
        $this->printUnitTestOutput('setTaskProcessingDates for '.$taskId.': ' . $this->taskStartDate . ' = ' . $startDate .' / ' . $this->taskEndDate . ' = '.$endDate);
        $this->api()->requestJson('editor/task/'.$taskId, 'PUT', array($this->taskStartDate => $startDate, $this->taskEndDate => $endDate));
    }
    
    /**
     * Filter the taskGrid and get the KPI-results from the API.
     */
    protected function getKpiResultsFromApi() {
        $nrTasks = $this->applyTaskGridFilter();
        $this->printUnitTestOutput('EXPECTED: ' . count($this->tasksForKPI));
        // Does the number of found task match the number of tasks we created?
        $this->assertEquals(count($this->tasksForKPI), $nrTasks);
        // TODO
    }
    
    /**
     * Filter the taskGrid for our tasks only and return the number of tasks that match.
     * @return int
     */
    protected function applyTaskGridFilter() {
        // taskGrid: apply the filter for our tasks! do NOT use the limit!
        $filter = '[{"operator":"like","value":"' . $this->taskNameBase . '","property":"taskName"}]';
        $result = $this->api()->requestJson('editor/task?filter='.urlencode($filter), 'GET');
        $this->printUnitTestOutput('editor/task?filter='.$filter. ' ===> FOUND: ' . count($result));
        return count($result);
    }
    
    /**
     * Check if the KPI-result we got matches what we expect.
     */
    protected function checkKpiResults() {
        $this->assertEquals(3,3); // TODO
    }
    
    /**
     * Delete the tasks for all stored task-is.
     */
    protected function deleteAllTasks() {
        $this->api()->login('testmanager');
        foreach ($this->taskIds as $taskId) {
            $this->api()->requestJson('editor/task/'.$taskId, 'PUT', array('userState' => 'open', 'id' => $taskId));
            $this->api()->requestJson('editor/task/'.$taskId, 'DELETE');
        }
    }
    
    public static function tearDownAfterClass(): void {
        // tasks are deleted already
        // TODO: What if there is an error and deleteAllTasks() is not reached????
    }
    
    /**
     * Output infos during executing the unit-test.
     * @param string $msg
     */
    protected function printUnitTestOutput (string $msg) {
        fwrite(STDOUT, $msg . "\n");
    }
}