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
 * This will import 2 tasks, and it will test the functionality if the user is able to open 2 different task at the same time.
 */
class Translate198Test extends editor_Test_JsonTest {
    /* @var $this Translate198Test */
    
    /***
     * Currently imported task ids
     * @var array
     */
    protected static $importedTasks = [];
    protected static $customerTest;
    protected static $sourceLangRfc = 'de';
    protected static $targetLangRfc = 'en';
    
    
    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
    }
    
    /***
     * Import the 2 test tasks
     */
    public function testSetupTask() {
        self::$customerTest = self::$api->requestJson('editor/customer/', 'POST',[
            'name'=>'API Testing::ResourcesLogCustomer',
            'number'=>uniqid('API Testing::ResourcesLogCustomer'),
        ]);
        
        $this->createTask("task1");
        $this->startImport();
        $this->checkTaskState();
        self::$importedTasks[] = self::$api->getTask();
        
        $this->createTask("task2");
        $this->startImport();
        $this->checkTaskState();
        self::$importedTasks[] = self::$api->getTask();
    }
    
    /***
     * Open the task for editing twice (as pmOverrwrite. It is the same as with user assoc). On the secound time, we should get 422 and error code E1341
     */
    public function testTaskAllowedEdit() {
        $task1 = self::$importedTasks[0];
        //open task for editing. This should not produce any error
        $response = self::$api->requestJson('editor/task/'.$task1->id,'PUT',['userState' => 'edit', 'id' => $task1->id]);
        $this->api()->setTask($task1);
        self::assertNotEmpty($response,'Unable to edit task 1.');

        $segments = self::$api->requestJson('editor/segment?page=1&start=0&limit=200');
        $this->assertSegmentsEqualsJsonFile('segments-task1.json', $segments);

        self::assertCount(1, $segments);

        $task2 = self::$importedTasks[1];
        //open the secound task with the same user. This should not be posible
        $response = self::$api->requestJson('editor/task/'.$task2->id,'PUT',['userState' => 'edit', 'id' => $task2->id]);
        $this->api()->setTask($task2);
        self::assertNotEmpty($response,'Unable to edit task 2.');

        $segments = self::$api->requestJson('editor/segment?page=1&start=0&limit=200');
        $this->assertSegmentsEqualsJsonFile('segments-task2.json', $segments);
    }
    
    
    /***
     * Create the task. The task will not be imported directly autoStartImport is 0!
     */
    protected function createTask(string $taskName){
        $task =[
            'taskName' => 'API Testing::'.__CLASS__.'_'.$taskName, 
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerId'=>self::$customerTest->id,
            'autoStartImport'=>0
        ];
        self::assertLogin('testmanager');
        self::$api->addImportFile(self::$api->getFile($taskName.'-de-en.xlf'));
        self::$api->import($task,false,false);
        error_log('Task created. '.self::$api->getTask()->taskName);
    }
    
    /***
     * Check the task state
     */
    protected function checkTaskState(){
        self::$api->checkTaskStateLoop();
    }
    
    /***
     * Start the import process
     */
    protected function startImport(){
        self::$api->requestJson('editor/task/'.self::$api->getTask()->id.'/import', 'GET');
        error_log('Import workers started.');
    }
    
    public static function tearDownAfterClass(): void {
        //open task for whole testcase
        self::$api->login('testmanager');
        
        //leave the first task with the testmanager
        self::$api->requestJson('editor/task/'.self::$importedTasks[0]->id, 'PUT', ['userState' => 'open', 'id' => self::$importedTasks[0]->id]);
        //remove the 2 tasks
        self::$api->requestJson('editor/task/'.self::$importedTasks[0]->id, 'DELETE');
        self::$api->requestJson('editor/task/'.self::$importedTasks[1]->id, 'DELETE');
        
        //remove the temp customer
        self::$api->requestJson('editor/customer/'.self::$customerTest->id, 'DELETE');
    }
}
