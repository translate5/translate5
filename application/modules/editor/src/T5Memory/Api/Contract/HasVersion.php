<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api\Contract;

interface HasVersion
{
    public function version(string $baseUrl, bool $suppressExceptions = true): string;
}