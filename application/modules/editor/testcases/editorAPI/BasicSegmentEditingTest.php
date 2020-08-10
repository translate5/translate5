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
 * BasicSegmentEditingTest imports a simple task, checks imported values,
 * edits segments and checks then the edited ones again on correct content
 */
class BasicSegmentEditingTest extends \ZfExtended_Test_ApiTestcase {
    public static function setUpBeforeClass(): void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        );
        
        $appState = self::assertTermTagger();
        self::assertNotContains('editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap', $appState->pluginsLoaded, 'Plugin LockSegmentsBasedOnConfig should not be activated for this test case!');
        self::assertNotContains('editor_Plugins_NoMissingTargetTerminology_Bootstrap', $appState->pluginsLoaded, 'Plugin NoMissingTargetTerminology should not be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        
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
        
        $this->assertCount(13, $segments);
        
        //bulk check of all pretrans fields
        $pretrans = array_map(function($item){
            return $item->pretrans;
        }, $segments);
        $this->assertEquals(array(1,1,1,1,1,1,0,0,0,0,0,0,0), $pretrans);
        
        //bulk check of all pretrans fields
        $matchRates = array_map(function($item){
            return $item->matchRate;
        }, $segments);
        $this->assertEquals(array('100','100','100','100','100','100','0','0','0','0','0','0','0'), $matchRates);
        
        //bulk check of all pretrans fields
        $autoStateIds = array_map(function($item){
            return $item->autoStateId;
        }, $segments);
        $this->assertEquals(array('0','0','0','3','0','0','4','4','4','4','0','4','4'), $autoStateIds);
        
        foreach($segments as $segment) {
            $this->assertEquals('{00000000-0000-0000-C100-CCDDEE000001}', $segment->userGuid);
            $this->assertEquals('manager test', $segment->userName);
            if($segment->mid === '4'){
                $this->assertEquals('0', $segment->editable);
            }
            else{
                $this->assertEquals('1', $segment->editable);
            }
            $this->assertEmpty($segment->qmId);
            $this->assertEquals(0, $segment->stateId);
            $this->assertEquals(0, $segment->fileOrder);
            $this->assertEmpty($segment->comments);
            $this->assertEquals(0, $segment->workflowStepNr);
            $this->assertEmpty($segment->workflowStep);
            $this->assertObjectNotHasAttribute('sourceEdit', $segment);
            $this->assertObjectNotHasAttribute('sourceEditToSort', $segment);
        }
        
        $firstSegment = $this->api()->removeUntestableSegmentContent(reset($segments));
        $this->assertEquals(1, $firstSegment->segmentNrInTask);
        $this->assertEquals(1, $firstSegment->mid);
        
        $this->assertEquals('This file is <div title="" class="term preferredTerm exact transNotFound" data-tbxid="term_NOT_TESTABLE">a</div> based on <div title="" class="term preferredTerm exact transNotFound" data-tbxid="term_NOT_TESTABLE">a</div> part of the php-online-Documentation. It\'s translation is done by <div title="" class="term preferredTerm exact transNotFound" data-tbxid="term_NOT_TESTABLE">a</div> pretranslation based on <div title="" class="term preferredTerm exact transNotFound" data-tbxid="term_NOT_TESTABLE">a</div> very fast winalign-Project and is not at all state of the translation art. It\'s only purpose is the generation of demo-data for translate5.', $firstSegment->source);
        $this->assertEquals('da37e24323d2953c3b48c82cd6e50d71', $firstSegment->sourceMd5);
        $this->assertEquals("This file is a based on a part of the php-online-Documentation. It's translation is done by a pretranslation based on a very fast winalign-Project and is not at all state of the translation art. It's only purpose is the generation of demo-data for translate5.", $firstSegment->sourceToSort);
        $this->assertEquals('Diese Datei ist Teil der php-online-Dokumentation. Ihre Übersetzung ist durch eine Vorübersetzung entstanden, die auf einem sehr schnell durchgeführten winalign-Project basiert und in keiner Art und Weise dem State of the Art eines Übersetzungsprojekts entspricht. Sein einziger Zweck ist die Erzeugung von Demo-Daten für translate5. ',$firstSegment->target);
        $this->assertEquals("Diese Datei ist Teil der php-online-Dokumentation. Ihre Übersetzung ist durch eine Vorübersetzung entstanden, die auf einem sehr schnell durchgeführten winalign-Project basiert und in keiner Art und Weise dem State of the Art eines Übersetzungsprojekts entspricht. Sein einziger Zweck ist die Erzeugung von Demo-Daten für translate5. ", $firstSegment->targetToSort);
        $this->assertEquals($firstSegment->target, $firstSegment->targetEdit);
        $this->assertEquals($firstSegment->targetToSort, $firstSegment->targetEditToSort);
        $this->assertEquals('74d85bd308aa69f558af1a3a9f1f2dae', $firstSegment->targetMd5);
        
        $tenthSegment = $this->api()->removeUntestableSegmentContent($segments[9]);
        
        $this->assertEquals('<div title="" class="term preferredTerm exact transNotDefined" data-tbxid="term_NOT_TESTABLE">Apache</div> 2.x on Unix systems.', $tenthSegment->source);
        $this->assertEquals('3471de7d2538cd261d744f828d9231c5', $tenthSegment->sourceMd5);
        $this->assertEquals('Apache 2.x on Unix systems.', $tenthSegment->sourceToSort);
        $this->assertEmpty($tenthSegment->target);
        $this->assertEmpty($tenthSegment->targetToSort);
        $this->assertEmpty($tenthSegment->targetEdit);
        $this->assertEmpty($tenthSegment->targetEditToSort);
        $this->assertEquals('d41d8cd98f00b204e9800998ecf8427e', $tenthSegment->targetMd5);
        
        $spaceTestSegment = $this->api()->removeUntestableSegmentContent($segments[10]);
        $this->assertEquals(11, $spaceTestSegment->segmentNrInTask);
        $this->assertEquals(11, $spaceTestSegment->mid);
        $this->assertEquals('Test multiple <div class="single 73706163652074733d223230323022206c656e6774683d2232222f space internal-tag ownttip"><span title="&lt;1/&gt;: 2 whitespace characters" class="short">&lt;1/&gt;</span><span data-originalid="space" data-length="2" class="full">··</span></div>Spaces<div class="single 7461622074733d22303922206c656e6774683d2231222f tab internal-tag ownttip"><span title="&lt;2/&gt;: 1 tab character" class="short">&lt;2/&gt;</span><span data-originalid="tab" data-length="1" class="full">→</span></div>and<div class="single 7461622074733d223039303922206c656e6774683d2232222f tab internal-tag ownttip"><span title="&lt;3/&gt;: 2 tab characters" class="short">&lt;3/&gt;</span><span data-originalid="tab" data-length="2" class="full">→→</span></div>tabs <div class="single 73706163652074733d22323022206c656e6774683d2231222f space internal-tag ownttip"><span title="&lt;4/&gt;: 1 whitespace character" class="short">&lt;4/&gt;</span><span data-originalid="space" data-length="1" class="full">·</span></div>in <div class="single 7461622074733d22303922206c656e6774683d2231222f tab internal-tag ownttip"><span title="&lt;5/&gt;: 1 tab character" class="short">&lt;5/&gt;</span><span data-originalid="tab" data-length="1" class="full">→</span></div>different combinations!', $spaceTestSegment->source);
        $this->assertEquals('1eae32504f33d67ff128325a3d576658', $spaceTestSegment->sourceMd5);
        $this->assertEquals('Test multiple Spacesandtabs in different combinations!', $spaceTestSegment->sourceToSort);
        $this->assertEquals('Teste mehrere <div class="single 73706163652074733d223230323022206c656e6774683d2232222f space internal-tag ownttip"><span title="&lt;1/&gt;: 2 whitespace characters" class="short">&lt;1/&gt;</span><span data-originalid="space" data-length="2" class="full">··</span></div>Leerzeichen<div class="single 7461622074733d22303922206c656e6774683d2231222f tab internal-tag ownttip"><span title="&lt;2/&gt;: 1 tab character" class="short">&lt;2/&gt;</span><span data-originalid="tab" data-length="1" class="full">→</span></div>und<div class="single 7461622074733d223039303922206c656e6774683d2232222f tab internal-tag ownttip"><span title="&lt;3/&gt;: 2 tab characters" class="short">&lt;3/&gt;</span><span data-originalid="tab" data-length="2" class="full">→→</span></div>Tabulatoren <div class="single 73706163652074733d22323022206c656e6774683d2231222f space internal-tag ownttip"><span title="&lt;4/&gt;: 1 whitespace character" class="short">&lt;4/&gt;</span><span data-originalid="space" data-length="1" class="full">·</span></div>in <div class="single 7461622074733d22303922206c656e6774683d2231222f tab internal-tag ownttip"><span title="&lt;5/&gt;: 1 tab character" class="short">&lt;5/&gt;</span><span data-originalid="tab" data-length="1" class="full">→</span></div>verschiedenen Kombinationen!', $spaceTestSegment->target);
        $this->assertEquals('Teste mehrere LeerzeichenundTabulatoren in verschiedenen Kombinationen!', $spaceTestSegment->targetToSort);
        $this->assertEquals('Teste mehrere <div class="single 73706163652074733d223230323022206c656e6774683d2232222f space internal-tag ownttip"><span title="&lt;1/&gt;: 2 whitespace characters" class="short">&lt;1/&gt;</span><span data-originalid="space" data-length="2" class="full">··</span></div>Leerzeichen<div class="single 7461622074733d22303922206c656e6774683d2231222f tab internal-tag ownttip"><span title="&lt;2/&gt;: 1 tab character" class="short">&lt;2/&gt;</span><span data-originalid="tab" data-length="1" class="full">→</span></div>und<div class="single 7461622074733d223039303922206c656e6774683d2232222f tab internal-tag ownttip"><span title="&lt;3/&gt;: 2 tab characters" class="short">&lt;3/&gt;</span><span data-originalid="tab" data-length="2" class="full">→→</span></div>Tabulatoren <div class="single 73706163652074733d22323022206c656e6774683d2231222f space internal-tag ownttip"><span title="&lt;4/&gt;: 1 whitespace character" class="short">&lt;4/&gt;</span><span data-originalid="space" data-length="1" class="full">·</span></div>in <div class="single 7461622074733d22303922206c656e6774683d2231222f tab internal-tag ownttip"><span title="&lt;5/&gt;: 1 tab character" class="short">&lt;5/&gt;</span><span data-originalid="tab" data-length="1" class="full">→</span></div>verschiedenen Kombinationen!', $spaceTestSegment->targetEdit);
        $this->assertEquals('Teste mehrere LeerzeichenundTabulatoren in verschiedenen Kombinationen!', $spaceTestSegment->targetEditToSort);
        $this->assertEquals('d58e6850103721a3c3122b6536f0ec79', $spaceTestSegment->targetMd5);
        
        $lastSegment = end($segments);
        $this->assertEquals(13, $lastSegment->segmentNrInTask);
        $this->assertEquals(13, $lastSegment->mid);
    }
    
    /**
     * @depends testBasicSegmentValuesAfterImport
     */
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
        
        // check correction of overpapped QM Tags (only when there is no contents between them)
        $segToTest = $segments[6];
        $tag1_open = '<img class="critical qmflag ownttip open qmflag-19" data-seq="497" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-19-left.png" />';
        $tag1_close = '<img class="critical qmflag ownttip close qmflag-19" data-seq="497" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-19-right.png" />';
        $tag2_open = '<img class="critical qmflag ownttip open qmflag-4" data-seq="498" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-4-left.png" />';
        $tag2_close = '<img class="critical qmflag ownttip close qmflag-4" data-seq="498" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-4-right.png" />';
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $tag1_open.'Apache 2.x'.$tag2_open.$tag1_close.' auf'.$tag2_close.' Unix-Systemen', $segToTest->id);
        $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        
        //check direct PUT result
        $this->assertSegmentContentToFile('testSegmentEditing-assert-seg7-a.json', $segment);
        
        //check again with GET fresh from server
        $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id);
        $this->assertSegmentContentToFile('testSegmentEditing-assert-seg7-a.json', $segment);
        
        // check for overpapped QM Tags with contents between them. They must be not corrected on saving.
        $segToTest = $segments[6];
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $tag1_open.'Apache 2.x'.$tag2_open.' auf'.$tag1_close.' Unix-Systemen'.$tag2_close, $segToTest->id);
        $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        
        //check direct PUT result
        $this->assertSegmentContentToFile('testSegmentEditing-assert-seg7-b.json', $segment);
        
        //check again with GET fresh from server
        $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id);
        $this->assertSegmentContentToFile('testSegmentEditing-assert-seg7-b.json', $segment);
        
        $segToTest = $segments[7];
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', 'edited by a test', $segToTest->id);
        $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        
        $segToTest = $segments[8];
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', 'edited also by a test', $segToTest->id);
        $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        
        
        //bulk check of all autoStateId fields
        $autoStateIds = array_map(function($item){
            return $item->autoStateId;
        }, $segments);
        $this->assertEquals(array('0','0','1','3','0','0','1','1','1','4','0','4','4'), $autoStateIds);
        
        //bulk check of all workflowStepNr fields
        $workflowStepNr = array_map(function($item){
            return $item->workflowStepNr;
        }, $segments);
        $this->assertEquals(array('0','0','1','0','0','0','1','1','1','0','0','0','0'), $workflowStepNr);
        
        //bulk check of all workflowStep fields
        $workflowStep = array_map(function($item){
            return $item->workflowStep;
        }, $segments);
        $this->assertEquals(array('','','reviewing','','','','reviewing','reviewing','reviewing','','','',''), $workflowStep);
    }
    
    /**
     * @depends testSegmentEditing
     */
    public function testTaskStatistics() {
        $this->api()->reloadTask();
        $task = $this->api()->getTask();
        $stat = $task->workflowProgressSummary;
        //file_put_contents("/home/tlauria/www/translate5-master/application/modules/editor/testcases/editorAPI/BasicSegmentEditingTest/expected-task-stat-new.json", json_encode($stat,JSON_PRETTY_PRINT));
        $this->assertEquals(self::$api->getFileContent('expected-task-stat.json'), $stat, 'Imported segments are not as expected!');
    }
    
    /**
     * tests the export results
     * @depends testSegmentEditing
     * @param stdClass $task
     * @param string $exportUrl
     * @param string $fileToCompare
     */
    public function testExport() {
        self::$api->login('testmanager');
        $task = $this->api()->getTask();
        //start task export
        
        $this->api()->request('editor/task/export/id/'.$task->id);
        //$fileToCompare;

        //get the exported file content
        $path = $this->api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        
        $exportedFile = $this->api()->getFileContentFromZipPath($pathToZip, $task->taskGuid.'/install-unix.apache2.html.sdlxliff');
        //file_put_contents('/home/tlauria/foo.sdlxliff', $exportedFile);
        $expectedResult = $this->api()->getFileContent('export-assert.sdlxliff');
        
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to export-assert.sdlxliff');
    }
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testlector');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}