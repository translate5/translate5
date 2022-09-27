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

    protected static bool $setupOwnCustomer = true;
    
    /**
     * imports two tasks
     */
    public function testTasks() {

        $task1 = $this->createTask('task1', static::$testOwnCustomer->id);
        $task2 = $this->createTask('task2', static::$testOwnCustomer->id);

        //open task for editing. This should not produce any error
        $response = static::api()->setTaskToEdit($task1->id);
        static::api()->setTask($task1);
        self::assertNotEmpty($response,'Unable to edit task 1.');

        $jsonFileName = 'segments-task1.json';
        $segments = static::api()->getSegments($jsonFileName);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments);

        self::assertCount(1, $segments);

        //open the secound task with the same user. This should not be posible
        $response = static::api()->setTaskToEdit($task2->id);
        static::api()->setTask($task2);
        self::assertNotEmpty($response,'Unable to edit task 2.');

        $jsonFileName = 'segments-task2.json';
        $segments = static::api()->getSegments($jsonFileName);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments);

        //open tasks for whole testcase
        static::api()->login('testmanager');

        // remove the 2 tasks
        static::api()->deleteTask($task1->id);
        static::api()->deleteTask($task2->id);


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
        static::api()->addImportFile(static::api()->getFile($taskName.'-de-en.xlf'));
        static::api()->import($task, false, false);
        static::api()->getJson('editor/task/'.static::api()->getTask()->id.'/import');
        static::api()->checkTaskStateLoop();
        return static::api()->getTask();
    }
}
