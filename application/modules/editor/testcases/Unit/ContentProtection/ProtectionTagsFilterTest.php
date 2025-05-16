<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\ContentProtection;

use MittagQI\Translate5\ContentProtection\ProtectionTagsFilter;
use PHPUnit\Framework\TestCase;

class ProtectionTagsFilterTest extends TestCase
{
    /**
     * @dataProvider filterTagsProvider
     */
    public function testFilterTags(string $source, string $target, string $sourceExpected, string $targetExpected): void
    {
        [$source, $target] = ProtectionTagsFilter::create()->filterTags($source, $target);

        self::assertSame($sourceExpected, $source, 'Source does not match expected');
        self::assertSame($targetExpected, $target, 'Target does not match expected');
    }

    public function filterTagsProvider(): iterable
    {
        yield [
            'source' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="20231020" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="20231020" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'target' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="20231020" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="20231020" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'sourceExpected' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="20231020" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="20231020" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'targetExpected' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="20231020" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="20231020" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
        ];

        yield [
            'source' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023.10.20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023*10*20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'target' => 'string <number type="date" name="default Y.m.d" source="2023.10.20" iso="2023-10-20" target="20231020" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Y*m*d" source="2023*10*20" iso="2023-10-20" target="20231020" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'sourceExpected' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023.10.20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023*10*20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'targetExpected' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023.10.20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023*10*20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
        ];

        yield [
            'source' => 'string <number type="date" name="default Ymd" source="20231022" iso="2023-10-22" target="2023-10-22" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231021" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'target' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Y-m-d" source="2023-10-21" iso="2023-10-21" target="20231021" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'sourceExpected' => 'string 20231022 string <number type="date" name="default Ymd" source="20231021" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'targetExpected' => 'string 20231020 string <number type="date" name="default Ymd" source="20231021" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
        ];

        yield [
            'source' => 'string <number type="date" name="default Y*m*d" source="2023*10*21" iso="2023-10-21" target="20231021" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'target' => 'string <number type="date" name="default Ymd" source="20231021" iso="2023-10-21" target="2023*10*21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'sourceExpected' => 'string <number type="date" name="default Y*m*d" source="2023*10*21" iso="2023-10-21" target="20231021" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'targetExpected' => 'string <number type="date" name="default Y*m*d" source="2023*10*21" iso="2023-10-21" target="20231021" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
        ];

        yield [
            'source' => 'string 2023-10-21 string',
            'target' => 'string <number type="date" name="default Ymd" source="20231021" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'sourceExpected' => 'string 2023-10-21 string',
            'targetExpected' => 'string 20231021 string',
        ];

        yield [
            'source' => 'string <number type="date" name="default Y-m-d" source="2023-10-21" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'target' => 'string 20231021 string',
            'sourceExpected' => 'string 2023-10-21 string',
            'targetExpected' => 'string 20231021 string',
        ];

        yield [
            'source' => '<number type="keep-content" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> ... <number type="keep-content" name="default simple" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> Temperaturklasse <number type="keep-content" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> ... <number type="keep-content" name="default simple (with units)" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1FCtObShJqwmN7cmOymzpKa4pqA4syYpsajGNyCxJtdRU0MjOkZPx8raXjEWZJCKpmYNiKqJ0dTULwUA"/>V',
            'target' => '<number type="keep-content" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> ... <number type="keep-content" name="default simple" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> Classe di temperatura <number type="keep-content" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> ... <number type="keep-content" name="default simple (with units)" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1FCtObShJqwmN7cmOymzpKa4pqA4syYpsajGNyCxJtdRU0MjOkZPx8raXjEWZJCKpmYNiKqJ0dTULwUA"/>V',
            'sourceExpected' => '<number type="keep-content" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> ... <number type="keep-content" name="default simple" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> Temperaturklasse <number type="keep-content" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> ... <number type="keep-content" name="default simple (with units)" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1FCtObShJqwmN7cmOymzpKa4pqA4syYpsajGNyCxJtdRU0MjOkZPx8raXjEWZJCKpmYNiKqJ0dTULwUA"/>V',
            'targetExpected' => '<number type="keep-content" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> ... <number type="keep-content" name="default simple" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> Classe di temperatura <number type="keep-content" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> ... <number type="keep-content" name="default simple (with units)" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1FCtObShJqwmN7cmOymzpKa4pqA4syYpsajGNyCxJtdRU0MjOkZPx8raXjEWZJCKpmYNiKqJ0dTULwUA"/>V',
        ];

        yield [
            'source' => '<number type="keep-content" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> ... <number type="keep-content" name="default simple" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> Temperaturklasse <number type="keep-content" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> ... <number type="keep-content" name="default simple (with units)" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1FCtObShJqwmN7cmOymzpKa4pqA4syYpsajGNyCxJtdRU0MjOkZPx8raXjEWZJCKpmYNiKqJ0dTULwUA"/>mA',
            'target' => '<number type="keep-content" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> ... <number type="keep-content" name="default simple" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> Classe di temperatura <number type="keep-content" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> ... <number type="keep-content" name="default simple" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> mA',
            'sourceExpected' => '<number type="keep-content" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> ... <number type="keep-content" name="default simple" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> Temperaturklasse <number type="keep-content" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> ... <number type="keep-content" name="default simple (with units)" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1FCtObShJqwmN7cmOymzpKa4pqA4syYpsajGNyCxJtdRU0MjOkZPx8raXjEWZJCKpmYNiKqJ0dTULwUA"/>mA',
            'targetExpected' => '<number type="keep-content" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> ... <number type="keep-content" name="default simple" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> Classe di temperatura <number type="keep-content" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA="/> ... <number type="keep-content" name="default simple (with units)" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1FCtObShJqwmN7cmOymzpKa4pqA4syYpsajGNyCxJtdRU0MjOkZPx8raXjEWZJCKpmYNiKqJ0dTULwUA"/> mA',
        ];

        yield 'same date with different rule pairs' => [
            'source' => 'string <number type="date" name="default Y*m*d" source="2023*10*22" iso="2023-10-22" target="2023.10.22" regex="rule_with_*"/> string <number type="date" name="default Y-m-d" source="2023-10-22" iso="2023-10-22" target="20231022" regex="other_rule_-"/> string',
            'target' => 'string <number type="date" name="default Ymd" source="20231022" iso="2023-10-22" target="2023-10-22" regex="other_rule"/> string <number type="date" name="default Y.m.d" source="2023.10.22" iso="2023-10-22" target="2023*10*22" regex="rule_with_."/> string',
            'sourceExpected' => 'string <number type="date" name="default Y*m*d" source="2023*10*22" iso="2023-10-22" target="2023.10.22" regex="rule_with_*"/> string <number type="date" name="default Y-m-d" source="2023-10-22" iso="2023-10-22" target="20231022" regex="other_rule_-"/> string',
            'targetExpected' => 'string <number type="date" name="default Y-m-d" source="2023-10-22" iso="2023-10-22" target="20231022" regex="other_rule_-"/> string <number type="date" name="default Y*m*d" source="2023*10*22" iso="2023-10-22" target="2023.10.22" regex="rule_with_*"/> string',
        ];

        yield 'target has more same tags' => [
            'source' => 'string <number type="date" name="default Y-m-d" source="2023-10-21" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'target' => 'string <number type="date" name="default Y-m-d" source="2023-10-21" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Y-m-d" source="2023-10-21" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'sourceExpected' => 'string <number type="date" name="default Y-m-d" source="2023-10-21" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'targetExpected' => 'string <number type="date" name="default Y-m-d" source="2023-10-21" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string 2023-10-21 string',
        ];

        yield 'source has more same tags' => [
            'source' => 'string <number type="date" name="default Y-m-d" source="2023-10-21" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Y-m-d" source="2023-10-21" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'target' => 'string <number type="date" name="default Y-m-d" source="2023-10-21" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'sourceExpected' => 'string <number type="date" name="default Y-m-d" source="2023-10-21" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string 2023-10-21 string',
            'targetExpected' => 'string <number type="date" name="default Y-m-d" source="2023-10-21" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
        ];
    }
}
