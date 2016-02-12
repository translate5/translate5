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
 * CsvEncodingTest imports a CSV with several special characters
 * The test task will be edited and exported. The generated changes.xml and 
 * exported file will then be checked for correct encoded content.
 */
class CsvEncodingTest extends \ZfExtended_Test_ApiTestcase {
    /**
     * Setting up the test task by fresh import, adds the lector and translator users
     */
    public static function setUpBeforeClass() {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
        );
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        $appState = self::assertTermTagger();
        self::assertNotContains('editor_Plugins_ManualStatusCheck_Bootstrap', $appState->pluginsLoaded, 'Plugin ManualStatusCheck may not be activated for this test case!');
        $api->addImportFile('editorAPI/CsvEncodingTest/CSV-test.zip');
        $api->import($task);
        
        $api->addUser('testlector');
        $api->reloadTask();
        $api->addUser('testtranslator', 'waiting', 'translator');
    }
    
    /**
     * tests if config is correct for using our test CSV
     * and 
     * tests if config is correct for testing changes.xliff 
     */
    public function testCsvSettings() {
        $tests = array(
            'runtimeOptions.import.csv.delimiter' => ',',
            'runtimeOptions.import.csv.enclosure' => '"',
            'runtimeOptions.import.csv.fields.mid' => 'mid',
            'runtimeOptions.import.csv.fields.source' => 'source',
            'runtimeOptions.editor.notification.saveXmlToFile' => 1,
        );
        
        foreach($tests as $name => $value) {
            $config = $this->api()->requestJson('editor/config', 'GET', array(
                'filter' => '[{"type":"string","value":"'.$name.'","field":"name"}]',
            ));
            $this->assertCount(1, $config);
            $this->assertEquals($value, $config[0]->value);
        }
    }
    
    
    /**
     * tests the specialcharacters encoding after import, edits some segments as lector, finish then the task
     * - checks for correct changes.xliff
     * - checks if task is open for translator and finished for lector
     * - modifies also segments with special characters to test encoding in changes.xml
     * @depends testCsvSettings
     */
    public function testEncodingAfterImport() {
        //check that testtranslator is waiting
        $this->api()->login('testtranslator');
        $this->assertEquals('waiting', $this->api()->reloadTask()->userState);
        
        //check that testlector is open
        $this->api()->login('testlector');
        $this->assertEquals('open', $this->api()->reloadTask()->userState);
        
        $task = $this->api()->getTask();
        //open task for whole testcase
        $this->api()->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
        
        //get segment list
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');

        //check imported segment content against correct encoded strings from CSV in not imported colums 4 and 5
        $approvalFileContent = $this->api()->getFileContentFromZip('CSV-test.zip','proofRead/specialCharactersInCSV.csv');
        $csvRows = explode("\n", $approvalFileContent);
        array_shift($csvRows); //remove headers
        array_shift($csvRows); //remove comments like row without testdata
        //remove data-seq attribute from segment, because it values change according to db-table-id
        $removeDataSeq = function($text){
          return preg_replace('#data-seq="\d+"#', 'data-seq=""', $text);
        };
        foreach($csvRows as $idx => $row) {
            //ignore last line
            if(empty($row)) {
                continue;
            }
            $idx++; //compensate comment row removal
            $row = str_getcsv($row);
            $expectedSource = $removeDataSeq($row[3]);
            $expectedTarget = $removeDataSeq($row[4]);
            $this->assertEquals($expectedSource, $removeDataSeq($segments[$idx]->source));
            $this->assertEquals($expectedTarget, $removeDataSeq($segments[$idx]->target));
            $this->assertEquals($expectedTarget, $removeDataSeq($segments[$idx]->targetEdit));
            
            $segToEdit = $segments[$idx];
            $segmentData = $this->api()->prepareSegmentPut('targetEdit', $expectedTarget.' - edited', $segToEdit->id);
            $this->api()->requestJson('editor/segment/'.$segToEdit->id, 'PUT', $segmentData);
        }
    }
    
    /**
     * tests the special characters in the changes.xml
     * @depends testEncodingAfterImport
     */
    public function testChangesXml() {
        $task = $this->api()->getTask();
        //finishing the task to get a changes.xml
        $res = $this->api()->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'finished', 'id' => $task->id));
        $this->assertEquals('finished', $this->api()->reloadTask()->userState);
        
        //get the changes file
        $path = $this->api()->getTaskDataDirectory();
        $foundChangeFiles = glob($path.'changes*.xliff');
        $this->assertNotEmpty($foundChangeFiles, 'No changes*.xliff file was written for taskGuid: '.$task->taskGuid);
        $foundChangeFile = end($foundChangeFiles);
        $this->assertFileExists($foundChangeFile);
        
        //no direct file assert equals possible here, since our diff format contains random sdl:revids
        //this revids has to be replaced before assertEqual
        $approvalFileContent = $this->api()->getFileContent('testCsvEncoding-assert-equal.xliff');
        $toCheck = $this->api()->replaceChangesXmlContent(file_get_contents($foundChangeFile));
        $this->assertSame($approvalFileContent, $toCheck);
    }
    
    /**
     * tests the special characters in the exported data
     * @depends testChangesXml
     */
    public function testExport() {
        $task = $this->api()->getTask();
        //start task export 
        $this->checkExport($task, 'editor/task/export/id/'.$task->id, 'specialCharactersInCSV-export-assert-equal.csv');
        //start task export with diff 
        $this->checkExport($task, 'editor/task/export/id/'.$task->id.'/diff/1', 'specialCharactersInCSV-exportdiff-assert-equal.csv');
    }
    
    /**
     * tests the export results
     * @param stdClass $task
     * @param string $exportUrl
     * @param string $fileToCompare
     */
    protected function checkExport(stdClass $task, $exportUrl, $fileToCompare) {
        $this->api()->login('testmanager');
        $this->api()->request($exportUrl);

        //get the exported file content
        $path = $this->api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        $exportedFile = $this->api()->getFileContentFromZipPath($pathToZip, $task->taskGuid.'/specialCharactersInCSV.csv');
        //compare it
        $expectedResult = $this->api()->getFileContent($fileToCompare);
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile));
    }
    
    public static function tearDownAfterClass() {
        $task = self::$api->getTask();
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}
