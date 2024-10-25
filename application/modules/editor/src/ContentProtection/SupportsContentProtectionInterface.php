<?php

namespace MittagQI\Translate5\ContentProtection;

use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;

interface SupportsContentProtectionInterface
{
    public function getTmConversionService(): TmConversionService;
}
