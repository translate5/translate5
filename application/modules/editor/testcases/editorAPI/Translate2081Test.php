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
 * Create tmp customer and define auto assigned users
 * Create task with the tmp customer and matching workflow/source/target and validate if the user is auto assigned
 */
class Translate2081Test extends editor_Test_JsonTest {

    protected static $customerTest;
    protected static $sourceLangRfc='de';
    protected static $targetLangRfc='en';

    /**
     */
    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);

        $appState = self::assertAppState();
        self::assertContains('editor_Plugins_Okapi_Init', $appState->pluginsLoaded, 'Plugin Okapi must be activated for this test case!');

        self::assertNeededUsers(); //last authed user is testmanager
        self::assertCustomer();//assert the test customer

        self::$customerTest = self::$api->postJson('editor/customer/',[
            'name'=>'API Testing::ResourcesLogCustomer',
            'number'=>uniqid('API Testing::ResourcesLogCustomer', true),
        ]);
    }

    /***
     * Add default user assoc and validate the results
     */
    public function testDefaultUserAssoc(){

        $params = [];
        $params['customerId']  = self::$customerTest->id;
        $params['workflow'] = 'default';
        $params['sourceLang'] = self::$sourceLangRfc;
        $params['targetLang'] = self::$targetLangRfc;
        $params['userGuid'] = '{00000000-0000-0000-C100-CCDDEE000003}'; // testlector
        $params['workflowStepName'] = 'translation';

        $result = self::$api->postJson('editor/userassocdefault', $params);
        unset($result->id);
        unset($result->customerId);

        //file_put_contents(self::$api->getFile('assocResult.txt', null, false), json_encode($result, JSON_PRETTY_PRINT));
        $expected=self::$api->getFileContent('assocResult.txt');
        $actual=json_encode($result, JSON_PRETTY_PRINT);
        //check for differences between the expected and the actual content
        self::assertEquals($expected, $actual, "The expected file(assocResult) an the result file does not match.");
    }

    /***
     * Create the task and validate if the auto assign is done
     *
     * @depends testDefaultUserAssoc
     */
    public function testTaskAutoAssign(){

        // create the task and wait for the import
        $task =[
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerId'=>self::$customerTest->id,
            'edit100PercentMatch' => true,
            'autoStartImport'=>1
        ];
        self::assertLogin('testmanager');
        self::$api->addImportFile(self::$api->getFile('TRANSLATE-2545-de-en.xlf'));
        self::$api->import($task,false);
        error_log('Task created. '.$this->api()->getTask()->taskName);


        // after the task is created/imported, check if the users are auto assigned.
        $data = $this->api()->getJson('editor/taskuserassoc',[
            'filter' => '[{"operator":"eq","value":"' . self::$api->getTask()->taskGuid . '","property":"taskGuid"}]'
        ]);

        //filter out the non static data
        $data = array_map(function($assoc){
            unset($assoc->id);
            unset($assoc->taskGuid);
            unset($assoc->usedInternalSessionUniqId);
            unset($assoc->staticAuthHash);
            unset($assoc->editable);
            unset($assoc->deletable);
            unset($assoc->assignmentDate);
            return $assoc;
        }, $data);

        //file_put_contents($this->api()->getFile('expected.json', null, false), json_encode($data,JSON_PRETTY_PRINT));
        $this->assertEquals(self::$api->getFileContent('expected.json'), $data, 'The expected users are not auto assigned to the task');
    }

    /***
     * Cleand up the resources and the task
     */
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        self::$api->deleteTask($task->id, 'testmanager');
        //remove the temp customer
        self::$api->delete('editor/customer/'.self::$customerTest->id);
    }
}
