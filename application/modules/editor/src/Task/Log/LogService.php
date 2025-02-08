<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Task\Log;

class LogService
{
    public function __construct(
        private readonly LogRepository $logRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new LogRepository(),
        );
    }

    public function getProjectLogSummary(int $projectId): array
    {
        return $this->logRepository->getLogSummaryForProject($projectId);
    }

    public function getTaskLogSummary(string $taskGuid): array
    {
        return $this->logRepository->getLogSummaryForTask([$taskGuid]);
    }
}
