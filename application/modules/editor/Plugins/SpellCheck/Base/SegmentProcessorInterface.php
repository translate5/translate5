<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\SpellCheck\Base;

use editor_Segment_Tags;

interface SegmentProcessorInterface
{
    /**
     * @param editor_Segment_Tags[] $segmentsTags
     * @param string|null $slot
     *
     * @return void
     */
    public function process(array $segmentsTags, ?string $slot = null): void;
}
