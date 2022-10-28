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

/**
 */
class Translate2551Test extends editor_Test_JsonTest {

    protected static array $forbiddenPlugins = [
    ];

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init'
    ];

    protected static array $requiredRuntimeOptions = [
    ];
 
    protected static bool $setupOwnCustomer = false;
    
    protected static string $setupUserLogin = 'testmanager';
    
    protected static function setupImport(Config $config): void
    {

        $config
            ->addTask('de', 'en', static::getTestCustomerId())
            ->addUploadFolder('testfiles');
    }

    public function testFileReimport(){

        /*
        self::api()->setTaskToEdit();
        $segmentsExpected = static::api()->getSegmentsWithBasicData();
        self::api()->setTaskToOpen();

        static::api()->isCapturing() && file_put_contents(static::api()->getFile('expected.json', null, false), print_r($segmentsExpected,1));
*/
        $route = '/editor/taskid/' . $this->getTask()->getId() . '/file/';

        $this->api()->get($route);

        $files = $this->api()->getLastResponseDecodeed() ?? null;

        self::assertNotEmpty($files,'No files found for the uploaded task.');

        $files = $files[0];// the firs file will be replaced

        $file = 'reimport.xliff';
        static::api()->addFile('fileReimport', static::api()->getFile($file), "application/xml");

        static::api()->postJson($route, [
            'fileId' => $files->id,
            'taskGuid' => $this->getTask()->getTaskGuid()
        ], null, false, true);

        self::api()->setTaskToEdit();
        $segmentsActual = static::api()->getSegmentsWithBasicData();
        self::api()->setTaskToOpen();

        static::api()->isCapturing() && file_put_contents(static::api()->getFile('expected.json', null, false), json_encode($segmentsActual, JSON_PRETTY_PRINT));

        $expected = static::api()->getFileContent('expected.json');

        self::assertEquals(json_encode($expected, JSON_PRETTY_PRINT), json_encode($segmentsActual, JSON_PRETTY_PRINT), 'Segments are not as expected after xlif reimport');
    }

}
