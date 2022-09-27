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
 * Test the default deadline date. For each workflow role (this tests only the default workflow),
 * an default deadline date task overwrite config is set.
 * 
 * 1. create task with and set static order date -> 2020-11-21 18:01:00
 * 
 * 2. define in task-config.ini the deadline date days
 *    translation ->  2 Days after orderdate
 *    reviewing -> 4,2 Days after orderdate -> this case will test also the hour deadline date.
 *    translatorCheck -> 6 
 *    
 * 3. assign user via api, set deadline date to default-date
 * 4. load all user assocs and check the expected results
 * 
 * 
 */
class Translate2375Test extends \editor_Test_ApiTest {
    protected static $fixedDate = '2020-11-21 18:01:00';
    public static function beforeTests(): void {

        $task = array(
            'sourceLang' => 'de',
            'targetLang' => 'en',
            'orderdate' => self::$fixedDate
        );

        $zipfile = static::api()->zipTestFiles('testfiles/','testTask.zip');

        static::api()->addImportFile($zipfile);
        static::api()->import($task);
        $task = static::api()->getTask();
        error_log('Task created. '.static::api()->getTask()->taskName);
    }
    
    public function testDeadlineDate(){
        self::assertLogin('testmanager');
        
        $assocParams = ['deadlineDate' => 'default', 'assignmentDate' => self::$fixedDate];
        
        static::api()->addUser('testtranslator','open','reviewing',$assocParams);
        static::api()->reloadTask();
        static::api()->addUser('testtranslator','waiting','translation',$assocParams);
        static::api()->reloadTask();
        static::api()->addUser('testtranslator','waiting','translatorCheck',$assocParams);
        
        $data = static::api()->getJson('editor/taskuserassoc',[
            'filter' => '[{"operator":"eq","value":"' . static::api()->getTask()->taskGuid . '","property":"taskGuid"}]'
        ]);
        
        //filter out the non static data
        $data = array_map(function($assoc){
            unset($assoc->id);
            unset($assoc->taskGuid);
            unset($assoc->usedInternalSessionUniqId);
            unset($assoc->staticAuthHash);
            unset($assoc->editable);
            unset($assoc->deletable);
            return $assoc;
        }, $data);
        
        //file_put_contents(static::api()->getFile('/expected.json', null, false), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertEquals(static::api()->getFileContent('expected.json'), $data, 'The calculate default deadline is are not as expected!');
    }
    
    public static function afterTests(): void {
        $task = static::api()->getTask();
        static::api()->deleteTask($task->id, 'testmanager');
    }
}