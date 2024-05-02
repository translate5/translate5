<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\TMMaintenance\DTO;

use MittagQI\Translate5\Plugins\TMMaintenance\Helper\Json;
use Zend_Controller_Request_Abstract as Request;

class CreateDTO
{
    public function __construct(
        private int    $tm,
        private string $source,
        private string $target
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $data = Json::decode($request->getParam('data'));

        return new self(
            (int)$data['tm'],
            $data['rawSource'],
            $data['rawTarget']
        );
    }

    public function getTm(): int
    {
        return $this->tm;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTarget(): string
    {
        return $this->target;
    }
}
