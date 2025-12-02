<?php

namespace MittagQI\Translate5\ContentProtection\DTO;

use MittagQI\Translate5\ContentProtection\NumberProtector;

class ProtectedContentTagData
{
    public readonly string $source;

    public readonly string $iso;

    public readonly string $target;

    public function __construct(
        public readonly string $type,
        public readonly string $name,
        string $source,
        string $iso,
        string $target,
        public readonly ?string $encodedRegex,
    ) {
        $this->source = html_entity_decode($source);
        $this->iso = html_entity_decode($iso);
        $this->target = html_entity_decode($target);
    }

    public static function fromTag(string $tag): self
    {
        preg_match(NumberProtector::fullTagRegex(), $tag, $data);

        if (count($data) < 6) {
            throw new \InvalidArgumentException('Invalid protected content tag: ' . $tag);
        }

        return new self(
            $data[1],
            $data[2],
            $data[3],
            $data[4],
            $data[5],
            $data[7] ?? null,
        );
    }

    public function uniqueKey(): string
    {
        return $this->encodedRegex ?: $this->name;
    }
}
