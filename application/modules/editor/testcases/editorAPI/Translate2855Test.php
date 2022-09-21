<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/***
 * This is a test for the pivot pre-translation feature.
 *
 * The test works in the following way:
 *   - create temporray customer
 *   - create temporray MT resource using ZDemoMT plugin
 *   - assign the temporrary customer to be used as pivot default for ZDemoMT resource
 *   - create new task (with only 3 segments) asn assign the MT ZDemoMT resource
 *   - check if for all 3 task segments the pivot is pre-translated
 */
class Translate2855Test extends editor_Test_JsonTest {

    protected static $customerTest;
    protected static $sourceLangRfc = 'en';
    protected static $targetLangRfc = 'de';

    /**
     * This method is called before the first test of this test class is run.
     * @throws Exception
     */
    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);

        $appState = self::assertAppState();
        self::assertContains('editor_Plugins_Okapi_Init', $appState->pluginsLoaded, 'Plugin Okapi must be activated for this test case!');
        self::assertContains('editor_Plugins_MatchAnalysis_Init', $appState->pluginsLoaded, 'Plugin MatchAnalysis must be activated for this test case!');
        self::assertContains('editor_Plugins_ZDemoMT_Init', $appState->pluginsLoaded, 'Plugin ZDemoMT must be activated for this test case!');

        self::assertNeededUsers(); //last authed user is testmanager

        self::createResource();
        self::createTask();
        self::queuePretranslation();
        self::startImport();
        self::checkTaskState();
    }

    /**
     * @throws Exception
     */
    private static function createResource(){
        self::$customerTest = self::$api->postJson('editor/customer/',[
            'name' => 'API Testing::Pivot pre-translation',
            'number' => uniqid('API Testing::Pivot pre-translation', true),
        ]);

        $params=[
            'resourceId'=>'ZDemoMT',
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerIds' => [self::$customerTest->id],
            'customerUseAsDefaultIds' => [],
            'customerWriteAsDefaultIds' => [],
            'customerPivotAsDefaultIds' => [self::$customerTest->id],
            'serviceType' => 'editor_Plugins_ZDemoMT',
            'serviceName'=> 'ZDemoMT',
            'name' => 'API Testing::Pivot pre-translation_'.__CLASS__
        ];

        self::$api->addResource($params);
    }

    /**
     * @return void
     */
    private static function createTask(): void
    {
        $task = [
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'relaisLang' => self::$targetLangRfc,
            'customerId'=>self::$customerTest->id,
            'edit100PercentMatch' => true,
            'autoStartImport'=>0
        ];

        self::assertLogin('testmanager');

        self::$api->addImportFile(self::$api->getFile('Task-de-en.html'));
        self::$api->import($task,false,false);
    }

    /***
     * Queue pivot pre-translation worker
     */
    private static function queuePretranslation(){
        self::$api->putJson('editor/languageresourcetaskpivotassoc/pretranslation/batch', [ 'taskGuid' => self::$api->getTask()->taskGuid ], null, false);
    }

    /***
     * Start the import process
     */
    private static function startImport(){
        self::$api->getJson('editor/task/'.self::$api->getTask()->id.'/import');
    }

    /***
     * Check the task state
     */
    private static function checkTaskState(){
        self::$api->checkTaskStateLoop();
    }

    /***
     * Test if the task relais segments are pre-translated using ZDemoMT
     * @return void
     */
    public function testSegmentContent(){
        //open task for whole testcase
        self::$api->putJson('editor/task/'.self::$api->getTask()->id, ['userState' => 'edit', 'id' => self::$api->getTask()->id]);
        $segments = $this->api()->getSegments();

        self::assertEquals(3,count($segments), 'The number of segments does not match.');

        foreach ($segments as $segment){
            self::assertNotEmpty($segment->relais);
        }
    }

    /**
     * This method is called after the last test of this test class is run.
     */
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        self::$api->login('testmanager');

        self::$api->removeResources();

        self::$api->cleanup && self::$api->putJson('editor/task/'.$task->id, ['userState' => 'open', 'id' => $task->id]);
        self::$api->cleanup && self::$api->delete('editor/task/'.$task->id);

        //remove the temp customer
        self::$api->delete('editor/customer/'.self::$customerTest->id);
    }
}
