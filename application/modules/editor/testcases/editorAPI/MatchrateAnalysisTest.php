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

use MittagQI\Translate5\Test\Api\AnalysisTrait;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\ImportTestAbstract;

/**
 * Match analysis tests.
 * The test will check if the current codebase provides a valid matchanalysis test results.
 * The valid test results are counted by hand.
 * For more info about the segment results check the Analysis test result Info.ods document in the test folder
 */
class MatchrateAnalysisTest extends ImportTestAbstract
{
    use AnalysisTrait;

    protected static array $requiredPlugins = [
        'editor_Plugins_MatchAnalysis_Init',
    ];

    /***
     * Configs which are changed for test tests and needs to be revoked after the test is done
     */
    protected static array $changedConfigs = [];

    protected static function setupImport(Config $config): void
    {
        $sourceLangRfc = 'en-GB';
        $targetLangRfc = 'de-DE';
        $customerId = static::getTestCustomerId();

        // collect the original config value so it will be set again after the test is finished
        self::$changedConfigs[] = static::api()->getConfig('runtimeOptions.plugins.MatchAnalysis.readImportAnalysis');

        static::api()->putJson('editor/config/', [
            'value' => 1,
            'name' => 'runtimeOptions.plugins.MatchAnalysis.readImportAnalysis',
        ]);

        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId, '2_units_4_segments.xlf')
            ->setProperty('taskName', 'API Testing::MatchrateAnalysisTest');
    }

    public function testAnalysisResults(): void
    {
        $unitType = 'word';
        $jsonFileName = 'analysis-' . $unitType . '.json';

        $analysis = static::api()->getJson('editor/plugins_matchanalysis_matchanalysis', [
            'taskGuid' => static::api()->getTask()->taskGuid,
            'unitType' => $unitType,
        ], $jsonFileName);
        $this->assertNotEmpty($analysis, 'No results found for the ' . $unitType . '-based task-specific matchanalysis.');

        static::api()->isCapturing() && file_put_contents(static::api()->getFile($jsonFileName, null, false), json_encode($this->filterUngroupedAnalysis($analysis), JSON_PRETTY_PRINT));

        $expectedAnalysis = static::api()->getFileContent($jsonFileName);

        $this->assertEquals(
            $this->filterTaskAnalysis($expectedAnalysis),
            $this->filterTaskAnalysis($analysis),
            'The expected file and the data does not match for the ' . $unitType . '-based task-specific matchanalysis.'
        );
    }

    public static function afterTests(): void
    {
        foreach (self::$changedConfigs as $c) {
            static::api()->putJson('editor/config/', [
                'value' => $c->value,
                'name' => $c->name,
            ]);
        }
    }
}
