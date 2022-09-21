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

/**
 * Testcase for TRANSLATE-2874 Mixing XLF id and rid values led to wrong tag numbering
 * For details see the issue.
 */
class Translate2874Test extends editor_Test_JsonTest {
    public static function setUpBeforeClass(): void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);

        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');

        $api->loadCustomer();
        $task = [
            'sourceLang' => 'de',
            'targetLang' => 'en',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
            'customerId' => $api->getCustomer()->id,
            'autoStartImport' => 0,
        ];

        self::$api->addResource([
            'resourceId'=>'ZDemoMT',
            'sourceLang' => $task['sourceLang'],
            'targetLang' => $task['targetLang'],
            'customerIds' => [$task['customerId']],
            'customerUseAsDefaultIds' => [[$task['customerId']]],
            'customerWriteAsDefaultIds' => [],
            'serviceType' => 'editor_Plugins_ZDemoMT',
            'serviceName'=> 'ZDemoMT',
            'name' => 'API Testing::ZDemoMT_'.__CLASS__
        ]);

        $api->addImportFile($api->zipTestFiles('testfiles/','testTask.zip'));
        $api->import($task, false, false);
        $api->reloadTask();

        //add assocs
        $api->addUser('testlector');
        self::$api->addTaskAssoc();

        $params = [
            'internalFuzzy' => 0,
            'pretranslateMatchrate' => 100,
            'pretranslateTmAndTerm' => 0,
            'pretranslateMt' => 1,
            'isTaskImport' => 0,
        ];
        self::$api->putJson('editor/task/'.self::$api->getTask()->id.'/pretranslation/operation', $params, null, false);

        self::$api->getJson('editor/task/'.self::$api->getTask()->id.'/import');

        self::$api->checkTaskStateLoop();

        //login in setUpBeforeClass means using this user in whole testcase!
        $api->login('testlector');
        
        $task = $api->getTask();
        //open task for whole testcase
        $api->putJson('editor/task/'.$task->id, array('userState' => 'edit', 'id' => $task->id));
    }
    
    /**
     */
    public function testPreTranslatedContent() {
        //test segment list
        $jsonFileName = 'expectedSegments-edited.json';
        $segments = $this->api()->getSegments($jsonFileName, 10);
        $this->assertModelsEqualsJsonFile('Segment', $jsonFileName, $segments, 'Imported segments are not as expected!');
    }
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testlector');
        self::$api->cleanup && self::$api->putJson('editor/task/'.$task->id, array('userState' => 'open', 'id' => $task->id));
        self::$api->login('testmanager');
        self::$api->cleanup && self::$api->delete('editor/task/'.$task->id);
    }
}
