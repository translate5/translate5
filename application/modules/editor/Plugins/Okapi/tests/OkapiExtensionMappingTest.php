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

use MittagQI\Translate5\Test\Import\Bconf;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\JsonTestAbstract;

/**
 * Test for Bconfs defining new file-extensions, either via an uploaded bconf or as embedded bconf in the ZIP
 * This test imlicitly also tests the FIGMA file-filter / fprm
 */
class OkapiExtensionMappingTest extends JsonTestAbstract
{
    private static Bconf $testBconf;

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
    ];

    /**
     * Just imports a bconf to test with
     */
    protected static function setupImport(Config $config): void
    {
        static::$testBconf = $config->addBconf('ExtensionMappingTestBconf', 'extensions-uvw-xyz.bconf');
    }

    /**
     * Tests an import with two workfiles with strange extensions that match/utilize the bconf added in the setup
     */
    public function testAddedExtensions()
    {
        $config = static::getConfig();
        $task = $config
            ->addTask('en', 'de', -1, 'textfiles-uvw-xyz-en-de.zip')
            ->setImportBconfId(static::$testBconf->getId())
            ->setToEditAfterImport();
        $config->import($task);
        $segments = static::api()->getSegments();
        $this->assertSegmentsEqualsJsonFile('textfiles-uvw-xyz-en-de.json', $segments, 'There was an error importing textfiles-uvw-xyz-en-de.zip', false);
    }

    /**
     * Tests an import with two workfiles with strange extensions that match/utilize the bconf embedded in the zip
     */
    public function testEmbeddedExtensions()
    {
        $config = static::getConfig();
        $task = $config
            ->addTask('en', 'de', -1, 'extensions-embedded-matching-en-de.zip')
            ->setToEditAfterImport();
        $config->import($task);
        $segments = static::api()->getSegments();
        $this->assertSegmentsEqualsJsonFile('extensions-embedded-matching-en-de.json', $segments, 'There was an error importing extensions-embedded-matching-en-de.zip', false);
    }

    /**
     * Tests an import with two workfiles with strange extensions that DO NOT match/utilize the bconf embedded in the zip
     */
    public function testMissingExtensions()
    {
        $config = static::getConfig();
        $task = $config
            ->addTask('en', 'de', -1, 'textfiles-txt-en-de.zip')
            ->setImportBconfId(static::$testBconf->getId())
            ->setNotToFailOnError();
        $config->import($task);

        $events = static::api()->getJson('/editor/task/' . $task->getId() . '/events');
        $eventsString = json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $message = 'The task events did not contain the Expected event.';

        static::assertStringContainsString('E1135', $eventsString, $message);
        static::assertStringContainsString('There are no importable files in the Task', $eventsString, $message);
        static::assertStringContainsString('.uvw', $eventsString, $message);
        static::assertStringContainsString('.xyz', $eventsString, $message);
    }

    /**
     * Tests an import with two workfiles with strange extensions that DO NOT match/utilize the bconf embedded in the zip
     */
    public function testMissingEmbeddedExtensions()
    {
        $config = static::getConfig();
        $task = $config
            ->addTask('en', 'de', -1, 'extensions-embedded-not-matching-en-de.zip')
            ->setNotToFailOnError();
        $config->import($task);

        $events = static::api()->getJson('/editor/task/' . $task->getId() . '/events');
        $eventsString = json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $message = 'The task events did not contain the Expected event.';

        static::assertStringContainsString('E1135', $eventsString, $message);
        static::assertStringContainsString('There are no importable files in the Task', $eventsString, $message);
        static::assertStringContainsString('.txt', $eventsString, $message);
    }
}
