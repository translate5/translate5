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

/***
 * This test will test the customerWriteAsDefaultIds flag in the languageresources-customer assoc.
 * The used tm memory is OpenTm2.
 */
class Translate2417Test extends editor_Test_JsonTest {

    protected static $sourceLangRfc='de';
    protected static $targetLangRfc='en';

    protected static $prefix='MATEST';

    /***
     *
     */
    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);

        $appState = self::assertAppState();
        self::assertContains('editor_Plugins_Okapi_Init', $appState->pluginsLoaded, 'Plugin Okapi must be activated for this test case!');

        self::assertNeededUsers(); //last authed user is testmanager
        self::assertCustomer();//assert the test customer
    }

    /***
     * Import all required resources and task before the validation
     */
    public function test10_SetupData(){
        $this->addTm('resource1.tmx',self::$prefix.'resource1');
        $this->createTask();
    }

    /***
     * Test if all the segments are as expected after import.
     * @depends test10_SetupData
     */
    public function test20_SegmentValuesAfterImport() {

        $this->api()->addUser('testmanager');

        $task = $this->api()->getTask();
        $this->api()->putJson('editor/task/'.$task->id, ['userState' => 'edit', 'id' => $task->id]);

        $segments = $this->api()->getJson('editor/segment');
        $this->assertSegmentsEqualsJsonFile('expectedSegments.json', $segments, 'Imported segments are not as expected!');
    }

    /***
     * Test the tm->query results before and after segment editing. After the segment is edited, and because of the writable as default flag,
     * the translated targetEdit should be offered as result from the tm when we query for the segment
     * @depends test20_SegmentValuesAfterImport
     */
    public function test30_TmResultQuery() {

        self::assertLogin('testmanager');

        $tm = $this->api()->getResources()[0];

        // load the first segment
        $segments = $this->api()->getSegments(null, 1);
        // test the first segment
        $segToTest = $segments[0];

        // query the results from this segment and compare them against the expected initial json
        $tmResults = $this->api()->getJson('editor/languageresourceinstance/'.$tm->id.'/query',['segmentId' => $segToTest->id]);

        $this->assertIsArray($tmResults, 'GET editor/languageresourceinstance/'.$tm->id.'/query does not return an array but: '.print_r($tmResults,1).' and raw result is '.print_r($this->api()->getLastResponse(),1));

        $this->assertTmResultEqualsJsonFile('tmResultsBeforeEdit.json', $tmResults, 'The received tm results before segment modification are not as expected!');

        // set dummy translation for the first segment and save it. This should upload this translation to the tm to.
        $segToTest->targetEdit = "Aleks test tm update.";

        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $segToTest->targetEdit, $segToTest->id);
        $this->api()->putJson('editor/segment/'.$segToTest->id, $segmentData);

        // after the segment save, check for the tm results for the same segment
        $tmResults = $this->api()->getJson('editor/languageresourceinstance/'.$tm->id.'/query',['segmentId' => $segToTest->id]);

        $this->assertTmResultEqualsJsonFile('tmResultsAfterEdit.json', $tmResults, 'The received tm results after segment modification are not as expected!');
    }

    /***
     * Add the translation memory resource. OpenTM2 in our case
     * @param string $fileName
     * @param string $name
     */
    protected function addTm(string $fileName,string $name){
        $customerId = $this->api()->getCustomer()->id;
        $params=[
            'resourceId'=>'editor_Services_OpenTM2_1',
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerIds' => [$customerId],
            'customerUseAsDefaultIds' => [$customerId],
            'customerWriteAsDefaultIds' => [$customerId],
            'serviceType' => 'editor_Services_OpenTM2',
            'serviceName'=> 'OpenTM2',
            'name' => $name
        ];
        //create the resource 1 and import the file
        self::$api->addResource($params,$fileName,true);
    }
    /***
     * Create the task. The task will not be imported directly autoStartImport is 0!
     */
    protected function createTask(){
        $task =[
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerId'=>self::$api->getCustomer()->id,
            'autoStartImport'=>1
        ];
        self::assertLogin('testmanager');

        $zipfile = self::$api->zipTestFiles('testfiles/','test.zip');
        self::$api->addImportFile($zipfile);
        self::$api->import($task,false);
        error_log('Task created. '.$this->api()->getTask()->taskName);
    }

    /***
     * Start the import process
     */
    protected function startImport(){
        $this->api()->getJson('editor/task/'.$this->api()->getTask()->id.'/import');
        error_log('Import workers started.');
    }

    /***
     * Check the task state
     */
    protected function checkTaskState(){
        self::$api->checkTaskStateLoop();
        sleep(3); //lets wait another 3 seconds, since this test was always failing with 403 due a not found tasl lang res assoc
    }

    /***
     * Cleand up the resources and the task
     */
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');

        self::$api->putJson('editor/task/'.$task->id, ['userState' => 'open', 'id' => $task->id]);

        self::$api->cleanup && self::$api->delete('editor/task/'.$task->id);
        //remove the created resources
        self::$api->cleanup && self::$api->removeResources();
    }
}
