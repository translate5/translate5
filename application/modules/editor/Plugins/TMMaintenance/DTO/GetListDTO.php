<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\TMMaintenance\DTO;

use Zend_Controller_Request_Abstract as Request;

class GetListDTO
{
    public function __construct(
        public readonly int $tmId,
        public readonly int $limit,
        public readonly string $offset,
        public readonly string $searchCriteria,
        public readonly string $searchField,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            (int) $request->getParam('tm'),
            (int) $request->getParam('limit'),
            (string) $request->getParam('offset'),
            $request->getParam('searchCriteria'),
            $request->getParam('searchField')
        );
    }
}
