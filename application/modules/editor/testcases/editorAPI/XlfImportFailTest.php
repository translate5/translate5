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
 * Checks if mrk segmentation errors and missing tag ids surround sub tags are stopping the import
 */
class XlfImportFailTest extends \editor_Test_ApiTest {

    protected static array $forbiddenPlugins = [
        'editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap',
        'editor_Plugins_NoMissingTargetTerminology_Bootstrap'
    ];

    public static function beforeTests(): void {

        self::assertAppState();

        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
    }
    
    public function testImportMissingTagId() {
        $taskConfig = [
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        ];
        static::api()->addImportFile(static::api()->getFile('ibm-opentm2-fail3.xlf'), 'application/xml');
        $this->assertFalse(static::api()->import($taskConfig, false), 'XLF with sub tags in tags without IDs did not produce a task state error!');
        $task = static::api()->getTask();
        static::api()->deleteTask($task->id);
    }
}