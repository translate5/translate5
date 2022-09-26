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
 * Testcase for TRANSLATE-2315 repetition filtering
 * For details see the issue.
 */
class Translate2315Test extends editor_Test_JsonTest {
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
        
        $zipfile = $api->zipTestFiles('testfiles/','testTask.zip');
        
        $api->addImportFile($zipfile);
        $api->import($task);
        
        $api->addUser('testlector');
        
        //login in setUpBeforeClass means using this user in whole testcase!
        $api->login('testlector');
        
        $task = $api->getTask();
        //open task for whole testcase
        $api->setTaskToEdit($task->id);
    }
    
    /**
     * Testing segment values directly after import
     */
    public function testSegmentValuesAfterImport() {
        $jsonFileName = 'expectedSegments.json';
        $segments = $this->api()->getSegments($jsonFileName, 10);
        $this->checkRepetition($segments[0]->id, [$segments[1]->id]);
        $this->checkRepetition($segments[2]->id, []);
        $this->checkRepetition($segments[4]->id, []);
        $this->checkRepetition($segments[6]->id, [$segments[7]->id]);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');
    }
    
    protected function checkRepetition(int $idToGetFor, array $idsToBeFound) {
        $alikes = $this->api()->getJson('editor/alikesegment/'.$idToGetFor);
        $idsFound = array_column($alikes, 'id');
        sort($idsFound);
        sort($idsToBeFound);
        $this->assertEquals($idsFound, $idsToBeFound, 'The found alike segment IDs are not as expected!');
    }
    
    
    /**
     * @depends testSegmentValuesAfterImport
     */
    public function testSegmentEditing() {
        //get segment list
        $segments = $this->api()->getSegments(null, 10);
        
        //edit the segment and make some target repetitions
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', "target rep 1", $segments[4]->id);
        $this->api()->putJson('editor/segment/'.$segments[4]->id, $segmentData);
        
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', "target rep 1", $segments[5]->id);
        $this->api()->putJson('editor/segment/'.$segments[5]->id, $segmentData);
        
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', "target rep 2", $segments[6]->id);
        $this->api()->putJson('editor/segment/'.$segments[6]->id, $segmentData);
        
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', "target rep 2", $segments[7]->id);
        $this->api()->putJson('editor/segment/'.$segments[7]->id, $segmentData);
        
        $this->checkRepetition($segments[0]->id, [$segments[1]->id]); //source rep
        $this->checkRepetition($segments[2]->id, []); //still no repetition
        $this->checkRepetition($segments[4]->id, [$segments[5]->id]); // target rep
        $this->checkRepetition($segments[6]->id, [$segments[7]->id]); // both is a rep
        
        //check direct PUT result
        $jsonFileName = 'expectedSegments-edited.json';
        $segments = $this->api()->getSegments($jsonFileName, 10);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Edited segments are not as expected!');
    }
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        self::$api->deleteTask($task->id, 'testmanager', 'testlector');
    }
}
