<?php
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
        $api->addImportFile('editorAPI/CsvEncodingTest/specialCharactersInCSV.csv');
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
        $approvalFileContent = $this->api()->getFileContent('specialCharactersInCSV.csv');
        $csvRows = explode("\n", $approvalFileContent);
        array_shift($csvRows); //remove headers
        array_shift($csvRows); //remove comments like row without testdata
        foreach($csvRows as $idx => $row) {
            //ignore last line
            if(empty($row)) {
                continue;
            }
            $idx++; //compensate comment row removal
            $row = str_getcsv($row);
            $expectedSource = $row[3];
            $expectedTarget = $row[4];
            $this->assertEquals($expectedSource, $segments[$idx]->source);
            $this->assertEquals($expectedTarget, $segments[$idx]->target);
            $this->assertEquals($expectedTarget, $segments[$idx]->targetEdit);
            
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
        $approvalFileContent = $this->api()->replaceChangesXmlContent($this->api()->getFileContent('testCsvEncoding-assert-equal.xliff'));
        $toCheck = $this->api()->replaceChangesXmlContent(file_get_contents($foundChangeFile));
        $this->assertXmlStringEqualsXmlString($approvalFileContent, $toCheck);
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

        //get the export zip
        $path = $this->api()->getTaskDataDirectory();
        $this->assertFileExists($path.'export.zip');
        //unzip it
        $zip = new ZipArchive();
        $zip->open($path.'export.zip');
        $zip->extractTo($path);
        $exportedFile = $path.$task->taskGuid.'/specialCharactersInCSV.csv';
        $this->assertFileExists($exportedFile);
        //compare the result with the expected result
        $expectedResult = $this->api()->getFileContent($fileToCompare);
        $this->assertEquals($expectedResult, file_get_contents($exportedFile));
        
        //delete exported file, so that next call can recreate it
        unlink($exportedFile);
    }
    
    public static function tearDownAfterClass() {
        $task = self::$api->getTask();
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}