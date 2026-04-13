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

use MittagQI\Translate5\Task\Reimport\DataProvider\AbstractDataProvider;
use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\Import\Exception;
use MittagQI\Translate5\Test\Import\LanguageResource;
use MittagQI\Translate5\Test\JsonTestAbstract;

/**
 * Test the reimport and package export features.
 * Task will be imported alongside termcollection and t5memory memory.
 */
class Translate3117Test extends JsonTestAbstract
{
    protected static array $forbiddenPlugins = [
    ];

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
        'editor_Plugins_TrackChanges_Init',
    ];

    protected static array $requiredRuntimeOptions = [
        'import.xlf.preserveWhitespace' => 0,
        'import.xlf.ignoreFramingTags' => 'paired',
    ];

    protected static bool $setupOwnCustomer = true;

    protected static TestUser $setupUserLogin = TestUser::TestManager;

    /**
     * Segment target on import
     */
    private static array $segmentsOnImport = [
        '1' => 'FIRST Bar',
        '2' => 'SECOND Translation for segment 2',
        '3' => 'THIRD Translation for Segment Three',
        '4' => 'FOURTH Bar',
        '5' => 'FIFTH Gjb',
        '6' => 'SIXTH Guerr',
    ];

    /**
     * Segment target on reimport
     */
    private static array $segmentsOnReimport = [
        '1' => 'FIRST First segment is changed after reimport',
        '2' => 'SECOND Translation for segment 2',
        '3' => 'THIRD Translation for Segment Three',
        '4' => 'FOURTH Bar reimport',
        '5' => 'FIFTH Gjb reimport 2 segment',
        '6' => 'SIXTH Guerr reimport of the 3 segment.',
    ];

    protected static LanguageResource $termCollection;

    protected static array $exportPackageStructure = [
        'tbx/',
        'reference/',
        'tmx/',
        'workfiles/',
        'workfiles/secondary-file.xlf',
        'workfiles/Level1/',
        'workfiles/Level1/primary-file.xlf',
    ];

    /**
     * Import directory path on the disk
     */
    protected static string $importDir;

    protected static function setupImport(Config $config): void
    {
        $ownCustomerId = static::$ownCustomer->id;

        self::$termCollection = $config
            ->addLanguageResource('termcollection', 'terms.tbx', $ownCustomerId)
            ->addDefaultCustomerId($ownCustomerId);

        $config->addTask('en', 'de', $ownCustomerId, 'import.zip');
    }

    /**
     * @throws Exception
     * @throws Zend_Http_Client_Exception
     */
    public function testExportPackage()
    {
        static::api()->setTaskToOpen();

        $task = static::api()->getTask();
        static::api()->get('editor/task/export/id/' . $task->id . '?format=package');
        $response = static::api()->getLastResponseDecodeed();
        self::assertEmpty(isset($response->error), 'There was an error on package export. Check the error log for more info.');

        $workerId = $response->workerId;

        $statusCheckRoute = 'editor/task/packagestatus?workerId=' . $workerId;
        static::api()->get($statusCheckRoute);

        $response = static::api()->getLastResponseDecodeed();
        self::assertEmpty(isset($response->error), 'There was an error on package export. Check the error log for more info.');

        $fileAvailable = $response->file_available ?? '';
        $checkCount = 20;
        while (empty($fileAvailable)) {
            if ($checkCount === 0) {
                self::markTestIncomplete('Package not available after maximum amount of status checks');
            }

            sleep(1);

            static::api()->get($statusCheckRoute);
            $response = static::api()->getLastResponseDecodeed();
            self::assertEmpty(isset($response->error), 'There was an error on package export. Check the error log for more info.');

            $fileAvailable = $response->file_available ?? '';
            $checkCount--;
        }
        $downloadLink = $response->download_link ?? '';
        if (empty($downloadLink)) {
            self::markTestIncomplete('No download link available in the package status response.');
        }

        $tempdir = tempnam(sys_get_temp_dir(), '');
        // tempnam creates a temp file with arbitrary name
        if (file_exists($tempdir)) {
            unlink($tempdir);
            usleep(100000); // very unlikely - but: unlink will take a while to really delete but return before
        }
        mkdir($tempdir);

        self::$importDir = $tempdir;
        $importArchive = $tempdir . '/TmpPackageExport.zip';

        $response = static::api()->get($downloadLink);

        file_put_contents($importArchive, $response->getBody());

        $zip = new ZipArchive();
        self::assertEquals(true, $zip->open($importArchive), 'Unable to open the exported zip archive');

        // The term collection name is dynamic -> add to package structure the name to be checked for tbx
        static::$exportPackageStructure[] = 'tbx/' . self::$termCollection->getId() . '.tbx';

        // reimport file is supported by the segment processor
        for ($idx = 0; $zipFile = $zip->statIndex($idx); $idx++) {
            self::assertContains(
                $zipFile['name'],
                static::$exportPackageStructure,
                'The export file structure is not as expected'
            );
        }

        $zip->extractTo(self::$importDir);
        unlink($importArchive);
    }

    /**
     * @throws Zend_Http_Client_Exception
     *
     * @depends testExportPackage
     */
    public function testReimport(): void
    {
        static::api()->setTaskToEdit();

        $importSegments = static::api()->getSegmentsWithBasicData();

        // validate the value on import
        foreach ($importSegments as $segment) {
            $expected = self::$segmentsOnImport[$segment['segmentNrInTask']];
            self::assertEquals($expected, $segment['targetEditToSort'], 'Segment does not match the expected import value');
        }

        $task = static::api()->reloadTask();
        static::api()->setTaskToOpen();

        // prepare import file
        $file1 = self::$importDir . '/workfiles/Level1/primary-file.xlf';
        $file2 = self::$importDir . '/workfiles/secondary-file.xlf';
        $content1 = file_get_contents($file1);
        $content2 = file_get_contents($file2);
        // replace segments with changed contents
        foreach (self::$segmentsOnImport as $id => $text) {
            $content1 = str_replace($text, self::$segmentsOnReimport[$id], $content1);
            $content2 = str_replace($text, self::$segmentsOnReimport[$id], $content2);
        }
        file_put_contents($file1, $content1);
        file_put_contents($file2, $content2);
        // create zip to send as reimport
        $reimportArchive = self::$importDir . '/TmpReimport.zip';
        $zip = new ZipArchive();
        $zip->open($reimportArchive, ZipArchive::CREATE);
        $zip->addFile($file1, 'workfiles/Level1/primary-file.xlf');
        $zip->addFile($file2, 'workfiles/secondary-file.xlf');
        $zip->close();
        // upload to reimport endpoint
        static::api()->addFile(
            AbstractDataProvider::UPLOAD_FILE_FIELD,
            $reimportArchive,
            'application/data'
        );
        static::api()->post('editor/taskid/' . $task->id . '/file/package');
        $this->waitForTaskOperation($task);

        // fetch the changed segments
        static::api()->setTaskToEdit();
        $reimportSegments = static::api()->getSegmentsWithBasicData();

        // validate the reimported segments
        foreach ($reimportSegments as $segment) {
            $id = (string) $segment['segmentNrInTask'];
            self::assertEquals(
                self::$segmentsOnReimport[$id],
                $segment['targetEditToSort'],
                'Segment does not match the expected import value'
            );
            // testing if trackchanges were applied properly ...
            if (self::$segmentsOnReimport[$id] !== self::$segmentsOnImport[$id]) {
                // there must be a trackchanges-node
                self::assertStringContainsString('</ins>', $segment['targetEdit']);
                // the changed content is expected to have a single ins-tag with just simple content
                // and maybe a single del-tags - where just the tag needs to be remved
                $unchanged = preg_replace('~<ins[^>]*>[^<]+</ins>~', '', $segment['targetEdit']);
                $unchanged = preg_replace('~<del[^>]*>~', '', $unchanged);
                $unchanged = str_replace('</del>', '', $unchanged);
                self::assertEquals(self::$segmentsOnImport[$id], $unchanged);
            }
        }
    }

    public static function afterTests(): void
    {
        if (isset(self::$importDir)) {
            ZfExtended_Utils::recursiveDelete(self::$importDir);
        }
        parent::afterTests();
    }
}
