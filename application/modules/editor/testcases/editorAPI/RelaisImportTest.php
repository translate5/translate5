<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Tests if Relais Files are imported correctly, inclusive our alignment checks 
 */
class RelaisImportTest extends \ZfExtended_Test_ApiTestcase {
    
    public static function setUpBeforeClass() {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'sourceLang' => 'de',
            'targetLang' => 'en',
            'relaisLang' => 'it',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        );
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        
        $appState = $api->requestJson('editor/index/applicationstate');
        self::assertNotContains('editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap', $appState->pluginsLoaded, 'Plugin LockSegmentsBasedOnConfig may not be activated for this test case!');
        self::assertNotContains('editor_Plugins_NoMissingTargetTerminology_Bootstrap', $appState->pluginsLoaded, 'Plugin NoMissingTargetTerminology may not be activated for this test case!');
        
        $api->addImportFile($api->getFile('RelaisImportTest.zip'));
        $api->import($task);
        
        $task = $api->getTask();
        //open task for whole testcase
        $api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
    }
    
    /**
     * Test using changealikes by source match
     */
    public function testAlikeCalculation() {
        //get segment list
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        $segments = array_map(function($segment){
            //TODO remove array cast with PHP7
            return (array) $segment;
        }, $segments);
        $relais = array_column($segments, 'relais', 'segmentNrInTask');
        
        $expected = [
            '1' => 'Questo e un casa roso.',
            '2' => 'RELAIS - Here the alignment is OK.',
            '3' => '',
            '4' => 'RELAIS – Here the alignment is OK again.',
            '5' => '',
            '6' => 'RELAIS – Here the alignment is OK again 2.',
            '7' => '',
            '8' => 'RELAIS – Here the alignment is OK again 3.',
            '9' => 'This is a red house',
            '10' => 'Here the alignment is OK.',
            '11' => '',
            '12' => 'Here the alignment is OK again.',
            '13' => 'Here the alignment is OK again 2.',
            '14' => 'Here the alignment is OK again 3.',
        ];
        
        $this->assertEquals($expected, $relais, 'Relais columns not filled as expected!');
    }
    
    public static function tearDownAfterClass() {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        return;
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}