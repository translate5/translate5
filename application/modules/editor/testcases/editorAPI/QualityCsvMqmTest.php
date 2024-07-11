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

use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\JsonTestAbstract;

/**
 * CsvMqmTest tests the correct export MQM Tags.
 *   Especially the cases of overlapping and misordered MQM tags
 */
class QualityCsvMqmTest extends JsonTestAbstract
{
    public const CSV_TARGET = 'target is coming from test edit';

    protected static bool $termtaggerRequired = true;

    protected static array $forbiddenPlugins = [
        editor_Plugins_ManualStatusCheck_Bootstrap::class,
        editor_Plugins_Translate24_Init::class,
    ];

    protected static array $requiredRuntimeOptions = [
        'import.csv.delimiter' => ',',
        'import.csv.enclosure' => '"',
        'import.csv.fields.mid' => 'id',
        'import.csv.fields.source' => 'source',
    ];

    protected $testData = [
        'M',
        '<m-o#5#1989>',
        'it den Einstellungen UNIPOL./FIX.SETPT oder BIPO',
        '<c-o#1#1990>',
        'L./FIX.',
        '<m-o#3#1991>',
        'SETPT',
        '<c-o#10#1982>',
        ', kann ',
        '<m-o#3#1992>',
        'das ',
        '<c-o#6#1983>',
        'setpoint',
        '<c-c#5#1983>',
        ' au',
        '<i-o#13#1986>',
        'c',
        '<c-o#18#1987>',
        'h',
        '<m-o#19#1988>',
        ' ',
        '<m-c#3#1992>',
        '<c-o#16#1984>',
        'über',
        '<i-c#10#1982>',
        ' Anschlüssen',
        '<c-c#16#1984>',
        ' ausgew',
        '<c-c#13#1986>',
        'ählt werden (fest',
        '<c-c#18#1987>',
        'es se',
        '<m-c#3#1991>',
        '<c-c#1#1990>',
        'tpoi',
        '<m-c#5#1989>',
        '<m-o#8#1993>',
        'nt).',
        '<m-c#19#1988>',
        '<m-c#8#1993>',
    ];

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('en', 'de')
            ->addUploadData("id,source,target\n" . '1,"source not needed here","' . self::CSV_TARGET . '"' . "\n" . '2,"zeile 2","row 2"')
            ->addTaskConfig('runtimeOptions.import.fileparser.csv.active', '1');
    }

    /**
     * Check imported data and add MQM to the target by editing it
     */
    public function testEditingSegmentWithMqm()
    {
        $task = static::api()->getTask();
        //open task for whole testcase
        static::api()->setTaskToEdit($task->id);

        //get segment list
        $segments = static::api()->getSegments();
        $segToEdit = $segments[0];

        //asserts that our content was imported properly
        $this->assertEquals(self::CSV_TARGET, $segToEdit->targetEdit);

        $editedData = $this->compileMqmTags($this->testData);
        static::api()->saveSegment($segToEdit->id, $editedData);

        //editing second segment
        $segToEdit = $segments[1];

        $test2 = [
            'nice',
            '<c-c#6#1>', //wrong open close order!
            'test',
            '<c-o#3#2>',
            'data',
            '<c-o#3#3>', //overlapping here
            'to',
            '<c-c#3#2>', //and here
            'test',
            '<c-o#6#1>', //wrong open close order!
            'wrong',
            '<c-c#3#3>',
            'order',
        ];
        static::api()->saveSegment($segToEdit->id, $this->compileMqmTags($test2));
    }

    /**
     * In our above testdata the mqm img tags were replaced for better readability
     * this method creates the img tags out of the meta annotation
     * @return string
     */
    protected function compileMqmTags(array $data)
    {
        //replacing img tags for better readability!
        $severity = [
            'c' => 'critical',
            'm' => 'major',
            'i' => 'minor',
        ];
        $tags = [
            'o' => 'open',
            'c' => 'close',
        ];
        $dir = [
            'o' => 'left',
            'c' => 'right',
        ];

        return join('', array_map(function ($tag) use ($severity, $tags, $dir) {
            return preg_replace_callback('/<([a-z])-([a-z])#([0-9]+)#([0-9]+)>/', function ($hit) use ($severity, $tags, $dir) {
                $type = $hit[3];
                $id = $hit[4];
                $css = $severity[$hit[1]] . ' qmflag ownttip ' . $tags[$hit[2]] . ' qmflag-' . $type;
                $img = '/modules/editor/images/imageTags/qmsubsegment-' . $type . '-' . $dir[$hit[2]] . '.png';

                return sprintf('<img  class="%s" data-t5qid="ext-%s" data-comment="" src="%s" />', $css, $id, $img);
            }, $tag);
        }, $data));
    }

    /**
     * test if MQM tags are as expected in exported data
     * @depends testEditingSegmentWithMqm
     */
    public function testExport()
    {
        $task = static::api()->getTask();
        //start task export
        $this->checkExport($task, 'editor/task/export/id/' . $task->id, 'cascadingMqm-export-assert-equal.csv');
        //start task export with diff
        $this->checkExport($task, 'editor/task/export/id/' . $task->id . '/diff/1', 'cascadingMqm-exportdiff-assert-equal.csv');
    }

    /**
     * tests the export results
     * @param string $exportUrl
     * @param string $fileToCompare
     */
    protected function checkExport(stdClass $task, $exportUrl, $fileToCompare)
    {
        static::api()->get($exportUrl);

        //get the exported file content
        // TODO FIXME: write a Helper API to check the export, this code-sequence is used in several tests ...
        $path = static::api()->getTaskDataDirectory();
        $pathToZip = $path . 'export.zip';
        $this->assertFileExists($pathToZip);
        $exportedFileContent = static::api()->getFileContentFromZipPath($pathToZip, '/apiTest.csv');
        // get the expected content
        $expectedResult = static::api()->getFileContent($fileToCompare, $exportedFileContent);
        $foundIds = [];

        //since the mqm ids are generated on each test run differently,
        //we have to replace them, by a unified counter, so that we can compare both files.
        //Just replacing the ids with a fixed text is no solution, since we can not recognize nesting errors then.
        $idReplacer = function ($matches) use (&$foundIds) {
            //since matches array is not filled up on first matches,
            //we just have to check the length of the matches
            $numMatches = count($matches);
            if ($numMatches == 5 && $matches[4] !== '') {
                $id = $matches[4];
                $box = 'idref=""%s""';
            } elseif ($numMatches == 4 && $matches[2] !== '' && $matches[3] !== '') {
                $id = $matches[2];
                $box = 'xml:id=""xoverlappingTagId-%s_' . $matches[3] . '""';
            } elseif ($numMatches == 2 && $matches[1] !== '') {
                $id = $matches[1];
                $box = 'xml:id=""x%s""';
            } else {
                error_log('ID MATCHING FAILED: ' . print_r($matches, 1));
            }
            $key = array_search($id, $foundIds, true);
            if ($key === false) {
                $key = count($foundIds);
                $foundIds[] = $id;
            }

            return sprintf($box, $key);
        };
        $regex = '/xml:id=""x([0-9]+)""|xml:id=""xoverlappingTagId-([0-9]+)_([0-9]+)""|idref=""([0-9]+)""/';

        $foundIds = [];
        $expectedResult = preg_replace_callback($regex, $idReplacer, $expectedResult);
        $foundIds = [];
        $exportedFileContent = preg_replace_callback($regex, $idReplacer, $exportedFileContent);
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFileContent), 'Exported result does not equal to ' . $fileToCompare);
    }
}
