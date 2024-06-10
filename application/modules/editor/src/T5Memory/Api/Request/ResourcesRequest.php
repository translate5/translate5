<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api\Request;

use GuzzleHttp\Psr7\Request;

class ResourcesRequest extends Request
{
    public function __construct(string $baseUrl)
    {
        parent::__construct(
            'GET',
            rtrim($baseUrl, '/') . '_service/resources',
            [
                'Accept-charset' => 'UTF-8',
                'Accept' => 'application/json; charset=utf-8',
            ]
        );
    }
}