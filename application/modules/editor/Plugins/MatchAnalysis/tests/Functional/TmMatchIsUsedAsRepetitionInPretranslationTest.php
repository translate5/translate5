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

class TmMatchIsUsedAsRepetitionInPretranslationTest extends ImportTestAbstract
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
                'TmMatchIsUsedAsRepetitionInPretranslationTest_TM.tmx',
                $customerId,
                $sourceLangRfc,
                $targetLangRfc
            )
            ->setProperty('name', 'TmMatchIsUsedAsRepetitionInPretranslationTest_TM');
        $config
            ->addPretranslation();

        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId, 'segs-with-resname-and-not.xlf')
            ->setProperty('taskName', 'API Testing::TmMatchIsUsedAsRepetitionInPretranslationTest')
            ->setToEditAfterImport();
    }

    public function test(): void
    {
        $segments = static::api()->getSegments();

        self::assertCount(10, $segments);

        usort($segments, static function ($a, $b) {
            return (int) $a->segmentNrInTask <=> (int) $b->segmentNrInTask;
        });

        $segmentsJson = json_encode($segments, JSON_PRETTY_PRINT);

        // First match. Has context match
        $segment1 = $segments[0];
        self::assertSame(103, (int) $segment1->matchRate, $segmentsJson);
        self::assertSame(
            'pretranslated;tm;OpenTM2 - TmMatchIsUsedAsRepetitionInPretranslationTest_TM',
            $segment1->matchRateType,
            $segmentsJson
        );
        self::assertSame('Mein Testabschnitt 1. (CONTEXT_1)', $segment1->target, $segmentsJson);

        // Second match. Has own context match
        $segment2 = $segments[1];
        self::assertSame(103, (int) $segment2->matchRate, $segmentsJson);
        self::assertSame(
            'pretranslated;tm;OpenTM2 - TmMatchIsUsedAsRepetitionInPretranslationTest_TM',
            $segment2->matchRateType,
            $segmentsJson
        );
        self::assertSame('Mein Testabschnitt 2. (CONTEXT_2)', $segment2->target, $segmentsJson);

        // This one does not have full match in TM so translated as general fuzzy match
        $segment3 = $segments[2];
        // match-rate is 100 because segment has res-name so match is lower if it is not used
        self::assertSame(100, (int) $segment3->matchRate, $segmentsJson);
        self::assertSame(
            'pretranslated;tm;OpenTM2 - TmMatchIsUsedAsRepetitionInPretranslationTest_TM',
            $segment3->matchRateType,
            $segmentsJson
        );
        // Second segment has newer match so it is used
        self::assertSame('Mein Testabschnitt 2. (CONTEXT_2)', $segment3->target, $segmentsJson);

        $segment4 = $segments[3];
        // same file and segment in file does not have res-name
        self::assertSame(101, (int) $segment4->matchRate, $segmentsJson);
        self::assertSame(
            'pretranslated;tm;OpenTM2 - TmMatchIsUsedAsRepetitionInPretranslationTest_TM',
            $segment4->matchRateType,
            $segmentsJson
        );
        self::assertSame('Mein Testabschnitt 2. (CONTEXT_2)', $segment4->target, $segmentsJson);

        // repetitions of segment 4
        for ($i = 4; $i <= 5; $i++) {
            $segment = $segments[$i];
            self::assertSame(101, (int) $segment->matchRate, $segmentsJson);
            self::assertSame(
                'pretranslated;tm;OpenTM2 - TmMatchIsUsedAsRepetitionInPretranslationTest_TM;auto-propagated',
                $segment->matchRateType,
                $segmentsJson
            );
            self::assertSame('Mein Testabschnitt 2. (CONTEXT_2)', $segment->target, $segmentsJson);
        }

        // This one does not have full match in TM so translated as general fuzzy match
        $segment7 = $segments[6];
        // match-rate is 100 because segment doesn't have res-name and different filename
        self::assertSame(100, (int) $segment7->matchRate, $segmentsJson);
        self::assertSame(
            'pretranslated;tm;OpenTM2 - TmMatchIsUsedAsRepetitionInPretranslationTest_TM',
            $segment7->matchRateType,
            $segmentsJson
        );
        self::assertSame('Mein Testabschnitt 2. #2', $segment7->target, $segmentsJson);

        for ($i = 7; $i <= 9; $i++) {
            $segment = $segments[$i];
            self::assertSame(100, (int) $segment->matchRate, $segmentsJson);
            self::assertSame(
                'pretranslated;tm;OpenTM2 - TmMatchIsUsedAsRepetitionInPretranslationTest_TM;auto-propagated',
                $segment->matchRateType,
                $segmentsJson
            );
            self::assertSame('Mein Testabschnitt 2. #2', $segment->target, $segmentsJson);
        }
    }
}
