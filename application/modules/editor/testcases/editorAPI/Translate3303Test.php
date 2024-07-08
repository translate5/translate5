<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2023 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Test to validate if sub segments are imported and can be edited. Also validate if the edited content of the sub
 * segments is contained in the export
 */
class Translate3303Test extends JsonTestAbstract
{
    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('en', 'de')
            ->addUploadFolder('testfiles')
            ->setToEditAfterImport();
    }

    /**
     * @throws JsonException
     */
    public function testSegmentValuesAfterImport()
    {
        $jsonFileName = 'expectedSegments.json';
        $segments = static::api()->getSegments($jsonFileName, 47);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');
    }

    /**
     * @depends testSegmentValuesAfterImport
     */
    public function testSegmentEditing()
    {
        //get segment list
        $segments = static::api()->getSegments(null, 10);

        $contentForSegment1 = str_replace(
            [
                'f2-5 - A img tag is recommended to replaced like this:',
                'this is described in XLIFF 1.2 Representation Guide for HTML',
            ],
            [
                'Segment 1 part 1',
                'Segment 1 part 2',
            ],
            $segments[0]->source
        );

        $contentForSegment2 = str_replace(
            'This is the alt attribute content of the img represented as plain ph tag',
            'Segment 2 translation',
            $segments[1]->source
        );

        static::api()->saveSegment($segments[0]->id, $contentForSegment1);
        static::api()->saveSegment($segments[1]->id, $contentForSegment2);

        //check direct PUT result
        $jsonFileName = 'expectedSegments-edited.json';

        $segments = static::api()->getSegments($jsonFileName, 10);
        $this->assertModelsEqualsJsonFile('Segment', $jsonFileName, $segments, 'Imported segments are not as expected!');
    }

    /**
     * @depends testSegmentEditing
     */
    public function testExport()
    {
        static::api()->login('testmanager');
        $task = static::getTask();

        //start task export
        static::api()->get('editor/task/export/id/' . $task->getId());

        //get the exported file content
        $path = $task->getDataDirectory();
        $pathToZip = $path . 'export.zip';
        $this->assertFileExists($pathToZip);

        $exportFileName = 'export-TRANSLATE-3303-de-en.xlf';
        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, '/TRANSLATE-3303-de-en.xlf');

        if (static::api()->isCapturing()) {
            file_put_contents(static::api()->getFile($exportFileName, null, false), $exportedFile);
        }
        $expectedResult = static::api()->getFileContent($exportFileName);

        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to export-TRANSLATE-3303-de-en.xlf');
    }
}
