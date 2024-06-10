<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\TMMaintenance\Overwrites;

class T5MemoryXliff extends \editor_Services_Connector_TagHandler_T5MemoryXliff
{
    public function restoreInResult(string $resultString, bool $isSource = true): ?string
    {
        $restoredResult = parent::restoreInResult($resultString);

        $pattern = '/<div class="([^"]*)\bignoreInEditor\b([^"]*)">/';
        $replacement = '<div class="$1$2">';
        // Normalize spaces in the class attribute
        $replacement = preg_replace('/\s+/', ' ', $replacement);
        // Replace ignoreInEditor class
        $updatedHtml = preg_replace($pattern, $replacement, $restoredResult);

        return preg_replace('/\s+/', ' ', $updatedHtml);
    }
}