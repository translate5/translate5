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

/**
 * SegmentCommentRoundtripTest imports a SDLXLIFF file with comments, adds new comments and export the file again
 */
class SegmentCommentRoundtripTest extends editor_Test_JsonTest {

    protected static bool $termtaggerRequired = true;

    protected static array $forbiddenPlugins = [
        'editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap',
        'editor_Plugins_NoMissingTargetTerminology_Bootstrap'
    ];

    protected static array $requiredRuntimeOptions = [
        'editor.export.exportComments' => 1,
        'import.sdlxliff.applyChangeMarks' => 1,
        'import.sdlxliff.importComments' => 1,
        'customers.anonymizeUsers' => 0,
    ];
    
    public static function beforeTests(): void {

        $task = array(
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        );

        self::assertAppState();

        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');

        $zipfile = static::api()->zipTestFiles('testfiles/','XLF-test.zip');
        static::api()->addImportFile($zipfile);
        static::api()->import($task);

        static::api()->addUser('testlector');

        //login in beforeTests means using this user in whole testcase!
        static::api()->login('testlector');

        $task = static::api()->getTask();
        //open task for whole testcase
        static::api()->setTaskToEdit($task->id);

        static::assertConfigs($task->taskGuid);
    }

    /**
     * Testing some segment values directly after import
     */
    public function testImportedContent() {
        $jsonFileName = 'expectedSegments.json';
        $segments = static::api()->getSegments($jsonFileName, 100);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');
        $segmentIds = array_column($segments, 'id');
        $comments = $this->getCommentsForTest($segmentIds);
        $this->assertCommentsEqualsJsonFile('expectedComments.json', $comments, 'Imported comments are not as expected!');
    }

    /**
     * Returns all comments for the current task
     * @param array $segmentIds
     * @return array
     */
    protected function getCommentsForTest(array $segmentIds) {
        $comments = [];
        foreach($segmentIds as $id) {
            $commentsBySegment = static::api()->getJson('editor/comment?segmentId='.$id);
            $comments[] = $commentsBySegment;
        }
        return $comments;
    }

    /**
     * Test adding comments via translate5, and check if they are added properly
     */
    public function testAddNewComments() {
        $segments = static::api()->getSegments(null, 100);
        $segmentIds = array_column($segments, 'id');
        foreach($segmentIds as $idx => $segmentId) {
            $comment = [
                'isEditable'    => true,
                'segmentId'     => $segmentId,
                'userName'      => 'A Test users name',
                'comment'       => 'A test comment for segment '.$idx,
            ];
            static::api()->postJson('editor/comment', $comment);
        }
        $jsonFileName = 'expectedSegmentsAfterAdd.json';
        $segments = static::api()->getSegments($jsonFileName, 100);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');
        $segmentIds = array_column($segments, 'id');
        $comments = $this->getCommentsForTest($segmentIds);
        $this->assertCommentsEqualsJsonFile('expectedCommentsAfterAdd.json', $comments, 'Imported comments are not as expected!', true);
    }

    /**
     * tests the export results
     * @depends testAddNewComments
     */
    public function testExport() {
        static::api()->login('testmanager');
        $task = static::api()->getTask();
        //start task export

        static::api()->get('editor/task/export/id/'.$task->id);

        //get the exported file content
        $path = static::api()->getTaskDataDirectory();

        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);

        $this->_testExportSdlXliff($pathToZip);
        $this->_testExportMemoQXliff($pathToZip);
    }

    protected function _testExportSdlXliff(string $pathToZip) {
        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, static::api()->getTask()->taskGuid.'/01-sdlxliff-en-de.sdlxliff');
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
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to export-assert.sdlxliff');
    }

    protected function _testExportMemoQXliff(string $pathToZip) {
        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, static::api()->getTask()->taskGuid.'/02-memoq-de-en.mqxliff');
        $expectedResult = static::api()->getFileContent('export-assert.mqxliff', $exportedFile);
        //Since we replace only our own comments, we can leave Medium and 1.0 as fixed string, since they are currently not modifiable by translate5
        $s = [
            '/<mq:comment id="[0-9abcdef-]{36}" creatoruser="lector test" time="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z"/'
        ];
        $r = [
            '<mq:comment id="NOT-TESTABLE" creatoruser="lector test" time="NOT-TESTABLE"'
        ];
        $result = preg_replace($s, $r, [$exportedFile, $expectedResult]);
        $exportedFile = $result[0];
        $expectedResult = $result[1];
        //file_put_contents('/home/tlauria/foo-export.mqxliff', $exportedFile);
        //file_put_contents('/home/tlauria/foo-expect.mqxliff', $expectedResult);
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to export-assert.mqxliff');
    }

    public static function afterTests(): void {
        $task = static::api()->getTask();
        static::api()->deleteTask($task->id, 'testmanager', 'testlector');
    }
}