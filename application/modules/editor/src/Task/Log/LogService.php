<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Task\Log;

class LogService
{
    private LogRepository $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
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
