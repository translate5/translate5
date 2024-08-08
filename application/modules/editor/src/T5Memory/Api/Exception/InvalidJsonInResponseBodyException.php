<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api\Exception;

use InvalidArgumentException;
use MittagQI\Translate5\T5Memory\Api\Contract\ResponseException;

class InvalidJsonInResponseBodyException extends InvalidArgumentException implements ResponseException
{
    public function __construct(InvalidArgumentException $exception)
    {
        parent::__construct($exception->getMessage(), previous: $exception);
    }
}
