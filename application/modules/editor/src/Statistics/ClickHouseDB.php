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

namespace MittagQI\Translate5\Statistics;

use ClickHouseDB\Client;
use Throwable;

class ClickHouseDB extends AbstractStatisticsDB
{
    public const NAME = 'clickhousedb';

    protected static string $logDomain = 'clickhouse';

    private const connectTimeout = 1; // seconds

    private const queryTimeout = 5; // seconds

    private const bulkInsertTimeout = 200; // seconds

    private ?Client $client = null;

    public function isAlive(): bool
    {
        return null !== $this->getClient();
    }

    public function select(string $sql, array $bind = []): array
    {
        return (array) $this->getClient()?->select($sql, $bind)->rows();
    }

    public function oneAssoc(string $sql, array $bind = []): array
    {
        if ($logQueryTime = ($this->logQueryTime && str_contains($sql, 'AVG('))) {
            $this->initQueryTime();
        }
        $result = (array) $this->getClient()?->select($sql, $bind)->fetchOne();
        if ($logQueryTime) {
            $this->logQueryTime('Read aggregation query took %.2f ms', [
                'sql' => $sql,
            ]);
        }

        return $result;
    }

    public function upsert(string $table, array $values, array $columns): void
    {
        $client = $this->getClient();
        if (null === $client) {
            return;
        }
        $timeout = $client->getTimeout();
        if ($isBulk = (count($values) > 1)) {
            $client->enableHttpCompression()->setTimeout(self::bulkInsertTimeout);
        }

        if ($this->logQueryTime) {
            $this->initQueryTime();
        }
        // behaves as REPLACE when table ENGINE = ReplacingMergeTree
        $client->insert($table, $values, $columns);
        if ($this->logQueryTime) {
            $this->logQueryTime('Write query took %.2f ms / ' . count($values) . ' record(s)');
        }

        if ($isBulk) { // 1s delay is recommended between bulk inserts
            $client->enableHttpCompression(false)->setTimeout($timeout);
            sleep(1);
        }
    }

    public function query(string $sql, array $bind = []): void
    {
        $this->getClient()?->write($sql, $bind);
    }

    public function optimize(string $table, bool $compact = false): void
    {
        // With FINAL seems to be heavily disk intensive
        // https://kb.altinity.com/altinity-kb-queries-and-syntax/altinity-kb-optimize-vs-optimize-final/
        $this->query('OPTIMIZE TABLE ' . $table . ($compact ? ' FINAL' : ''));
    }

    public function tableExists(string $table): bool
    {
        try {
            $this->select('DESCRIBE TABLE ' . $table);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Only one attempt to connect/ping: since 2nd call returns object if was connected successfully before
     * and NULL if previous connection attempt failed
     */
    private function getClient(): ?Client
    {
        if ($this->connectFailed) {
            return null;
        }

        if (null === $this->client) {
            $params = $this->config->resources->db->clickhouse?->params;

            if (null === $params) {
                $this->connectFailed = true;

                return null;
            }

            try {
                /**
                 * $clientConfig keys required: host, username, password, dbname
                 */
                $clientConfig = $params->toArray();
            } catch (\Exception) {
                $this->connectFailed = true;

                return null;
            }

            try {
                $client = new Client([
                    'host' => $clientConfig['host'],
                    'port' => '8123',
                    'username' => $clientConfig['username'],
                    'password' => $clientConfig['password'],
                    'https' => false,
                ]);

                $client->database($clientConfig['dbname'])->setConnectTimeOut(self::connectTimeout);
                $client->setTimeout(self::queryTimeout);

                // if can`t connect throw exception
                $client->ping(true);
                $this->client = $client;
            } catch (\Throwable $e) {
                $this->connectFailed = true;
                $this->logger->error('E1632', 'Connection to Analytics DB failed: {msg}', [
                    'msg' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return $this->client;
    }
}
