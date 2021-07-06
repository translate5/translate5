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
    public function testSetupData(){
        $this->addTm('resource1.tmx',self::$prefix.'resource1');
        $this->createTask();
        $this->startImport();
        $this->checkTaskState();
        $task = $this->api()->getTask();
        $this->api()->requestJson('editor/task/'.$task->id, 'PUT', ['userState' => 'edit', 'id' => $task->id]);

    }

    /***
     * Test if all the segments are as expected after import.
     */
    public function testSegmentValuesAfterImport() {
        $segments = $this->api()->requestJson('editor/segment');
        //file_put_contents($this->api()->getFile('/expectedSegments.json', null, false), json_encode($segments,JSON_PRETTY_PRINT));
        $this->assertSegmentsEqualsJsonFile('expectedSegments.json', $segments, 'Imported segments are not as expected!');
    }

    /***
     * Test the tm->query results before and after segment editing. After the segment is edited, and because of the writable as default flag,
     * the translated targetEdit should be offered as result from the tm when we query for the segment
     */
    public function testTmResultQuery() {
        $tm = $this->api()->getResources()[0];

        // load the first segment
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=1');
        // test the first segment
        $segToTest = $segments[0];

        // query the results from this segment and compare them against the expected initial json
        $tmResults = $this->api()->requestJson('editor/languageresourceinstance/'.$tm->id.'/query','GET',['segmentId' => $segToTest->id]);

        $this->assertIsArray($tmResults, 'GET editor/languageresourceinstance/'.$tm->id.'/query does not return an array but: '.print_r($tmResults,1).' and raw result is '.print_r($this->api()->getLastResponse(),1));

        //file_put_contents($this->api()->getFile('/tmResultsBeforeEdit.json', null, false), json_encode($tmResults,JSON_PRETTY_PRINT));
        $this->assertTmResultEqualsJsonFile('tmResultsBeforeEdit.json', $tmResults, 'The received tm results before segment modification are not as expected!');

        // set dummy translation for the first segment and save it. This should upload this translation to the tm to.
        $segToTest->targetEdit = "Aleks test tm update.";

        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $segToTest->targetEdit, $segToTest->id);
        $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);

        // after the segment save, check for the tm results for the same segment
        $tmResults = $this->api()->requestJson('editor/languageresourceinstance/'.$tm->id.'/query','GET',['segmentId' => $segToTest->id]);
        //file_put_contents($this->api()->getFile('/tmResultsAfterEdit.json', null, false), json_encode($tmResults, JSON_PRETTY_PRINT));
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
            'autoStartImport'=>0
        ];
        self::assertLogin('testmanager');
        self::$api->addImportFile(self::$api->getFile('test-analyse.html'));
        self::$api->import($task,false,false);
        error_log('Task created. '.$this->api()->getTask()->taskName);
    }

    /***
     * Start the import process
     */
    protected function startImport(){
        $this->api()->requestJson('editor/task/'.$this->api()->getTask()->id.'/import', 'GET');
        error_log('Import workers started.');
    }

    /***
     * Check the task state
     */
    protected function checkTaskState(){
        self::$api->checkTaskStateLoop();
    }

    /***
     * Cleand up the resources and the task
     */
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');

        self::$api->requestJson('editor/task/'.$task->id, 'PUT', ['userState' => 'open', 'id' => $task->id]);

        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
        //remove the created resources
        self::$api->removeResources();
    }
}
