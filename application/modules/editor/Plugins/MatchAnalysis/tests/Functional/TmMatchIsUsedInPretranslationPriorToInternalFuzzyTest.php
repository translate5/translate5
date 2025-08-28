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

class TmMatchIsUsedInPretranslationPriorToInternalFuzzyTest extends ImportTestAbstract
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
                'TmMatchIsUsedInPretranslationPriorToInternalFuzzyTest_TM1.tmx',
                $customerId,
                $sourceLangRfc,
                $targetLangRfc
            )
            ->setProperty('name', 'TmMatchIsUsedInPretranslationPriorToInternalFuzzyTest_TM1');

        //        $config
        //            ->addLanguageResource(
        //                'opentm2',
        //                'TmMatchIsUsedInPretranslationPriorToInternalFuzzyTest_TM2.tmx',
        //                $customerId,
        //                $sourceLangRfc,
        //                $targetLangRfc
        //            )
        //            ->setProperty('name', 'TmMatchIsUsedInPretranslationPriorToInternalFuzzyTest_TM2');

        $config
            ->addPretranslation();

        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId, 'segs.xlf')
            ->setProperty('taskName', 'API Testing::TmMatchIsUsedInPretranslationPriorToInternalFuzzyTest')
//            ->setToEditAfterImport()
        ;
    }

    public function test(): void
    {
        static::api()->login(TestUser::TestApiUser->value);

        $task = static::api()->getTask();

        static::api()->putJson(
            'editor/task/' . $task->id . '/pretranslation/operation',
            [
                'internalFuzzy' => 1,
                'pretranslateMatchrate' => 80,
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

        error_log('test: $userGuid = ' . TestUser::TestApiUser->value);
        error_log('test: $taskGuid = ' . $task->taskGuid);

        static::api()->addUserToTask($task->taskGuid, TestUser::TestApiUser->value);

        $segments = static::api()->getSegments();

        self::assertCount(2, $segments);

        usort($segments, static function ($a, $b) {
            return (int) $a->segmentNrInTask <=> (int) $b->segmentNrInTask;
        });

        $segmentsJson = json_encode($segments, JSON_PRETTY_PRINT);

        // First match. Has context match
        $segment1 = $segments[0];
        self::assertSame(103, (int) $segment1->matchRate, $segmentsJson);
        self::assertSame(
            'pretranslated;tm;OpenTM2 - TmMatchIsUsedInPretranslationPriorToInternalFuzzyTest_TM1',
            $segment1->matchRateType,
            $segmentsJson
        );
        self::assertSame('Mein Testabschnitt 1.', $segment1->target, $segmentsJson);

        // Second match. Has own context match
        $segment2 = $segments[1];
        self::assertSame(103, (int) $segment2->matchRate, $segmentsJson);
        self::assertSame(
            'pretranslated;tm;OpenTM2 - TmMatchIsUsedAsRepetitionInPretranslationTest_TM',
            $segment2->matchRateType,
            $segmentsJson
        );
        self::assertSame('Mein Testabschnitt 2. (CONTEXT_2)', $segment2->target, $segmentsJson);
    }
}
