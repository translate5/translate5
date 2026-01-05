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

class TmMatchMorePriorThenRepetitionTest extends ImportTestAbstract
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
                't5memory',
                'TmMatchMorePriorThenRepetitionTest_TM.tmx',
                $customerId,
                $sourceLangRfc,
                $targetLangRfc
            )
            ->setProperty('name', 'TmMatchMorePriorThenRepetitionTest_TM');
        $config
            ->addPretranslation();

        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId, '3-seg-resname.xlf')
            ->setProperty('taskName', 'API Testing::TmMatchMorePriorThenRepetitionTest')
            ->setToEditAfterImport();
    }

    public function test(): void
    {
        $segments = static::api()->getSegments();

        self::assertCount(3, $segments);

        usort($segments, static function ($a, $b) {
            return (int) $a->segmentNrInTask <=> (int) $b->segmentNrInTask;
        });

        $segmentsJson = json_encode($segments, JSON_PRETTY_PRINT);

        // First match. Has context match
        $segment1 = $segments[0];
        self::assertSame(103, (int) $segment1->matchRate, $segmentsJson);
        self::assertSame(
            'pretranslated;tm;T5Memory - TmMatchMorePriorThenRepetitionTest_TM',
            $segment1->matchRateType,
            $segmentsJson
        );
        self::assertSame('Mein Testabschnitt 1. (CONTEXT_1)', $segment1->target, $segmentsJson);

        // Second match. Has own context match
        $segment2 = $segments[1];
        self::assertSame(103, (int) $segment2->matchRate, $segmentsJson);
        self::assertSame(
            'pretranslated;tm;T5Memory - TmMatchMorePriorThenRepetitionTest_TM',
            $segment2->matchRateType,
            $segmentsJson
        );
        self::assertSame('Mein Testabschnitt 2. (CONTEXT_2)', $segment2->target, $segmentsJson);

        // This one does not have full match in TM so translated as general fuzzy match
        $segment3 = $segments[2];
        self::assertSame(100, (int) $segment3->matchRate, $segmentsJson);
        self::assertSame(
            'pretranslated;tm;T5Memory - TmMatchMorePriorThenRepetitionTest_TM',
            $segment3->matchRateType,
            $segmentsJson
        );
        // CONTEXT_2 because it has newer timestamp
        self::assertSame('Mein Testabschnitt 2. (CONTEXT_2)', $segment3->target, $segmentsJson);
    }
}
