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
 * Abstraction layer for performing API tests which need to work with a mocked task
 * These are usually "classic" PHPunit tests and no API-Tests
 */
abstract class editor_Test_MockedTaskTest extends \ZfExtended_Test_ApiTestcase {

    /**
     *
     * @var editor_Models_Task
     */
    protected static $testTask = NULL;
    /**
     * Retrieves a test-tak to init field-tags with
     * @return editor_Models_Task
     */
    protected function getTestTask() : editor_Models_Task {
        if(static::$testTask == NULL){
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->setId(1234);
            $task->setEntityVersion(280);
            $task->setTaskGuid('{c56eadf5-ca66-43ae-931f-a09ff22643ab}');
            $task->setTaskName('UNIT_TEST_TASK');
            $task->setForeignName('');
            $task->setSourceLang(5);
            $task->setTargetLang(4);
            $task->setRelaisLang(0);
            $task->setState('open');
            $task->setQmSubsegmentFlags('{"qmSubsegmentFlags":[{"text":"Accuracy","id":1,"children":[{"text":"Terminology","id":2},{"text":"Mistranslation","id":3},{"text":"Omission","id":4},{"text":"Untranslated","id":5},{"text":"Addition","id":6}]},{"text":"Fluency","id":7,"children":[{"text":"Content","id":8,"children":[{"text":"Register","id":9},{"text":"Style","id":10},{"text":"Inconsistency","id":11}]},{"text":"Mechanical","id":12,"children":[{"text":"Spelling","id":13},{"text":"Typography","id":14},{"text":"Grammar","id":15},{"text":"Locale violation","id":16}]},{"text":"Unintelligible","id":17}]},{"text":"Verity","id":18,"children":[{"text":"Completeness","id":19},{"text":"Legal requirements","id":20},{"text":"Locale applicability","id":21}]}],"severities":{"critical":"Critical","major":"Major","minor":"Minor"}}');
            $task->setTaskType('default');
            $task->setProjectId(1233);
            static::$testTask = $task;
        }
        return static::$testTask;
    }
}