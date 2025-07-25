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
 * Testcase for TRANSLATE-2362 Mixing XLF id and rid values led to wrong tag numbering
 * For details see the issue.
 */
class InternalTranslationTaskTest extends JsonTestAbstract
{
    protected static TestUser $setupUserLogin = TestUser::TestLector;

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('de', 'it')
            ->addUploadFolder('testfiles')
            ->setToEditAfterImport();
    }

    /**
     * Testing segment values directly after import
     */
    public function testSegmentValuesAfterImport()
    {
        $jsonFileName = 'expectedSegments.json';
        $segments = static::api()->getSegments($jsonFileName, 10);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');
    }

    /**
     * @depends testSegmentValuesAfterImport
     */
    public function testSegmentEditing()
    {
        //get segment list
        $segments = static::api()->getSegments(null, 10);

        //test editing a prefilled segment
        $segToTest = $segments[0];
        $segToTest->targetEdit = 'Conversione del formato dei file';
        static::api()->saveSegment($segToTest->id, $segToTest->targetEdit);

        $segToTest = $segments[1];
        $segToTest->targetEdit = 'Ottimo link';
        static::api()->saveSegment($segToTest->id, $segToTest->targetEdit);

        $segToTest = $segments[2];
        $segToTest->targetEdit = 'Note';
        static::api()->saveSegment($segToTest->id, $segToTest->targetEdit);

        //check direct PUT result
        $jsonFileName = 'expectedSegments-edited.json';
        $segments = static::api()->getSegments($jsonFileName, 10);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Edited segments are not as expected!');
    }

    /**
     * tests the export results
     * @depends testSegmentEditing
     */
    public function testExport()
    {
        static::api()->login(TestUser::TestManager->value);
        $task = static::api()->getTask();
        //start task export

        static::api()->get('editor/task/export/id/' . $task->id);

        //get the exported file content
        $path = static::api()->getTaskDataDirectory();
        $pathToZip = $path . 'export.zip';
        $this->assertFileExists($pathToZip);

        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, '/subdir/it.xliff');
        if (static::api()->isCapturing()) {
            file_put_contents(static::api()->getFile('export-it.xliff', null, false), rtrim($exportedFile));
        }
        $expectedResult = static::api()->getFileContent('export-it.xliff');

        $this->assertEquals(
            rtrim($expectedResult),
            rtrim($exportedFile),
            'Exported result does not equal to export-it.xliff'
        );

        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, '/subdir/test.it.json');
        if (static::api()->isCapturing()) {
            file_put_contents(static::api()->getFile('export-test.it.json', null, false), rtrim($exportedFile));
        }
        $expectedResult = static::api()->getFileContent('export-test.it.json');

        $this->assertEquals(
            $expectedResult,
            json_decode($exportedFile),
            'Exported result does not equal to export-test.it.json'
        );
    }
}
