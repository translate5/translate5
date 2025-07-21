<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Tools\DatabaseOptimizer;

class ReportDto
{
    public string $table;

    public bool $statusOk = false;

    public string $text;
}
