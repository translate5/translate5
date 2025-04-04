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

use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\JsonTestAbstract;

/**
 * CsvEncodingTest imports a CSV with several special characters
 * The test task will be edited and exported. The generated changes.xml and
 * exported file will then be checked for correct encoded content.
 */
class CsvEncodingTest extends JsonTestAbstract
{
    protected static bool $termtaggerRequired = true;

    protected static array $forbiddenPlugins = [
        'editor_Plugins_ManualStatusCheck_Bootstrap',
    ];

    protected static array $requiredRuntimeOptions = [
        'import.csv.delimiter' => ',',
        'import.csv.enclosure' => '"',
        'import.csv.fields.mid' => 'id',
        'import.csv.fields.source' => 'source',
        'editor.notification.saveXmlToFile' => 1,
    ];

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('en', 'de')
            ->addUploadFolder('CSV-testfiles')
            ->addTaskConfig('runtimeOptions.import.fileparser.csv.active', '1')
            ->addUser(TestUser::TestLector->value)
            ->addUser(TestUser::TestTranslator->value, 'waiting', 'translation')
            ->setProperty('taskName', static::NAME_PREFIX . 'CsvEncodingTest'); // TODO FIXME: we better generate data independent from resource-names ...
    }

    /**
     * tests the specialcharacters encoding after import, edits some segments as lector, finish then the task
     * - checks for correct changes.xliff
     * - checks if task is open for translator and finished for lector
     * - modifies also segments with special characters to test encoding in changes.xml
     */
    public function testEncodingAfterImport()
    {
        //check that testtranslator is waiting
        static::api()->login(TestUser::TestTranslator->value);
        static::getTask()->reload(static::api());
        $this->assertEquals('waiting', static::getTask()->getProperty('userState'));

        //check that testlector is open
        static::api()->login(TestUser::TestLector->value);
        static::getTask()->reload(static::api());
        $this->assertEquals('open', static::getTask()->getProperty('userState'));

        //open task for whole testcase
        static::getTask()->setTaskToEdit(static::api());

        //Testing Reference files. Is a little bit hidden in here, but as separate method we would have to play with logins and the task,
        // in this method we are logged in and the task is opened.
        $res = static::api()->get('editor/referencefile/Translate%205%20Referenz%20Demonstration.pdf');
        /*@var $res Zend_Http_Response */
        $this->assertEquals(200, $res->getStatus(), 'GET reference file does not return HTTP 200');
        $this->assertEquals('2a0275e5921f9127120403b0306758b5', md5($res->getBody()), 'GET reference file does not return correct body');

        //get segment list
        $segments = static::api()->getSegments();

        //check imported segment content against correct encoded strings from CSV in not imported colums 4 and 5
        //MQM is in this file for check correct encoding order, see TRANSLATE-654
        $approvalFileContent = static::api()->getFileContent('CSV-testfiles/workfiles/specialCharactersInCSV.csv');

        $csvRows = explode("\n", $approvalFileContent);
        array_shift($csvRows); //remove headers
        array_shift($csvRows); //remove comments like row without testdata

        //remove img tags from compare column, since mqm import is currently not working.
        //compare column is used as edited data also, so mqm should remain in there for editing
        $removeImgTags = function ($text) {
            return preg_replace('#<img class="[^"]+qmflag[^"]+"[^>]+>#', '', $text);
        };

        foreach ($csvRows as $idx => $row) {
            //ignore last line
            if (empty($row)) {
                continue;
            }
            $idx++; //compensate comment row removal
            $row = str_getcsv($row);
            $expectedSource = $removeImgTags($row[3]);
            $expectedTarget = $removeImgTags($row[4]);
            $message = 'Imported column %1$s is NOT equal to expected column %1$s (CSV col %2$s) in CSV row/segNr %3$s';
            $this->assertFieldTextEquals($expectedSource, $segments[$idx]->source, sprintf($message, 'source', '3', $idx + 1));
            $this->assertFieldTextEquals($expectedTarget, $segments[$idx]->target, sprintf($message, 'target', '4', $idx + 1));
            $this->assertFieldTextEquals($expectedTarget, $segments[$idx]->targetEdit, sprintf($message, 'targetEdit', '4', $idx + 1));

            $segToEdit = $segments[$idx];
            $editedData = $row[4] . ' - edited';
            static::api()->saveSegment($segToEdit->id, $editedData);
        }
    }

    /**
     * tests the special characters in the changes.xml
     * @depends testEncodingAfterImport
     */
    public function testChangesXml()
    {
        $task = static::api()->getTask();
        //finishing the task to get a changes.xml
        static::getTask()->setTaskToFinished(static::api());
        static::getTask()->reload(static::api());
        $this->assertEquals('finished', static::getTask()->getProperty('userState'));

        //get the changes file
        $path = static::api()->getTaskDataDirectory();
        $foundChangeFiles = glob($path . 'changes*.xliff');
        $this->assertNotEmpty($foundChangeFiles, 'No changes*.xliff file was written for taskGuid: ' . $task->taskGuid);
        $foundChangeFile = end($foundChangeFiles);
        $this->assertFileExists($foundChangeFile);

        //no direct file assert equals possible here, since our diff format contains random sdl:revids
        //this revids has to be replaced before assertEqual
        $approvalFileContent = static::api()->getFileContent('testCsvEncoding-assert-equal.xliff');
        $toCheck = static::api()->replaceChangesXmlContent(file_get_contents($foundChangeFile));
        $this->assertSame($approvalFileContent, $toCheck);
    }

    /**
     * tests the special characters in the exported data
     * @depends testChangesXml
     */
    public function testExport()
    {
        $task = static::api()->getTask();
        //start task export
        $this->checkExport($task, 'editor/task/export/id/' . $task->id, 'specialCharactersInCSV-export-assert-equal.csv');
        //start task export with diff
        $this->checkExport($task, 'editor/task/export/id/' . $task->id . '/diff/1', 'specialCharactersInCSV-exportdiff-assert-equal.csv');
    }

    /**
     * tests the export results
     * @param string $exportUrl
     * @param string $fileToCompare
     */
    protected function checkExport(stdClass $task, $exportUrl, $fileToCompare)
    {
        static::api()->login(TestUser::TestManager->value);
        static::api()->get($exportUrl);

        $removeMqmIds = function ($text) {
            return preg_replace('/xml:id=""[^"]+""/', 'xml:id=""removed-for-comparing""', $text);
        };

        //get the exported file content
        $path = static::api()->getTaskDataDirectory();
        $pathToZip = $path . 'export.zip';
        $this->assertFileExists($pathToZip);
        $exportedFile = $removeMqmIds(static::api()->getFileContentFromZipPath($pathToZip, '/specialCharactersInCSV.csv'));
        //compare it
        $expectedResult = $removeMqmIds(static::api()->getFileContent($fileToCompare));
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to ' . $fileToCompare);
    }
}
