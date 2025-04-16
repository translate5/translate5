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

use MittagQI\Translate5\ContentProtection\NumberProtection\Tag\NumberTag;
use MittagQI\Translate5\ContentProtection\ProtectionTagsFilter;
use PHPUnit\Framework\TestCase;

class ProtectionTagsFilterTest extends TestCase
{
    /**
     * @dataProvider filterTagsProvider
     */
    public function testFilterTags(string $source, string $target, string $sourceExpected, string $targetExpected): void
    {
        ProtectionTagsFilter::create()->filterTags($source, $target);

        self::assertSame($sourceExpected, $source);
        self::assertSame($targetExpected, $target);
    }

    public function filterTagsProvider(): iterable
    {
        yield [
            'source' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'target' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'sourceExpected' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'targetExpected' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
        ];

        yield [
            'source' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'target' => 'string <number type="date" name="default Y.m.d" source="2023.10.20" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Y*m*d" source="2023*10*20" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'sourceExpected' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'targetExpected' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
        ];

        yield [
            'source' => 'string <number type="date" name="default Ymd" source="20231022" iso="2023-10-22" target="2023-10-22" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231021" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'target' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231021" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'sourceExpected' => 'string 20231022 string <number type="date" name="default Ymd" source="20231021" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'targetExpected' => 'string 20231020 string <number type="date" name="default Ymd" source="20231021" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
        ];

        yield [
            'source' => 'string <number type="date" name="default Y*m*d" source="2023*10*21" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'target' => 'string <number type="date" name="default Ymd" source="20231021" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'sourceExpected' => 'string <number type="date" name="default Y*m*d" source="2023*10*21" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            'targetExpected' => 'string <number type="date" name="default Y*m*d" source="2023*10*21" iso="2023-10-21" target="2023-10-21" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
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
            'source' => 'string <number type="date" name="default Y*m*d" source="2023*10*22" iso="2023-10-22" target="2023-10-22" regex="rule_with_*"/> string <number type="date" name="default Ymd" source="20231022" iso="2023-10-22" target="2023.10.22" regex="other_rule"/> string',
            'target' => 'string <number type="date" name="default Ymd" source="20231022" iso="2023-10-22" target="2023.10.22" regex="other_rule"/> string <number type="date" name="default Y*m*d" source="2023*10*22" iso="2023-10-22" target="2023-10-22" regex="rule_with_*"/> string',
            'sourceExpected' => 'string <number type="date" name="default Y*m*d" source="2023*10*22" iso="2023-10-22" target="2023-10-22" regex="rule_with_*"/> string <number type="date" name="default Ymd" source="20231022" iso="2023-10-22" target="2023.10.22" regex="other_rule"/> string',
            'targetExpected' => 'string <number type="date" name="default Ymd" source="20231022" iso="2023-10-22" target="2023.10.22" regex="other_rule"/> string <number type="date" name="default Y*m*d" source="2023*10*22" iso="2023-10-22" target="2023-10-22" regex="rule_with_*"/> string',
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

    /**
     * @dataProvider filterTagsChunksProvider
     */
    public function testFilterTagsInChunks(array $source, array $target, array $sourceExpected, array $targetExpected): void
    {
        ProtectionTagsFilter::create()->filterTagsInChunks($source, $target);

        self::assertEquals($sourceExpected, $source);
        self::assertEquals($targetExpected, $target);
    }

    public function filterTagsChunksProvider(): iterable
    {
        $tag1 = '<number type="date" name="default" source="20231020" iso="2023-10-20" target="2023-10-20"/>';
        $converted1 = '<div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c742220736f757263653d223230323331303230222069736f3d22323032332d31302d323022207461726765743d22323032332d31302d3230222f number internal-tag ownttip"><span title="&lt;1/&gt;: Number" class="short">&lt;1/&gt;</span><span data-originalid="number" data-length="10" data-source="20231020" data-target="2023-10-20" class="full"></span></div>';

        $parsedTag1 = new NumberTag();
        $parsedTag1->originalContent = $tag1;
        $parsedTag1->tagNr = 1;
        $parsedTag1->id = 'number';
        $parsedTag1->tag = 'number';
        $parsedTag1->text = '{"source":"20231020","target":"2023-10-20"}';
        $parsedTag1->iso = '2023-10-20';
        $parsedTag1->source = '20231020';
        $parsedTag1->renderedTag = $converted1;

        $tag2 = '<number type="integer" name="default" source="1234" iso="1234" target=""/>';
        $converted2 = '<div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742220736f757263653d2231323334222069736f3d223132333422207461726765743d22222f number internal-tag ownttip"><span title="&lt;2/&gt;: Number" class="short">&lt;2/&gt;</span><span data-originalid="number" data-length="4" data-source="1234" data-target="1234" class="full"></span></div>';

        $parsedTag2 = new NumberTag();
        $parsedTag2->originalContent = $tag2;
        $parsedTag2->tagNr = 2;
        $parsedTag2->id = 'number';
        $parsedTag2->tag = 'number';
        $parsedTag2->text = '{"source":"1234","target":"1234"}';
        $parsedTag2->iso = '1234';
        $parsedTag2->source = '1234';
        $parsedTag2->renderedTag = $converted2;

        yield [
            'source' => ['string', $parsedTag1, ' string ', $parsedTag2, 'string'],
            'target' => ['string', $parsedTag1, ' string ', $parsedTag2, 'string'],
            'sourceExpected' => ['string', $parsedTag1, ' string ', $parsedTag2, 'string'],
            'targetExpected' => ['string', $parsedTag1, ' string ', $parsedTag2, 'string'],
        ];

        $parsedTag3 = clone $parsedTag1;
        $parsedTag3->iso = '2023-10-20';
        $parsedTag3->source = '2023.10.20';

        $parsedTag4 = clone $parsedTag2;
        $parsedTag4->iso = '1234';
        $parsedTag4->source = '1.234';

        yield [
            'source' => ['string', $parsedTag1, ' string ', $parsedTag2, 'string'],
            'target' => ['string', $parsedTag3, ' string ', $parsedTag4, 'string'],
            'sourceExpected' => ['string', $parsedTag1, ' string ', $parsedTag2, 'string'],
            'targetExpected' => ['string', $parsedTag1, ' string ', $parsedTag2, 'string'],
        ];

        $parsedTag5 = clone $parsedTag1;
        $parsedTag5->iso = '2023-10-22';
        $parsedTag5->source = '2023.10.22';

        yield [
            'source' => ['string', $parsedTag1, ' string ', $parsedTag2, 'string'],
            'target' => ['string', $parsedTag5, ' string ', $parsedTag4, 'string'],
            'sourceExpected' => ['string', '20231020', ' string ', $parsedTag2, 'string'],
            'targetExpected' => ['string', '2023.10.22', ' string ', $parsedTag2, 'string'],
        ];

        yield [
            'source' => ['string', '20231020', 'string'],
            'target' => ['string', $parsedTag5, 'string'],
            'sourceExpected' => ['string', '20231020', 'string'],
            'targetExpected' => ['string', '2023.10.22', 'string'],
        ];

        yield [
            'source' => ['string', $parsedTag5, 'string'],
            'target' => ['string', '20231020', 'string'],
            'sourceExpected' => ['string', '2023.10.22', 'string'],
            'targetExpected' => ['string', '20231020', 'string'],
        ];
    }
}
