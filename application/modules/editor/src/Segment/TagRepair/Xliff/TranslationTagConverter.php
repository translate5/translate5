<?php

namespace MittagQI\Translate5\Segment\TagRepair\Xliff;

use Exception;
use ZfExtended_Logger;

/**
 * Converts translation tags between the xliff format (bx, ex, x with attributes)
 * and a simplified service format (t5x_RID or t5x_ID based on original attributes).
 *
 * Paired tags (bx/ex) share the same tag name using the RID, producing valid XML:
 *   <bx id="1" rid="1"/> -> <t5x_1>
 *   <ex id="3" rid="1"/> -> </t5x_1>
 *   <x id="2"/>          -> <t5x_2 />
 *
 * The mapping of RID -> {bx_id, ex_id} is returned as part of TagConversionContext so
 * that original IDs can be restored during reverse conversion.
 */
class TranslationTagConverter implements TagConverterInterface
{
    /**
     * Prefix used for the simplified service tags.
     */
    private const T5X_TAG_PREFIX = 't5x_';

    public function __construct(
        private ZfExtended_Logger $logger
    ) {
    }

    /**
     * Converts text with xliff tags (bx, ex, x) to the service format (t5x_RID or t5x_ID).
     * Paired tags share the same tag name (RID), producing well-formed XML.
     *
     * @param string $text The input text with xliff tags.
     * @return TagConversionContext The converted text together with the ID maps needed for reverse conversion.
     * @throws Exception
     */
    public function convertToServiceFormat(string $text): TagConversionContext
    {
        $ridToIdMap = [];
        $singleTagIds = [];

        $pattern = '/<(bx|ex)\s+id="(\d+)"\s+rid="(\d+)"\s*\/>|<(x)\s+id="(\d+)"\s*\/>/i';

        $convertedText = preg_replace_callback(
            $pattern,
            function ($matches) use (&$ridToIdMap, &$singleTagIds) {
                // $matches[1]: 'bx' or 'ex'
                // $matches[2]: id for bx/ex
                // $matches[3]: rid for bx/ex
                // $matches[4]: 'x'
                // $matches[5]: id for x

                // Check if bx or ex tag matched
                if (! empty($matches[1]) && isset($matches[2]) && isset($matches[3])) { // @phpstan-ignore-line
                    $tagName = strtolower($matches[1]);
                    $id = $matches[2];
                    $rid = $matches[3];

                    // Store the original ID indexed by RID so we can restore it later
                    if (! isset($ridToIdMap[$rid])) {
                        $ridToIdMap[$rid] = [
                            'bx_id' => null,
                            'ex_id' => null,
                        ];
                    }

                    if ($tagName === 'bx') {
                        $ridToIdMap[$rid]['bx_id'] = $id;

                        return '<' . self::T5X_TAG_PREFIX . $rid . '>';
                    } else {
                        $ridToIdMap[$rid]['ex_id'] = $id;

                        return '</' . self::T5X_TAG_PREFIX . $rid . '>';
                    }
                } // Check if x tag matched
                elseif (! empty($matches[4]) && isset($matches[5])) { // @phpstan-ignore-line
                    $id = $matches[5];
                    // Track single tag IDs so we can recognise them during reverse conversion
                    // even if the service drops the self-closing slash and returns <t5x_N > instead of <t5x_N />.
                    $singleTagIds[$id] = true;

                    return '<' . self::T5X_TAG_PREFIX . $id . ' />';
                }

                $this->logger->warn(
                    'E1710',
                    'Unexpected tag structure encountered during forward conversion:' . $matches[0]
                );

                return $matches[0];
            },
            $text
        );

        if ($convertedText === null) {
            throw new TagConverterException(
                'E1711',
                [
                    'convertedText' => $text,
                ]
            );
        }

        return new TagConversionContext($convertedText, $ridToIdMap, $singleTagIds);
    }

    /**
     * Converts text with service format tags (t5x_RID or t5x_ID) back to xliff tags (bx, ex, x).
     * Uses the RID-to-ID map from the supplied context to restore original IDs.
     * Falls back to using the RID itself as the ID when the map entry is absent (e.g. tag was
     * invented by the translation service and then repaired).
     *
     * @param string $text The input text with service format tags.
     * @param TagConversionContext $context The context returned by convertToServiceFormat().
     * @return string The text with tags converted back to the xliff format.
     * @throws Exception
     */
    public function convertToOriginalFormat(string $text, TagConversionContext $context): string
    {
        // Step 1: Convert self-closing tags <t5x_ID /> -> <x id="ID"/>
        // Matches <t5x_DIGITS optional_space /> — the space before /> distinguishes these from opening tags.
        $patternSelfClosing = '/<(' . self::T5X_TAG_PREFIX . '(\d+))\s*\/>/i';
        $textStep1 = preg_replace_callback(
            $patternSelfClosing,
            function ($matches) {
                // $matches[2] is the ID
                $id = $matches[2];

                return '<x id="' . $id . '"/>';
            },
            $text
        );

        if ($textStep1 === null) {
            throw new TagConverterException('E1712', [
                'errorInfo' => 'Error during regex replacement for self-closing tags.',
                'receivedText' => $text,
            ]);
        }

        // Step 2: Convert closing tags </t5x_RID> -> <ex id="EX_ID" rid="RID"/>
        // Matches </t5x_DIGITS>
        $patternClosing = '/<\/(' . self::T5X_TAG_PREFIX . '(\d+))>/i';
        $textStep2 = preg_replace_callback(
            $patternClosing,
            function ($matches) use ($context) {
                // $matches[2] is the RID
                $rid = $matches[2];
                $exId = $context->ridToIdMap[$rid]['ex_id'] ?? $rid;

                return '<ex id="' . $exId . '" rid="' . $rid . '"/>';
            },
            $textStep1
        );

        if ($textStep2 === null) {
            throw new TagConverterException('E1712', [
                'errorInfo' => 'Error during regex replacement for closing tags.',
                'receivedText' => $text,
                'step1Text' => $textStep1,
            ]);
        }

        // Step 3: Convert opening tags <t5x_RID> -> <bx id="BX_ID" rid="RID"/>
        // Also handles service-mangled self-closing tags where the slash was dropped,
        // e.g. <t5x_4 /> became <t5x_4 > — detected via $singleTagIds.
        // \s* allows for optional trailing whitespace before > that some services introduce.
        $patternOpening = '/<(' . self::T5X_TAG_PREFIX . '(\d+))\s*>/i';
        $textStep3 = preg_replace_callback(
            $patternOpening,
            function ($matches) use ($context) {
                $rid = $matches[2];

                // If this ID was originally an <x> single tag (service dropped the slash),
                // restore it as a self-closing x tag instead of an opening bx tag.
                if (isset($context->singleTagIds[$rid])) {
                    return '<x id="' . $rid . '"/>';
                }

                $bxId = $context->ridToIdMap[$rid]['bx_id'] ?? $rid;

                return '<bx id="' . $bxId . '" rid="' . $rid . '"/>';
            },
            $textStep2
        );

        if ($textStep3 === null) {
            throw new TagConverterException('E1712', [
                'errorInfo' => 'Error during regex replacement for opening tags.',
                'receivedText' => $text,
                'step1Text' => $textStep1,
                'step2Text' => $textStep2,
            ]);
        }

        return $textStep3;
    }
}
