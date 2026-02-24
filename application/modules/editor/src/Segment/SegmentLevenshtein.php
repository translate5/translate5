<?php
/*
 START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/gpl.html
 http://www.translate5.net/plugin-exception.txt

 END LICENSE AND COPYRIGHT
 */

declare(strict_types=1);

namespace MittagQI\Translate5\Segment;

use editor_Models_Segment_InternalTag;
use editor_Models_Segment_Mqm;
use editor_Models_Segment_TermTag;
use editor_Models_Segment_TrackChangeTag;
use MittagQI\Translate5\Segment\Exception\InvalidInputForLevenshtein;

/**
 * Replaces tags as characters out of the Private Use Area (PUA) of Unicode
 *  so that levenshtein recognizes them as single change.
 * All multibyte characters are also replaced as single bytes till grapheme_levenshtein can be used.
 */
final readonly class SegmentLevenshtein
{
    public function __construct(
        private editor_Models_Segment_TrackChangeTag $trackChangeTag,
        private editor_Models_Segment_TermTag $termTag,
        private editor_Models_Segment_InternalTag $internalTag,
        private editor_Models_Segment_Mqm $mqmTag,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new editor_Models_Segment_TrackChangeTag(),
            new editor_Models_Segment_TermTag(),
            new editor_Models_Segment_InternalTag(),
            new editor_Models_Segment_Mqm(),
        );
    }

    /**
     * calculates the levenshtein distance for segment content
     * @throws InvalidInputForLevenshtein
     */
    public function calcDistance(string $s1, string $s2): int
    {
        if ($s1 === $s2) {
            return 0;
        }

        $charMap = [];
        $usedPuaChars = $this->collectPrivateUseAreaChars($s1 . $s2);
        $puaCursor = 0xE000;
        $s1 = $this->removeAndProtectTags($s1, $charMap, $usedPuaChars, $puaCursor);
        $s2 = $this->removeAndProtectTags($s2, $charMap, $usedPuaChars, $puaCursor);

        if (str_contains($s1, '<')) {
            throw new InvalidInputForLevenshtein('E1776', [
                'content' => $s1,
            ]);
        }
        if (str_contains($s2, '<')) {
            throw new InvalidInputForLevenshtein('E1776', [
                'content' => $s2,
            ]);
        }

        return $this->levenshteinDistance($s1, $s2);
    }

    private function levenshteinDistance(string $s1, string $s2): int
    {
        if (function_exists('grapheme_levenshtein')) {
            return grapheme_levenshtein($s1, $s2);
        }

        return $this->asciiLevenshtein($s1, $s2);
    }

    /**
     * - removes TrackChanges and MQM tags
     * - protects internal tags as private use area characters so that tag changes are recognized as change too
     * @throws InvalidInputForLevenshtein
     */
    private function removeAndProtectTags(
        string $str,
        array &$map,
        array &$usedPuaChars,
        int &$puaCursor
    ): string {
        if (str_contains($str, '<')) {
            // remove change tracking tags
            $str = $this->trackChangeTag->removeTrackChanges($str);
        }

        // find all internal tags - if any
        if (str_contains($str, '<')) {
            $str = $this->internalTag->protectWithSemanticIds($str);
            $placeHolders = array_keys($this->internalTag->getOriginalTags());
            foreach ($placeHolders as $placeholder) {
                if (! array_key_exists($placeholder, $map)) {
                    $map[$placeholder] = $this->nextUnusedPrivateUseAreaChar($usedPuaChars, $puaCursor);
                }
            }

            //Term removing MUST be done after internalTag protection!
            $str = $this->termTag->remove($str);
        }

        if (str_contains($str, '<img')) {
            $str = $this->mqmTag->remove($str);
        }

        // finally remap tag placeholders with private use area character
        return strtr($str, $map);
    }

    /**
     * @return array<string, bool>
     */
    private function collectPrivateUseAreaChars(string $text): array
    {
        if (! preg_match_all('/[\x{E000}-\x{F8FF}]/u', $text, $matches)) {
            return [];
        }

        return array_fill_keys($matches[0], true);
    }

    /**
     * @throws InvalidInputForLevenshtein
     */
    private function nextUnusedPrivateUseAreaChar(array &$usedPuaChars, int &$puaCursor): string
    {
        while ($puaCursor <= 0xF8FF) {
            $char = function_exists('mb_chr')
                ? mb_chr($puaCursor, 'UTF-8')
                : html_entity_decode('&#x' . dechex($puaCursor) . ';', ENT_NOQUOTES, 'UTF-8');
            $puaCursor++;

            if (! isset($usedPuaChars[$char])) {
                $usedPuaChars[$char] = true;

                return $char;
            }
        }

        throw new InvalidInputForLevenshtein('E1777');
    }

    private function asciiLevenshtein(string $s1, string $s2): int
    {
        $map = [];
        $s1 = $this->utf8ToExtendedAscii($s1, $map);
        $s2 = $this->utf8ToExtendedAscii($s2, $map);

        return levenshtein($s1, $s2);
    }

    private function utf8ToExtendedAscii(string $str, array &$map): string
    {
        $matches = [];
        if (preg_match_all('/[\xC0-\xF7][\x80-\xBF]+/', $str, $matches)) {
            foreach ($matches[0] as $ch) {
                if (! isset($map[$ch])) {
                    $map[$ch] = chr(128 + count($map));
                }
            }
        }

        return empty($map) ? $str : strtr($str, $map);
    }
}
