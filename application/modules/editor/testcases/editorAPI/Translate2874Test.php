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
 * Testcase for TRANSLATE-2874 Mixing XLF id and rid values led to wrong tag numbering
 * For details see the issue.
 * includes a test for TRANSLATE-3068 - pre-translation with MT matches only and repetitions did use wrong tags
 */
class Translate2874Test extends editor_Test_JsonTest {

    protected static string $setupUserLogin = 'testlector';

    protected static function setupImport(Config $config): void
    {
        $sourceLangRfc = 'de';
        $targetLangRfc = 'en';
        $customerId = static::getTestCustomerId();
        $config
            ->addLanguageResource('zdemomt', null, $customerId, $sourceLangRfc, $targetLangRfc)
            ->addDefaultCustomerId($customerId)
            ->setProperty('name', 'API Testing::ZDemoMT_Translate2874Test'); // TODO FIXME: we better generate data independent from resource-names ...
        $config
            ->addPretranslation()
            ->setProperty('pretranslateTmAndTerm', 0)
            ->setProperty('pretranslateMt', 1);
        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId)
            ->addUploadFolder('testfiles')
            ->setProperty('edit100PercentMatch', false)
            ->setToEditAfterImport();
    }

    /**
     */
    public function testPreTranslatedContent() {
        //test segment list
        $jsonFileName = 'expectedSegments-edited.json';
        $segments = static::api()->getSegments($jsonFileName, 10);
        $this->assertModelsEqualsJsonFile('Segment', $jsonFileName, $segments, 'Imported segments are not as expected!');
    }
}
