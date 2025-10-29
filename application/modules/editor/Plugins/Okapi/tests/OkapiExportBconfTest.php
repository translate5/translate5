<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Test\Import\Bconf;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\JsonTestAbstract;

class OkapiExportBconfTest extends JsonTestAbstract
{
    private static Bconf $bconf1;

    private static Bconf $bconf2;

    /**
     * Just imports a bconf to test with
     */
    protected static function setupImport(Config $config): void
    {
        self::$bconf1 = $config->addBconf('TestBconf', 'typo3.bconf');
        self::$bconf2 = $config->addBconf('JsonBconf', 'json.bconf');
    }

    public function test10_BconfsImport()
    {
        static::assertStringStartsWith(
            'TestBconf',
            self::$bconf1->getName(),
            'Imported bconf\'s name is not like ' . 'TestBconf' . ' but ' . self::$bconf1->getName()
        );
        static::assertStringStartsWith(
            'JsonBconf',
            self::$bconf2->getName(),
            'Imported bconf\'s name is not like ' . 'JsonBconf' . ' but ' . self::$bconf2->getName()
        );
    }

    /**
     * Test imported with a custom JSON Bconf
     * @depends test10_BconfsImport
     */
    public function test20_OkapiJsonImport()
    {
        $config = static::getConfig();
        $config->import(
            $config
                ->addTask('en', 'de')
                ->setImportBconfId(self::$bconf2->getId())
                ->addUploadFile('workfiles/unity_en_newly_added_keys1.json')
                ->setToEditAfterImport()
        );
        $segments = static::api()->getSegments();
        $this->assertEquals(17, count($segments));
        $this->assertEquals('Green:', $segments[0]->source);
    }

    /**
     * Test imported with a custom Typo3 Bconf
     * @depends test10_BconfsImport
     */
    public function test30_OkapiTypo3Import()
    {
        $config = static::getConfig();
        $config->import(
            $config
                ->addTask('en', 'de')
                ->setImportBconfId(self::$bconf1->getId())
                ->addUploadFile('workfiles/export-contentelements-14104-EN.xliff.typo3')
                ->setToEditAfterImport()
        );

        $segments = static::api()->getSegments();
        $this->assertEquals(42, count($segments));
        $this->assertEquals('Reference', $segments[0]->source);
    }

    /**
     * Tests the typo3 export
     * @depends test30_OkapiTypo3Import
     */
    public function test40_OkapiTaskExport()
    {
        static::api()->get('editor/task/export/id/' . static::api()->getTask()->id . '/diff/1');

        //get the exported file content
        $path = static::api()->getTaskDataDirectory();
        $pathToZip = $path . 'export.zip';
        $this->assertFileExists($pathToZip);

        $adjustData = function (string $s): string {
            return rtrim(str_replace("\r\n", "\n", $s));
        };
        $exportedData = static::api()->getFileContentFromZipPath($pathToZip, 'export-contentelements-14104-EN.xliff.typo3');
        $expectedData = static::api()->getFileContent('workfiles/export-contentelements-14104-EN.xliff.typo3');

        $this->assertEquals($adjustData($expectedData), $adjustData($exportedData), 'Exported result does not equal to expected XLIFF content');
    }
}
