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

        return self::fromMatch($data);
    }

    public static function fromMatch(array $match): self
    {
        if (count($match) < 6) {
            throw new \InvalidArgumentException('Invalid protected content tag: ' . $match[0]);
        }

        return new self(
            $match[1],
            $match[2],
            $match[3],
            $match[4],
            $match[5],
            $match[7] ?? null,
        );
    }

    public function uniqueKey(): string
    {
        return $this->encodedRegex ?: $this->name;
    }
}
