<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\TMMaintenance\DTO;

use MittagQI\Translate5\Plugins\TMMaintenance\Helper\Json;
use Zend_Controller_Request_Abstract as Request;

class DeleteDTO
{
    public function __construct(
        public readonly string $id
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $data = Json::decode($request->getParam('data'));

        return new self($data['id']);
    }
}
