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
 * This will create a task, resource and customer and assign the resource by default as pivot resource.
 * Based on runtimeOptions.import.autoStartPivotTranslations , the pivot pre-translation will be auto queue
 */
class Translate3077Test extends editor_Test_JsonTest
{
    protected static stdClass $customerTest;

    public static function setUpBeforeClass(): void
    {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);

        $appState = self::assertAppState();
        if(!in_array('editor_Plugins_DeepL_Init', $appState->pluginsLoaded)) {
            self::markTestSkipped('DeepL-Plugin must be activated for this test case, which is not the case!');
            return;
        }

        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');

        self::$customerTest = self::$api->postJson('editor/customer/',[
            'name'=>'API Testing::Pivot pre-translation auto queue',
            'number'=>uniqid('API Testing::ResourcesLogCustomer', true),
        ]);

        $task = [
            'sourceLang' => 'de',
            'targetLang' => 'en',
            'relaisLang' => 'it',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
            'customerId' => self::$customerTest->id,
            'autoStartImport' => 1
        ];

        self::$api->addResource([
            'name' => __CLASS__ .'::Pivot pre-translation auto queue',
            'sourceLang' => $task['sourceLang'],
            'targetLang' => 'it',
            'customerIds' => [$task['customerId']],
            'resourceId'=>'editor_Plugins_DeepL_1',
            'customerUseAsDefaultIds' => [],
            'customerWriteAsDefaultIds' => [],
            'customerPivotAsDefaultIds' => [$task['customerId']],
            'serviceType' => 'editor_Plugins_DeepL',
            'serviceName'=> 'DeepL'
        ]);

        $api->addImportFile($api->zipTestFiles('testfiles/','testTask.zip'));
        $api->import($task, false, true);

        $api->addUser('testlector');

        //login in setUpBeforeClass means using this user in whole testcase!
        $api->login('testlector');

        $task = $api->getTask();
        //open task for whole testcase
        $api->setTaskToEdit($task->id);
    }

    /***
     * Check if the segment pivot is pretranslated
     */
    public function testPivotAutoPretranslation() {
        //get segment list
        $segments = $this->api()->getSegments();

        $this->assertCount(1, $segments);

        foreach($segments as $segment) {
            static::assertNotEmpty($segment->relais,'The pivot field for the segment is empty');
        }

    }

    public static function tearDownAfterClass(): void
    {
        $task = self::$api->getTask();
        self::$api->deleteTask($task->id,'testmanager');
        self::$api->removeResources();
        //remove the temp customer
        self::$api->delete('editor/customer/'.self::$customerTest->id);
    }
}
