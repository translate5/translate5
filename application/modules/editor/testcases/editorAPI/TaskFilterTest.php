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
 * Test the task joined filters
 */
class TaskFilterTest extends \ZfExtended_Test_ApiTestcase {
    /**
     * Setting up the test task by fresh import, adds the lector and translator users
     */
    public static function setUpBeforeClass():void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
        );
        
        self::assertTermTagger();
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        $api->addImportFile('editorAPI/SegmentWorkflowTest/simple-en-de.zip');
        $api->import($task);
        
        $api->addUser('testlector','open','reviewer',[
            'deadlineDate'=>date("Y-m-d 00:00:00", strtotime("+1 day"))
        
        ]);
        $api->reloadTask();
        $api->addUser('testtranslator', 'waiting', 'translator',[
            'deadlineDate'=>date("Y-m-d 00:00:00", strtotime("+2 day"))
            
        ]);
    }
    
    /**
     * Test if the task user assoc filters are workign
     */
    public function testTaskUserAssocFilters() {
        //test the assigment date of the task
        $return = $this->api()->requestJson('editor/task', 'GET',[
            'filter' => '[{"operator":"eq","value":"'.date("Y-m-d 00:00:00", strtotime("now")).'","property":"assignmentDate"},{"operator":"eq","value":'.self::$api->getTask()->id.',"property":"id"}]',
        ]);
        $this->assertCount(2, $return);
        
        //test the finish count filter
        $return = $this->api()->requestJson('editor/task', 'GET',[
            'filter' => '[{"operator":"eq","value":0,"property":"segmentFinishCount"},{"operator":"eq","value":'.self::$api->getTask()->id.',"property":"id"}]',
        ]);
        $this->assertCount(1, $return);
        $this->assertEquals(0, $return[0]->segmentFinishCount);
    }

    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}
