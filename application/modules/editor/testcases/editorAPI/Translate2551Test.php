<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

class Translate2551Test extends JsonTestAbstract
{
    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
        'editor_Plugins_TrackChanges_Init',
    ];

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('de', 'en', static::getTestCustomerId())
            ->addUploadFolder('testfiles');
    }

    /**
     * @throws \MittagQI\Translate5\Test\Import\Exception
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     */
    public function testFileReimport()
    {
        $task = static::getTask();
        $route = '/editor/taskid/' . $task->getId() . '/file/';

        $this->api()->get($route);

        $files = $this->api()->getLastResponseDecodeed() ?? null;

        self::assertNotEmpty($files, 'No files found for the uploaded task.');

        $files = $files[0]; // the files file will be replaced

        $file = 'reimport.xliff';
        static::api()->addFile('fileReimport', static::api()->getFile($file), "application/xml");

        static::api()->postJson($route, [
            'fileId' => $files->id,
        ], null, false, true);

        // wait for reimport worker
        $this->waitForWorker(MittagQI\Translate5\Task\Reimport\Worker::class, $task);

        self::api()->setTaskToEdit();
        $segmentsActual = static::api()->getSegmentsWithBasicData();
        self::api()->setTaskToOpen();

        static::api()->isCapturing() && file_put_contents(
            static::api()->getFile('expected.json', null, false),
            json_encode($segmentsActual, JSON_PRETTY_PRINT)
        );

        $expected = static::api()->getFileContent('expected.json');

        $this->compareSegments($segmentsActual, $expected);
    }

    /**
     * Compare the segments with protected tags
     * @throws ReflectionException
     */
    public function compareSegments(array $actual, array $expected): void
    {
        $segmentTagger = ZfExtended_Factory::get(editor_Models_Segment_InternalTag::class);
        $actualFiltered = [];

        foreach ($actual as $a) {
            $actualFiltered[$a['segmentNrInTask']] = $segmentTagger->toDebug($a['targetEdit']);
        }

        foreach ($expected as $e) {
            static::assertEquals(
                $segmentTagger->toDebug($e->targetEdit),
                $actualFiltered[$e->segmentNrInTask],
                'The compared segment [#' . $e->segmentNrInTask . '] content is not equal'
            );
        }
    }
}
