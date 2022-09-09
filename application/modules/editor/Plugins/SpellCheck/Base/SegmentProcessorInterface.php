<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\SpellCheck\Base;

interface SegmentProcessorInterface
{
    public function process(array $segmentsTags, ?string $slot = null): void;
}
