<?php

namespace MittagQI\Translate5\Segment\TagRepair\Xliff;

/**
 * Holds the state produced by TranslationTagConverter::convertToServiceFormat()
 * and consumed by TranslationTagConverter::convertToOriginalFormat().
 */
class TagConversionContext
{
    /**
     * @param string $convertedText The text with tags converted to the service format.
     * @param array<string, array{bx_id: string|null, ex_id: string|null}> $ridToIdMap Maps RID -> {bx_id, ex_id}.
     * @param array<string, true> $singleTagIds Set of IDs that originated from single <x> tags.
     */
    public function __construct(
        public readonly string $convertedText,
        public readonly array $ridToIdMap,
        public readonly array $singleTagIds,
    ) {
    }
}
