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
 * Testcase for TRANSLATE-1475 Merging of term tagger result and track changes content leads to several errors 
 */
class Translate1475Test extends \ZfExtended_Test_ApiTestcase {
    public static function setUpBeforeClass(): void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        );
        
        $appState = self::assertAppState();
        self::assertNotContains('editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap', $appState->pluginsLoaded, 'Plugin LockSegmentsBasedOnConfig should not be activated for this test case!');
        self::assertNotContains('editor_Plugins_NoMissingTargetTerminology_Bootstrap', $appState->pluginsLoaded, 'Plugin NoMissingTargetTerminology should not be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        
        $tests = array(
            'runtimeOptions.import.xlf.preserveWhitespace' => 0,
        );
        self::$api->testConfig($tests);
        
        $zipfile = $api->zipTestFiles('testfiles/','XLF-test.zip');
        
        $api->addImportFile($zipfile);
        $api->import($task);
        
        $api->addUser('testlector');
        
        //login in setUpBeforeClass means using this user in whole testcase!
        $api->login('testlector');
        
        $task = $api->getTask();
        //open task for whole testcase
        $api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
    }
    
    /**
     * Testing segment values directly after import
     */
    public function testSegmentValuesAfterImport() {
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=10');
        
        $data = array_map([self::$api,'removeUntestableSegmentContent'], $segments);
        //file_put_contents("/home/tlauria/www/translate5-master/application/modules/editor/testcases/editorAPI/Translate1475Test/expectedSegments-new.json", json_encode($data,JSON_PRETTY_PRINT));
        $this->assertEquals(self::$api->getFileContent('expectedSegments.json'), $data, 'Imported segments are not as expected!');
    }
    
    /**
     * @depends testSegmentValuesAfterImport
     */
    public function testSegmentEditing() {
        $replacements = [
            '<tag/>' => '<div class="single 70682069643d2231223e266c743b73796d2667743bee80a3266c743b2f73796d2667743b3c2f7068 internal-tag ownttip"><span title="&lt;ph id=&quot;1&quot;&gt;&amp;lt;sym&amp;gt;&amp;lt;/sym&amp;gt;&lt;/ph&gt;" class="short">&lt;1/&gt;</span><span data-originalid="ph" data-length="-1" class="full">&lt;ph id=&quot;1&quot;&gt;&amp;lt;sym&amp;gt;&amp;lt;/sym&amp;gt;&lt;/ph&gt;</span></div>',
            '<i mana="ger">' => '<ins class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000001}" data-username="manager test" data-usercssnr="usernr1" data-workflowstep="reviewing1" data-timestamp="2018-11-20T10:38:16+01:00">',
            '<i>' => '<ins class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000002}" data-username="lector test" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2018-11-20T10:38:16+01:00">',
            '<d>' => '<del class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000002}" data-username="lector test" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2018-11-20T10:38:16+01:00">',
            '</i>' => '</ins>',
            '</d>' => '</del>',
        ];
        
        $segmentContent = [];
        $segmentContent[] = '<tag/>Ichbin<i>einTerm Ichbin</i>einTerm';
        $segmentContent[] = '<tag/><i>Ichbin keinTerm</i> <i>Ichbin keinTerm</i>x';
        $segmentContent[] = '<tag/><i mana="ger">Ichbin keinTerm</i> <d>x</d><i>Ichbin keinTerm</i>';
        $segmentContent[] = '<tag/><i mana="ger">Ichbi</i><i>n kein</i><i mana="ger">Term</i> <i>Ichbin keinTerm</i><d>x</d>';
        $segmentContent[] = '<tag/><i>Soll heißen Ich</i>bineinTerm';
        $segmentContent[] = '<tag/>Muss heißen <d>x</d><i>Ichbin keinTerm</i>';
        
        $segmentContent = str_replace(array_keys($replacements), $replacements, $segmentContent);
        
        //get segment list 
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=20');
        $this->assertNotEmpty($segments, 'No segments are found in the Task!');
        
        //the first three segments remain unedited, since content is getting to long with edited content
        $i = 0;
        foreach($segments as $idx => $segToEdit) {
            $segmentData = $this->api()->prepareSegmentPut('targetEdit', $segmentContent[$i++], $segToEdit->id);
            $this->api()->requestJson('editor/segment/'.$segToEdit->id, 'PUT', $segmentData);
        }
        
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=20');
        $data = array_map([self::$api,'removeUntestableSegmentContent'], $segments);
        //file_put_contents("/home/tlauria/www/translate5-master/application/modules/editor/testcases/editorAPI/Translate1475Test/expectedSegmentsEdited-new.json", json_encode($data,JSON_PRETTY_PRINT));
        
        $this->assertEquals(self::$api->getFileContent('expectedSegmentsEdited.json'), $data, 'Imported segments are not as expected!');
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