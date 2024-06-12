<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api\V6\Request;

use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;

class DownloadTmxChunkRequest extends Request
{
    public function __construct(
        string $baseUrl,
        string $tmName,
        ?int $limit = null,
        ?string $startFromInternalKey = null
    ) {
        $body = [];

        if ($limit !== null) {
            $body['limit'] = $limit;
        }

        if ($startFromInternalKey !== null) {
            if (! preg_match('/\d+:\d+/', $startFromInternalKey)) {
                throw new InvalidArgumentException('Invalid "startFromInternalKey" format: ' . $startFromInternalKey);
            }

            $body['startFromInternalKey'] = $startFromInternalKey;
        }

        parent::__construct(
            'GET',
            rtrim($baseUrl, '/') . "/$tmName/download.tmx",
            [
                'Accept-charset' => 'UTF-8',
                'Accept' => 'application/xml',
                'Content-Type' => 'application/json',
            ],
            json_encode($body)
        );
    }
}
