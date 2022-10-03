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
    protected static $sourceLangRfc = 'de';
    protected static $targetLangRfc = 'en';
    
    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
    }

    /**
     * imports two tasks
     */
    public function testTasks() {
        $testCustomer = self::$api->postJson('editor/customer/',[
            'name'=>'API Testing::ResourcesLogCustomer',
            'number'=>uniqid('API Testing::ResourcesLogCustomer'),
        ]);

        $task1 = $this->createTask('task1', $testCustomer->id);
        $task2 = $this->createTask('task2', $testCustomer->id);

        //open task for editing. This should not produce any error
        $result = self::$api->setTaskToEdit($task1->id);
        self::assertObjectNotHasAttribute('error',$result, (property_exists($result, 'error') ? $result->error : ''));
        $this->api()->setTask($task1);

        $jsonFileName = 'segments-task1.json';
        $segments = self::$api->getSegments($jsonFileName);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments);

        self::assertCount(1, $segments);

        //open the secound task with the same user. This should not be posible
        $result = self::$api->setTaskToEdit($task2->id);
        self::assertObjectNotHasAttribute('error',$result, (property_exists($result, 'error') ? $result->error : ''));
        $this->api()->setTask($task2);


        $jsonFileName = 'segments-task2.json';
        $segments = self::$api->getSegments($jsonFileName);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments);

        //open tasks for whole testcase
        self::$api->login('testmanager');

        // remove the 2 tasks
        self::$api->deleteTask($task1->id);
        self::$api->deleteTask($task2->id);

        //remove the temp customer
        self::$api->delete('editor/customer/'.$testCustomer->id);
    }

    /**
     * Create the task. The task will not be imported directly autoStartImport is 0
     * TODO FIXME: why don't we use the API-functions completely ?
     * @param string $taskName
     * @return stdClass
     */
    private function createTask(string $taskName, int $customerId){
        $task =[
            'taskName' => 'API Testing::'.__CLASS__.'_'.$taskName, 
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerId' => $customerId,
            'autoStartImport' => 0
        ];
        self::$api->addImportFile(self::$api->getFile($taskName.'-de-en.xlf'));
        self::$api->import($task, false, false);
        self::$api->getJson('editor/task/'.self::$api->getTask()->id.'/import');
        self::$api->checkTaskStateLoop();
        return self::$api->getTask();
    }
}
