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
 * This test class will create test task and pretranslate it with ZDemoMT and OpenTm2 TM
 * Then the export result from the logg will be compared against the expected result.
 */
class Translate1484Test extends JsonTestAbstract
{
    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
        'editor_Plugins_MatchAnalysis_Init',
        'editor_Plugins_ZDemoMT_Init',
    ];

    protected static bool $setupOwnCustomer = true;

    protected static function setupImport(Config $config): void
    {
        $sourceLangRfc = 'en';
        $targetLangRfc = 'de';
        $customerId = static::$ownCustomer->id;
        $config
            ->addLanguageResource('zdemomt', null, $customerId, $sourceLangRfc, $targetLangRfc)
            ->addDefaultCustomerId($customerId);
        $config
            ->addLanguageResource('opentm2', 'resource1.tmx', $customerId, $sourceLangRfc, $targetLangRfc)
            ->addDefaultCustomerId($customerId);
        $config
            ->addPretranslation()
            ->setProperty('pretranslateMt', 1);
        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId)
            ->addUploadFile('simple-en-de.xlf')
            ->setProperty('edit100PercentMatch', false);
    }

    /**
     * To be able to compare results properly
     */
    private static array $rowKeys = [
        'category',
        'langageResourceName',
        'langageResourceServiceName',
        'sourceLang',
        'targetLang',
        'taskCount',
        'repetition',
        'charactersPerCustomer',
        'totalCharacters',
    ];

    /**
     * Test the Excel export.
     */
    public function testExportResourcesLog()
    {
        $jsonFileName = 'exportResults.json';
        $actualObject = static::api()->getJson('editor/customer/exportresource', [
            'customerId' => static::$ownCustomer->id,
        ], $jsonFileName);
        $expectedObject = static::api()->getFileContent($jsonFileName);
        // we need to order the results to avoid tests failing due to runtime-differences
        $actual = $this->convertResultForComparision(json_decode(json_encode($actualObject), true));
        $expected = $this->convertResultForComparision(json_decode(json_encode($expectedObject), true));
        $this->assertEquals(
            $expected,
            $actual,
            'The expected file (exportResults) an the result file does not match.'
            . PHP_EOL
            . json_encode($actual, JSON_PRETTY_PRINT)
        );
    }

    private function convertResultForComparision(array $data): array
    {
        $result = [];
        foreach ($data as $category => $rows) {
            foreach ($rows as $rowData) {
                $result[] = $this->createResultRow($category, $rowData);
            }
        }

        usort($result, function ($a, $b) {
            //category
            $compare = strcmp($a[0], $b[0]);
            if ($compare === 0) {
                // langageResourceName
                $compare = strcmp($a[1], $b[1]);
                if ($compare === 0) {
                    // charactersPerCustomer
                    if ($a[7] !== '' && $b[7] !== '') {
                        return intval($a[7]) - intval($b[7]);
                    }
                    // totalCharacters
                    if ($a[8] !== '' && $b[8] !== '') {
                        return intval($a[8]) - intval($b[8]);
                    }

                    return 0;
                } else {
                    return $compare;
                }
            } else {
                return $compare;
            }
        });

        $lines = [];
        foreach ($result as $row) {
            $line = '';
            foreach ($row as $column) {
                $comma = ($line === '') ? '' : ', ';
                $line .= ($column === '') ? '' : $comma . $column;
            }
            $lines[] = $line;
        }

        return $lines;
    }

    private function createResultRow(string $category, array $data): array
    {
        $row = [];
        foreach (self::$rowKeys as $key) {
            $row[] = ($key === 'category') ? $category : (array_key_exists($key, $data) ? $data[$key] : '');
        }

        return $row;
    }
}
