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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DevelopmentCreatetestCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:createtest';

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
            'Force a name (must end with Test!) instead of getting it from the branch.'
        );

        $this->addOption(
            'plugin',
            'p',
            InputOption::VALUE_REQUIRED,
            'Create the test in the given Plugin (give the relative path to the plugin root!).'
        );
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

        if ($plugin = $input->getOption('plugin')) {
            $path = APPLICATION_ROOT . '/' . $plugin . '/tests';
            if (! is_dir($path) && ! mkdir($path)) {
                $this->io->error('The given path does not exist or the "tests" folder could not be created: ' . $path);
            }
        } else {
            $path = APPLICATION_PATH . '/modules/editor/testcases/editorAPI';
        }

        if ($name = $input->getOption('name')) {
            $issue = $name;
        } else {
            $gitout = [];
            exec('cd ' . $path . '; git branch --show-current', $gitout);
            if (empty($gitout)) {
                $this->io->error('git could not find local branch!');

                return 1;
            }

            $name = reset($gitout);
            $matches = [];
            if (preg_match('/([A-Z][A-Z0-9]*)-([0-9]+)/', $name, $matches)) {
                $issue = $matches[1] . '-' . $matches[2];
                $name = ucfirst(strtolower($matches[1])) . $matches[2] . 'Test';
            } else {
                $issue = $name = 'AnotherTest';
            }
        }

        $phpFile = $path . '/' . $name . '.php';
        if (file_exists($phpFile)) {
            $this->io->error('Test ' . $name . '.php does already exist!');

            return 1;
        }
        $this->makeTestPhp($phpFile, $name, $issue);
        $this->makeTestFiles($path, $name, $issue);

        $this->io->success("Test created: \n  " . $phpFile);

        return 0;
    }

    protected function makeTestPhp($file, $name, $issue)
    {
        file_put_contents($file, '<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - ' . date('Y') . ' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Test\JsonTestAbstract;
use MittagQI\Translate5\Test\Import\Config;

/**
 * Testcase for ' . $issue . ' //TODO FOR TEST USAGE: add a description
 * For details see the issue.
 */
class ' . $name . ' extends JsonTestAbstract {

//TODO FOR TEST USAGE: check plugin pre conditions
    protected static array $forbiddenPlugins = [
        \'editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap\',
        \'editor_Plugins_NoMissingTargetTerminology_Bootstrap\'
    ];
//TODO FOR TEST USAGE: check plugin pre conditions
    protected static array $requiredPlugins = [
        \'editor_Plugins_Okapi_Init\'
    ];
//TODO FOR TEST USAGE: check config pre conditions
    protected static array $requiredRuntimeOptions = [
        \'import.xlf.preserveWhitespace\' => 0,
        \'runtimeOptions.import.xlf.ignoreFramingTags\' => \'all\'
    ];
 
//TODO FOR TEST USAGE: If set to true, an own customer is created for the test. If not needed, remove
    protected static bool $setupOwnCustomer = false;
    
//TODO FOR TEST USAGE: This is the user that will be logged in after the auto setup. Also all imported tasks will be associated with this user
    protected static string $setupUserLogin = \'testmanager\';
    
    protected static function setupImport(Config $config): void
    {
        $sourceLangRfc = \'de\';
        $targetLangRfc = \'en\';
        $customerId = static::getTestCustomerId();
//TODO FOR TEST USAGE: Adding a Language-Resource, that will be automatically imported in the setup-phase
        $config
            ->addLanguageResource(\'opentm2\', \'my-translation-memory.tmx\', $customerId, $sourceLangRfc, $targetLangRfc);
//TODO FOR TEST USAGE: Adding a Language-Resource, that will be automatically imported in the setup-phase
        $config
            ->addLanguageResource(\'termcollection\', \'my-term-collection.tbx\', $customerId);
//TODO FOR TEST USAGE: Adding a Pretranslation Operation to the task
        $config
            ->addPretranslation();
//TODO FOR TEST USAGE: Adding a Task. During import, the added LanguageResources are automatically assigned and an added Operation will be queued
        $config
            ->addTask($sourceLangRfc, $targetLangRfc, static::getTestCustomerId(), \'my-test-data-de-en.zip\')
            ->setProperty(\'wordCount\', 1270);
    }
    
    /**
//TODO FOR TEST USAGE: adjust your tests, here is a simple example of testing values after import
     * Testing segment values directly after import
     */
    public function testSegmentValuesAfterImport()
    {
        $jsonFileName = \'expectedSegments.json\';
// REMINDER FOR TEST USAGE:
// when the option -c is set on calling this test as a single test (e.g /var/www/translate5/application/modules/editor/testcases/apitest.sh -c editorAPI/MyFunnyTest.php), the files are automatically saved after fetching with the passed filename (third argument)
        $segments = static::api()->getSegments($jsonFileName, 10);
        $this->assertModelsEqualsJsonFile(\'Segment\', $jsonFileName, $segments, \'Imported segments are not as expected!\');
    }
    
    /**
//TODO FOR TEST USAGE: adjust your tests, here is a simple example of editing a segment value
     * @depends testSegmentValuesAfterImport
     */
    public function testSegmentEditing()
    {
        //get segment list
        $segments = static::api()->getSegments(null, 10);
        
        //test editing a prefilled segment
        $segToTest = $segments[0];
        
//TODO FOR TEST USAGE: adjust your segment editings here
        $segToTest->targetEdit = str_replace([\'cool.\', \'is &lt; a\'], [\'cool &amp; cööler.\', \'is &gt; a\'], $segToTest->targetEdit);
        static::api()->saveSegment($segToTest->id, $segToTest->targetEdit);
        
        //check direct PUT result
        $jsonFileName = \'expectedSegments-edited.json\';
// REMINDER FOR TEST USAGE:
// when the option -c is set on calling this test as a single test (e.g /var/www/translate5/application/modules/editor/testcases/apitest.sh -c editorAPI/MyFunnyTest.php), the files are automatically saved after fetching with the passed filename (third argument)
        $segments = static::api()->getSegments($jsonFileName, 10);
        $this->assertModelsEqualsJsonFile(\'Segment\', $jsonFileName, $segments, \'Imported segments are not as expected!\');
    }
    
    /**
//TODO FOR TEST USAGE: adjust your tests, here is a simple example of export the edited task
     * tests the export results
     * @depends testSegmentEditing
     */
    public function testExport()
    {
        static::api()->login(\'testmanager\');
        $task = static::getTask();
        
        //start task export        
        static::api()->get(\'editor/task/export/id/\'.$task->getId());
        
        //get the exported file content
        $path = $task->getDataDirectory();
        $pathToZip = $path.\'export.zip\';
        $this->assertFileExists($pathToZip);
        
        $exportFileName = \'export-' . $issue . '-de-en.xlf\';
        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, \'/' . $issue . '-de-en.xlf\');
// REMINDER FOR TEST USAGE:
// This is the manual way to save files when the command-option -c (= capture) was set
        if(static::api()->isCapturing()){
            file_put_contents(static::api()->getFile($exportFileName, null, false), $exportedFile);
        }
        $expectedResult = static::api()->getFileContent($exportFileName);
        
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), \'Exported result does not equal to export-' . $issue . '-de-en.xlf\');
    }
}
');
    }

    protected function makeTestFiles($path, $name, $issue)
    {
        $testpath = $path . '/' . $name . '/';
        $workfiles = $path . '/' . $name . '/testfiles/workfiles/';
        mkdir($workfiles, 0755, true);

        //make a sample task-config.ini
        file_put_contents($testpath . 'testfiles/task-config.ini', "runtimeOptions.import.fileparser.options.protectTags = 0\n");

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

        file_put_contents($workfiles . $issue . '-de-en.xlf', $xliff);

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
