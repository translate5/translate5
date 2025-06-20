<?php

namespace MittagQI\Translate5\Segment\TrackChange\DTO;

class TrackChangeUserDto
{
    public function __construct(
        public readonly string $userTrackingId,
        public readonly string $userColorNr,
        public readonly string $attributeWorkflowstep,
    ) {
    }
}
