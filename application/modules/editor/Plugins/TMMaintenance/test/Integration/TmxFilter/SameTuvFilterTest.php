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

use MittagQI\Translate5\T5Memory\TmxFilter\SameTuvFilter;
use PHPUnit\Framework\TestCase;

class SameTuvFilterTest extends TestCase
{
    private const TMX_FILE = __DIR__ . '/SameTuvFilterTest/test.tmx';

    private const EXPECTED_FILTERED_TMX_FILE = __DIR__ . '/SameTuvFilterTest/expected.tmx';

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

    public function test(): void
    {
        $filter = SameTuvFilter::create();
        $filter->filter(self::$testFile);

        self::assertFileExists(self::$testFile);
        self::assertFileEquals(self::EXPECTED_FILTERED_TMX_FILE, self::$testFile);
    }
}
