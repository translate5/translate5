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
class KpiTest extends \editor_Test_ApiTest {
    
    const KPI_REVIEWER = 'averageProcessingTimeReviewer';
    const KPI_TRANSLATOR = 'averageProcessingTimeTranslator';
    const KPI_TRANSLATOR_CHECK = 'averageProcessingTimeSecondTranslator';
    
    /**
     * What our tasknames start with (e.g.for creating and filtering tasks).
     * @var string
     */
    private static $taskNameBase = 'API Testing::'.__CLASS__;
    
    /**
     * Settings for the tasks we create and check.
     * @var array
     */
    private static $tasksForKPI = [
        array('taskNameSuffix' => 'nr1', 'doExport' => true,  'processingTimeInDays' => 10),
        array('taskNameSuffix' => 'nr2', 'doExport' => true,  'processingTimeInDays' => 20),
        array('taskNameSuffix' => 'nr3', 'doExport' => false, 'processingTimeInDays' => 30),
        array('taskNameSuffix' => 'nr4', 'doExport' => false, 'processingTimeInDays' => 40)
    ];
    
    /**
     * Remember the task-ids we created for deleting the tasks at the end
     * taskIds[$taskNameSuffix] = id;
     * @var array 
     */
    private static $taskIds = [];
    
    /***
     * Task id to taskUserAssoc id map
     * @var array
     */
    private static $taskUserAssocMap=[];
    
    /**
     * KPI average processing time: taskUserAssoc-property for startdate
     * @var string
     */
    private static $taskStartDate = 'assignmentDate';
    
    /**
     * KPI average processing time: taskUserAssoc-property for enddate
     * @var string
     */
    private static $taskEndDate = 'finishedDate';
    
    public static function beforeTests(): void {
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');

        // If any task exists already, filtering will be wrong!
        $filteredTasks = static::getFilteredTasks();
        static::assertEquals('0', count($filteredTasks), 'The translate5 instance contains already a task with the name "'.static::$taskNameBase.'" remove this task before!');

        // create the tasks and store their ids
        foreach (static::$tasksForKPI as $taskData) {
            $taskNameSuffix = $taskData['taskNameSuffix'];
            $task = array(
                'taskName' => static::$taskNameBase.'_'.$taskNameSuffix, //no date in file name possible here!
                'sourceLang' => 'en',
                'targetLang' => 'de'
            );
            static::api()->addImportFile(static::api()->getFile('../TestImportProjects/testcase-de-en.xlf'));
            static::api()->import($task);

            // store task-id for later deleting
            $task = static::api()->getTask();
            static::$taskIds[$taskNameSuffix] = $task->id;

            //add user to the task
            $tua = static::api()->addUser('testlector', params: [
                'workflow'=>'default',
                'workflowStepName'=>'reviewing'
            ]);
            static::$taskUserAssocMap[$task->id] = $tua->id;
        }
    }

    /**
     * Renders the filter for filtering our tasks in the taskGrid.
     * @return string
     */
    private static function renderTaskGridFilter() {
        return '[{"operator":"like","value":"' . static::$taskNameBase . '","property":"taskName"}]';
    }

    /**
     * Filter the taskGrid for our tasks only and return the found tasks that match the filtering.
     * @return int
     */
    private static function getFilteredTasks() {
        // taskGrid: apply the filter for our tasks! do NOT use the limit!
        return static::api()->getJson('editor/task?filter='.urlencode(static::renderTaskGridFilter()));
    }
    
    /**
     * create values for KPIs, check the KPI-results .
     */
    public function testKPI() {

        // --- For KPI I: number of exported tasks ---
        foreach (static::$tasksForKPI as $task) {
            if ($task['doExport']) {
                $this->runExcelExportAndImport($task['taskNameSuffix']);
            }
        }
        
        // --- For KPI II: average processing time ---
        foreach (static::$tasksForKPI as $task) {
            $interval_spec = 'P'.(string)$task['processingTimeInDays'].'D';
            $this->setTaskProcessingDates($task['taskNameSuffix'], $interval_spec);
        }
        
        // check the KPI-results
        $this->checkKpiResults();
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Check if the KPI-result we get from the API matches what we expect.
     */
    private function checkKpiResults() {
        // Does the number of found tasks match the number of tasks we created?
        $filteredTasks = static::getFilteredTasks();
        $this->assertEquals(count(static::$tasksForKPI), count($filteredTasks));

        $result = static::api()->postJson('editor/task/kpi', ['filter' => static::renderTaskGridFilter()], null, false);

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

    /**
     * Export a task via API.
     * @param string $taskNameSuffix
     */
    private function runExcelExportAndImport(string $taskNameSuffix) {
        $taskId = self::$taskIds[$taskNameSuffix];

        $response = static::api()->get('editor/task/'.$taskId.'/excelexport');
        $tempExcel = tempnam(sys_get_temp_dir(), 't5testExcel');
        file_put_contents($tempExcel, $response->getBody());

        static::api()->addFile('excelreimportUpload', $tempExcel, 'application/data');
        static::api()->post('editor/task/'.$taskId.'/excelreimport');
        static::api()->reloadTask();
    }

    /**
     * Set the start- and end-date of a task.
     * @param string $taskNameSuffix
     * @param $interval_spec
     */
    private function setTaskProcessingDates(string $taskNameSuffix, $interval_spec) {
        // We set the endDate to now and the startDate to the given days ago.
        $now = date('Y-m-d H:i:s');
        $endDate = $now;
        $startDate = new DateTime($now);
        $startDate->sub(new DateInterval($interval_spec));
        $startDate = $startDate->format('Y-m-d H:i:s');
        $taskId = self::$taskIds[$taskNameSuffix];
        $assocId=self::$taskUserAssocMap[$taskId];
        static::api()->putJson('editor/taskuserassoc/'.$assocId, [static::$taskStartDate => $startDate, static::$taskEndDate => $endDate]);
    }
    
    /**
     * Get the KPI-values we expect for our tasks.
     * @return array
     */
    private function getExpectedKpiStatistics() {
        $nrExported = 0;
        $processingTimeInDays = 0;
        $nrTasks = count(static::$tasksForKPI);
        foreach (static::$tasksForKPI as $task) {
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

    public static function afterTests(): void {
        static::api()->login('testmanager');
        foreach (self::$taskIds as $taskId) {
            static::api()->deleteTask($taskId);
        }
    }
}