<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
class XlfImportFailTest extends \ZfExtended_Test_ApiTestcase {
    protected $taskConfig = [
        'sourceLang' => 'en',
        'targetLang' => 'de',
        'edit100PercentMatch' => true,
        'lockLocked' => 1,
    ];
    
    public static function setUpBeforeClass() {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        $appState = $api->requestJson('editor/index/applicationstate');
        
        self::assertNotContains('editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap', $appState->pluginsLoaded, 'Plugin LockSegmentsBasedOnConfig may not be activated for this test case!');
        self::assertNotContains('editor_Plugins_NoMissingTargetTerminology_Bootstrap', $appState->pluginsLoaded, 'Plugin NoMissingTargetTerminology may not be activated for this test case!');
    }
    
    public function testImportFailOnSegmentatationErrors1() {
        $this->api()->addImportFile($this->api()->getFile('ibm-opentm2-fail1.xlf'), 'application/xml');
        $this->assertFalse($this->api()->import($this->taskConfig, false), 'XLF with segmentation errors did not produce a task state error!');
        $task = $this->api()->getTask();
        $this->api()->requestJson('editor/task/'.$task->id, 'DELETE');
    }
    
    public function testImportFailOnSegmentatationErrors2() {
        $this->api()->addImportFile($this->api()->getFile('ibm-opentm2-fail2.xlf'), 'application/xml');
        $this->assertFalse($this->api()->import($this->taskConfig, false), 'XLF with segmentation errors did not produce a task state error!');
        $task = $this->api()->getTask();
        $this->api()->requestJson('editor/task/'.$task->id, 'DELETE');
    }
    
    public function testImportMissingTagId() {
        $this->api()->addImportFile($this->api()->getFile('ibm-opentm2-fail1.xlf'), 'application/xml');
        $this->assertFalse($this->api()->import($this->taskConfig, false), 'XLF with sub tags in tags without IDs did not produce a task state error!');
        $task = $this->api()->getTask();
        $this->api()->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}