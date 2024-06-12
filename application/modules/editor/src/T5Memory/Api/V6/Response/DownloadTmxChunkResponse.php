<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api\V6\Response;

use MittagQI\Translate5\T5Memory\Api\Exception\CorruptResponseBodyException;
use MittagQI\Translate5\T5Memory\Api\Exception\InvalidResponseStructureException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class DownloadTmxChunkResponse
{
    public function __construct(
        public readonly ?string $nextInternalKey,
        public readonly StreamInterface $chunk
    ) {
    }

    /**
     * @throws CorruptResponseBodyException
     * @throws InvalidResponseStructureException
     */
    public static function fromResponse(ResponseInterface $response): self
    {
        $nextInternalKeyHeader = $response->getHeader('NextInternalKey');
        $nextInternalKey = null;

        if (! empty($nextInternalKeyHeader)) {
            $nextInternalKey = $nextInternalKeyHeader[0];

            if (! preg_match('/\d+:\d+/', $nextInternalKey)) {
                throw InvalidResponseStructureException::invalidHeader('NextInternalKey', $nextInternalKey);
            }
        }

        return new self($nextInternalKey, $response->getBody());
    }
}
