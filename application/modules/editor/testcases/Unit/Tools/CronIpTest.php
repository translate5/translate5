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

use MittagQI\Translate5\Tools\CronIp;
use MittagQI\Translate5\Tools\IpMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zend_Config;
use ZfExtended_RemoteAddress;

class CronIpTest extends TestCase
{
    private MockObject|Zend_Config $configMock;
    private MockObject|IpMatcher $ipMatcherMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createConfiguredMock(Zend_Config::class, []);
        $this->ipMatcherMock = $this->createConfiguredMock(IpMatcher::class, []);
        $this->remoteAddressMock = $this->createConfiguredMock(ZfExtended_RemoteAddress::class, []);
    }

    public function testEmptyConfig(): void
    {
        $cronIp = $this->createCronIp('');

        $this->ipMatcherMock->expects(self::never())->method('isIpInRange');
        $this->remoteAddressMock->expects(self::never())->method('getIpAddress');

        self::assertFalse($cronIp->isAllowed($this->getFakeIpV4()));
    }

    public function testIpMismatch(): void
    {
        $ip = $this->getFakeIpV4();

        $cronIp = $this->createCronIp($ip);

        $this->ipMatcherMock->expects(self::atLeast(1))->method('isIpInRange')->willReturn(false);

        self::assertFalse($cronIp->isAllowed($this->getFakeIpV4()));
    }

    public function testWithEmptyGiven(): void
    {
        $ip = $this->getFakeIpV4();

        $cronIp = $this->createCronIp($ip);

        $this->ipMatcherMock->expects(self::never())->method('isIpInRange');
        $this->remoteAddressMock->expects(self::atLeast(1))->method('getIpAddress')->willReturn($ip);

        self::assertTrue($cronIp->isAllowed(null));
    }

    public function testIpMatch(): void
    {
        $ips = [$this->getFakeIpV4(), $this->getFakeIpV4(), $this->getFakeIpV4()];

        $cronIp = $this->createCronIp(implode(',', $ips));

        $this->ipMatcherMock->expects(self::never())->method('isIpInRange');

        self::assertTrue($cronIp->isAllowed($ips[array_rand($ips)]));
    }

    public function testIpInRange(): void
    {
        $ip = $this->getFakeIpV4();

        $cronIp = $this->createCronIp($ip . '/30');

        $this->ipMatcherMock->expects(self::atLeast(1))->method('isIpInRange')->willReturn(true);

        self::assertTrue($cronIp->isAllowed($ip));
    }

    public function testIpMatchesDomain(): void
    {
        $domain = 'hello-world.com';

        $ip = $this->getFakeIpV4();
        $this->ipMatcherMock->expects(self::once())
            ->method('getIpsForDomain')
            ->with($domain)
            ->willReturn([$ip]);

        $cronIp = $this->createCronIp($domain);

        $this->ipMatcherMock->expects(self::never())->method('isIpInRange');

        self::assertTrue($cronIp->isAllowed($ip));
    }

    public function testGetAllowedIps(): void
    {
        $ips = [$this->getFakeIpV4(), $this->getFakeIpV4(), $this->getFakeIpV4()];

        $cronIp = $this->createCronIp(implode(',', $ips));

        self::assertEquals($ips, $cronIp->getAllowedIps());
    }

    private function createCronIp(string $value): CronIp
    {
        $this->mockConfigValue($value);

        return new CronIp($this->configMock, $this->ipMatcherMock, $this->remoteAddressMock);
    }

    public function mockConfigValue(string $value): void
    {
        $this->configMock->method('__get')->willReturn($this->createConfiguredMock(Zend_Config::class, [
            '__get' => $value
        ]));
    }

    public function getFakeIpV4(): string
    {
        return sprintf(
            '%d.%d.%d.%d',
            random_int(1, 255),
            random_int(0, 255),
            random_int(0, 255),
            random_int(0, 255),
        );
    }
}
