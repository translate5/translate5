<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Statistics;

use Zend_Config;
use ZfExtended_Logger;

abstract class AbstractStatisticsDB
{
    protected bool $connectFailed = false;

    protected bool $logQueryTime;

    protected static string $logDomain = 'statistics';

    protected string $sqlTruncate = 'TRUNCATE TABLE %s';

    private int $time = 0;

    final public function __construct(
        protected readonly Zend_Config $config,
        protected readonly ZfExtended_Logger $logger,
    ) {
        $this->logQueryTime = (bool) $config->resources->db->statistics?->logQueryTime;
    }

    public static function create(): AbstractStatisticsDB
    {
        return new static(
            \Zend_Registry::get('config'),
            \Zend_Registry::get('logger')->cloneMe('core.db.' . static::$logDomain),
        );
    }

    public function truncate(string $table): void
    {
        if ($this->logQueryTime) {
            $this->initQueryTime();
        }
        $this->query(sprintf($this->sqlTruncate, $table));
        $this->optimize($table, true);
        if ($this->logQueryTime) {
            $this->logQueryTime('Truncate/optimize queries took %.2f ms');
        }
    }

    abstract public function isAlive(): bool;

    abstract public function select(string $sql, array $bind = []): array;

    abstract public function oneAssoc(string $sql, array $bind = []): array;

    abstract public function upsert(string $table, array $values, array $columns = []): void;

    abstract public function query(string $sql, array $bind = []): void;

    /**
     * Recommended to run after performing large updates (inserts and/or deletes)
     */
    abstract public function optimize(string $table, bool $compact = false): void;

    abstract public function tableExists(string $table): bool;

    protected function initQueryTime(): void
    {
        $this->time = -hrtime(true);
    }

    /**
     * logs the given stuff
     * @param $message - use "%.2f" to render query time in milliseconds
     * @param $extraData optional extra data / info
     */
    protected function logQueryTime(string $message, array $extraData = []): void
    {
        if ($this->time === 0) {
            return;
        }
        $time = round(($this->time + hrtime(true)) / 1e+6, 2);
        $this->time = 0;
        $this->logger->info('', sprintf($message, $time), $extraData);
    }
}
