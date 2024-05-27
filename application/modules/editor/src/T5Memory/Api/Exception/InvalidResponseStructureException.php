<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api\Exception;

use InvalidArgumentException;
use MittagQI\Translate5\T5Memory\Api\Contract\ResponseException;

class InvalidResponseStructureException extends InvalidArgumentException implements ResponseException
{
    public const ERROR_CODE = 103;

    public function __construct(
        public readonly string $expectedFieldPath,
        public readonly string $responseBody,
    ) {
        parent::__construct(
            sprintf('Element "%s" not found in response body:%s%s', $expectedFieldPath, PHP_EOL, $responseBody),
            self::ERROR_CODE
        );
    }
}