<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\TMMaintenance\DTO;

use MittagQI\Translate5\Plugins\TMMaintenance\Helper\Json;
use Zend_Controller_Request_Abstract as Request;
use ZfExtended_Authentication;

class CreateDTO
{
    public function __construct(
        public readonly int $tmId,
        public readonly string $source,
        public readonly string $target,
        public readonly string $documentName,
        public readonly string $author,
        public readonly string $context,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $data = Json::decode($request->getParam('data'));

        return new self(
            (int) $data['tm'],
            $data['source'],
            $data['target'],
            'none',
            ZfExtended_Authentication::getInstance()->getUser()->getUserName(),
            'none',
        );
    }
}
