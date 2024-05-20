<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\TMMaintenance\DTO;

use MittagQI\Translate5\Plugins\TMMaintenance\Helper\Json;
use Zend_Controller_Request_Abstract as Request;

class UpdateDTO
{
    public function __construct(
        public readonly string $id,
        public readonly int $tmId,
        public readonly string $source,
        public readonly string $target,
        public readonly string $documentName,
        public readonly string $author,
        public readonly string $timestamp,
        public readonly string $context,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $data = Json::decode($request->getParam('data'));

        return new self(
            $data['id'],
            (int) $data['languageResourceid'],
            $data['source'],
            $data['target'],
            $data['metaData']['documentName'],
            $data['metaData']['author'],
            $data['metaData']['timestamp'],
            $data['metaData']['context'],
        );
    }
}
