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
 * ChangeAlikeTranslate683Test imports a simple task, checks and checks the ChangeAlike Behaviour in combination 
 * with Source Editing and trans[Not]Found mark up.
 * See therefore: 
 * TRANSLATE-683 source original will be overwritten even if Source is not editable, 
 *   and contents are no repetition of each other
 * TRANSLATE-549 fixing Source Editing and Change Alike behaviour
 * TRANSLATE-543 fixing red terms go blue on using change alikes, causing 683
 * 
 * This test also covers:
 * TRANSLATE-686 by testing the autostates
 * 
 * So in conclusion the desired behaviuor is: 
 * Without Source Editing: 
 *   - Segments to be changed with the repetition editor are getting the edited target and the autostates of the master segment
 *   - In Source Original the transFound states are recalculated
 *   
 * With Source Editing: 
 *   - Segments to be changed with the repetition editor are getting the edited target and the edited source and the autostates of the master segment
 *   - In Source Original the transFound states are recalculated
 */
class ChangeAlikeTranslate683Test extends editor_Test_JsonTest {
    protected static $useSourceEditing = false;
    
    /**
     * the strings to be compared on testing change alike source matching 
     * @var array
     */
    protected $toCompareSource = array(
        'sourceBeforeEdit' => 'Ich wiederhole mich in der <div title="" class="term preferredTerm exact transNotFound">Quelle</div>',
        'targetBeforeEdit' => 'I repeat <div title="" class="term preferredTerm exact">me</div> in the spring',
        'sourceAfterEdit' => 'Ich wiederhole mich in der <div title="" class="term preferredTerm exact transFound">Quelle</div>',
        'targetEditAfterEdit' => 'I repeat <div title="" class="term preferredTerm exact">me</div> in the <div title="" class="term preferredTerm exact">source</div>',
    );
    
    /**
     * the strings to be compared on testing change alike target matching 
     * @var array
     */
    protected $toCompareTarget = array(
        'sourceBeforeEdit5' => 'Ich wiederhole mich im <div title="" class="term preferredTerm exact transNotFound">Zieltext</div>',
        'targetBeforeEdit' => 'I repeat <div title="" class="term preferredTerm exact">me</div> in the destinationtext',
        'targetBeforeEdit6' => 'I repeat <div title="" class="term preferredTerm exact">me</div> in the <div title="" class="term preferredTerm exact">targettext</div>',
            
        'sourceAfterEdit4' => 'Ich wiederhole mich im Targettext',
        'sourceAfterEdit6' => 'Ich wiederhole mich im <div title="" class="term preferredTerm exact transFound">Zieltext</div>',
        'sourceAfterEdit5' => 'Ich wiederhole mich im <div title="" class="term preferredTerm exact transFound">Zieltext</div>',
        'sourceAfterEdit7' => 'Ich wiederhole mich im <div title="" class="term preferredTerm exact transFound">Zieltext</div> und bin in der <div title="" class="term preferredTerm exact transNotFound">Quelle</div> <div title="" class="term preferredTerm exact transNotDefined">unterschiedlich</div>',
            
        'targetAfterEdit' => 'I repeat <div title="" class="term preferredTerm exact">me</div> in the <div title="" class="term preferredTerm exact">targettext</div>',
    );
    
