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
namespace Translate5\MaintenanceCli\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

class DevelopmentCreatetestCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:createtest';
    
    /**
     * @var InputInterface
     */
    protected $input;
    
    /**
     * @var OutputInterface
     */
    protected $output;
    
    /**
     * @var SymfonyStyle
     */
    protected $io;
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Development: Creates a new API test, gets the name (ISSUE-XXX) from the current branch.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Creates a new API test, gets the name (ISSUE-XXX) from the current branch.');

        $this->addOption(
            'name',
            'N',
            InputOption::VALUE_REQUIRED,
            'Force a name (must end with Test!) instead of getting it from the branch.');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();
        
        $this->writeTitle('Create a new API test from skeleton');
        
        $path = APPLICATION_PATH.'/modules/editor/testcases/editorAPI';
        
        if($name = $input->getOption('name')) {
            $issue = $name;
        }
        else {
            $gitout = [];
            exec('cd '.$path.'; git branch --show-current', $gitout);
            if(empty($gitout)) {
                $this->io->error('git could not find local branch!');
                return 1;
            }
            
            $name = reset($gitout);
            $matches = [];
            if(preg_match('/([A-Z][A-Z0-9]*)-([0-9]+)/', $name, $matches)) {
                $issue = $matches[1].'-'.$matches[2];
                $name = ucfirst(strtolower($matches[1])).$matches[2].'Test';
            }
            else{
                $issue = $name = 'AnotherTest';
            }
        }
        
        $phpFile = $path.'/'.$name.'.php';
        if(file_exists($phpFile)){
            $this->io->error('Test '.$name.'.php does already exist!');
            return 1;
        }
        $this->makeTestPhp($phpFile, $name, $issue);
        $this->makeTestFiles($path, $name, $issue);
        
        $this->io->success("Test created: \n  ".$phpFile);
        return 0;
    }
    
    protected function makeTestPhp($file, $name, $issue) {
        file_put_contents($file, '<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - '.date('Y').' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Testcase for '.$issue.' Mixing XLF id and rid values led to wrong tag numbering
 * For details see the issue.
 */
class '.$name.' extends editor_Test_JsonTest {
    public static function setUpBeforeClass(): void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            \'sourceLang\' => \'de\',
            \'targetLang\' => \'en\',
            \'edit100PercentMatch\' => true,
            \'lockLocked\' => 1,
        );
        
        $appState = self::assertAppState();

//TODO FOR TEST USAGE: check plugin pre conditions
        self::assertNotContains(\'editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap\', $appState->pluginsLoaded, \'Plugin LockSegmentsBasedOnConfig should not be activated for this test case!\');
        self::assertNotContains(\'editor_Plugins_NoMissingTargetTerminology_Bootstrap\', $appState->pluginsLoaded, \'Plugin NoMissingTargetTerminology should not be activated for this test case!\');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin(\'testmanager\');
        
//TODO FOR TEST USAGE: adjust or delete the created testfiles/task-config.ini

//TODO FOR TEST USAGE: check config checks
        $tests = array(
            \'runtimeOptions.import.xlf.preserveWhitespace\' => 0,
            \'runtimeOptions.import.xlf.ignoreFramingTags\' => 1,
        );
        self::$api->testConfig($tests);
        
        $zipfile = $api->zipTestFiles(\'testfiles/\',\'testTask.zip\');
        
        $api->addImportFile($zipfile);
        $api->import($task);
        
        $api->addUser(\'testlector\');
        
        //login in setUpBeforeClass means using this user in whole testcase!
        $api->login(\'testlector\');
        
        $task = $api->getTask();
        //open task for whole testcase
        $api->requestJson(\'editor/task/\'.$task->id, \'PUT\', array(\'userState\' => \'edit\', \'id\' => $task->id));
    }
    
    /**
//TODO FOR TEST USAGE: adjust your tests, here is a simple example of testing values after import
     * Testing segment values directly after import
     */
    public function testSegmentValuesAfterImport() {
        $segments = $this->api()->requestJson(\'editor/segment?page=1&start=0&limit=10\');
        
//TODO FOR TEST USAGE: run the test, the next line creates the expected content json, comment the line out, validate if the produced JSON is as expected
        file_put_contents($this->api()->getFile(\'/expectedSegments.json\', null, false), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertModelsEqualsJsonFile(\'Segment\', \'expectedSegments.json\', $segments, \'Imported segments are not as expected!\');
    }
    
    /**
//TODO FOR TEST USAGE: adjust your tests, here is a simple example of editing a segment value
     * @depends testSegmentValuesAfterImport
     */
    public function testSegmentEditing() {
        //get segment list
        $segments = $this->api()->requestJson(\'editor/segment?page=1&start=0&limit=10\');
        
        //test editing a prefilled segment
        $segToTest = $segments[0];
        
//TODO FOR TEST USAGE: adjust your segment editings here
        $segToTest->targetEdit = str_replace([\'cool.\', \'is &lt; a\'], [\'cool &amp; cööler.\', \'is &gt; a\'], $segToTest->targetEdit);
        
        $segmentData = $this->api()->prepareSegmentPut(\'targetEdit\', $segToTest->targetEdit, $segToTest->id);
        $this->api()->requestJson(\'editor/segment/\'.$segToTest->id, \'PUT\', $segmentData);
        
        //check direct PUT result
        $segments = $this->api()->requestJson(\'editor/segment?page=1&start=0&limit=10\');

//TODO FOR TEST USAGE: run the test, the next line creates the expected content json, comment the line out, validate if the produced JSON is as expected
        file_put_contents($this->api()->getFile(\'/expectedSegments-edited.json\', null, false), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertModelsEqualsJsonFile(\'Segment\', \'expectedSegments-edited.json\', $segments, \'Imported segments are not as expected!\');
    }
    
    /**
//TODO FOR TEST USAGE: adjust your tests, here is a simple example of export the edited task
     * tests the export results
     * @depends testSegmentEditing
     */
    public function testExport() {
        self::$api->login(\'testmanager\');
        $task = $this->api()->getTask();
        //start task export
        
        $this->api()->request(\'editor/task/export/id/\'.$task->id);
        
        //get the exported file content
        $path = $this->api()->getTaskDataDirectory();
        $pathToZip = $path.\'export.zip\';
        $this->assertFileExists($pathToZip);
        
        $exportedFile = $this->api()->getFileContentFromZipPath($pathToZip, $task->taskGuid.\'/'.$issue.'-de-en.xlf\');
//TODO FOR TEST USAGE: run the test, the next line creates the expected export file, comment the line out, validate if the produced export is as expected
        file_put_contents($this->api()->getFile(\'export-'.$issue.'-de-en.xlf\', null, false), $exportedFile);
        $expectedResult = $this->api()->getFileContent(\'export-'.$issue.'-de-en.xlf\');
        
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), \'Exported result does not equal to export-'.$issue.'-de-en.xlf\');
    }

    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login(\'testlector\');
        self::$api->requestJson(\'editor/task/\'.$task->id, \'PUT\', array(\'userState\' => \'open\', \'id\' => $task->id));
        self::$api->login(\'testmanager\');
        self::$api->requestJson(\'editor/task/\'.$task->id, \'DELETE\');
    }
}
');
    }
    
    protected function makeTestFiles($path, $name, $issue) {
        $testpath = $path.'/'.$name.'/';
        $workfiles = $path.'/'.$name.'/testfiles/workfiles/';
        mkdir($workfiles, 0755, true);
        
        //make a sample task-config.ini
        file_put_contents($testpath.'testfiles/task-config.ini', "runtimeOptions.import.fileparser.options.protectTags = 0\n");
        
        $xliff = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2" xmlns:okp="okapi-framework:xliff-extensions" xmlns:its="http://www.w3.org/2005/11/its" xmlns:itsxlf="http://www.w3.org/ns/its-xliff/" its:version="2.0">
<file original="word/document.xml" source-language="de" target-language="en" datatype="x-undefined">
<body>
<trans-unit id="NFDBB2FA9-tu1" xml:space="preserve">
<source xml:lang="de">Das ist ein Test</source>
<seg-source><mrk mid="0" mtype="seg">Das ist ein Test</mrk></seg-source>
<target xml:lang="en">This is a test</target>
</trans-unit>
</body>
</file>
</xliff>
EOF;

        file_put_contents($workfiles.$issue.'-de-en.xlf', $xliff);

$json = <<<EOF
[
    {
        "segmentNrInTask": "1",
        "mid": "NFDBB2FA9-tu1_1",
        "userGuid": "{00000000-0000-0000-C100-CCDDEE000001}",
        "userName": "manager test",
        "editable": "1",
        "pretrans": "0",
        "matchRate": "0",
        "matchRateType": "import;empty",
        "stateId": "0",
        "autoStateId": "4",
        "fileOrder": "0",
        "comments": null,
        "workflowStepNr": "0",
        "workflowStep": null,
        "source": "Das ist ein Test",
        "sourceMd5": "dcb15d765cf668edd3ee6de471d22834",
        "sourceToSort": "Das ist ein Test",
        "target": "This is a test",
        "targetMd5": "d41d8cd98f00b204e9800998ecf8427e",
        "targetToSort": "This is a test",
        "targetEdit": "",
        "targetEditToSort": "",
        "metaCache": "{\"minWidth\":null,\"maxWidth\":null,\"maxNumberOfLines\":null,\"sizeUnit\":\"\",\"font\":\"\",\"fontSize\":0,\"additionalUnitLength\":0,\"additionalMrkLength\":2,\"siblingData\":{\"fakeSegId_1\":{\"nr\":\"1\",\"length\":{\"targetEdit\":2}}}}",
        "isWatched": false
    }
]
EOF;

    }
}
