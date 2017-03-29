<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
			 http://www.gnu.org/licenses/agpl.html

END LICENSE AND COPYRIGHT
*/

/**
 * TaskEntityWorkflowTest is currently incomplete, just created as a stub to be implemented
 */
class TaskEntityWorkflowTest extends \ZfExtended_Test_ApiTestcase {
    public static function setUpBeforeClass() {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
        );
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        self::assertTermTagger(); //FIXME change this test so that no termtagger is needed. (deactivate termtagging by task param possible?)
        $api->addImportFile('editorAPI/MainTest/simple-en-de.zip');
        $api->import($task);
    }
    
    public function testEntityVersionOnChangingUsers() {
        $this->markTestIncomplete("test in draft mode, has to be completed!");
        //first add one user
        $this->api()->addUser('testlector');
        //dont reload task and add another user, this results correctly in an 409 HTTP status
        //problem for this test is now, that addUser already checks for 200, this has to be flexibilized
        $this->api()->addUser('testtranslator');
    }
    
    public static function tearDownAfterClass() {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}