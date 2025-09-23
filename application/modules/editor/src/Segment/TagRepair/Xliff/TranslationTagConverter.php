<?php

namespace MittagQI\Translate5\Segment\TagRepair\Xliff;

use Exception;
use ZfExtended_Logger;

/**
 * Converts translation tags between the xliff format (bx, ex, x with attributes)
 * and a simplified service format (t5x_ID_RID or t5x_ID based on original attributes).
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
     * Converts text with xliff tags (bx, ex, x) to the service format (t5x_ID_RID or t5x_ID).
     *
     * @param string $text The input text with xliff tags.
     * @return string The text with tags converted to the service format.
     * @throws Exception
     */
    public function convertToServiceFormat(string $text): string
    {
        // Regex remains the same as it already captures all necessary IDs
        $pattern = '/<(bx|ex)\s+id="(\d+)"\s+rid="(\d+)"\s*\/>|<(x)\s+id="(\d+)"\s*\/>/i';

        $convertedText = preg_replace_callback(
            $pattern,
            function ($matches) {
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
                    // Embed both ID and RID in the tag name
                    $targetTagBase = self::T5X_TAG_PREFIX . $id . '_' . $rid;

                    return $tagName === 'bx' ? '<' . $targetTagBase . '>' : '</' . $targetTagBase . '>';
                } // Check if x tag matched
                elseif (! empty($matches[4]) && isset($matches[5])) { // @phpstan-ignore-line
                    $id = $matches[5];
                    // Embed only the ID in the tag name
                    $targetTagBase = self::T5X_TAG_PREFIX . $id;

                    return '<' . $targetTagBase . ' />';
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

        return $convertedText;
    }

    /**
     * Converts text with service format tags (t5x_ID_RID or t5x_ID) back to xliff tags (bx, ex, x).
     * Uses IDs embedded in the tag names. Uses separate regex for clarity.
     *
     * @param string $text The input text with service format tags.
     * @return string The text with tags converted back to the xliff format.
     * @throws Exception
     */
    public function convertToOriginalFormat(string $text): string
    {
        // Step 1: Convert self-closing tags <t5x_ID /> -> <x id="ID"/>
        // Regex specifically matches <t5x_DIGITS optional_space />
        $patternSelfClosing = '/<(' . self::T5X_TAG_PREFIX . '(\d+))\s*\/>/i';
        $textStep1 = preg_replace_callback(
            $patternSelfClosing,
            function ($matches) {
                // $matches[1] is the full tag name base like t5x_2
                // $matches[2] is the ID like 2
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

        // Step 2: Convert closing tags </t5x_ID_RID> -> <ex id="ID" rid="RID"/>
        // Regex specifically matches </t5x_DIGITS_DIGITS>
        $patternClosing = '/<\/(' . self::T5X_TAG_PREFIX . '(\d+)_(\d+))>/i';
        $textStep2 = preg_replace_callback(
            $patternClosing,
            function ($matches) {
                // $matches[1] is the full tag name base like t5x_3_1
                // $matches[2] is the ID like 3
                // $matches[3] is the RID like 1
                $id = $matches[2];
                $rid = $matches[3];

                return '<ex id="' . $id . '" rid="' . $rid . '"/>';
            },
            $textStep1 // Use text from previous step
        );

        if ($textStep2 === null) {
            throw new TagConverterException('E1712', [
                'errorInfo' => 'Error during regex replacement for closing tags.',
                'receivedText' => $text,
                'step1Text' => $textStep1,
                'step2Text' => $textStep2,
            ]);
        }

        // Step 3: Convert opening tags <t5x_ID_RID> -> <bx id="ID" rid="RID"/>
        // Regex specifically matches <t5x_DIGITS_DIGITS>
        $patternOpening = '/<(' . self::T5X_TAG_PREFIX . '(\d+)_(\d+))>/i';
        $textStep3 = preg_replace_callback(
            $patternOpening,
            function ($matches) {
                // $matches[1] is the full tag name base like t5x_1_1
                // $matches[2] is the ID like 1
                // $matches[3] is the RID like 1
                $id = $matches[2];
                $rid = $matches[3];

                return '<bx id="' . $id . '" rid="' . $rid . '"/>';
            },
            $textStep2 // Use text from previous step
        );

        if ($textStep3 === null) {
            throw new TagConverterException('E1712', [
                'errorInfo' => 'Error during regex replacement for opening tags.',
                'receivedText' => $text,
                'step1Text' => $textStep1,
                'step2Text' => $textStep2,
                'step3Text' => $textStep3,
            ]);
        }

        // check for unconverted tags
        // if (preg_match('/<[\/]?t5x_/', $textStep3)) {
        //     trigger_error("Warning: Found potentially unconverted t5x tags remaining in final text.", E_USER_WARNING);
        // }

        return $textStep3; // Return the result of the final step
    }
}
