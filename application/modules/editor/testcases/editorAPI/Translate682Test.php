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
 * Translate682Test imports a simple task, where the segments contain htmlentities
 * this were changed and afterwards a diff export is done. The entities may not be destroyed by the diff, but completly encapsulated.
 * This test covers also TRANSLATE-682 since this can be achieved just by special testdata but the same steps as in this test.
 * See therefore: 
 * TRANSLATE-678 
 */
class Translate682Test extends \ZfExtended_Test_ApiTestcase {
    protected static $expectedCsvResult;
    
    public static function setUpBeforeClass(): void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
        );
        
        $appState = self::assertTermTagger();
        self::assertNotContains('editor_Plugins_ManualStatusCheck_Bootstrap', $appState->pluginsLoaded, 'Plugin ManualStatusCheck should not be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        
        $api->addImportFile($api->getFile('testsamples.sdlxliff'), 'application/xml');
        $api->addImportTbx($api->getFile('tbx_without_ids.tbx'));
        
        $api->import($task);
    }
    
    public function testEditing() {
        $task = $this->api()->getTask();
        //open task for whole testcase
        $this->api()->putJson('editor/task/'.$task->id, array('userState' => 'edit', 'id' => $task->id));
        
        //get segment list
        $segments = $this->api()->getSegments();
        $segToEdit = $segments[0];
        
        
        //swap < and >
        $editedData = str_replace('&lt;FilesMatch', '&gt;FilesMatch', $segToEdit->targetEdit);
        
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $editedData, $segToEdit->id);
        $this->api()->putJson('editor/segment/'.$segToEdit->id, $segmentData);
    }

    /**
     * tests the special characters in the exported data
     * @depends testEditing
     */
    public function testExport() {
        $task = $this->api()->getTask();

        //start task export with diff 
        $this->api()->get('editor/task/export/id/'.$task->id.'/diff/1');

        //get the exported file content
        $path = $this->api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        
        //sdl:revid="ddd535ec-ed90-4e0a-9151-4724caf00ccc
        //rev-def id="ddd535ec-ed90-4e0a-9151-4724caf00ccc"
        
        $replaceDynamicRevId = function($string) {
            $string = preg_replace('#author="manager test" date="[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}"#', 'author="manager test" date="NOW"', $string);
            return preg_replace('/(sdl:revid="|rev-def id=")[a-z0-9-]{36}"/', '\1foo-bar"', $string);
        };
        
        $exportedData = $replaceDynamicRevId($this->api()->getFileContentFromZipPath($pathToZip, $task->taskGuid.'/testsamples.sdlxliff'));
        $expectedData = $replaceDynamicRevId($this->api()->getFileContent('expectedResult.sdlxliff'));
        
        $this->assertEquals(rtrim($expectedData), rtrim($exportedData), 'Exported result does not equal to expected SDLXLIFF content');
    }
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');
        self::$api->putJson('editor/task/'.$task->id, array('userState' => 'open', 'id' => $task->id));
        self::$api->delete('editor/task/'.$task->id);
    }
}