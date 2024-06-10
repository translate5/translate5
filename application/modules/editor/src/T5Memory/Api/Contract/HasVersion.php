<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api\Contract;

interface HasVersion
{
    public const FALLBACK_VERSION = '0.4';

    public function version(string $baseUrl, bool $suppressExceptions = true): string;
}