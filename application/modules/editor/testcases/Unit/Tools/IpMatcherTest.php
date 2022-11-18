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

namespace MittagQI\Translate5\Test\Unit\Tools;

use MittagQI\Translate5\Tools\IpMatcher;
use PHPUnit\Framework\TestCase;

class IpMatcherTest extends TestCase
{
    private IpMatcher $ipMatcher;

    protected function setUp(): void
    {
        $this->ipMatcher = new IpMatcher();
    }

    public function provideDirectIp(): array
    {
        return [
            ['192.168.0.1', '192.168.0.1', true],
            ['192.168.0.1', '192.168.0.2', false],
        ];
    }

    /**
     * @dataProvider provideDirectIp
     */
    public function testDirectIp(string $ipToCheck, string $range, bool $expected): void
    {
        self::assertEquals($expected, $this->ipMatcher->isIpInRange($ipToCheck, $range));
    }

    public function provideRange(): array
    {
        return [
            ['192.168.0.1', '192.168.0.1/32', true],
            ['192.168.0.2', '192.168.0.1/30', true],
            ['192.168.0.' . random_int(1, 255), '192.168.0.1/24', true],
            ['192.168.0.5', '192.168.0.1/30', false],
            ['192.168.1.1', '192.168.0.1/24', false],
        ];
    }

    /**
     * @dataProvider provideRange
     */
    public function testRange(string $ipToCheck, string $range, bool $expected): void
    {
        self::assertEquals($expected, $this->ipMatcher->isIpInRange($ipToCheck, $range));
    }
}
