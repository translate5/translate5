<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\TMMaintenance\DTO;

use Zend_Controller_Request_Abstract as Request;

class GetListDTO
{
    public function __construct(
        private int $tmId,
        private int $limit,
        private ?string $offset,
        private string $searchCriteria,
        private string $searchField
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            (int)$request->getParam('tm'),
            (int)$request->getParam('limit'),
            $request->getParam('offset'),
            $request->getParam('searchCriteria'),
            $request->getParam('searchField')
        );
    }

    public function getTmId(): int
    {
        return $this->tmId;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): ?string
    {
        return $this->offset;
    }

    public function getSearchCriteria(): string
    {
        return $this->searchCriteria;
    }

    public function getSearchField(): string
    {
        return $this->searchField;
    }
}
