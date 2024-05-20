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
 * SegmentCommentRoundtripTest imports a SDLXLIFF file with comments, adds new comments and export the file again
 */
class SegmentCommentRoundtripTest extends editor_Test_JsonTest
{
    protected static bool $termtaggerRequired = true;

    protected static array $forbiddenPlugins = [
        'editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap',
        'editor_Plugins_NoMissingTargetTerminology_Bootstrap',
    ];

    protected static string $setupUserLogin = 'testlector';

    protected static function setupImport(Config $config): void
    {
        Zend_Registry::get('PluginManager')->setActive('TrackChanges', false);
        $config
            ->addTask('en', 'de')
            ->addUploadFolder('testfiles')
            ->setToEditAfterImport();
    }

    public static function beforeTests(): void
    {
        $requiredTaskConfigs = [
            'editor.export.exportComments' => 1,
            'import.xliff.importComments' => 1,
            'import.sdlxliff.applyChangeMarks' => 1,
            'import.sdlxliff.importComments' => 1,
            'runtimeOptions.export.xliff.commentAddTranslate5Namespace' => 1,
            'customers.anonymizeUsers' => 0,
        ];
        static::assertTaskConfigs(static::getTask()->getTaskGuid(), $requiredTaskConfigs);
    }

    /**
     * Testing some segment values directly after import
     */
    public function testImportedContent()
    {
        $jsonFileName = 'expectedSegments.json';
        $segments = static::api()->getSegments($jsonFileName, 100);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');
        $segmentIds = array_column($segments, 'id');
        $comments = $this->getCommentsForTest($segmentIds);

        foreach ($comments as $idx1 => $innerComments) {
            foreach ($innerComments as $idx2 => $comment) {
                if ($comment->userGuid === \MittagQI\Translate5\Task\Import\FileParser\Xlf\Comments::NOTE_USERGUID) {
                    $comment->created = $comment->modified = 'FAKED_NOT_POSSIBLE_ON_XLF_NOTEs';
                    $comments[$idx1][$idx2] = $comment;
                }
            }
        }

        $this->assertCommentsEqualsJsonFile(
            'expectedComments.json',
            $comments,
            'Imported comments are not as expected!'
        );
    }

    /**
     * Returns all comments for the current task
     * @return array
     */
    protected function getCommentsForTest(array $segmentIds)
    {
        $comments = [];
        foreach ($segmentIds as $id) {
            $commentsBySegment = static::api()->getJson('editor/comment?segmentId=' . $id);
            $comments[] = $commentsBySegment;
        }

        return $comments;
    }

    /**
     * Test adding comments via translate5, and check if they are added properly
     */
    public function testAddNewComments()
    {
        $segments = static::api()->getSegments(null, 100);
        $segmentIds = array_column($segments, 'id');
        foreach ($segmentIds as $idx => $segmentId) {
            $comment = [
                'isEditable' => true,
                'segmentId' => $segmentId,
                'userName' => 'A Test users name',
                'comment' => 'A test comment for segment ' . $idx,
            ];
            static::api()->postJson('editor/comment', $comment);
        }
        $jsonFileName = 'expectedSegmentsAfterAdd.json';
        $segments = static::api()->getSegments($jsonFileName, 100);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');
        $segmentIds = array_column($segments, 'id');
        $comments = $this->getCommentsForTest($segmentIds);
        $this->assertCommentsEqualsJsonFile(
            'expectedCommentsAfterAdd.json',
            $comments,
            'Imported comments are not as expected!',
            true
        );
    }

    /**
     * tests the export results
     * @depends testAddNewComments
     */
    public function testExport()
    {
        static::api()->login('testmanager');
        $task = static::api()->getTask();
        //start task export

        static::api()->get('editor/task/export/id/' . $task->id);

        //get the exported file content
        $path = static::api()->getTaskDataDirectory();

        $pathToZip = $path . 'export.zip';
        $this->assertFileExists($pathToZip);

        $this->_testExportSdlXliff($pathToZip);
        $this->_testExportMemoQXliff($pathToZip);
        $this->_testExportXliff($pathToZip);
        $this->_testAcrossXliff($pathToZip);
    }

