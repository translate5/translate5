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
        $api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
        $api->reloadTask();
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
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=2');
        $this->assertNotEmpty($segments, 'No segments are found in the Task!');
        
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $segEdit1, $segments[0]->id);
        $this->api()->requestJson('editor/segment/'.$segments[0]->id, 'PUT', $segmentData);
        
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $segEdit2, $segments[1]->id);
        $this->api()->requestJson('editor/segment/'.$segments[1]->id, 'PUT', $segmentData);
        
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
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}