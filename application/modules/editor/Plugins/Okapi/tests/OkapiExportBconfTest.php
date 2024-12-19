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

use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
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

    public function test10_BConfsImport()
    {
        $bconf = new BconfEntity();
        $bconf->load(self::$bconf1->getId());
        static::assertStringStartsWith(
            'TestBconf',
            $bconf->getName(),
            'Imported bconf\'s name is not like ' . 'TestBconf' . ' but ' . $bconf->getName()
        );
        $bconf = new BconfEntity();
        $bconf->load(self::$bconf2->getId());
        static::assertStringStartsWith(
            'JsonBconf',
            $bconf->getName(),
            'Imported bconf\'s name is not like ' . 'JsonBconf' . ' but ' . $bconf->getName()
        );
    }

    public function test20_OkapiTasksImport()
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

        $config->import(
            $config
                ->addTask('en', 'de')
                ->setImportBconfId(self::$bconf2->getId())
                ->addUploadFile('workfiles/unity_en_newly_added_keys1.json')
        );
    }
}