    protected function _testExportSdlXliff(string $pathToZip)
    {
        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, '/01-sdlxliff-en-de.sdlxliff');
        $expectedResult = static::api()->getFileContent('export-assert.sdlxliff', $exportedFile);
        //Since we replace only our own comments, we can leave Medium and 1.0 as fixed string, since they are currently not modifiable by translate5
        $s = [
            '/<Comment severity="Medium" user="lector test" date="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}\+[0-9]{2}:[0-9]{2}" version="1\.0">/',
            '/sdl:cid="[0-9abcdef-]{36}"/',
            '/<cmt-def id="[0-9abcdef-]{36}">/',
        ];
        $r = [
            '<Comment severity="Medium" user="lector test" date="NOT-TESTABLE" version="1.0">',
            'sdl:cid="NOT-TESTABLE"',
            '<cmt-def id="NOT-TESTABLE">',
        ];
        $result = preg_replace($s, $r, [$exportedFile, $expectedResult]);
        $exportedFile = $result[0];
        $expectedResult = $result[1];
        //file_put_contents('/home/tlauria/foo-export.sdlxliff', $exportedFile);
        //file_put_contents('/home/tlauria/foo-expect.sdlxliff', $expectedResult);
        $expectedResult = str_replace("\r\n", "\n", rtrim($expectedResult));
        $exportedFile = str_replace("\r\n", "\n", rtrim($exportedFile));
        $this->assertEquals($expectedResult, $exportedFile, 'Exported result does not equal to export-assert.sdlxliff');
    }

    protected function _testExportMemoQXliff(string $pathToZip)
    {
        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, '/02-memoq-de-en.mqxliff');
        $expectedResult = static::api()->getFileContent('export-assert.mqxliff', $exportedFile);
        //Since we replace only our own comments, we can leave Medium and 1.0 as fixed string, since they are currently not modifiable by translate5
        $s = [
            '/<mq:comment id="[0-9abcdef-]{36}" creatoruser="lector test" time="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z"/',
        ];
        $r = [
            '<mq:comment id="NOT-TESTABLE" creatoruser="lector test" time="NOT-TESTABLE"',
        ];
        $result = preg_replace($s, $r, [$exportedFile, $expectedResult]);
        $exportedFile = $result[0];
        $expectedResult = $result[1];
        //file_put_contents('/home/tlauria/foo-export.mqxliff', $exportedFile);
        //file_put_contents('/home/tlauria/foo-expect.mqxliff', $expectedResult);
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to export-assert.mqxliff');
    }

    protected function _testExportXliff(string $pathToZip)
    {
        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, '/03-xlf-en-de.xliff');
        //file_put_contents(static::api()->getFile('export-assert.xlf.xliff', assert: false), $exportedFile);
        $expectedResult = static::api()->getFileContent('export-assert.xlf.xliff', $exportedFile);
        //file_put_contents(static::api()->getFile('export-assert.xlf.xliff', assert: false), $expectedResult);
        $result = preg_replace([
            '/translate5:time="[0-9-]+T[0-9:]+Z"/',
        ], [
            'translate5:time="NOTTESTABLEZ"',
        ], [
            $exportedFile,
            $expectedResult,
        ]);
        $exportedFile = $result[0];
        $expectedResult = $result[1];
        $this->assertEquals(
            rtrim($expectedResult),
            rtrim($exportedFile),
            'Exported result does not equal to export-assert.xlf.xliff'
        );
    }

    /**
     * @throws Exception
     */
    protected function _testAcrossXliff(string $pathToZip)
    {
        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, '/04-across-en-de.xliff');
        //file_put_contents(static::api()->getFile('export-assert.across.xliff', assert: false), $exportedFile);
        $expectedResult = static::api()->getFileContent('export-assert.across.xliff', $exportedFile);

        $s = [
            '~Comment from lector test \([0-9.: /]+\) in translate5~',
        ];
        $r = [
            'Comment from lector test (NOT TESTABLE) in translate5',
        ];
        $result = preg_replace($s, $r, [$exportedFile, $expectedResult]);
        $exportedFile = $result[0];
        $expectedResult = $result[1];

        //file_put_contents(static::api()->getFile('export-assert.across.xliff', assert: false), $expectedResult);
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to export-assert.across.xliff');
    }
}
