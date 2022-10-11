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

/***
 * This is a test for the pivot pre-translation feature.
 *
 * The test works in the following way:
 *   - create temporray customer
 *   - create temporray MT resource using ZDemoMT plugin
 *   - assign the temporrary customer to be used as pivot default for ZDemoMT resource
 *   - create new task (with only 3 segments) asn assign the MT ZDemoMT resource
 *   - check if for all 3 task segments the pivot is pre-translated
 */
class Translate2855Test extends editor_Test_JsonTest {

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
        'editor_Plugins_MatchAnalysis_Init',
        'editor_Plugins_ZDemoMT_Init'
    ];

    protected static bool $setupOwnCustomer = true;

    protected static function setupImport(Config $config): void
    {
        $sourceLangRfc = 'en';
        $targetLangRfc = 'de';
        $customerId = static::$ownCustomer->id;
        $config
            ->addLanguageResource('zdemomt', null, $customerId, $sourceLangRfc, $targetLangRfc)
            ->addProperty('customerPivotAsDefaultIds', [ $customerId ]);
        $config
            ->addPivotBatchPretranslation();
        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId)
            ->addUploadFile('Task-de-en.html')
            ->addProperty('relaisLang', $targetLangRfc)
            ->setToEditAfterImport();
    }

    /**
     * Test if the task relais segments are pre-translated using ZDemoMT
     * @return void
     */
    public function testSegmentContent(){
        $segments = static::api()->getSegments();
        self::assertEquals(3, count($segments), 'The number of segments does not match.');
        foreach ($segments as $segment){
            self::assertNotEmpty($segment->relais);
        }
    }
}
