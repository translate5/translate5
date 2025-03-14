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
declare(strict_types=1);

use MittagQI\Translate5\Test\Api\AnalysisTrait;
use MittagQI\Translate5\Test\Api\DbHelper;
use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\ImportTestAbstract;

class WordCountCalculationWithoutInternalFuzzyTest extends ImportTestAbstract
{
    use AnalysisTrait;

    protected static TestUser $setupUserLogin = TestUser::TestLector;

    protected static array $requiredPlugins = [
        'editor_Plugins_MatchAnalysis_Init',
    ];

    protected static function setupImport(Config $config): void
    {
        $sourceLangRfc = 'en';
        $targetLangRfc = 'de';
        $customerId = static::getTestCustomerId();

        $config
            ->addLanguageResource(
                'opentm2',
                'WordCountCalculationWithoutInternalFuzzyTest_TM.tmx',
                $customerId,
                $sourceLangRfc,
                $targetLangRfc
            )
            ->setProperty('name', 'WordCountCalculationWithoutInternalFuzzyTest_TM');

        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId, 'segs-with-resname-and-not.xlf')
            ->setProperty('taskName', 'API Testing::WordCountCalculationWithoutInternalFuzzyTest')
            ->setToEditAfterImport();
    }

    public function testAnalysisWithoutCountInternalFuzzy(): void
    {
        static::api()->login(TestUser::TestApiUser->value);

        $task = static::api()->getTask();

        static::api()->putJson(
            'editor/task/' . $task->id . '/pretranslation/operation',
            [
                'internalFuzzy' => 0,
                'pretranslateMatchrate' => 100,
                'pretranslateTmAndTerm' => 1,
                'pretranslateMt' => 0,
            ],
            null,
            false
        );

        DbHelper::waitForWorkers(
            /** @phpstan-ignore-next-line */
            $this,
            \editor_Task_Operation_FinishingWorker::class,
            [$task->taskGuid],
            true,
            300
        );

        $jsonFileName = 'expected.json';

        // fetch task data
        $analysis = static::api()->getJson('editor/plugins_matchanalysis_matchanalysis', [
            'taskGuid' => $task->taskGuid,
            'unitType' => 'word',
        ]);
        $this->assertNotEmpty($analysis, 'No results found for the word-based task-specific matchanalysis.');

        $result = $this->filterTaskAnalysis($analysis);

        if (static::api()->isCapturing()) {
            file_put_contents(
                static::api()->getFile($jsonFileName, null, false),
                json_encode($result, JSON_PRETTY_PRINT)
            );
        }

        $expectedAnalysis = static::api()->getFileContent($jsonFileName);

        $this->assertEquals(
            $expectedAnalysis,
            $this->filterTaskAnalysis($analysis),
            'The expected file and the data does not match for the work-based task-specific matchanalysis.'
        );
    }
}
