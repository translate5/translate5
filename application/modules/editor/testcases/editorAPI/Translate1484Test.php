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

/**
 * This test class will create test task and pretranslate it with ZDemoMT and OpenTm2 TM
 * Then the export result from the logg will be compared against the expected result.
 */
class Translate1484Test extends editor_Test_JsonTest {

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
            ->addDefaultCustomerId($customerId)
            ->setProperty('name', 'API Testing::ZDemoMT_Translate1484Test'); // TODO FIXME: we better generate data independent from resource-names ...
        $config
            ->addLanguageResource('opentm2', 'resource1.tmx', $customerId, $sourceLangRfc, $targetLangRfc)
            ->addDefaultCustomerId($customerId)
            ->setProperty('name', 'API Testing::OpenTm2Tm_Translate1484Test'); // TODO FIXME: we better generate data independent from resource-names ...
        $config
            ->addPretranslation()
            ->setProperty('pretranslateMt', 1);
        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId)
            ->addUploadFile('simple-en-de.xlf')
            ->setProperty('edit100PercentMatch', false)
            ->setProperty('taskName', 'API Testing::Translate1484Test'); // TODO FIXME: we better generate data independent from resource-names ...
    }

    /***
     * Test the excel export.
     */
    public function testExportResourcesLog() {

        $jsonFileName = 'exportResults.json';
        $actualObject = static::api()->getJson('editor/customer/exportresource', [ 'customerId' => static::$ownCustomer->id ], $jsonFileName);
        $expectedObject = static::api()->getFileContent($jsonFileName);
        // we need to order the results to avoid tests failing due to runtime-differences
        $this->sortExportResource($actualObject);
        $this->sortExportResource($expectedObject);
        $this->assertEquals($expectedObject, $actualObject, 'The expected file (exportResults) an the result file does not match.');
    }

    /**
     * @param stdClass $exportResource
     */
    private function sortExportResource(stdClass $exportResource){
        usort($exportResource->MonthlySummaryByResource, function($a, $b) {
            return $a->totalCharacters - $b->totalCharacters;
        });
        usort($exportResource->UsageLogByCustomer, function($a, $b) {
            return $a->charactersPerCustomer - $b->charactersPerCustomer;
        });
    }
}
