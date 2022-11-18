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

class IpMatcher
{
    /**
     * Checks if particular IP address is within the provided range or matches provided IP
     *
     * @param string $ipToCheck - ip address in dot notation to check against the particular range
     * @param string $range - ip range in dot notation with subnet mask e.g. (192.168.0.1/32),
     *                        please note that subnet mask can be omitted this will lead to simple ips comparison
     *
     * @return bool
     */
    public function isIpInRange(string $ipToCheck, string $range): bool
    {
        $parts = explode('/', $range);

        $subnet = $parts[0];
        $bits = isset($parts[1]) ? (int)$parts[1] : 32;

        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask; # nb: in case the supplied subnet wasn't correctly aligned
        $ip = ip2long($ipToCheck);

        return ($ip & $mask) === $subnet;
    }

    /**
     * Get a list of IPv4 addresses corresponding to a given Internet host name
     *
     * @param string $domainName
     *
     * @return array
     */
    public function getIpsForDomain(string $domainName): array
    {
        $ips = gethostbynamel($domainName);

        if (false === $ips) {
            return [];
        }

        return $ips;
    }
}
