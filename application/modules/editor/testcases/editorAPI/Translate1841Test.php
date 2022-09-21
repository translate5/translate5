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
 * BasicSegmentEditingTest imports a simple task, checks imported values,
 * edits segments and checks then the edited ones again on correct content
 */
class Translate1841Test extends \ZfExtended_Test_ApiTestcase {
    public static function setUpBeforeClass(): void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'sourceLang' => 'de',
            'targetLang' => 'en',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        );
        
        $appState = self::assertAppState();
        self::assertNotContains('editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap', $appState->pluginsLoaded, 'Plugin LockSegmentsBasedOnConfig should not be activated for this test case!');
        self::assertNotContains('editor_Plugins_NoMissingTargetTerminology_Bootstrap', $appState->pluginsLoaded, 'Plugin NoMissingTargetTerminology should not be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        
        $api->addImportFile($api->getFile('TRANSLATE-1841-de-en.xlf'), 'application/xml');
        
        $api->import($task);
        
        $api->addUser('testlector');
        
        //login in setUpBeforeClass means using this user in whole testcase!
        $api->login('testlector');
        
        $task = $api->getTask();
        //open task for whole testcase
        $api->putJson('editor/task/'.$task->id, array('userState' => 'edit', 'id' => $task->id));
        $api->reloadTask();
    }

    /**
     * fixing TRANSLATE-1841 was causing TRANSLATE-2771 - tag pairing of segments containing a single tag did not work any more - this is tested here as PHP Unit test
     */
    public function testTranslate2771() {
        $tag = new \editor_Models_Segment_InternalTag();
        //tag pairs only must work
        $tagPairsOnly = '<div class="open 672069643d223122 internal-tag ownttip"><span class="short" title="&lt;g id=&quot;1&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;g id=&quot;1&quot;&gt;</span></div><div class="open 672069643d223222 internal-tag ownttip"><span class="short" title="&lt;g id=&quot;2&quot;&gt;">&lt;2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;g id=&quot;2&quot;&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/g&gt;">&lt;/2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;/g&gt;</span></div>Test<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/g&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/g&gt;</span></div>';
        //tag pair with single tag must work too
        $tagPairWithSingleTag = '<div class="open 6270742069643d2231223e266c743b53796d626f6c20466f726d61743d22417269616c223e3c2f627074 internal-tag ownttip"><span class="short" title="&lt;Symbol Format=&quot;Arial&quot;&gt;">&lt;2&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;Symbol Format="Arial"></span></div><div class="single 70682069643d2232223e6d3c2f7068 internal-tag ownttip"><span class="short" title="m">&lt;3/&gt;</span><span class="full" data-originalid="2" data-length="-1">m</span></div><div class="close 6570742069643d2231223e266c743b2f53796d626f6c3e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/Symbol&gt;">&lt;/2&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/Symbol></span></div>Test 2';
        $this->assertEquals('<g id="1"><g id="2"></g>Test</g>', $tag->toXliffPaired($tagPairsOnly), 'XLF Pairer with tag pairs only does not work');
        $this->assertEquals('<g id="1"><x id="2"/></g>Test 2', $tag->toXliffPaired($tagPairWithSingleTag), 'XLF Pairer with tag pair and single tag does not work');
    }

    /**
     * Test the issues fixed behaviour
     */
    public function testIssue() {
        $task = $this->api()->getTask();
        $this->assertIsArray($task->userTracking, 'UserTracking in task is no array!');
        $this->assertNotEmpty($task->userTracking, 'UserTracking of task is empty!');
        $user = reset($task->userTracking);
        
        $segEdit1 = '<ins class="trackchanges ownttip" data-usertrackingid="'.$user->id.'" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2020-02-26T10:49:28+01:00"><div class="open 6270742069643d223122207269643d223122202f internal-tag ownttip"><span title="&lt;bpt id=&quot;1&quot; rid=&quot;1&quot; /&gt;" class="short">&lt;1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;bpt id=&quot;1&quot; rid=&quot;1&quot; /&gt;</span></div></ins>back <del class="trackchanges ownttip deleted" data-usertrackingid="'.$user->id.'" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2020-02-26T10:49:27+01:00"><div class="open 6270742069643d223122207269643d223122202f internal-tag ownttip"><span title="&lt;bpt id=&quot;1&quot; rid=&quot;1&quot; /&gt;" class="short">&lt;1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;bpt id=&quot;1&quot; rid=&quot;1&quot; /&gt;</span></div></del>to the house<div class="close 6570742069643d223222207269643d223122202f internal-tag ownttip"><span title="&lt;ept id=&quot;2&quot; rid=&quot;1&quot; /&gt;" class="short">&lt;/1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;ept id=&quot;2&quot; rid=&quot;1&quot; /&gt;</span></div>';
        $segEdit2 = '<div class="open 6270742069643d223122207269643d223122202f internal-tag ownttip"><span title="&lt;bpt id=&quot;1&quot; rid=&quot;1&quot; /&gt;" class="short">&lt;1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;bpt id=&quot;1&quot; rid=&quot;1&quot; /&gt;</span></div>the house<del class="trackchanges ownttip deleted" data-usertrackingid="'.$user->id.'" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2020-02-26T10:52:14+01:00"><div class="close 6570742069643d223222207269643d223122202f internal-tag ownttip"><span title="&lt;ept id=&quot;2&quot; rid=&quot;1&quot; /&gt;" class="short">&lt;/1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;ept id=&quot;2&quot; rid=&quot;1&quot; /&gt;</span></div></del>\u00a0is<ins class="trackchanges ownttip" data-usertrackingid="'.$user->id.'" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2020-02-26T10:52:15+01:00"><div class="close 6570742069643d223222207269643d223122202f internal-tag ownttip"><span title="&lt;ept id=&quot;2&quot; rid=&quot;1&quot; /&gt;" class="short">&lt;/1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;ept id=&quot;2&quot; rid=&quot;1&quot; /&gt;</span></div></ins> back';
        
        //get segment list (just the ones of the first file for that tests)
        $segments = $this->api()->getSegments(null, 2);
        $this->assertNotEmpty($segments, 'No segments are found in the Task!');
        
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $segEdit1, $segments[0]->id);
        $this->api()->putJson('editor/segment/'.$segments[0]->id, $segmentData);
        
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $segEdit2, $segments[1]->id);
        $this->api()->putJson('editor/segment/'.$segments[1]->id, $segmentData);
        
        $task = $this->api()->getTask();
        //start task export 
        $this->api()->login('testmanager');
        $this->api()->request('editor/task/export/id/'.$task->id.'?format=xliff2');
        
        //get the exported file content
        $path = $this->api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        $exportedFile = $this->api()->getFileContentFromZipPath($pathToZip, 'export-*.xliff');
        $exportedFile = preg_replace([
            '#translate5:taskguid="[^"]+"#',
            '#translate5:taskname="API Testing::Translate1841Test [^"]+"#',
            '#<file id="[0-9]+" translate5:filename="TRANSLATE-1841-de-en.xlf">#'
        ], [
            'translate5:taskguid="{XXXX}"', 
            'translate5:taskname="API Testing::Translate1841Test"', 
            '<file id="XXXX" translate5:filename="TRANSLATE-1841-de-en.xlf">',
        ], $exportedFile);
        //compare it
        $expectedResult = $this->api()->getFileContent('exportCompare.xlf');
        
        //file_put_contents('/home/tlauria/foo1.xlf', rtrim($expectedResult));
        //file_put_contents('/home/tlauria/foo2.xlf', rtrim($exportedFile));
        //file_put_contents('/home/tlauria/foo-'.$fileToCompare, rtrim($exportedFile));
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to exportCompare.xlf');
    }
    
    /**
     * tests the export results
     * @param stdClass $task
     * @param string $exportUrl
     * @param string $fileToExport
     * @param string $fileToCompare
     */
    protected function checkExport(stdClass $task, $exportUrl, $fileToExport, $fileToCompare) {

    }

    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testlector');
        self::$api->putJson('editor/task/'.$task->id, array('userState' => 'open', 'id' => $task->id));
        self::$api->login('testmanager');
        self::$api->cleanup && self::$api->delete('editor/task/'.$task->id);
    }
}