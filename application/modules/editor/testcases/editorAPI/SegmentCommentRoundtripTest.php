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

/**
 * SegmentCommentRoundtripTest imports a SDLXLIFF file with comments, adds new comments and export the file again
 */
class SegmentCommentRoundtripTest extends editor_Test_JsonTest {
    
    public static function setUpBeforeClass(): void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);

        $task = array(
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        );

        $appState = self::assertTermTagger();
        self::assertNotContains('editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap', $appState->pluginsLoaded, 'Plugin LockSegmentsBasedOnConfig should not be activated for this test case!');
        self::assertNotContains('editor_Plugins_NoMissingTargetTerminology_Bootstrap', $appState->pluginsLoaded, 'Plugin NoMissingTargetTerminology should not be activated for this test case!');

        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');

        $zipfile = $api->zipTestFiles('testfiles/','XLF-test.zip');
        $api->addImportFile($zipfile);
        $api->import($task);

        $api->addUser('testlector');

        //login in setUpBeforeClass means using this user in whole testcase!
        $api->login('testlector');

        $task = $api->getTask();
        //open task for whole testcase
        $api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
        
        $tests = array(
            'runtimeOptions.editor.export.exportComments' => 1,
            'runtimeOptions.import.sdlxliff.applyChangeMarks' => 1,
            'runtimeOptions.import.sdlxliff.importComments' => 1,
            'runtimeOptions.customers.anonymizeUsers' => 0,
        );
        self::$api->testConfig($tests, ['taskGuid' => $task->taskGuid]);
    }

    /**
     * Testing some segment values directly after import
     */
    public function testImportedContent() {
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=100');
        $segmentIds = array_column($segments, 'id');
        
        $this->assertSegmentsEqualsJsonFile('expectedSegments.json', $segments, 'Imported segments are not as expected!');

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
            $commentsBySegment = $this->api()->requestJson('editor/comment?segmentId='.$id);
            $comments[] = $commentsBySegment;
        }
        return $comments;
    }

    /**
     * Test adding comments via translate5, and check if they are added properly
     */
    public function testAddNewComments() {
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=100');
        $segmentIds = array_column($segments, 'id');
        foreach($segmentIds as $idx => $segmentId) {
            $comment = [
                'isEditable'    => true,
                'segmentId'     => $segmentId,
                'userName'      => 'A Test users name',
                'comment'       => 'A test comment for segment '.$idx,
            ];
            $this->api()->requestJson('editor/comment', 'POST', $comment);
        }

        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=100');
        $segmentIds = array_column($segments, 'id');
        
        $this->assertSegmentsEqualsJsonFile('expectedSegmentsAfterAdd.json', $segments, 'Imported segments are not as expected!');

        $comments = $this->getCommentsForTest($segmentIds);
        
        $this->assertCommentsEqualsJsonFile('expectedCommentsAfterAdd.json', $comments, 'Imported comments are not as expected!', true);
    }

    /**
     * tests the export results
     * @depends testAddNewComments
     */
    public function testExport() {
        self::$api->login('testmanager');
        $task = $this->api()->getTask();
        //start task export

        $this->api()->request('editor/task/export/id/'.$task->id);

        //get the exported file content
        $path = $this->api()->getTaskDataDirectory();

        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);

        $this->_testExportSdlXliff($pathToZip);
        $this->_testExportMemoQXliff($pathToZip);
    }

    protected function _testExportSdlXliff(string $pathToZip) {
        $exportedFile = $this->api()->getFileContentFromZipPath($pathToZip, $this->api()->getTask()->taskGuid.'/01-sdlxliff-en-de.sdlxliff');
        //file_put_contents('/home/tlauria/foo.sdlxliff', $exportedFile);
        $expectedResult = $this->api()->getFileContent('export-assert.sdlxliff');

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
        $exportedFile = $this->api()->getFileContentFromZipPath($pathToZip, $this->api()->getTask()->taskGuid.'/02-memoq-de-en.mqxliff');
        //file_put_contents('/home/tlauria/foo.mqxliff', $exportedFile);
        $expectedResult = $this->api()->getFileContent('export-assert.mqxliff');

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

    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testlector');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}