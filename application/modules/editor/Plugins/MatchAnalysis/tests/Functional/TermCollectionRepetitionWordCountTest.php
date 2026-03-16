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
use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\ImportTestAbstract;

/**
 * Validates if segments pre-translated with term collection gets all repetitions pre-translated.
 */
class TermCollectionRepetitionWordCountTest extends ImportTestAbstract
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
                'termcollection',
                'TermCollectionRepetitionWordCountTest_Collection.tbx',
                $customerId
            )
            ->setProperty('name', 'TermCollectionRepetitionWordCountTest_Collection');

        $config
            ->addPretranslation();

        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId, 'repetition-term.xlf')
            ->setProperty('taskName', 'API Testing::TermCollectionRepetitionWordCountTest')
            ->setProperty('wordCount', 12)
            ->setToEditAfterImport();
    }

    public function testTaskAndAnalysisWordCountAreEqualForTermCollectionRepetitions(): void
    {
        $task = static::api()->getTask();
        self::assertTrue(property_exists($task, 'wordCount'), 'Task payload has no wordCount.');

        $segments = static::api()->getSegments();
        self::assertCount(6, $segments);

        usort($segments, static function ($a, $b) {
            return (int) $a->segmentNrInTask <=> (int) $b->segmentNrInTask;
        });

        $segmentsJson = json_encode($segments, JSON_PRETTY_PRINT);

        $masterSegment = $segments[0];
        self::assertSame(104, (int) $masterSegment->matchRate, $segmentsJson);
        self::assertStringContainsString('pretranslated;termcollection', (string) $masterSegment->matchRateType, $segmentsJson);

        for ($i = 1; $i < 6; $i++) {
            $repetition = $segments[$i];
            self::assertSame(102, (int) $repetition->matchRate, $segmentsJson);
            self::assertStringContainsString('pretranslated;termcollection', (string) $repetition->matchRateType, $segmentsJson);
            self::assertStringContainsString('auto-propagated', (string) $repetition->matchRateType, $segmentsJson);
        }

        $analysis = static::api()->getJson('editor/plugins_matchanalysis_matchanalysis', [
            'taskGuid' => $task->taskGuid,
            'unitType' => 'word',
        ]);
        self::assertNotEmpty($analysis, 'No analysis rows returned.');

        $summary = $this->findSummaryRow($analysis);
        self::assertNotNull($summary, 'No summary row in analysis.');
        self::assertTrue(property_exists($summary, 'unitCountTotal'), 'Summary row has no unitCountTotal.');

        self::assertSame(
            (int) $task->wordCount,
            (int) $summary->unitCountTotal,
            'Analysis word count does not match task word count.'
        );
    }

    private function findSummaryRow(array $analysis): ?stdClass
    {
        foreach ($analysis as $row) {
            if (($row->resourceName ?? null) === 'summary') {
                return $row;
            }
        }

        return null;
    }
}
