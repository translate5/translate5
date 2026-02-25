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

namespace MittagQI\Translate5\Plugins\TMMaintenance\test\Integration\TmxFilter;

use MittagQI\Translate5\Plugins\TMMaintenance\TmxFilter\SearchFilter;
use MittagQI\Translate5\T5Memory\DTO\SearchDTO;
use MittagQI\Translate5\T5Memory\Enum\SearchMode;
use PHPUnit\Framework\TestCase;

class SearchFilterTest extends TestCase
{
    private const TMX_FILE = __DIR__ . '/SearchFilterTest/test.tmx';

    private static string $testFile = '';

    public function setUp(): void
    {
        copy(self::TMX_FILE, self::$testFile = sys_get_temp_dir() . '/test_' . bin2hex(random_bytes(8)) . '.tmx');
    }

    public function tearDown(): void
    {
        if (file_exists(self::$testFile)) {
            unlink(self::$testFile);
        }
    }

    /**
     * @dataProvider searchDtoProvider
     */
    public function test(SearchDTO $searchDTO, string $expectedFile): void
    {
        $filter = SearchFilter::create();
        $filter->filter(self::$testFile, $searchDTO);

        self::assertFileExists(self::$testFile);
        self::assertFileEquals($expectedFile, self::$testFile);
    }

    public function searchDtoProvider(): iterable
    {
        #region author
        yield 'exact author, case sensitive (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: 'OTHER MANAGER',
                authorMode: SearchMode::Exact,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_author_filtered.tmx',
        ];

        yield 'exact author, case insensitive (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: false,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: false,
                sourceLanguage: '',
                targetLanguage: '',
                author: 'other manager',
                authorMode: SearchMode::Exact,
                authorCaseSensitive: false,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: false,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: false,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: false,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_author_filtered.tmx',
        ];

        yield 'exact author case sensitive (not filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: 'other manager',
                authorMode: SearchMode::Exact,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_not_filtered.tmx',
        ];

        yield 'author contains, case sensitive (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: 'OTHER',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_author_filtered.tmx',
        ];

        yield 'author contains, case insensitive (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: false,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: false,
                sourceLanguage: '',
                targetLanguage: '',
                author: 'other',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: false,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: false,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: false,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: false,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_author_filtered.tmx',
        ];

        yield 'author contains, case sensitive (not filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: 'other',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_not_filtered.tmx',
        ];
        #endregion author

        #region context
        yield 'exact context, case sensitive (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: 'SOME_CONTEXT',
                contextMode: SearchMode::Exact,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_context_filtered.tmx',
        ];

        yield 'exact context, case insensitive (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: false,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: false,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: false,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: false,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: false,
                context: 'some_context',
                contextMode: SearchMode::Exact,
                contextCaseSensitive: false,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_context_filtered.tmx',
        ];

        yield 'exact context case sensitive (not filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: 'some_context',
                contextMode: SearchMode::Exact,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_not_filtered.tmx',
        ];

        yield 'context contains, case sensitive (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: 'SOME',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_context_filtered.tmx',
        ];

        yield 'context contains, case insensitive (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: false,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: false,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: false,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: false,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: false,
                context: 'some',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: false,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_context_filtered.tmx',
        ];

        yield 'context contains, case sensitive (not filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: 'some',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_not_filtered.tmx',
        ];
        #endregion context

        #region document
        yield 'exact document, case sensitive (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: 'some-file.txt',
                documentMode: SearchMode::Exact,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_document_filtered.tmx',
        ];

        yield 'exact document, case insensitive (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: false,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: false,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: false,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: false,
                document: 'SOME-file.txt',
                documentMode: SearchMode::Exact,
                documentCaseSensitive: false,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: false,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_document_filtered.tmx',
        ];

        yield 'exact document case sensitive (not filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: 'SOME-file.txt',
                documentMode: SearchMode::Exact,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_not_filtered.tmx',
        ];

        yield 'document contains, case sensitive (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: 'some',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_document_filtered.tmx',
        ];

        yield 'document contains, case insensitive (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: false,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: false,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: false,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: false,
                document: 'SOME',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: false,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: false,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_document_filtered.tmx',
        ];

        yield 'document contains, case sensitive (not filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: 'SOME',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_not_filtered.tmx',
        ];
        #endregion document

        #region source
        yield 'exact source, case sensitive (filtered)' => [
            new SearchDTO(
                source: 'Unser schönes 4 Segment',
                sourceMode: SearchMode::Exact,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_source_filtered.tmx',
        ];

        yield 'exact source, case insensitive (filtered)' => [
            new SearchDTO(
                source: 'UNSER schönes 4 Segment',
                sourceMode: SearchMode::Exact,
                sourceCaseSensitive: false,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_source_filtered.tmx',
        ];

        yield 'exact source case sensitive (not filtered)' => [
            new SearchDTO(
                source: 'UNSER schönes 4 Segment',
                sourceMode: SearchMode::Exact,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_not_filtered.tmx',
        ];

        yield 'source contains, case sensitive (filtered)' => [
            new SearchDTO(
                source: 'schönes 4',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_source_filtered.tmx',
        ];

        yield 'source contains, case insensitive (filtered)' => [
            new SearchDTO(
                source: 'schöNES 4',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: false,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_source_filtered.tmx',
        ];

        yield 'source contains, case sensitive (not filtered)' => [
            new SearchDTO(
                source: 'schöNES 4',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_not_filtered.tmx',
        ];
        #endregion source

        #region target
        yield 'exact target, case sensitive (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: 'Our nice 9 segment',
                targetMode: SearchMode::Exact,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_target_filtered.tmx',
        ];

        yield 'exact target, case insensitive (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: 'Our NICE 9 segment',
                targetMode: SearchMode::Exact,
                targetCaseSensitive: false,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_target_filtered.tmx',
        ];

        yield 'exact target case sensitive (not filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: 'Our NICE 9 segment',
                targetMode: SearchMode::Exact,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_not_filtered.tmx',
        ];

        yield 'target contains, case sensitive (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: 'nice 9',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_target_filtered.tmx',
        ];

        yield 'target contains, case insensitive (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: 'NICE 9',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: false,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_target_filtered.tmx',
        ];

        yield 'target contains, case sensitive (not filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: 'NICE 9',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_not_filtered.tmx',
        ];
        #endregion target

        #region date
        yield 'date between (filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: strtotime('2025-03-04 14:24:40Z'),
                creationDateTo: strtotime('2025-03-04 16:24:40Z'),
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_date_filtered.tmx',
        ];

        yield 'date between (not filtered)' => [
            new SearchDTO(
                source: '',
                sourceMode: SearchMode::Contains,
                sourceCaseSensitive: true,
                target: '',
                targetMode: SearchMode::Contains,
                targetCaseSensitive: true,
                sourceLanguage: '',
                targetLanguage: '',
                author: '',
                authorMode: SearchMode::Contains,
                authorCaseSensitive: true,
                creationDateFrom: strtotime('2025-03-07 14:24:40Z'),
                creationDateTo: strtotime('2025-03-08 14:24:40Z'),
                additionalInfo: '',
                additionalInfoMode: SearchMode::Contains,
                additionalInfoCaseSensitive: true,
                document: '',
                documentMode: SearchMode::Contains,
                documentCaseSensitive: true,
                context: '',
                contextMode: SearchMode::Contains,
                contextCaseSensitive: true,
                onlyCount: false,
            ),
            __DIR__ . '/SearchFilterTest/expected_not_filtered.tmx',
        ];
        #endregion date
    }
}
