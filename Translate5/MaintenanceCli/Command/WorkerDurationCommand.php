<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace Translate5\MaintenanceCli\Command;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use MittagQI\ZfExtended\Worker\Logger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;

class WorkerDurationCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'worker:duration';

    protected function configure(): void
    {
        $this
            ->setDescription('Analyze worker.log and show queue/dispatch/run durations per worker')
            ->setHelp(
                'Analyzes ' . Logger::LOG_NAME . ' and prints finished workers with timings.' . PHP_EOL
                . 'Columns:' . PHP_EOL
                . '  - Queue->Dispatch: from queue to dispatcher run' . PHP_EOL
                . '  - Dispatch->Run: from dispatcher run to running' . PHP_EOL
                . '  - Runtime: from running to done/defunct' . PHP_EOL
                . PHP_EOL
                . 'The optional argument filters worker class names by substring (case-insensitive).' . PHP_EOL
                . 'Use --task with either taskGuid or taskId.'
            )
            ->setAliases(['wduration']);

        $this->addArgument(
            'worker-filter',
            InputArgument::OPTIONAL,
            'Optional worker class substring filter (case-insensitive).'
        );

        $this->addOption(
            'since',
            's',
            InputOption::VALUE_REQUIRED,
            'Include workers queued since this point in time (strtotime parsable).'
        );

        $this->addOption(
            'until',
            'u',
            InputOption::VALUE_REQUIRED,
            'Include workers queued until this point in time (strtotime parsable). '
            . 'If the value starts with "+", it is applied relative to --since.'
        );

        $this->addOption(
            'today',
            null,
            InputOption::VALUE_NONE,
            'Shortcut for --since "today 00:00:00" and --until "tomorrow 00:00:00".'
        );

        $this->addOption(
            'task',
            null,
            InputOption::VALUE_REQUIRED,
            'Filter workers by taskGuid or taskId (from queue-data parameters/task info).'
        );
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateInvalidTimeZoneException
     * @throws Zend_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5AppOrTest();

        $logPath = APPLICATION_DATA . '/logs/' . Logger::LOG_NAME;
        if (! is_readable($logPath)) {
            $this->io->error('Log file "' . $logPath . '" not found or not readable.');

            return self::FAILURE;
        }

        [$since, $until] = $this->resolveTimeWindow();
        if ($since === false || $until === false) {
            return self::FAILURE;
        }

        $workerFilter = (string) ($input->getArgument('worker-filter') ?? '');
        $taskFilter = trim((string) ($input->getOption('task') ?? ''));

        $workers = $this->readWorkerData($logPath);
        $rows = $this->buildRows($workers, $since, $until, $workerFilter, $taskFilter);

        if (empty($rows)) {
            $this->io->warning('No matching finished workers found.');

            return self::SUCCESS;
        }

        $sumQueueToDispatch = 0.0;
        $sumDispatchToRun = 0.0;
        $sumRuntime = 0.0;

        $table = $this->io->createTable();
        $table->setHeaders(['Worker ID', 'Worker Name', 'Queue->Dispatch', 'Dispatch->Run', 'Runtime', 'Final status']);

        foreach ($rows as $row) {
            $table->addRow([
                $row['id'],
                $row['worker'],
                $this->formatDuration($row['queueToDispatch']),
                $this->formatDuration($row['dispatchToRun']),
                $this->formatDuration($row['runtime']),
                $row['status'],
            ]);

            $sumQueueToDispatch += $row['queueToDispatch'] ?? 0.0;
            $sumDispatchToRun += $row['dispatchToRun'] ?? 0.0;
            $sumRuntime += $row['runtime'] ?? 0.0;
        }

        $table->addRow([
            'SUM',
            '-',
            $this->formatDuration($sumQueueToDispatch),
            $this->formatDuration($sumDispatchToRun),
            $this->formatDuration($sumRuntime),
            '-',
        ]);

        $table->render();

        if ($taskFilter !== '') {
            $this->writeTaskEndToEnd($rows);
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0:int|false|null,1:int|false|null}
     * @throws DateInvalidTimeZoneException
     * @throws DateMalformedStringException
     */
    private function resolveTimeWindow(): array
    {
        $since = null;
        $until = null;

        if ($this->input->getOption('today')) {
            $tz = new DateTimeZone((string) date_default_timezone_get());
            $start = new DateTimeImmutable('today', $tz);
            $end = $start->modify('+1 day');
            $since = $start->getTimestamp();
            $until = $end->getTimestamp();

            return [$since, $until];
        }

        $sinceRaw = $this->input->getOption('since');
        if (is_string($sinceRaw) && $sinceRaw !== '') {
            $since = strtotime($sinceRaw);
            if ($since === false) {
                $this->io->error('Could not parse --since value: ' . $sinceRaw);

                return [false, false];
            }
        }

        $untilRaw = $this->input->getOption('until');
        if (is_string($untilRaw) && $untilRaw !== '') {
            if ($since !== null && str_starts_with($untilRaw, '+')) {
                $until = strtotime(date('Y-m-d H:i:s', $since) . ' ' . $untilRaw);
            } else {
                $until = strtotime($untilRaw);
            }

            if ($until === false) {
                $this->io->error('Could not parse --until value: ' . $untilRaw);

                return [false, false];
            }
        }

        return [$since, $until];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readWorkerData(string $logPath): array
    {
        $workers = [];
        $fh = fopen($logPath, 'rb');

        if ($fh === false) {
            return $workers;
        }

        while (($line = fgets($fh)) !== false) {
            $entry = $this->parseLine(trim($line));
            if ($entry === null) {
                continue;
            }

            $id = $entry['id'];
            if (! isset($workers[$id])) {
                $workers[$id] = [
                    'id' => $id,
                    'queueAt' => null,
                    'dispatchAt' => null,
                    'runningAt' => null,
                    'terminalAt' => null,
                    'terminalType' => null,
                    'firstAt' => $entry['ts'],
                    'taskGuid' => null,
                    'taskIds' => [],
                ];
            }

            $workers[$id]['worker'] = $entry['worker'];
            if ($workers[$id]['firstAt'] === null || $entry['ts'] < $workers[$id]['firstAt']) {
                $workers[$id]['firstAt'] = $entry['ts'];
            }

            if ($entry['taskGuid'] !== null && $entry['taskGuid'] !== '') {
                $workers[$id]['taskGuid'] = $entry['taskGuid'];
            }

            if ($entry['type'] === 'queue') {
                $workers[$id]['queueAt'] = $entry['ts'];
                $this->mergeTaskDataFromQueuePayload($workers[$id], $entry['queueData']);
            } elseif ($entry['type'] === 'dispatcher run') {
                $workers[$id]['dispatchAt'] = $entry['ts'];
            } elseif ($entry['type'] === 'running') {
                $workers[$id]['runningAt'] = $entry['ts'];
            } elseif ($entry['type'] === 'done' || $entry['type'] === 'defunct'
                || str_starts_with($entry['type'], 'defunc')) {
                $workers[$id]['terminalAt'] = $entry['ts'];
                $workers[$id]['terminalType'] = $entry['type'];
            }
        }

        fclose($fh);

        return $workers;
    }

    /**
     * @param array<int, array<string, mixed>> $workers
     * @return array<int, array{
     *   id:int,
     *   worker:string,
     *   taskKey:string,
     *   queueToDispatch : ?float,
     *   dispatchToRun : ?float,
     *   runtime : ?float,
     *   status:string,
     *   queueAt : ?DateTimeImmutable,
     *   terminalAt : ?DateTimeImmutable
     * }>
     */
    private function buildRows(
        array $workers,
        ?int $since,
        ?int $until,
        string $workerFilter,
        string $taskFilter
    ): array {
        $rows = [];

        foreach ($workers as $worker) {
            if (! $this->isFinalized($worker)) {
                continue;
            }

            if ($workerFilter !== '' && stripos((string) $worker['worker'], $workerFilter) === false) {
                continue;
            }

            $referenceTime = $worker['queueAt'] ?? $worker['firstAt'];
            if (! $referenceTime instanceof DateTimeImmutable) {
                continue;
            }

            if ($since !== null && $referenceTime->getTimestamp() < $since) {
                continue;
            }
            if ($until !== null && $referenceTime->getTimestamp() >= $until) {
                continue;
            }

            if (! $this->matchesTaskFilter($worker, $taskFilter)) {
                continue;
            }

            $queueToDispatch = $this->calculateDuration($worker['queueAt'], $worker['dispatchAt']);
            $dispatchToRun = $this->calculateDuration($worker['dispatchAt'], $worker['runningAt']);
            $runtime = $this->calculateDuration($worker['runningAt'], $worker['terminalAt']);
            $status = ((string) $worker['terminalType'] === 'done') ? 'successful' : 'defunct';

            $rows[] = [
                'id' => (int) $worker['id'],
                'worker' => (string) $worker['worker'],
                'taskKey' => $this->resolveTaskKey($worker),
                'queueToDispatch' => $queueToDispatch,
                'dispatchToRun' => $dispatchToRun,
                'runtime' => $runtime,
                'status' => $status,
                'queueAt' => $worker['queueAt'],
                'terminalAt' => $worker['terminalAt'],
            ];
        }

        usort(
            $rows,
            static fn (array $a, array $b): int => ($a['id'] <=> $b['id'])
        );

        return $rows;
    }

    /**
     * @param array<string,mixed> $worker
     */
    private function isFinalized(array $worker): bool
    {
        if (! isset($worker['terminalType'])) {
            return false;
        }

        $terminalType = (string) $worker['terminalType'];

        return $terminalType === 'done' || $terminalType === 'defunct' || str_starts_with($terminalType, 'defunc');
    }

    /**
     * @param array<string,mixed> $worker
     */
    private function matchesTaskFilter(array $worker, string $taskFilter): bool
    {
        if ($taskFilter === '') {
            return true;
        }

        $normalizedFilter = $this->normalizeTaskGuid($taskFilter);

        $workerTaskGuid = $worker['taskGuid'] ?? null;
        if (is_string($workerTaskGuid) && $this->normalizeTaskGuid($workerTaskGuid) === $normalizedFilter) {
            return true;
        }

        foreach ($worker['taskIds'] ?? [] as $taskId) {
            if ((string) $taskId === $taskFilter) {
                return true;
            }
        }

        return false;
    }

    private function normalizeTaskGuid(string $guid): string
    {
        return strtolower(trim($guid, "{} \t\n\r\0\x0B"));
    }

    private function formatDuration(?float $seconds): string
    {
        if ($seconds === null) {
            return '-';
        }

        return sprintf('%.3fs', $seconds);
    }

    private function calculateDuration(?DateTimeImmutable $start, ?DateTimeImmutable $end): ?float
    {
        if (! $start instanceof DateTimeImmutable || ! $end instanceof DateTimeImmutable) {
            return null;
        }

        return max(0.0, $this->toFloatTimestamp($end) - $this->toFloatTimestamp($start));
    }

    private function toFloatTimestamp(DateTimeImmutable $date): float
    {
        return (float) $date->format('U.u');
    }

    /**
     * @return array{
     *     ts:DateTimeImmutable,
     *     id:int,
     *     worker:string,
     *     type:string,
     *     taskGuid : ?string,
     *     queueData : ?array<string,mixed>
     *   }|null
     */
    private function parseLine(string $line): ?array
    {
        if ($line === '') {
            return null;
        }

        if (! preg_match(
            '/^(?<ts>\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}\\.\\d{6} [+-]\\d{2}:\\d{2}) (?<type>[a-z]+(?: [a-z]+)*) (?<id>\\d+) (?<worker>[^ ]+)(?<rest>.*)$/',
            $line,
            $match
        )) {
            return null;
        }

        $timestamp = DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u P', $match['ts']);
        if (! $timestamp instanceof DateTimeImmutable) {
            return null;
        }

        $rest = $match['rest'];
        $taskGuid = null;
        if (preg_match('/ task: (?<task>[^ ]+)/', $rest, $taskMatch)) {
            $taskGuid = trim($taskMatch['task']);
        }

        $queueData = null;
        if ($match['type'] === 'queue' && preg_match('/ data: (?<json>\\{.*?\\})(?= task: | event: |$)/', $rest, $dataMatch)) {
            $decoded = json_decode($dataMatch['json'], true);
            if (is_array($decoded)) {
                $queueData = $decoded;
            }
        }

        return [
            'ts' => $timestamp,
            'type' => $match['type'],
            'id' => (int) $match['id'],
            'worker' => $match['worker'],
            'taskGuid' => $taskGuid,
            'queueData' => $queueData,
        ];
    }

    /**
     * @param array<string,mixed> $worker
     * @param array<string,mixed>|null $queueData
     */
    private function mergeTaskDataFromQueuePayload(array &$worker, ?array $queueData): void
    {
        if (! is_array($queueData)) {
            return;
        }

        $taskIds = [];
        $taskGuids = [];
        $this->extractTaskData($queueData, $taskIds, $taskGuids);

        if (! empty($taskGuids) && empty($worker['taskGuid'])) {
            $worker['taskGuid'] = reset($taskGuids);
        }

        $existingTaskIds = $worker['taskIds'] ?? [];
        $worker['taskIds'] = array_values(array_unique(array_merge($existingTaskIds, $taskIds)));
    }

    /**
     * @param array<mixed> $data
     * @param array<int, string> $taskIds
     * @param array<int, string> $taskGuids
     */
    private function extractTaskData(array $data, array &$taskIds, array &$taskGuids): void
    {
        foreach ($data as $key => $value) {
            $keyString = is_string($key) ? strtolower($key) : '';

            if (($keyString === 'taskid' || str_ends_with($keyString, 'taskid')) && (is_int($value) || is_string($value))) {
                $taskId = trim((string) $value);
                if ($taskId !== '' && ctype_digit($taskId)) {
                    $taskIds[] = $taskId;
                }
            }

            if ((str_contains($keyString, 'taskguid') || $keyString === 'task') && is_string($value) && $this->looksLikeTaskGuid($value)) {
                $taskGuids[] = trim($value);
            }

            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
                    $decoded = json_decode($trimmed, true);
                    if (is_array($decoded)) {
                        $this->extractTaskData($decoded, $taskIds, $taskGuids);
                    }
                }

                if ($this->looksLikeTaskGuid($trimmed)) {
                    $taskGuids[] = $trimmed;
                }
            } elseif (is_array($value)) {
                $this->extractTaskData($value, $taskIds, $taskGuids);
            }
        }
    }

    private function looksLikeTaskGuid(string $value): bool
    {
        $normalized = $this->normalizeTaskGuid($value);

        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $normalized);
    }

    /**
     * @param array<string,mixed> $worker
     */
    private function resolveTaskKey(array $worker): string
    {
        if (! empty($worker['taskGuid']) && is_string($worker['taskGuid'])) {
            return $this->normalizeTaskGuid($worker['taskGuid']);
        }

        foreach ($worker['taskIds'] ?? [] as $taskId) {
            return 'taskId:' . $taskId;
        }

        return 'unknown-task';
    }

    /**
     * @param array<int, array{
     *     taskKey:string,
     *     queueAt : ?DateTimeImmutable,
     *     terminalAt : ?DateTimeImmutable
     * }> $rows
     */
    private function writeTaskEndToEnd(array $rows): void
    {
        $taskRanges = [];

        foreach ($rows as $row) {
            $taskKey = (string) $row['taskKey'];
            $start = $row['queueAt'];
            $end = $row['terminalAt'];

            if (! $start instanceof DateTimeImmutable || ! $end instanceof DateTimeImmutable) {
                continue;
            }

            if (! isset($taskRanges[$taskKey])) {
                $taskRanges[$taskKey] = [
                    'start' => $start,
                    'end' => $end,
                ];

                continue;
            }

            if ($start < $taskRanges[$taskKey]['start']) {
                $taskRanges[$taskKey]['start'] = $start;
            }
            if ($end > $taskRanges[$taskKey]['end']) {
                $taskRanges[$taskKey]['end'] = $end;
            }
        }

        $this->io->writeln('');
        $this->io->writeln('End-to-end per task:');

        if (empty($taskRanges)) {
            $this->io->writeln('  n/a');

            return;
        }

        foreach ($taskRanges as $taskKey => $range) {
            $duration = $this->calculateDuration($range['start'], $range['end']);
            $this->io->writeln('  ' . $taskKey . ': ' . $this->formatDuration($duration));
        }
    }
}
