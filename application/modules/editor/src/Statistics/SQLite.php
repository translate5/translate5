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

use SQLite3;
use SQLite3Result;

class SQLite extends AbstractStatisticsDB
{
    public const NAME = 'sqlite';

    protected static string $logDomain = self::NAME;

    protected string $sqlTruncate = 'DELETE FROM %s';

    private ?SQLite3 $client = null;

    public function isAlive(): bool
    {
        return null !== $this->getClient();
    }

    public function select(string $sql, array $bind = []): array
    {
        $result = $this->execute($sql, $bind);
        if ($result === false) {
            $this->logLastError();

            return [];
        }
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function oneAssoc(string $sql, array $bind = []): array
    {
        if ($logQueryTime = ($this->logQueryTime && str_contains($sql, 'AVG('))) {
            // we can query SQLite via DuckDB if ever needed
            $this->initQueryTime();
        }
        $result = $this->execute($sql, $bind);
        if ($logQueryTime) {
            $this->logQueryTime('Read aggregation query took %.2f ms', [
                'sql' => $sql,
            ]);
        }

        if ($result === false) {
            $this->logLastError();

            return [];
        }
        $row = $result->fetchArray(SQLITE3_ASSOC);

        return $row ?: [];
    }

    public function upsert(string $table, array $values, array $columns = []): void
    {
        $db = $this->getClient();
        if (null === $db) {
            return;
        }

        if ($isBulk = (count($values) > 1)) {
            $this->query('BEGIN');
        }
        if ($this->logQueryTime) {
            $this->initQueryTime();
        }
        $sql = 'REPLACE INTO ' . $table . ' (' . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')';
        foreach ($values as $value) {
            $bind = array_combine($columns, $value);
            $result = $this->execute($sql, $bind);
            if ($result === false) {
                $this->logLastError();
                if ($isBulk) {
                    $this->query('ROLLBACK');
                }

                return;
            }
        }
        if ($isBulk) {
            $this->query('COMMIT');
        }
        if ($this->logQueryTime) {
            $this->logQueryTime('Write query took %.2f ms / ' . count($values) . ' record(s)');
        }
    }

    public function query(string $sql, array $bind = []): void
    {
        $result = $this->execute($sql, $bind);
        if ($result === false) {
            $this->logLastError();
        }
    }

    public function optimize(string $table, bool $compact = false): void
    {
        $this->query('PRAGMA optimize');
        if ($compact) {
            $this->query('VACUUM');
        }
    }

    public function tableExists(string $table): bool
    {
        $row = $this->oneAssoc('SELECT COUNT(*) AS ok FROM sqlite_master WHERE type=\'table\' AND name=\'' . $table . '\'');

        return isset($row['ok']) && (bool) $row['ok'];
    }

    private function execute(string $sql, array $bind = []): SQLite3Result|false
    {
        $db = $this->getClient();
        if ($db === null) {
            return false;
        }
        foreach ($bind as $param => $value) {
            // handle IN(?) with array parameters
            if (is_array($value)) {
                if (ctype_digit((string) $value[0])) {
                    $sqlValue = implode(',', array_map('intval', $value));
                } else {
                    $sqlValue = "'" . implode("','", array_map([$db, 'escapeString'], $value)) . "'";
                }
                $sql = str_replace(':' . $param, $sqlValue, $sql);
            }
        }

        $stmt = $db->prepare($sql);
        if (! $stmt) {
            return false;
        }
        foreach ($bind as $param => $value) {
            if (! is_array($value)) {
                $stmt->bindValue(':' . $param, $value);
            }
        }

        return $stmt->execute();
    }

    private function logLastError(): void
    {
        $this->logger->error('E1633', 'Statistics DB error: {msg}', [
            'msg' => $this->client->lastErrorMsg(),
        ]);
    }

    /**
     * Only one attempt to connect: since 2nd call returns object if was connected successfully before
     * and NULL if previous connection attempt failed
     */
    private function getClient(): ?SQLite3
    {
        if ($this->connectFailed) {
            return null;
        }

        if (null === $this->client) {
            $dbFile = $this->config->resources->db->statistics?->sqliteDbname;

            if (null === $dbFile) {
                $this->connectFailed = true;

                return null;
            }

            try {
                // we don't create file if it is absent (read-only if autocreated)
                $this->client = new SQLite3($dbFile, SQLITE3_OPEN_READWRITE);
                $statConfig = $this->config->resources->db->statistics;
                if (isset($statConfig->sqliteSync)) {
                    // 0 = OFF | 1 = NORMAL | 2 = FULL (default)
                    // OFF offers WAY faster writes
                    $this->query('PRAGMA synchronous=' . $statConfig->sqliteSync);
                }
                if ((bool) $statConfig->sqliteWriteAheadLog) {
                    $this->query('PRAGMA journal_mode=wal');
                }
            } catch (\Throwable $e) {
                $this->connectFailed = true;
                $this->logger->error('E1632', 'Connection to Statistics DB failed: {msg}', [
                    'msg' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return $this->client;
    }
}
