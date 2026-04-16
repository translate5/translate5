<?php

namespace MittagQI\Translate5\Segment\TagRepair\Xliff;

/**
 * Interface for services converting tags between xliff and external formats.
 */
interface TagConverterInterface
{
    /**
     * Converts text with xliff tags (bx, ex, x) to the service format (t5x_RID or t5x_ID).
     * Paired tags share the same tag name based on their RID, producing valid XML.
     *
     * Example: <bx id="1" rid="1"/> -> <t5x_1>
     * Example: <ex id="3" rid="1"/> -> </t5x_1>
     * Example: <x id="2"/>         -> <t5x_2 />
     *
     * @param string $text The input text with xliff tags.
     * @return TagConversionContext The converted text together with the ID maps needed for reverse conversion.
     */
    public function convertToServiceFormat(string $text): TagConversionContext;

    /**
     * Converts text with service format tags (t5x_RID or t5x_ID) back to xliff tags (bx, ex, x).
     * Uses the ID maps from the supplied context (produced by convertToServiceFormat()) to restore original IDs.
     *
     * Example: <t5x_1>   -> <bx id="1" rid="1"/>
     * Example: </t5x_1>  -> <ex id="3" rid="1"/>
     * Example: <t5x_2 /> -> <x id="2"/>
     *
     * @param string $text The input text with service format tags.
     * @param TagConversionContext $context The context returned by convertToServiceFormat().
     * @return string The text with tags converted back to the xliff format.
     */
    public function convertToOriginalFormat(string $text, TagConversionContext $context): string;
}
