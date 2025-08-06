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

namespace MittagQI\Translate5\LanguageResource;

use editor_Services_ServiceResult;
use Redis;
use RedisException;
use Zend_Config;
use Zend_Exception;
use Zend_Registry;

/**
 * TODO: Test implementation to test if caches are improving the performance.
 * If this goes into production we should consider to wrap with a proper cache layer, invalidation etc.
 */
class QueryCache
{
    private ?Redis $cache = null;

    private int $timeToLive = 3600;

    /**
     * @throws Zend_Exception
     */
    public static function create(): self
    {
        return new self(Zend_Registry::get('config'));
    }

    /**
     * @throws Zend_Exception
     */
    public function __construct(Zend_Config $config)
    {
        if (! extension_loaded('redis')) {
            $this->cache = null;

            return;
        }

        $url = $config->runtimeOptions->LanguageResources?->cache?->redisUrl ?? '';
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            return;
        }
        $port = parse_url($url, PHP_URL_PORT) ?: 6379;

        //config is in minutes
        $this->timeToLive = ($config->runtimeOptions->LanguageResources?->cache?->timeToLive ?? 60) * 60;

        try {
            $this->cache = new Redis();
            $this->cache->connect($host, $port);
        } catch (RedisException $e) {
            $this->cache = null;
            Zend_Registry::get('logger')->exception($e);

            return;
        }
    }

    public function get(array $key): ?editor_Services_ServiceResult
    {
        $key = $this->prepareKey($key);
        if ($this->cache === null) {
            return null;
        }

        $value = $this->cache->get($key);

        if ($value === false) {
            //error_log('MISS ' . $key);

            return null;
        }
        //error_log('HIT ' . $key);

        return unserialize($value);
    }

    public function set(array $key, editor_Services_ServiceResult $data): void
    {
        if ($this->cache !== null) {
            $this->cache->setex($this->prepareKey($key), $this->timeToLive, serialize($data));
        }
    }

    private function prepareKey(array $key): string
    {
        return join(':', $key);
    }
}
