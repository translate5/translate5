<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api\V5\Response;

use MittagQI\Translate5\T5Memory\Api\Exception\CorruptResponseBodyException;
use MittagQI\Translate5\T5Memory\Api\Exception\InvalidResponseStructureException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class DownloadTmxResponse
{
    public function __construct(
        public readonly StreamInterface $tmx
    ) {
    }

    /**
     * @throws CorruptResponseBodyException
     * @throws InvalidResponseStructureException
     */
    public static function fromResponse(ResponseInterface $response): self
    {
        return new self($response->getBody());
    }
}
