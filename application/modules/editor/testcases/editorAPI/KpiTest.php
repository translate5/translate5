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

/**
 * KpiTest imports three simple tasks, sets some KPI-relevant dates, exports some of the tasks,
 * and then checks if the KPIs (Key Performance Indicators) get calculated as expected.
 */
class KpiTest extends \ZfExtended_Test_ApiTestcase {
    
    const KPI_REVIEWER='averageProcessingTimeReviewer';
    const KPI_TRANSLATOR='averageProcessingTimeTranslator';
    const KPI_TRANSLATOR_CHECK='averageProcessingTimeSecondTranslator';
    
    /**
     * What our tasknames start with (e.g.for creating and filtering tasks).
     * @var string
     */
    protected $taskNameBase = 'API Testing::'.__CLASS__;
    
    /**
     * Settings for the tasks we create and check.
     * @var array
     */
    protected $tasksForKPI = [array('taskNameSuffix' => 'nr1', 'doExport' => true,  'processingTimeInDays' => 10),
                              array('taskNameSuffix' => 'nr2', 'doExport' => true,  'processingTimeInDays' => 20),
                              array('taskNameSuffix' => 'nr3', 'doExport' => false, 'processingTimeInDays' => 30),
                              array('taskNameSuffix' => 'nr4', 'doExport' => false, 'processingTimeInDays' => 40)
    ];
    
    /**
     * Remember the task-ids we created for deleting the tasks at the end
     * taskIds[$taskNameSuffix] = id;
     * @var array 
     */
    protected static $taskIds = [];
    
    /***
     * Task id to taskUserAssoc id map
     * @var array
     */
    protected static $taskUserAssocMap=[];
    
    /**
     * KPI average processing time: taskUserAssoc-property for startdate
     * @var string
     */
    protected $taskStartDate = 'assignmentDate';
    
    /**
     * KPI average processing time: taskUserAssoc-property for enddate
     * @var string
     */
    protected $taskEndDate = 'finishedDate';
    
    /**
     * @var string contains the file name to the downloaded excel
     */
    protected static $tempExcel;
    
    
    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
    }
    
    /**
     * If any task exists already, filtering will be wrong!
     */
    public function testConditions() {
        $filteredTasks = $this->getFilteredTasks();
        $this->assertEquals('0', count($filteredTasks), 'The translate5 instance contains already a task with the name "'.$this->taskNameBase.'" remove this task before!');
    }
    
    /**
     * Create tasks, create values for KPIs, check the KPI-results .
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
                $this->runExcelExportAndImport($task['taskNameSuffix']);
            }
        }
        
        // --- For KPI II: average processing time ---
        foreach ($this->tasksForKPI as $task) {
            $interval_spec = 'P'.(string)$task['processingTimeInDays'].'D';
            $this->setTaskProcessingDates($task['taskNameSuffix'], $interval_spec);
        }
        
        // check the KPI-results
        $this->checkKpiResults();
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
        $this->api()->addImportFile($this->api()->getFile('../TestImportProjects/testcase-de-en.xlf'));
        $this->api()->import($task);
        
        // store task-id for later deleting
        $task = $this->api()->getTask();
        self::$taskIds[$taskNameSuffix] = $task->id;
        
        //add user to the task
        $tua=$this->api()->addUser('testlector',params: [
            'workflow'=>'default',
            'workflowStepName'=>'reviewing'
        ]);
        
        self::$taskUserAssocMap[$task->id]=$tua->id;
    }
    
    /**
     * Export a task via API.
     * @param string $taskNameSuffix
     */
    protected function runExcelExportAndImport(string $taskNameSuffix) {
        $taskId = self::$taskIds[$taskNameSuffix];
        
        $response = $this->api()->get('editor/task/'.$taskId.'/excelexport');
        self::$tempExcel = $tempExcel = tempnam(sys_get_temp_dir(), 't5testExcel');
        file_put_contents($tempExcel, $response->getBody());
        
        $this->api()->addFile('excelreimportUpload', self::$tempExcel, 'application/data');
        $this->api()->post('editor/task/'.$taskId.'/excelreimport');
        $this->api()->reloadTask();
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
        $taskId = self::$taskIds[$taskNameSuffix];
        $assocId=self::$taskUserAssocMap[$taskId];
        $this->api()->putJson('editor/taskuserassoc/'.$assocId, [$this->taskStartDate => $startDate, $this->taskEndDate => $endDate]);
    }
    
    /**
     * Check if the KPI-result we get from the API matches what we expect.
     */
    protected function checkKpiResults() {
        // Does the number of found tasks match the number of tasks we created?
        $filteredTasks = $this->getFilteredTasks();
        $this->assertEquals(count($this->tasksForKPI), count($filteredTasks));
        
        $result = $this->api()->postJson('editor/task/kpi', ['filter' => $this->renderTaskGridFilter()], null, false);
        
        $statistics = $this->getExpectedKpiStatistics();
        
        // averageProcessingTime from API comes with translated unit (e.g. "2 days", "14 Tage"),
        // but these translations are not available here (are they?)
        $search = array("days", "Tage", " ");
        $replace = array("", "", "");
        $result->{self::KPI_REVIEWER} = str_replace($search, $replace, $result->{self::KPI_REVIEWER});
        //test only for reviewer (for all ther roles will be the same)
        $this->assertEquals($result->{self::KPI_REVIEWER}, $statistics[self::KPI_REVIEWER]);
        $this->assertEquals($result->excelExportUsage, $statistics['excelExportUsage']);
    }
    
    public static function tearDownAfterClass(): void {
        self::$api->login('testmanager');
        foreach (self::$taskIds as $taskId) {
            self::$api->putJson('editor/task/'.$taskId, ['userState' => 'open', 'id' =>$taskId]);
            self::$api->delete('editor/task/'.$taskId);
        }
    }
    
    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------
    
    /**
     * Renders the filter for filtering our tasks in the taskGrid.
     * @return string
     */
    protected function renderTaskGridFilter() {
        return '[{"operator":"like","value":"' . $this->taskNameBase . '","property":"taskName"}]';
    }
    
    /**
     * Filter the taskGrid for our tasks only and return the found tasks that match the filtering.
     * @return int
     */
    protected function getFilteredTasks() {
        // taskGrid: apply the filter for our tasks! do NOT use the limit!
        $result = $this->api()->getJson('editor/task?filter='.urlencode($this->renderTaskGridFilter()));
        return $result;
    }
    
    /**
     * Get the KPI-values we expect for our tasks.
     * @return array
     */
    protected function getExpectedKpiStatistics() {
        $nrExported = 0;
        $processingTimeInDays = 0;
        $nrTasks = count($this->tasksForKPI);
        foreach ($this->tasksForKPI as $task) {
            if ($task['doExport']) {
                $nrExported++;
            }
            $processingTimeInDays += $task['processingTimeInDays'];
        }
        $statistics = [];
        $statistics[self::KPI_REVIEWER] = (string)round($processingTimeInDays / $nrTasks, 0);
        $statistics['excelExportUsage'] = round((($nrExported / $nrTasks) * 100),2) . '%';
        return $statistics;
    }
}