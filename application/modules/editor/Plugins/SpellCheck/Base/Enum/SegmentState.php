<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\SpellCheck\Base\Enum;

class SegmentState
{
    public const SEGMENT_STATE_UNCHECKED = 'unchecked';
    public const SEGMENT_STATE_INPROGRESS = 'inprogress';
    public const SEGMENT_STATE_CHECKED = 'checked';
    public const SEGMENT_STATE_RECHECK = 'recheck';
    public const SEGMENT_STATE_DEFECT = 'defect';
}
