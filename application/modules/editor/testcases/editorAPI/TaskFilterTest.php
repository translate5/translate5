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
 * Test the task joined filters
 */
class TaskFilterTest extends \editor_Test_ApiTest {

    protected static bool $termtaggerRequired = true;
    /**
     * Setting up the test task by fresh import, adds the lector and translator users
     */
    public static function beforeTests():void {

        $task = array(
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
        );

        static::api()->addImportFile('SegmentWorkflowTest/simple-en-de.zip');
        static::api()->import($task);
        
        static::api()->addUser('testlector','open','reviewing',[
            'deadlineDate'=>date("Y-m-d 00:00:00", strtotime("+1 day"))
        
        ]);
        static::api()->reloadTask();
        static::api()->addUser('testtranslator', 'waiting', 'translation',[
            'deadlineDate'=>date("Y-m-d 00:00:00", strtotime("+2 day"))
            
        ]);
    }
    
    /**
     * Test if the task user assoc filters are workign
     */
    public function testTaskUserAssocFilters() {
        //test the assigment date of the task
        $return = static::api()->getJson('editor/task',[
            'filter' => '[{"operator":"eq","value":"'.date("Y-m-d 00:00:00", strtotime("now")).'","property":"assignmentDate"},{"operator":"eq","value":'.static::api()->getTask()->id.',"property":"id"}]'
        ]);
        $this->assertCount(2, $return);
        
        //test the finish count filter
        $return = static::api()->getJson('editor/task',[
            'filter' => '[{"operator":"eq","value":0,"property":"segmentFinishCount"},{"operator":"eq","value":'.static::api()->getTask()->id.',"property":"id"}]',
        ]);
        $this->assertCount(1, $return);
        $this->assertEquals(0, $return[0]->segmentFinishCount);
    }

    public static function afterTests(): void {
        $task = static::api()->getTask();
        static::api()->deleteTask($task->id, 'testmanager');
    }
}