    public static function setUpBeforeClass():void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'sourceLang' => 'de',
            'targetLang' => 'en',
            'edit100PercentMatch' => true,
            'enableSourceEditing' => static::$useSourceEditing,
            'lockLocked' => 1,
        );
        
        $appState = self::assertTermTagger();
        self::assertNotContains('editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap', $appState->pluginsLoaded, 'Plugin LockSegmentsBasedOnConfig should not be activated for this test case!');
        self::assertNotContains('editor_Plugins_NoMissingTargetTerminology_Bootstrap', $appState->pluginsLoaded, 'Plugin NoMissingTargetTerminology should not be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        
        $api->addImportFile('editorAPI/TRANSLATE-683/TRANSLATE-683-de-en.csv');
        $api->addImportTbx('editorAPI/TRANSLATE-683/TRANSLATE-683-de-en.tbx');
        $api->import($task);
        
        $task = $api->getTask();
        //open task for whole testcase
        $api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
    }
    
    /**
     * Test using changealikes by source match
     */
    public function testSourceMatches() {
        //get segment list
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        $this->assertCount(7, $segments);
        
        //test source editing 
        $isSE = $this->api()->getTask()->enableSourceEditing;
        
        //test editing a prefilled segment
        $segToTest = $segments[1];
        
        if($isSE) {
            $this->assertFieldTextEquals($this->toCompareSource['sourceBeforeEdit'], $segToTest->sourceEdit);
        }
        $this->assertFieldTextEquals($this->toCompareSource['sourceBeforeEdit'], $segToTest->source);
        $this->assertFieldTextEquals($this->toCompareSource['targetBeforeEdit'], $segToTest->targetEdit);
        $this->assertFieldTextEquals($this->toCompareSource['targetBeforeEdit'], $segToTest->target);
        
        //edit one segment
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', 'I repeat me in the source', $segToTest->id);
        $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        
        //edit source also, currently our test helper cant make this in one API call
        if($isSE) {
            $segmentData = $this->api()->prepareSegmentPut('sourceEdit', 'Ich wiederhole mich in der Quelle - edited', $segToTest->id);
            $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        }
      
        //assert source / target after editing
        $this->assertFieldTextEquals($this->toCompareSource['sourceAfterEdit'], $segment->source);
        $this->assertFieldTextEquals($this->toCompareSource['targetBeforeEdit'], $segment->target); //not changed the target original
        $this->assertFieldTextEquals($this->toCompareSource['targetEditAfterEdit'], $segment->targetEdit);
        
        //fetch alikes and assert correct segments found by segmentNrInTask
        $alikes = $this->api()->requestJson('editor/alikesegment/'.$segToTest->id, 'GET');
        $segmentNrInTask = array_map(function($item){
            return $item->segmentNrInTask;
        },$alikes);
        $this->assertEquals(array(1,3), $segmentNrInTask);
        $alikeIds = array_map(function($item){
            return $item->id;
        },$alikes);
        
        //save alikes
        $alikePutData = [
            'duration' => 777, //faked duration value
            'alikes' => json_encode($alikeIds)
        ]; 
        //Alike Data is sent as plain HTTP request parameters not as JSON in data parameter!
        $resp = $this->api()->request('editor/alikesegment/'.$segToTest->id, 'PUT', $alikePutData);
        $foo = $this->api()->decodeJsonResponse($resp);
        
        //get segment list again to check if change alikes were applied correctly
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        
        //check the alike were the ChangeAlikes handler only changed the autoState, the content was already correct
        $segment = $segments[0];
        $this->assertEquals($isSE ? 11 : 13, $segment->autoStateId);
        $this->assertFieldTextEquals($this->toCompareSource['sourceAfterEdit'], $segment->source);
        //this changealike was prefilled with the correct segment data, so targetEditAfterEdit == targetBeforeEdit
        $this->assertFieldTextEquals($this->toCompareSource['targetEditAfterEdit'], $segment->target);
        $this->assertFieldTextEquals($this->toCompareSource['targetEditAfterEdit'], $segment->targetEdit);
        
        //retest the master segment, if the edited content remains and the autostate is correct
        $segment = $segments[1];
        $this->assertEquals(10, $segment->autoStateId);
        $this->assertFieldTextEquals($this->toCompareSource['sourceAfterEdit'], $segment->source);
        $this->assertFieldTextEquals($this->toCompareSource['targetBeforeEdit'], $segment->target);
        $this->assertFieldTextEquals($this->toCompareSource['targetEditAfterEdit'], $segment->targetEdit);
        
        //test the alike were changed content and autostate
        $segment = $segments[2];
        $this->assertEquals(11, $segment->autoStateId);
        $this->assertFieldTextEquals($this->toCompareSource['sourceAfterEdit'], $segment->source);
        $this->assertFieldTextEquals($this->toCompareSource['targetBeforeEdit'], $segment->target);
        $this->assertFieldTextEquals($this->toCompareSource['targetEditAfterEdit'], $segment->targetEdit);
    }
    
    /**
     * Test using changealikes by target match
     */
    public function testTargetMatches() {
        //get segment list
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        $this->assertCount(7, $segments);
        
        //test source editing 
        $isSE = $this->api()->getTask()->enableSourceEditing;
        
        //test editing a prefilled segment
        $segToTest = $segments[4];
        if($isSE) {
            $this->assertFieldTextEquals($this->toCompareTarget['sourceBeforeEdit5'], $segToTest->sourceEdit);
        }
        $this->assertFieldTextEquals($this->toCompareTarget['sourceBeforeEdit5'], $segToTest->source);
        $this->assertFieldTextEquals($this->toCompareTarget['targetBeforeEdit'], $segToTest->targetEdit);
        $this->assertFieldTextEquals($this->toCompareTarget['targetBeforeEdit'], $segToTest->target);

        //edit one segment
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', 'I repeat me in the targettext', $segToTest->id);
        $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);

        //edit source also, currently our test helper cant make this in one API call
        if($isSE) {
            $segmentData = $this->api()->prepareSegmentPut('sourceEdit', 'Ich wiederhole mich im Zieltext - edited', $segToTest->id);
            $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        }
         
        //assert source / target after editing
        $this->assertFieldTextEquals($this->toCompareTarget['sourceAfterEdit5'], $segment->source);
        $this->assertFieldTextEquals($this->toCompareTarget['targetBeforeEdit'], $segment->target); //not changed the target original
        $this->assertFieldTextEquals($this->toCompareTarget['targetAfterEdit'], $segment->targetEdit);
        
        //fetch alikes and assert correct segments found by segmentNrInTask
        $alikes = $this->api()->requestJson('editor/alikesegment/'.$segToTest->id, 'GET');
        $segmentNrInTask = array_map(function($item){
            return $item->segmentNrInTask;
        },$alikes);
        $this->assertEquals(array(4,6,7), $segmentNrInTask);
        $alikeIds = array_map(function($item){
            return $item->id;
        },$alikes);
        
        //save alikes
        $alikePutData = [
            'duration' => 777, //faked duration value
            'alikes' => json_encode($alikeIds)
        ]; 
        //Alike Data is sent as plain HTTP request parameters not as JSON in data parameter!
        $resp = $this->api()->request('editor/alikesegment/'.$segToTest->id, 'PUT', $alikePutData);
        $foo = $this->api()->decodeJsonResponse($resp);
        
        //get segment list again to check if change alikes were applied correctly
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        
        //check the alike were the ChangeAlikes handler only changed the autoState, the content was already correct
        $segment = $segments[3];
        $this->assertEquals(11, $segment->autoStateId);
        $this->assertFieldTextEquals($this->toCompareTarget['sourceAfterEdit4'], $segment->source);
        $this->assertFieldTextEquals($this->toCompareTarget['targetBeforeEdit'], $segment->target);
        $this->assertFieldTextEquals($this->toCompareTarget['targetAfterEdit'], $segment->targetEdit);
        
        //retest the master segment, if the edited content remains and the autostate is correct
        $segment = $segments[4];
        $this->assertEquals(10, $segment->autoStateId);
        $this->assertFieldTextEquals($this->toCompareTarget['sourceAfterEdit5'], $segment->source);
        $this->assertFieldTextEquals($this->toCompareTarget['targetBeforeEdit'], $segment->target);
        $this->assertFieldTextEquals($this->toCompareTarget['targetAfterEdit'], $segment->targetEdit);
        
        //test the alike were changed content and autostate
        $segment = $segments[5];
        $this->assertEquals($isSE ? 11 : 13, $segment->autoStateId);
        $this->assertFieldTextEquals($this->toCompareTarget['sourceAfterEdit6'], $segment->source);
        $this->assertFieldTextEquals($this->toCompareTarget['targetBeforeEdit6'], $segment->target);
        $this->assertFieldTextEquals($this->toCompareTarget['targetAfterEdit'], $segment->targetEdit);
        
        //test the alike were changed content and autostate
        $segment = $segments[6];
        $this->assertEquals(11, $segment->autoStateId);
        $this->assertFieldTextEquals($this->toCompareTarget['sourceAfterEdit7'], $segment->source);
        $this->assertFieldTextEquals($this->toCompareTarget['targetBeforeEdit'], $segment->target);
        $this->assertFieldTextEquals($this->toCompareTarget['targetAfterEdit'], $segment->targetEdit);
    }
    
    /**
     * Test if sourceEdited has been changed correctly if source editing was enabled in the task
     * @depends testTargetMatches
     * @depends testSourceMatches
     */
    public function testSourceEditing() {
        if(!$this->api()->getTask()->enableSourceEditing) {
            //if no sourceEditing pass this test
            $this->markTestSkipped('Skipped in this run since source editing disabled.');
            return;
        }
        //get segment list again to check if change alikes were applied correctly
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        
        $sourceCompareString = $this->toCompareSource['sourceAfterEdit'].' - edited';
        $targetCompareString = $this->toCompareTarget['sourceAfterEdit6'].' - edited';

        $this->assertFieldTextEquals($sourceCompareString, $segments[0]->sourceEdit);
        $this->assertFieldTextEquals($sourceCompareString, $segments[1]->sourceEdit);
        $this->assertFieldTextEquals($sourceCompareString, $segments[2]->sourceEdit);
        $this->assertFieldTextEquals($targetCompareString, $segments[3]->sourceEdit);
        $this->assertFieldTextEquals($targetCompareString, $segments[4]->sourceEdit);
        $this->assertFieldTextEquals($targetCompareString, $segments[5]->sourceEdit);
        $this->assertFieldTextEquals($targetCompareString, $segments[6]->sourceEdit);
    }
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}