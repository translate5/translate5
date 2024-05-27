<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api\Exception;

use MittagQI\Translate5\T5Memory\Api\Contract\ResponseException;
use RuntimeException;

class CorruptResponseBodyException extends RuntimeException implements ResponseException
{
    public const ERROR_CODE = 101;
    public function __construct(RuntimeException $contentException)
    {
        parent::__construct('Unable to get Content from response body', self::ERROR_CODE, $contentException);
    }
}