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
use MittagQI\Translate5\Test\Filter;

/**
 * Testcase for TRANSLATE-2538
 */
class QualitySpellCheckTest extends editor_Test_JsonTest {

    protected static array $requiredPlugins = [
        'editor_Plugins_SpellCheck_Init'
    ];

    protected static array $requiredRuntimeOptions = [
        'autoQA.enableSegmentSpellCheck' => 1
    ];

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('de-DE', 'en-US')
            ->addUploadFile('testfiles/ten segments --- de-DE en-US.csv')
            ->setToEditAfterImport();
    }

    public function testSpellCheck()
    {
        // extract the language-tool version out of the service-check
        // this way at least the maintainer of the test can easily see, that the error originates from a different languagetool-version
        $service = editor_Plugins_SpellCheck_Init::createService('languagetool');
        $service->check();
        $checkResult = $service->createServiceMsg('', ',', false, true);
        $matches = [];
        preg_match('~Versions?:([^,]+)~', $checkResult, $matches);
        $checkResult = (count($matches) > 0) ? 'Version: ' . trim($matches[1]) : 'UNKNOWN VERSION';
        $expectedResult = static::api()->getFileContent('languagetool-version.txt', $checkResult);
        $this->assertEquals($expectedResult, $checkResult, 'The language-tool differs to what the testdata was created with, this certainly leads to a failing test.');

        // Get segments and check their quantity
        $expectedSegmentQuantity = 10;
        $factQty = count(static::api()->getSegments(null, 10));
        static::assertEquals($factQty, $expectedSegmentQuantity, 'Not enough segments in the imported task');

        // Check qualities
        $jsonFile = 'ten segments --- de-DE en-US.json';
        $tree = static::api()->getJsonTree('/editor/quality', [], $jsonFile);
        $treeFilter = Filter::createSingle('qtype', 'spellcheck');
        $this->assertModelEqualsJsonFile('FilterQuality', $jsonFile, $tree, '', $treeFilter);
    }
}