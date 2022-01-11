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
 * Testcase for TRANSLATE-2756 - basically a match analysis and pretranslation test regarding repetitions and internal fuzzies
 * For details see the issue.
 */
class Translate2756Test extends editor_Test_JsonTest {
    /**
     * @throws Zend_Exception
     */
    public static function setUpBeforeClass(): void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);


        /// → Testfall für aktuellen Issue (target update) erstellen!
        /// Wiederholungen und match rate mit rein packen?

        $appState = self::assertAppState();

        self::assertNotContains('editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap', $appState->pluginsLoaded, 'Plugin LockSegmentsBasedOnConfig should not be activated for this test case!');
        self::assertNotContains('editor_Plugins_NoMissingTargetTerminology_Bootstrap', $appState->pluginsLoaded, 'Plugin NoMissingTargetTerminology should not be activated for this test case!');
        self::assertContains('editor_Plugins_ZDemoMT_Init', $appState->pluginsLoaded, 'Plugin ZDemoMT must be activated for this test case!');

        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        
        $zipfile = $api->zipTestFiles('testfiles/','testTask.zip');

        //create task
        $api->loadCustomer();
        $api->addImportFile($zipfile);
        $api->import([
            'sourceLang' => 'de',
            'targetLang' => 'en',
            'edit100PercentMatch' => true,
            'customerId' => $api->getCustomer()->id,
            'autoStartImport' => 0, //don't start the import directly
            'lockLocked' => 1,
        ], true, false);

        $task = $api->getTask();

        //create dummy TM
        $api->addDummyTm('DummyTmxData.tmx');

        //link task and TM
        $api->addTaskAssoc();

        //prepare analysis
        $params = [
            'internalFuzzy' => 1,
            'pretranslateMatchrate' => 100,
            'pretranslateTmAndTerm' => 1,
            'pretranslateMt' => 1,
            'isTaskImport' => 0,
        ];
        $api->requestJson('editor/task/'.self::$api->getTask()->id.'/pretranslation/operation', 'PUT', $params, $params);

        //start import and wait for it
        $api->requestJson('editor/task/'.self::$api->getTask()->id.'/import', 'GET');
        $api->checkTaskStateLoop();
        
        $api->addUser('testlector');
        
        //login in setUpBeforeClass means using this user in whole testcase!
        $api->login('testlector');
        
        //open task for whole testcase
        $api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
    }
    
    /**
     * Testing segment values directly after import
     */
    public function testSegmentValuesAfterImport() {
        $jsonFileName = 'expectedSegments.json';
        $segments = $this->api()->getJson('editor/segment?page=1&start=0&limit=10', [], $jsonFileName);
        $this->assertModelsEqualsJsonFile('Segment', $jsonFileName, $segments, 'Imported segments are not as expected!');
    }
    
    /**
     * @depends testSegmentValuesAfterImport
     */
    public function testSegmentEditing() {
        //get segment list
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=10');
        
        //prepare segment with changed TM data from GUI
        $segToTest = $segments[2];
        $tmId = $this->api()->getResources()[0]->id ?? 0;
        $result = [
            'id' => $segToTest->id,
            'target' => '=&gt; contact Translate5 service',
            'targetEdit' => 'contact Translate5 service',
            'matchRate' => 91,
            'matchRateType' => 'matchresourceusage;languageResourceid='.$tmId,
            'autoStateId' => 999,
            'durations' => [],
        ];

        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $result['targetEdit'], $result);
        $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);

        //change also the repetitions
        $this->api()->requestJson('editor/alikesegment/'.$segToTest->id, 'PUT', [], [
            'duration' => 666,
            'alikes' => json_encode([$segments[3]->id]),
        ]);

        //check direct PUT result
        $jsonFileName = 'expectedSegments-edited.json';
        $segments = $this->api()->getJson('editor/segment?page=1&start=0&limit=10', [], $jsonFileName);
        $this->assertModelsEqualsJsonFile('Segment', $jsonFileName, $segments, 'Imported segments are not as expected!');
    }

    /**
     * @depends testSegmentEditing
     */
    public function testAnalysisResult() {
        $this->api()->login('testmanager');
        $jsonFileName = 'expectedAnalysis.json';
        $analysis = $this->api()->getJson('editor/plugins_matchanalysis_matchanalysis', [
            'taskGuid' => $this->api()->getTask()->taskGuid,
            'notGrouped' => 1
        ], $jsonFileName);
        $this->assertModelsEqualsJsonFile('Analysis', $jsonFileName, $analysis, 'Analysis is not as expected!');
    }

    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testlector');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        if(self::$api->cleanup) {
            self::$api->login('testmanager');
            self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
            self::$api->removeResources();
        }
    }
}
