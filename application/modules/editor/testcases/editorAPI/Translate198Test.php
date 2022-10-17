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

/**
 * This will import 2 tasks, and it will test the functionality if the user is able to open 2 different task at the same time.
 */
class Translate198Test extends editor_Test_JsonTest {

    protected static bool $setupOwnCustomer = true;

    protected static function setupImport(Config $config): void
    {
        $customerId = static::$ownCustomer->id;
        $config->addTask('de', 'en', $customerId, 'task1-de-en.xlf');
        $config->addTask('de', 'en', $customerId, 'task2-de-en.xlf');
    }

    /**
     * imports two tasks
     */
    public function testTasks() {

        $task1 = static::getTaskAt(0)->getAsObject();
        $task2 = static::getTaskAt(1)->getAsObject();

        //open task for editing. This should not produce any error
        $result = static::api()->setTaskToEdit($task1->id);
        self::assertObjectNotHasAttribute('error', $result, (property_exists($result, 'error') ? $result->error : ''));
        static::api()->setTask($task1);

        $jsonFileName = 'segments-task1.json';
        $segments = static::api()->getSegments($jsonFileName);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments);

        self::assertCount(1, $segments);

        //open the secound task with the same user. This should not be posible
        $result = static::api()->setTaskToEdit($task2->id);
        self::assertObjectNotHasAttribute('error', $result, (property_exists($result, 'error') ? $result->error : ''));
        static::api()->setTask($task2);

        $jsonFileName = 'segments-task2.json';
        $segments = static::api()->getSegments($jsonFileName);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments);
    }
}
