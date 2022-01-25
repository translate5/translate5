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
 */
class Translate2080Test extends editor_Test_JsonTest {

    public static function setUpBeforeClass(): void {

        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        

        self::assertNeededUsers(); //last authed user is testmanager
        self::assertCustomer();//assert the test customer
        self::assertLogin('testmanager');
    }

    /***
     */
    public function testImportProject(){
        $this->createTask();
    }

    /***
     * Create and import the task
     */
    protected function createTask(){
        $task =[
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => 'de',
            'targetLang' => ['en','mk'],
            'customerId'=>self::api()->getCustomer()->id,
            'edit100PercentMatch' => true,
            'importUpload_language' => ['en','mk'],
            'importUpload_type' => ['workfile','workfile'],
            'autoStartImport'=>1
        ];
        self::assertLogin('testmanager');
        self::$api->addImportFile(self::$api->getFile('en.xlf'));
        self::$api->addImportFile(self::$api->getFile('mk.xlf'));
        self::$api->import($task,false);
        error_log('Task created. '.$this->api()->getTask()->taskName);
    }

    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}
