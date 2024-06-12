<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api\V5\Request;

use GuzzleHttp\Psr7\Request;

class DownloadTmxRequest extends Request
{
    public function __construct(string $baseUrl, string $tmName)
    {
        parent::__construct(
            'GET',
            rtrim($baseUrl, '/') . "/$tmName/",
            [
                'Accept-charset' => 'UTF-8',
                'Accept' => 'application/xml',
            ],
        );
    }
}
