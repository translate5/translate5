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

/**
 * XlfSegmentLinesPixelLengthTest imports a simple task and checks imported values about the segment lengths,
 * edits segments and checks then the edited ones again on correct content
 */
class XlfSegmentLinesPixelLengthTest extends editor_Test_JsonTest {

    protected static array $forbiddenPlugins = [
        'editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap',
        'editor_Plugins_NoMissingTargetTerminology_Bootstrap'
    ];

    protected static array $requiredRuntimeOptions = [
        'import.xlf.preserveWhitespace' => 0
    ];

    protected static string $setupUserLogin = 'testlector';

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('en', 'de')
            ->addUploadFolder('testfiles')
            ->setToEditAfterImport();
    }

    /**
     * Testing segment values directly after import
     * Other constellations of the segment length count are implicitly tested in the XlfImportTest!
     */
    public function testSegmentValuesAfterImport() {
        //get segment list (just the ones of the first file for that tests)
        $jsonFileName = 'expectedSegments.json';
        $segments = static::api()->getSegments($jsonFileName, 20);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');
    }
    
    /**
     * @depends testSegmentValuesAfterImport
     */
    public function testSegmentEditing() {
        //get segment list (just the ones of the first file for that tests)
        $segments = static::api()->getSegments(null, 20);
        $this->assertNotEmpty($segments, 'No segments are found in the Task!');
        
        require_once 'Models/Segment/TagAbstract.php';
        require_once 'Models/Segment/InternalTag.php';

        $failingSegments = [
            1 => '{"targetEdit": {
                "segmentTooManyLines": "There are 4 lines in the segment, but only 3 lines are allowed.",
                "segmentLinesTooLong": "Not all lines in the segment match the given maximal length: 1: 252; 3: 396; 4: 468"
            }}',
            3 => '{"targetEdit": {
                "segmentLinesTooLong": "Not all lines in the segment match the given maximal length: 1: 995"
            }}',
            4 => '{"targetEdit": {
                "segmentLinesTooShort": "Not all lines in the segment match the given minimal length: 1: 84",
                "segmentLinesTooLong": "Not all lines in the segment match the given maximal length: 2: 1223"
            }}',
            5 => '{"targetEdit": {
                "segmentLinesTooLong": "Not all lines in the segment match the given maximal length: 2: 960"
            }}',
        ];

        foreach($segments as $segToEdit) {
            if(empty($segToEdit->editable)) {
                continue;
            }
            if(empty($segToEdit->targetEdit)) {
                $contentToUse = $segToEdit->source;
            }
            else {
                $contentToUse = $segToEdit->targetEdit;
            }
            $editedData = $this->getEditedData($contentToUse, $segToEdit->segmentNrInTask);

            if(in_array($segToEdit->segmentNrInTask, array_keys($failingSegments))) {
                static::api()->allowHttpStatusOnce(422);
                $result = (array) static::api()->saveSegment($segToEdit->id, $editedData);
                $this->assertEquals(422, $result['httpStatus'], 'Segment ['.$segToEdit->segmentNrInTask.'] is returning wrong HTTP Status.');
                $this->assertEquals(json_decode($failingSegments[$segToEdit->segmentNrInTask]), $result['errors'], 'Segment ['.$segToEdit->segmentNrInTask.'] is returning wrong or no error.');
            }
            else {
                static::api()->saveSegment($segToEdit->id, $editedData);
            }

        }

        $jsonFileName = 'expectedSegmentsEdited.json';
        $segments = static::api()->getSegments($jsonFileName, 20);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Edited segments are not as expected!');

        $task = static::api()->getTask();
        //start task export
        $this->checkExport($task, 'editor/task/export/id/'.$task->id, 'segmentlinespixellength-en-de.xlf', 'expected-export.xlf');
    }
    
    /**
     * returns the "edited" data for testing
     * @param string $contentToUse
     * @param int $segmentNrInTask
     * @return string
     */
    protected function getEditedData (string $contentToUse, int $segmentNrInTask): string {
        switch ($segmentNrInTask) {
            case 1: // = not ok: segmentTooManyLines, segmentLinesTooLong
                $contentEdited = "Edited: Dies ist ein <div class=\"single 6861726452657475726e2f newline internal-tag ownttip\"><span title=\"&lt;2/&gt;: Newline\" class=\"short\">&lt;2/&gt;</span><span data-originalid=\"hardReturn\" data-length=\"1\" class=\"full\">\u21b5</span></div>einfacher Satz <div class=\"single 6861726452657475726e2f newline internal-tag ownttip\"><span title=\"&lt;3/&gt;: Newline\" class=\"short\">&lt;3/&gt;</span><span data-originalid=\"hardReturn\" data-length=\"1\" class=\"full\">\u21b5</span></div>(aus task-template: maxWidth 200,<div class=\"single 6861726452657475726e2f newline internal-tag ownttip\"><span title=\"&lt;4/&gt;: Newline\" class=\"short\">&lt;4/&gt;</span><span data-originalid=\"hardReturn\" data-length=\"1\" class=\"full\">\u21b5</span></div> <div class=\"single 73706163652074733d22323022206c656e6774683d2231222f space internal-tag ownttip\"><span title=\"&lt;1/&gt;: 1 whitespace character\" class=\"short\">&lt;1/&gt;</span><span data-originalid=\"space\" data-length=\"1\" class=\"full\">\u00b7</span></div>maxNumberOfLines 3, size-unit pixel).";
                break;
            case 2: // = ok: segmentLengthValid
                $contentEdited = "Edited: Dasselbe<div class=\"single 6861726452657475726e2f newline internal-tag ownttip\"><span title=\"&lt;3/&gt;: Newline\" class=\"short\">&lt;3/&gt;</span><span data-originalid=\"hardReturn\" data-length=\"1\" class=\"full\">\u21b5</span></div>in<div class=\"single 6861726452657475726e2f newline internal-tag ownttip\"><span title=\"&lt;2/&gt;: Newline\" class=\"short\">&lt;2/&gt;</span><span data-originalid=\"hardReturn\" data-length=\"1\" class=\"full\">\u21b5</span></div>kurz.";
                break;
            case 3: // = not ok: segmentLinesTooLong
                $contentEdited = "Edited: Dies ist ein Satz zum Testen f\u00fcr maximal 1 Zeile und maxWidth 600. <div class=\"single 73706163652074733d2232303230323022206c656e6774683d2233222f space internal-tag ownttip\"><span title=\"&lt;1/&gt;: 3 whitespace characters\" class=\"short\">&lt;1/&gt;</span><span data-originalid=\"space\" data-length=\"3\" class=\"full\">\u00b7\u00b7\u00b7</span></div>";
                break;
            case 4: // = not ok: segmentLinesTooShort, segmentLinesTooLong
                $contentEdited = "Edited:<div class=\"single 6861726452657475726e2f newline internal-tag ownttip\"><span title=\"&lt;2/&gt;: Newline\" class=\"short\">&lt;2/&gt;</span><span data-originalid=\"hardReturn\" data-length=\"1\" class=\"full\">\u21b5</span></div>Dies\u00a0ist\u00a0ein Satz zum\u00a0Testen f\u00fcr maximal 2 Zeilen, minwidth 100, maxWidth 300. <div class=\"single 73706163652074733d2232303230323022206c656e6774683d2233222f space internal-tag ownttip\"><span title=\"&lt;1/&gt;: 3 whitespace characters\" class=\"short\">&lt;1/&gt;</span><span data-originalid=\"space\" data-length=\"3\" class=\"full\">\u00b7\u00b7\u00b7</span></div>";
                break;
            case 5: // = not ok: segmentLinesTooLong
                $contentEdited = "Edited: Dies ist ein Satz<div class=\"single 6861726452657475726e2f newline internal-tag ownttip\"><span title=\"&lt;2/&gt;: Newline\" class=\"short\">&lt;2/&gt;</span><span data-originalid=\"hardReturn\" data-length=\"1\" class=\"full\">\u21b5</span></div>\u00a0zum\u00a0Testen f\u00fcr maximal 2 Zeilen, minwidth 100, maxWidth 300. <div class=\"single 73706163652074733d2232303230323022206c656e6774683d2233222f space internal-tag ownttip\"><span title=\"&lt;1/&gt;: 3 whitespace characters\" class=\"short\">&lt;1/&gt;</span><span data-originalid=\"space\" data-length=\"3\" class=\"full\">\u00b7\u00b7\u00b7</span></div>";
                break;
            case 6: // ok: segmentLengthValid
                $contentEdited = "Edited: Dies ist ein Satz zum Testen f\u00fcr<div class=\"single 6861726452657475726e2f newline internal-tag ownttip\"><span title=\"&lt;2/&gt;: Newline\" class=\"short\">&lt;2/&gt;</span><span data-originalid=\"hardReturn\" data-length=\"1\" class=\"full\">\u21b5</span></div>maximal 2 Zeilen\u00a0und maxWidth 600.";
                break;
            case 7: // ok: segmentLengthValid
                $contentEdited = "Edited: Dies ist ein Satz zum Testen f\u00fcr<div class=\"single 6861726452657475726e2f newline internal-tag ownttip\"><span title=\"&lt;3/&gt;: Newline\" class=\"short\">&lt;3/&gt;</span><span data-originalid=\"hardReturn\" data-length=\"1\" class=\"full\">\u21b5</span></div>maximal 3 Zeilen<div class=\"single 6861726452657475726e2f newline internal-tag ownttip\"><span title=\"&lt;2/&gt;: Newline\" class=\"short\">&lt;2/&gt;</span><span data-originalid=\"hardReturn\" data-length=\"1\" class=\"full\">\u21b5</span></div> und maxWidth 600.";
                break;
            case 8: // ok: segmentLengthValid
                $contentEdited = "Edited: Dies ist ein Satz zum Testen<div class=\"single 6861726452657475726e2f newline internal-tag ownttip\"><span title=\"&lt;2/&gt;: Newline\" class=\"short\">&lt;2/&gt;</span><span data-originalid=\"hardReturn\" data-length=\"1\" class=\"full\">\u21b5</span></div>f\u00fcr maxWidth 600 und size-unit char.";
                break;
            default:
                $contentEdited = 'Edited: ' . $contentToUse;
                break;
        }
        return $contentEdited;
    }
    
    /**
     * tests the export results
     * @param stdClass $task
     * @param string $exportUrl
     * @param string $fileToExport
     * @param string $fileToCompare
     */
    protected function checkExport(stdClass $task, $exportUrl, $fileToExport, $fileToCompare) {
        static::api()->login('testmanager');
        static::api()->get($exportUrl);

        //get the exported file content
        $path = static::api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, '/'.$fileToExport);
        //compare it
        $expectedResult = static::api()->getFileContent($fileToCompare);
        //file_put_contents('/home/tlauria/foo1.xlf', rtrim($expectedResult));
        //file_put_contents('/home/tlauria/foo2.xlf', rtrim($exportedFile));
        //file_put_contents('/home/tlauria/foo-'.$fileToCompare, rtrim($exportedFile));
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to '.$fileToCompare);
    }
}