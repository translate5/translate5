<?php
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