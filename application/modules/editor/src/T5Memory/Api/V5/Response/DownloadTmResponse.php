<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api\V5\Response;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class DownloadTmResponse
{
    public function __construct(
        public readonly StreamInterface $tm
    ) {
    }

    public static function fromResponse(ResponseInterface $response): self
    {
        return new self($response->getBody());
    }
}
