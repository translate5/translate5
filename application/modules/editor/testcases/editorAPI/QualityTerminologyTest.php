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

use MittagQI\Translate5\Test\Filter;
use MittagQI\Translate5\Test\Import\Config;

/**
 * Testcase for TRANSLATE-3593
 */
class QualityTerminologyTest extends editor_Test_JsonTest
{
    protected static bool $termtaggerRequired = true;

    protected static array $requiredPlugins = [
        'editor_Plugins_TermTagger_Bootstrap',
    ];

    protected static function setupImport(Config $config): void
    {
        $customerId = static::getTestCustomerId();
        $testName = 'Translate3593Test';

        $config
            ->addLanguageResource('termcollection', "testfiles/$testName.tbx", $customerId)
            ->setProperty('name', $testName);

        $config->addPretranslation();

        $config
            ->addTask('en', 'de', $customerId, "testfiles/$testName.csv")
            ->setProperty('taskName', "API Testing::$testName")
            ->setToEditAfterImport();
    }

    public function testTerminology()
    {
        // Get segments and check their quantity
        $segmentQuantity = count(static::api()->getSegments(null, 10));
        static::assertEquals(5, $segmentQuantity, 'Not enough segments in the imported task');
        $testName = 'Translate3593Test';

        // Check qualities
        $jsonFile = $testName . '.json';
        $tree = static::api()->getJsonTree('/editor/quality', [], $jsonFile);
        $treeFilter = Filter::createSingle('qtype', 'term');
        $this->assertModelEqualsJsonFile('FilterQuality', $jsonFile, $tree, '', $treeFilter);
    }
}
