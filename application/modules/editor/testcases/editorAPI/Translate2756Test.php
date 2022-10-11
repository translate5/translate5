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

use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\Import\LanguageResource;

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

    protected static string $setupUserLogin = 'testlector';

    private static LanguageResource $dummyTm;

    protected static function setupImport(Config $config): void
    {
        $sourceLangRfc = 'de';
        $targetLangRfc = 'en';
        $customerId = static::getTestCustomerId();
        static::$dummyTm = $config
            ->addLanguageResource('dummytm', 'DummyTmxData.tmx', $customerId, $sourceLangRfc, $targetLangRfc)
            ->setProperty('name', 'Translate2756Test');  // TODO FIXME: we better generate data independent from resource-names ...
        $config
            ->addPretranslation()
            ->setProperty('pretranslateMt', 1);
        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId)
            ->addUploadFolder('testfiles', 'testTask.zip')
            ->setToEditAfterImport();
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
        $tmId = static::$dummyTm->getId() ?? 0;
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
}
