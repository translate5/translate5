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

    protected static array $forbiddenPlugins = [
        'editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap',
        'editor_Plugins_NoMissingTargetTerminology_Bootstrap'
    ];

    protected static array $requiredPlugins = [
        'editor_Plugins_ZDemoMT_Init'
    ];

    /**
     * @throws Zend_Exception
     */
    public static function beforeTests(): void {

        /// → Testfall für aktuellen Issue (target update) erstellen!
        /// Wiederholungen und match rate mit rein packen?

        self::assertAppState();

        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        
        $zipfile = static::api()->zipTestFiles('testfiles/','testTask.zip');

        //create task
        static::api()->loadCustomer();
        static::api()->addImportFile($zipfile);
        static::api()->import([
            'sourceLang' => 'de',
            'targetLang' => 'en',
            'edit100PercentMatch' => true,
            'customerId' => static::api()->getCustomer()->id,
            'autoStartImport' => 0, //don't start the import directly
            'lockLocked' => 1,
        ], true, false);

        $task = static::api()->getTask();

        //create dummy TM
        static::api()->addDummyTm('DummyTmxData.tmx');
        sleep(2);

        //link task and TM
        static::api()->addTaskAssoc();

        //prepare analysis
        $params = [
            'internalFuzzy' => 1,
            'pretranslateMatchrate' => 100,
            'pretranslateTmAndTerm' => 1,
            'pretranslateMt' => 1,
            'isTaskImport' => 0,
        ];
        static::api()->putJson('editor/task/'.static::api()->getTask()->id.'/pretranslation/operation', $params, null, false);

        //start import and wait for it
        static::api()->getJson('editor/task/'.static::api()->getTask()->id.'/import');
        static::api()->checkTaskStateLoop();
        
        static::api()->addUser('testlector');
        
        //login in beforeTests means using this user in whole testcase!
        static::api()->login('testlector');
        
        //open task for whole testcase
        static::api()->setTaskToEdit($task->id);
    }
    
    /**
     * Testing segment values directly after import
     */
    public function test10_SegmentValuesAfterImport() {
        $jsonFileName = 'expectedSegments.json';
        $segments = static::api()->getSegments($jsonFileName, 10);
        $this->assertModelsEqualsJsonFile('Segment', $jsonFileName, $segments, 'Imported segments are not as expected!');
    }
    
    /**
     * @depends test10_SegmentValuesAfterImport
     */
    public function test20_SegmentEditing() {
        //get segment list
        $segments = static::api()->getSegments(null, 10);
        
        //prepare segment with changed TM data from GUI
        $segToTest = $segments[2];
        $tmId = static::api()->getResources()[0]->id ?? 0;
        $additionalPutData = [
            'target' => '=&gt; contact Translate5 service',
            'matchRate' => 91,
            'matchRateType' => 'matchresourceusage;languageResourceid='.$tmId
        ];
        static::api()->saveSegment($segToTest->id, 'contact Translate5 service', null, null, $additionalPutData);

        //change also the repetitions
        static::api()->putJson('editor/alikesegment/'.$segToTest->id, [  'duration' => 666, 'alikes' => json_encode([$segments[3]->id]) ], null, false);

        //check direct PUT result
        $jsonFileName = 'expectedSegments-edited.json';
        $segments = static::api()->getSegments($jsonFileName, 10);
        $this->assertModelsEqualsJsonFile('Segment', $jsonFileName, $segments, 'Imported segments are not as expected!');
    }

    /**
     * @depends test20_SegmentEditing
     */
    public function test30_AnalysisResult() {
        static::api()->login('testmanager');
        $jsonFileName = 'expectedAnalysis.json';
        $analysis = static::api()->getJson('editor/plugins_matchanalysis_matchanalysis', [
            'taskGuid' => static::api()->getTask()->taskGuid,
            'notGrouped' => 1
        ], $jsonFileName);
        $this->assertModelsEqualsJsonFile('Analysis', $jsonFileName, $analysis, 'Analysis is not as expected!');
    }

    public static function afterTests(): void {
        $task = static::api()->getTask();
        static::api()->deleteTask($task->id, 'testmanager', 'testlector');
        static::api()->removeResources();
    }
}
