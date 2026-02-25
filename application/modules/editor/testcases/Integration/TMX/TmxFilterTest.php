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

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Integration\TMX;

use MittagQI\Translate5\T5Memory\DTO\TmxFilterOptions;
use MittagQI\Translate5\TMX\Filter\TmxFilter;
use MittagQI\Translate5\TMX\TmxUtilsWrapper;
use PHPUnit\Framework\TestCase;

class TmxFilterTest extends TestCase
{
    private string $tmpFile;

    private string $tmpResultFile;

    public function setUp(): void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'tmxfilter_');
        $this->tmpResultFile = tempnam(sys_get_temp_dir(), 'tmxfilter_result_');
    }

    public function tearDown(): void
    {
        @unlink($this->tmpFile);
        @unlink($this->tmpResultFile);
    }

    /**
     * @dataProvider rustProvider
     */
    public function testFilterNoSkipKeep(bool $rust): void
    {
        $tmxFilter = $this->getFilter($rust);

        $filterOptions = new TmxFilterOptions(
            skipAuthor: false,
            skipDocument: false,
            skipContext: false,
            preserveTargets: true,
        );

        copy(__DIR__ . '/TmxFilterTest/test.tmx', $this->tmpFile);

        $it = $tmxFilter->filter($this->tmpFile, $filterOptions);

        foreach ($it as [$node, $type]) {
            file_put_contents($this->tmpResultFile, $node, FILE_APPEND);
        }

        self::assertFileEquals(__DIR__ . '/TmxFilterTest/test_no_skip_keep.tmx', $this->tmpResultFile);
    }

    /**
     * @dataProvider rustProvider
     */
    public function testFilterNoSkipNoKeep(bool $rust): void
    {
        $tmxFilter = $this->getFilter($rust);

        $filterOptions = new TmxFilterOptions(
            skipAuthor: false,
            skipDocument: false,
            skipContext: false,
            preserveTargets: false,
        );

        copy(__DIR__ . '/TmxFilterTest/test.tmx', $this->tmpFile);

        $it = $tmxFilter->filter($this->tmpFile, $filterOptions);

        foreach ($it as [$node, $type]) {
            file_put_contents($this->tmpResultFile, $node, FILE_APPEND);
        }

        self::assertFileEquals(__DIR__ . '/TmxFilterTest/test_no_skip_no_keep.tmx', $this->tmpResultFile);
    }

    /**
     * @dataProvider rustProvider
     */
    public function testFilterSkipDocumentNoKeep(bool $rust): void
    {
        $tmxFilter = $this->getFilter($rust);

        $filterOptions = new TmxFilterOptions(
            skipAuthor: false,
            skipDocument: true,
            skipContext: false,
            preserveTargets: false,
        );

        copy(__DIR__ . '/TmxFilterTest/test.tmx', $this->tmpFile);

        $it = $tmxFilter->filter($this->tmpFile, $filterOptions);

        foreach ($it as [$node, $type]) {
            file_put_contents($this->tmpResultFile, $node, FILE_APPEND);
        }

        self::assertFileEquals(__DIR__ . '/TmxFilterTest/test_skip_document_no_keep.tmx', $this->tmpResultFile);
    }

    public function rustProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    private function getFilter(bool $rust): TmxFilter
    {
        return new TmxFilter(
            TmxUtilsWrapper::create(),
            new \Zend_Config([
                'runtimeOptions' => [
                    'LanguageResources' => [
                        't5memory' => [
                            'useTmxUtilsFilter' => $rust,
                        ],
                    ],
                ],
            ]),
        );
    }
}
