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

namespace MittagQI\Translate5\Tools;

use Zend_Config;
use ZfExtended_RemoteAddress;

class CronIp
{
    private array $configuredIps = [];

    public function __construct(
        Zend_Config $config,
        private IpMatcher $ipMatcher,
        private ZfExtended_RemoteAddress $remoteAddress
    )
    {
        $configValue = explode(',', $config->runtimeOptions->cronIP);

        foreach ($configValue as $item) {
            $this->parse($item);
        }
    }

    /**
     * Check if particular IP against configured list, use the calculated remote address if omitted
     *
     * @param string|null $ip
     *
     * @return bool
     */
    public function isAllowed(?string $ip = null): bool
    {
        if(is_null($ip)) {
            $ip = $this->remoteAddress->getIpAddress();
        }

        if (in_array($ip, $this->configuredIps, true)) {
            return true;
        }

        foreach ($this->configuredIps as $range) {
            if ($this->ipMatcher->isIpInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return list af all configured IPs
     *
     * @return array
     */
    public function getAllowedIps(): array
    {
        return $this->configuredIps;
    }

    /**
     * Parse provided value and store in configuredIps property
     *
     * @param string $item
     *
     * @return void
     */
    private function parse(string $item): void
    {
        if ('' === $item) {
            return;
        }

        // IP can contain a subnet, so taking that in count
        [$subnet, ] = explode('/', $item);

        if (filter_var($subnet, FILTER_VALIDATE_IP)) {
            $this->configuredIps[] = $item;

            return;
        }

        // Given item failed validation for correct IP, try to resolve IP addresses corresponding given item
        $ips = $this->ipMatcher->getIpsForDomain($item);
        foreach ($ips as $ip) {
            $this->configuredIps[] = $ip;
        }
    }
}
