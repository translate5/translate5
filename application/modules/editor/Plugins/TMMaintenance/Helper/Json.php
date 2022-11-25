<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\TMMaintenance\Helper;

class Json
{
    // TODO move somewhere
    private const JSON_DEFAULT_DEPTH = 512;

    public static function decode(string $data): array
    {
        return json_decode($data, true, self::JSON_DEFAULT_DEPTH, JSON_THROW_ON_ERROR);
    }
}
