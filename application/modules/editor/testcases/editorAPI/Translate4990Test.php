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
use MittagQI\Translate5\Test\JsonTestAbstract;

/**
 * Testcase for TRANSLATE-4990
 */
class Translate4990Test extends JsonTestAbstract
{
    protected static bool $termtaggerRequired = true;

    protected static array $requiredPlugins = [
        'editor_Plugins_TermTagger_Bootstrap',
    ];

    protected static function setupImport(Config $config): void
    {
        $customerId = static::getTestCustomerId();

        $name = static::class;

        $config
            ->addLanguageResource('termcollection', "testfiles/$name.tbx", $customerId)
            ->setProperty('name', $name);

        $config->addPretranslation();

        $config
            ->addTask('de-CH', 'en-GB', $customerId, "testfiles/$name.csv")
            ->addTaskConfig('runtimeOptions.import.fileparser.csv.active', '1')
            ->setProperty('taskName', "API Testing::$name: de-CH => en-GB")
            ->setToEditAfterImport();
    }

    public function testTagging()
    {
        // Segments json file
        $jsonFile = static::class . '.json';

        // Get actual segments
        $actual = json_encode(static::api()->getSegmentsWithBasicData(), JSON_PRETTY_PRINT);

        // Update json file if we're in capture-mode
        static::api()->isCapturing() && file_put_contents(static::api()->getFile($jsonFile), $actual);

        // Get expected json
        $expected = static::api()->getFileContentRaw($jsonFile);

        // Compare with actual json
        $this->assertEquals($expected, $actual, "Some tags were not recognized by TermTagger");
    }
}
