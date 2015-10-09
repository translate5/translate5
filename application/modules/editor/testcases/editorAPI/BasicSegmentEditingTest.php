<?php
class BasicSegmentEditingTest extends \ZfExtended_Test_ApiTestcase {
    public static function setUpBeforeClass() {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
        );
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        self::assertTermTagger();
        $api->addImportFile('editorAPI/MainTest/simple-en-de.zip');
        $api->import($task);
        
        $api->addUser('testlector');
        
        //login in setUpBeforeClass means using this user in whole testcase!
        $api->login('testlector');
        
        $task = $api->getTask();
        //open task for whole testcase
        $api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
    }
    
    /**
     * Testing some segment values directly after import
     */
    public function testBasicSegmentValuesAfterImport() {
        //get segment list
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        
        $this->assertCount(7, $segments);
        
        //bulk check of all pretrans fields
        $pretrans = array_map(function($item){
            return $item->pretrans;
        }, $segments);
        $this->assertEquals(array(1,1,1,1,1,1,0), $pretrans);
        
        //bulk check of all pretrans fields
        $matchRates = array_map(function($item){
            return $item->matchRate;
        }, $segments);
        $this->assertEquals(array('100','100','100','100','100','100','0'), $matchRates);
        
        //bulk check of all pretrans fields
        $autoStateIds = array_map(function($item){
            return $item->autoStateId;
        }, $segments);
        $this->assertEquals(array('0','0','0','0','0','0','4'), $autoStateIds);
        
        foreach($segments as $segment) {
            $this->assertEquals('{00000000-0000-0000-C100-CCDDEE000001}', $segment->userGuid);
            $this->assertEquals('manager test', $segment->userName);
            $this->assertEquals(1, $segment->editable);
            $this->assertEmpty($segment->qmId);
            $this->assertEquals(0, $segment->stateId);
            $this->assertEquals(0, $segment->fileOrder);
            $this->assertEmpty($segment->comments);
            $this->assertEquals(0, $segment->workflowStepNr);
            $this->assertEmpty($segment->workflowStep);
            $this->assertObjectNotHasAttribute('sourceEdit', $segment);
            $this->assertObjectNotHasAttribute('sourceEditToSort', $segment);
        }
        
        $firstSegment = reset($segments);
        $this->assertEquals(1, $firstSegment->segmentNrInTask);
        $this->assertEquals(1, $firstSegment->mid);
        
        $this->assertEquals('This file is <div title="" class="term preferredTerm exact transNotFound" data-tbxid="term_0000003_001_en_001_0000006">a</div> based on <div title="" class="term preferredTerm exact transNotFound" data-tbxid="term_0000003_001_en_001_0000006">a</div> part of the php-online-Documentation. It\'s translation is done by <div title="" class="term preferredTerm exact transNotFound" data-tbxid="term_0000003_001_en_001_0000006">a</div> pretranslation based on <div title="" class="term preferredTerm exact transNotFound" data-tbxid="term_0000003_001_en_001_0000006">a</div> very fast winalign-Project and is not at all state of the translation art. It\'s only purpose is the generation of demo-data for translate5.', $firstSegment->source);
        $this->assertEquals('da37e24323d2953c3b48c82cd6e50d71', $firstSegment->sourceMd5);
        $this->assertEquals('This file is a based on a part', $firstSegment->sourceToSort);
        $this->assertEquals('Diese Datei ist Teil der php-online-Dokumentation. Ihre Übersetzung ist durch eine Vorübersetzung entstanden, die auf einem sehr schnell durchgeführten winalign-Project basiert und in keiner Art und Weise dem State of the Art eines Übersetzungsprojekts entspricht. Sein einziger Zweck ist die Erzeugung von Demo-Daten für translate5. ',$firstSegment->target);
        $this->assertEquals('Diese Datei ist Teil der php-o', $firstSegment->targetToSort);
        $this->assertEquals($firstSegment->target, $firstSegment->targetEdit);
        $this->assertEquals($firstSegment->targetToSort, $firstSegment->targetEditToSort);
        $this->assertEquals('74d85bd308aa69f558af1a3a9f1f2dae', $firstSegment->targetMd5);
        
        $lastSegment = end($segments);
        $this->assertEquals(7, $lastSegment->segmentNrInTask);
        $this->assertEquals(7, $lastSegment->mid);
        
        $this->assertEquals('<div title="" class="term preferredTerm exact transNotDefined" data-tbxid="term_0000011_001_en_001_0000022">Apache</div> 2.x on Unix systems', $lastSegment->source);
        $this->assertEquals('7c672e73fab402d8d99addec970a47b6', $lastSegment->sourceMd5);
        $this->assertEquals('Apache 2.x on Unix systems', $lastSegment->sourceToSort);
        $this->assertEmpty($lastSegment->target);
        $this->assertEmpty($lastSegment->targetToSort);
        $this->assertEmpty($lastSegment->targetEdit);
        $this->assertEmpty($lastSegment->targetEditToSort);
        $this->assertEquals('d41d8cd98f00b204e9800998ecf8427e', $lastSegment->targetMd5);
    }
    
    public function testSegmentEditing() {
        //get segment list
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        
        //test editing a prefilled segment
        $segToTest = $segments[2];
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', 'PHP Handbuch', $segToTest->id);
        $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        
        //check direct PUT result
        $this->assertSegmentContentToFile('testSegmentEditing-assert-seg3.json', $segment);
        
        //check again with GET fresh from server
        $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id);
        $this->assertSegmentContentToFile('testSegmentEditing-assert-seg3.json', $segment);
        
        //test editing an empty segment
        $segToTest = $segments[6];
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', 'Apache 2.x auf Unix-Systemen', $segToTest->id);
        $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        
        //check direct PUT result
        $this->assertSegmentContentToFile('testSegmentEditing-assert-seg7.json', $segment);
        
        //check again with GET fresh from server
        $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id);
        $this->assertSegmentContentToFile('testSegmentEditing-assert-seg7.json', $segment);
        
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        
        //bulk check of all autoStateId fields
        $autoStateIds = array_map(function($item){
            return $item->autoStateId;
        }, $segments);
        $this->assertEquals(array('0','0','1','0','0','0','1'), $autoStateIds);
        
        //bulk check of all workflowStepNr fields
        $workflowStepNr = array_map(function($item){
            return $item->workflowStepNr;
        }, $segments);
        $this->assertEquals(array('0','0','1','0','0','0','1'), $workflowStepNr);
        
        //bulk check of all autoStateId fields
        $workflowStep = array_map(function($item){
            return $item->workflowStep;
        }, $segments);
        $this->assertEquals(array('','','lectoring','','','','lectoring'), $workflowStep);
    }
    
    public static function tearDownAfterClass() {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}