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

    protected static $sourceLangRfc = 'de';
    protected static $targetLangRfc = 'en';
    protected static $prefix = 'T2417';

    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);

        $appState = self::assertAppState();
        self::assertContains('editor_Plugins_Okapi_Init', $appState->pluginsLoaded, 'Plugin Okapi must be activated for this test case!');

        self::assertNeededUsers(); //last authed user is testmanager
        self::assertCustomer();//assert the test customer
        self::assertLogin('testmanager');

        // add the TM
        $customerId = self::$api->getCustomer()->id;
        $params=[
            'resourceId' => 'editor_Services_OpenTM2_1',
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerIds' => [$customerId],
            'customerUseAsDefaultIds' => [$customerId],
            'customerWriteAsDefaultIds' => [$customerId],
            'serviceType' => 'editor_Services_OpenTM2',
            'serviceName'=> 'OpenTM2',
            'name' => self::$prefix.'resource1'
        ];
        //create the resource 1 and import the file
        self::$api->addResource($params, 'resource1.tmx',true);

        // import the task
        $task =[
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerId'=>self::$api->getCustomer()->id,
            'autoStartImport'=>1
        ];
        $zipfile = self::$api->zipTestFiles('testfiles/','test.zip');
        self::$api->addImportFile($zipfile);
        self::$api->import($task,false);
    }

    /**
     * Test if all the segments are as expected after import.
     */
    public function testSegments() {

        $task = self::$api->getTask();
        self::$api->addUser('testmanager');
        self::$api->setTaskToEdit($task->id);
        $jsonFileName = 'expectedSegments.json';
        $segments = self::$api->getSegments($jsonFileName);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');

        // now test editing the segments

        self::assertLogin('testmanager');

        $tm = self::$api->getResources()[0];

        // load the first segment
        $segments = self::$api->getSegments(null, 1);
        // test the first segment
        $segToTest = $segments[0];

        // query the results from this segment and compare them against the expected initial json
        $jsonFileName = 'tmResultsBeforeEdit.json';
        $tmResults = self::$api->getJson('editor/languageresourceinstance/'.$tm->id.'/query', ['segmentId' => $segToTest->id], $jsonFileName);
        $this->assertIsArray($tmResults, 'GET editor/languageresourceinstance/'.$tm->id.'/query does not return an array but: '.print_r($tmResults,1).' and raw result is '.print_r(self::$api->getLastResponse(),1));
        $this->assertTmResultEqualsJsonFile($jsonFileName, $tmResults, 'The received tm results before segment modification are not as expected!');

        // set dummy translation for the first segment and save it. This should upload this translation to the tm to.
        $segToTest->targetEdit = "Aleks test tm update.";
        $segmentData = self::$api->prepareSegmentPut('targetEdit', $segToTest->targetEdit, $segToTest->id);
        self::$api->putJson('editor/segment/'.$segToTest->id, $segmentData);

        // after the segment save, check for the tm results for the same segment
        $jsonFileName = 'tmResultsAfterEdit.json';
        $tmResults = self::$api->getJson('editor/languageresourceinstance/'.$tm->id.'/query', ['segmentId' => $segToTest->id], $jsonFileName);
        $this->assertTmResultEqualsJsonFile($jsonFileName, $tmResults, 'The received tm results after segment modification are not as expected!');
    }

    /***
     * Cleand up the resources and the task
     */
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        self::$api->deleteTask($task->id, 'testmanager');
        //remove the created resources
        self::$api->removeResources();
    }
}
