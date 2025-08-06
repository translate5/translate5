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

namespace MittagQI\Translate5\Service;

/**
 * Represents the Proxy-Config for a dockerized T5
 * if configured must be just a hostname or a list of hostnames, not an URL
 */
final class Redis extends DockerServiceAbstract
{
    /**
     * TODO Service is currently introduced, so not mandatory yet
     */
    protected bool $mandatory = false;

    protected array $configurationConfig = [
        'name' => 'runtimeOptions.LanguageResources.cache.redisUrl',
        'type' => 'string',
        'url' => 'http://redis.:6379',
    ];

    public function checkUrl(string $url, ?string $healthCheck): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $port = (int) parse_url($url, PHP_URL_PORT) ?: 6379;

        if (empty(gethostbynamel($host))) {
            $this->errors = ['Could not resolve DNS'];

            return false;
        }

        try {
            $redis = new \Redis();
            $redis->connect($host, $port, 1.0);
            if ($redis->ping()) {
                return true;
            }
            $this->errors = ['Could not ping redis server: '];
        } catch (\RedisException $e) {
            $this->errors = [
                'Could not connect to redis server',
                $e->getMessage(),
            ];
        }

        return false;
    }
}
