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

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\T5Memory\ContentProtection;

use MittagQI\Translate5\T5Memory\ContentProtection\QueryStringGuesser;
use PHPUnit\Framework\TestCase;

class QueryStringGuesserTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function test(
        string $requestSource,
        string $memorySource,
        string $expectedSource,
        array $expectedSkippedTags
    ): void {
        $guesser = new QueryStringGuesser();

        [$tunedQuery, $skippedTags] = $guesser->filterExtraTags($requestSource, $memorySource);

        self::assertSame(
            $expectedSource,
            $tunedQuery,
            'The filtered source does not match the expected source.'
        );

        self::assertEquals(
            $expectedSkippedTags,
            $skippedTags,
            'The skipped tags do not match the expected skipped tags.'
        );
    }

    public function cases(): iterable
    {
        yield 'no extra tags' => [
            'requestSource' => '<p>Test</p>',
            'memorySource' => '<p>Test</p>',
            'expectedSource' => '<p>Test</p>',
            'expectedSkippedTags' => [],
        ];

        yield 'extra tags in request' => [
            'requestSource' => 'Eine <t5:n id="1" r="fghjkjhg" n="1"/>-kanalig',
            'memorySource' => 'Eine 1-kanalig',
            'expectedSource' => 'Eine 1-kanalig',
            'expectedSkippedTags' => ['1'],
        ];

        yield 'different tags in request' => [
            'requestSource' => 'Eine <t5:n id="1" r="fghjkjhg" n="1"/>-kanalig und <t5:n id="2" r="rfipue3rifucbei" n="2025-10-14"/>',
            'memorySource' => 'Eine 1-kanalig und <t5:n id="2" r="rfipue3rifucbei" n="2025-10-14"/>',
            'expectedSource' => 'Eine 1-kanalig und <t5:n id="2" r="rfipue3rifucbei" n="2025-10-14"/>',
            'expectedSkippedTags' => ['1'],
        ];

        yield 'string that can be translated to single and double smth' => [
            'requestSource' => 'Eine <t5:n id="1" r="fghjkjhg" n="1"/>-kanalig und <t5:n id="2" r="fghjkjhg" n="2"/>-kanalig und <t5:n id="3" r="fghjkjhg" n="3"/>-kanalig und <t5:n id="4" r="fghjkjhg" n="4"/>-kanalig die <t5:n id="5" r="rfipue3rifucbei" n="2025-10-14"/>',
            'memorySource' => 'Eine 1-kanalig und 2-kanalig und <t5:n id="1" r="fghjkjhg" n="3"/>-kanalig und <t5:n id="2" r="fghjkjhg" n="4"/>-kanalig die <t5:n id="3" r="rfipue3rifucbei" n="2023-10-09"/>',
            'expectedSource' => 'Eine 1-kanalig und 2-kanalig und <t5:n id="3" r="fghjkjhg" n="3"/>-kanalig und <t5:n id="4" r="fghjkjhg" n="4"/>-kanalig die <t5:n id="5" r="rfipue3rifucbei" n="2025-10-14"/>',
            'expectedSkippedTags' => ['1', '2'],
        ];

        yield 'mixed numbers' => [
            'requestSource' => 'Eine <t5:n id="1" r="fghjkjhg" n="1"/>-kanalig und <t5:n id="2" r="fghjkjhg" n="3"/>-kanalig und <t5:n id="3" r="fghjkjhg" n="4"/>-kanalig die <t5:n id="4" r="rfipue3rifucbei" n="2025-10-14"/>',
            'memorySource' => 'Eine 1-kanalig und 2-kanalig und <t5:n id="1" r="fghjkjhg" n="3"/>-kanalig die <t5:n id="2" r="rfipue3rifucbei" n="2023-10-09"/>',
            'expectedSource' => 'Eine 1-kanalig und <t5:n id="2" r="fghjkjhg" n="3"/>-kanalig und <t5:n id="3" r="fghjkjhg" n="4"/>-kanalig die <t5:n id="4" r="rfipue3rifucbei" n="2025-10-14"/>',
            'expectedSkippedTags' => ['1'],
        ];
    }
}
