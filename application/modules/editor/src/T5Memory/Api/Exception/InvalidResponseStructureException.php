<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api\Exception;

use InvalidArgumentException;
use MittagQI\Translate5\T5Memory\Api\Contract\ResponseException;

class InvalidResponseStructureException extends InvalidArgumentException implements ResponseException
{
    public static function invalidBody(string $expectedFieldPath, string $responseBody): self
    {
        return new self(
            sprintf('Element "%s" not found in response body:%s%s', $expectedFieldPath, PHP_EOL, $responseBody),
        );
    }

    public static function invalidHeader(string $name, string $value): self
    {
        return new self(
            sprintf('Header "%s" has invalid value: %s', $name, $value),
        );
    }
}