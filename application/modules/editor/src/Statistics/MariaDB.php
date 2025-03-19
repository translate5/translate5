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

use Zend_Db;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;

class MariaDB extends AbstractStatisticsDB
{
    private ?Zend_Db_Adapter_Abstract $client = null;

    public function isAlive(): bool
    {
        return true;
    }

    public function select(string $sql, array $bind = []): array
    {
        $sql = $this->adjustBindings($sql, $bind);

        return $this->getClient()->fetchAll($sql, $bind, Zend_Db::FETCH_ASSOC);
    }

    public function oneAssoc(string $sql, array $bind = []): array
    {
        $sql = $this->adjustBindings($sql, $bind);
        if ($logQueryTime = ($this->logQueryTime && str_contains($sql, 'AVG('))) {
            $this->initQueryTime();
        }
        $row = $this->getClient()->fetchRow($sql, $bind);
        if ($logQueryTime) {
            $this->logQueryTime('Read aggregation query took %.2f ms', [
                'sql' => $sql,
            ]);
        }

        return is_array($row) ? $row : [];
    }

    public function upsert(string $table, array $values, array $columns = []): void
    {
        $db = $this->getClient();

        if ($isBulk = (count($values) > 1)) {
            $db->beginTransaction();
        }
        if ($this->logQueryTime) {
            $this->initQueryTime();
        }
        $sql = 'REPLACE INTO ' . $table . ' (' . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')';
        foreach ($values as $value) {
            $bindings = array_combine($columns, $value);
            $db->query($sql, $bindings);
        }
        if ($isBulk) {
            $db->commit();
        }
        if ($this->logQueryTime) {
            $this->logQueryTime('Write query took %.2f ms / ' . count($values) . ' record(s)');
        }
    }

    public function query(string $sql, array $bind = []): void
    {
        $sql = $this->adjustBindings($sql, $bind);
        $this->getClient()->query($sql, $bind);
    }

    public function optimize(string $table, bool $compact = false): void
    {
        $this->query('ANALYZE TABLE ' . $table);
        if ($compact) {
            $this->query('OPTIMIZE TABLE ' . $table);
        }
    }

    public function tableExists(string $table): bool
    {
        $row = $this->oneAssoc('SHOW TABLES LIKE "' . addcslashes($table, '_') . '"');

        return ! empty($row);
    }

    /**
     * handle IN(?) with array parameters
     */
    private function adjustBindings(string $sql, array &$bind): string
    {
        $db = $this->getClient();
        foreach ($bind as $param => $value) {
            if (is_array($value)) {
                if (ctype_digit((string) $value[0])) {
                    $sqlValue = implode(',', array_map('intval', $value));
                } else {
                    $sqlValue = implode(",", array_map([$db, 'quote'], $value));
                }
                $sql = str_replace(':' . $param, $sqlValue, $sql);
                unset($bind[$param]);
            }
        }

        return $sql;
    }

    private function getClient(): ?Zend_Db_Adapter_Abstract
    {
        if ($this->connectFailed) {
            return null;
        }
        if ($this->client === null) {
            $this->client = Zend_Db_Table::getDefaultAdapter();
            if (empty($this->client)) {
                $this->connectFailed = true;
            }
        }

        return $this->client;
    }
}
