<?php

namespace MittagQI\Translate5\Segment\TagRepair\Xliff;

/**
 * Interface for services converting tags between xliff and external formats.
 * (Interface definition remains the same as before)
 */
interface TagConverterInterface
{
    /**
     * Converts text with xliff tags (bx, ex, x) to the service format (t5x_ID_RID or t5x_ID).
     *
     * Example: <bx id="1" rid="1"/> -> <t5x_1_1>
     * Example: <ex id="3" rid="1"/> -> </t5x_3_1>
     * Example: <x id="2"/>         -> <t5x_2 />
     *
     * @param string $text The input text with xliff tags.
     * @return string The text with tags converted to the service format.
     */
    public function convertToServiceFormat(string $text): string;

    /**
     * Converts text with service format tags (t5x_ID_RID or t5x_ID) back to xliff tags (bx, ex, x).
     * Uses IDs embedded in the tag names.
     *
     * Example: <t5x_1_1>   -> <bx id="1" rid="1"/>
     * Example: </t5x_3_1>  -> <ex id="3" rid="1"/>
     * Example: <t5x_2 /> -> <x id="2"/>
     *
     * @param string $text The input text with service format tags.
     * @return string The text with tags converted back to the xliff format.
     */
    public function convertToOriginalFormat(string $text): string;
}
