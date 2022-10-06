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
 * This test class will create test task and pretranslate it with ZDemoMT and OpenTm2 TM
 * Then the export result from the logg will be compared against the expected result.
 */
class Translate1484Test extends editor_Test_JsonTest {
    /* @var $this Translate1484Test */
    
    protected static $customerTest;
    protected static $sourceLangRfc = 'en';
    protected static $targetLangRfc = 'de';

    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $appState = self::assertAppState();
        self::assertContains('editor_Plugins_Okapi_Init', $appState->pluginsLoaded, 'Plugin Okapi must be activated for this test case!');
        self::assertContains('editor_Plugins_MatchAnalysis_Init', $appState->pluginsLoaded, 'Plugin MatchAnalysis must be activated for this test case!');
        self::assertContains('editor_Plugins_ZDemoMT_Init', $appState->pluginsLoaded, 'Plugin ZDemoMT must be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');

        static::setupTestTask();
    }
    
    private static function setupTestTask() {

        // add customer
        self::$customerTest = self::$api->postJson('editor/customer/',[
            'name'=>'API Testing::ResourcesLogCustomer',
            'number'=>uniqid('API Testing::ResourcesLogCustomer'),
        ]);

        // add Task
        $task = [
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerId' => self::$customerTest->id,
            'edit100PercentMatch' => false,
            'autoStartImport' => 0
        ];
        self::assertLogin('testmanager');

        // Create dummy mt
        $params = [
            'resourceId'=>'ZDemoMT',
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerIds' => [self::$customerTest->id],
            'customerUseAsDefaultIds' => [self::$customerTest->id],
            'customerWriteAsDefaultIds' => [],
            'serviceType' => 'editor_Plugins_ZDemoMT',
            'serviceName'=> 'ZDemoMT',
            'name' => 'API Testing::ZDemoMT_'.__CLASS__
        ];
        self::$api->addResource($params);

        // Create OpentTm2 resource and upload tm memory
        $params = [
            'resourceId' => 'editor_Services_OpenTM2_1',
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerIds' => [self::$customerTest->id],
            'customerUseAsDefaultIds' => [self::$customerTest->id],
            'customerWriteAsDefaultIds' => [],
            'serviceType' => 'editor_Services_OpenTM2',
            'serviceName'=> 'OpenTM2',
            'name' => 'API Testing::OpenTm2Tm_'.__CLASS__
        ];
        self::$api->addResource($params,'resource1.tmx',true);

        self::$api->addImportFile(self::$api->getFile('simple-en-de.xlf'));
        self::$api->import($task,false,false);

        // Queue the match anlysis worker
        $params = [
            'internalFuzzy' => 1,
            'pretranslateMatchrate' => 100,
            'pretranslateTmAndTerm' => 1,
            'pretranslateMt' => 1,
            'isTaskImport' => 0
        ];
        self::$api->putJson('editor/task/'.self::$api->getTask()->id.'/pretranslation/operation', $params, null, false);

        // start the import
        self::$api->getJson('editor/task/'.self::$api->getTask()->id.'/import');

        // waiit for the import to be finished
        self::$api->checkTaskStateLoop();
    }

    /***
     * Test the excel export.
     */
    public function testExportResourcesLog() {

        $jsonFileName = 'exportResults.json';
        $actualObject = self::$api->getJson('editor/customer/exportresource', [ 'customerId' => self::$customerTest->id ], $jsonFileName);
        $expectedObject = self::$api->getFileContent($jsonFileName);
        // we need to order the results to avoid tests failing due to runtime-differences
        $this->sortExportResource($actualObject);
        $this->sortExportResource($expectedObject);
        $this->assertEquals($expectedObject, $actualObject, "The expected file (exportResults) an the result file does not match.");
    }

    /**
     * @param stdClass $exportResource
     */
    private function sortExportResource(stdClass $exportResource){
        usort($exportResource->MonthlySummaryByResource, function($a, $b) {
            return $a->totalCharacters - $b->totalCharacters;
        });
        usort($exportResource->UsageLogByCustomer, function($a, $b) {
            return $a->charactersPerCustomer - $b->charactersPerCustomer;
        });
    }

    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        self::$api->deleteTask($task->id, 'testmanager');
        //remove the created resources
        self::$api->removeResources();
        //remove the temp customer
        self::$api->delete('editor/customer/'.self::$customerTest->id);
    }
}
