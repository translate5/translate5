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

use MittagQI\Translate5\Task\Reimport\ZipDataProvider;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\Import\TermCollectionResource;

/***
 * Test the reimport and package export features.
 * Task will be imported alongside termcollection an opentm2 memory.
 *
 */
class Translate3117Test extends editor_Test_JsonTest {

    protected static array $forbiddenPlugins = [
    ];

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init'
    ];

    protected static array $requiredRuntimeOptions = [
        'import.xlf.preserveWhitespace' => 0,
        'runtimeOptions.import.xlf.ignoreFramingTags' => 'all'
    ];
 
    protected static bool $setupOwnCustomer = true;
    
    protected static string $setupUserLogin = 'testmanager';

    /***
     * Segment target on import
     * @var array|string[]
     */
    private static array $segmentsOnImport = [
        '1' => 'Bar',
        '2' => 'Translation for segment 2',
        '3' => 'Translation for Segment Three',
        '4' => 'Bar',
        '5' => 'Gjb',
        '6' => 'Guerr'
    ];

    /***
     * Segment target on reimport
     * @var array|string[]
     */
    private static array $segmentsOnReimport = [
        '1' => 'First segment is changed after reimport',
        '2' => 'Translation for segment 2',
        '3' => 'Translation for Segment Three',
        '4' => 'Bar reimport',
        '5' => 'Gjb reimport 2 segment',
        '6' => 'Guerr reimport of the 3 segment.'
    ];

    protected static TermCollectionResource $termCollection;

    protected static array $exportPackageStructure = [
        'tbx/',
        'reference/',
        'tmx/',
        'tmx/OpenTm2-Translate3117Test-1.tmx',
        'workfiles/',
        'workfiles/Task-en-de.html.xlf',
        'workfiles/Level1/',
        'workfiles/Level1/Task-en-de.html.xlf'
    ];

    /***
     * Import archive path on the disk
     * @var string
     */
    protected static string $importArchive;

    protected static function setupImport(Config $config): void
    {

        $ownCustomerId = static::$ownCustomer->id;

        self::$termCollection = $config
            ->addLanguageResource('termcollection', 'Term.tbx', $ownCustomerId)
            ->addDefaultCustomerId($ownCustomerId);

        $config
            ->addLanguageResource('opentm2', 'resource1.tmx', $ownCustomerId, 'en', 'de')
            ->addDefaultCustomerId($ownCustomerId);

        $config
            ->addTask('en', 'de', $ownCustomerId, 'Import.zip')
            ->setToEditAfterImport();
    }

    /**
     */
    public function testReimport()
    {
        $importSegments = static::api()->getSegmentsWithBasicData();

        // validate the value on import
        foreach ($importSegments as $segment){
            $expected = self::$segmentsOnImport[$segment['segmentNrInTask']];
            self::assertEquals($expected,$segment['targetEditToSort'],'Segment does not match the expected import value');
        }

        $taskId = static::api()->reloadTask()->id;

        static::api()->setTaskToOpen();

        static::api()->addFile(ZipDataProvider::UPLOAD_FILE_FIELD, static::api()->getFile('Reimport.zip'), 'application/data');
        static::api()->post('editor/taskid/'.$taskId.'/file/package');

        static::api()->setTaskToEdit();

        $reimportSegments = static::api()->getSegmentsWithBasicData();

        // validate the value on import
        foreach ($reimportSegments as $segment){
            $expected = self::$segmentsOnReimport[$segment['segmentNrInTask']];
            self::assertEquals($expected,$segment['targetEditToSort'],'Segment does not match the expected import value');
        }
    }

    public function testExportPackage(){
        static::api()->setTaskToOpen();

        $task = static::api()->getTask();
        $response = static::api()->get('editor/task/export/id/'.$task->id.'?format=package');

        self::$importArchive = tempnam(sys_get_temp_dir(), 'PackageExport');

        file_put_contents(self::$importArchive, $response->getBody());

        $zip = new ZipArchive();
        self::assertEquals(true, $zip->open(self::$importArchive),'Unable to open the exported zip archive');

        // The term collection name is dynamic -> add to package structure the name to be checked for tbx
        static::$exportPackageStructure[] = 'tbx/'.self::$termCollection->getId().'.tbx';

        // reimport file is supported by the segment processor
        for ($idx = 0; $zipFile = $zip->statIndex($idx); $idx++) {
            self::assertContains($zipFile['name'],static::$exportPackageStructure,'The export file structure is not as expected');
        }
    }

    public static function afterTests(): void
    {
        if(isset(self::$importArchive)){
            unlink(self::$importArchive);
        }
        parent::afterTests();
    }

}
