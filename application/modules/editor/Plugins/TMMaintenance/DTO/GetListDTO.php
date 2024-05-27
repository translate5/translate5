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
        public readonly string $source,
        public readonly string $sourceMode,
        public readonly string $target,
        public readonly string $targetMode,
        public readonly string $author,
        public readonly string $authorMode,
        public readonly string $creationDateFrom,
        public readonly string $creationDateTo,
        public readonly string $additionalInfo,
        public readonly string $additionalInfoMode,
        public readonly string $document,
        public readonly string $documentMode,
        public readonly string $context,
        public readonly string $contextMode,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            (int) $request->getParam('tm'),
            (int) $request->getParam('limit'),
            (string) $request->getParam('offset'),
            (string) $request->getParam('source'),
            (string) $request->getParam('sourceMode'),
            (string) $request->getParam('target'),
            (string) $request->getParam('targetMode'),
            (string) $request->getParam('author'),
            (string) $request->getParam('authorMode'),
            (string) $request->getParam('creationDateFrom'),
            (string) $request->getParam('creationDateTo'),
            (string) $request->getParam('additionalInfo'),
            (string) $request->getParam('additionalInfoMode'),
            (string) $request->getParam('document'),
            (string) $request->getParam('documentMode'),
            (string) $request->getParam('context'),
            (string) $request->getParam('contextMode'),
        );
    }

    public function toArray()
    {
        return [
            'tmId' => $this->tmId,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'source' => $this->source,
            'sourceMode' => $this->sourceMode,
            'target' => $this->target,
            'targetMode' => $this->targetMode,
            'author' => $this->author,
            'authorMode' => $this->authorMode,
            'creationDateFrom' => $this->creationDateFrom,
            'creationDateTo' => $this->creationDateTo,
            'additionalInfo' => $this->additionalInfo,
            'additionalInfoMode' => $this->additionalInfoMode,
            'document' => $this->document,
            'documentMode' => $this->documentMode,
            'context' => $this->context,
            'contextMode' => $this->contextMode,
        ];
    }
}
