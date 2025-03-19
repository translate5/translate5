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
use editor_Models_Segment_TrackChangeTag;

/**
 * For multibyte encodings, such as UTF-8, see https://www.php.net/manual/en/function.levenshtein.php
 * and to handle internal tags as 1 symbol
 */
final class LevenshteinUTF8
{
    public static function calcDistance(string $s1, string $s2): int
    {
        if ($s1 === $s2) {
            return 0;
        }

        $charMap = [];
        $s1 = self::stringToExtendedAscii($s1, $charMap);
        $s2 = self::stringToExtendedAscii($s2, $charMap);

        return levenshtein($s1, $s2);
    }

    /**
     * Convert an UTF-8 encoded string to a single-byte string suitable for functions such as levenshtein.
     *
     * It simply uses (and updates) a tailored dynamic encoding (in/out map parameter) where non-ascii characters
     * are remapped to the range [128-255] in order of appearance.
     *
     * Thus it supports up to 128 different multibyte code points max over the whole set of strings sharing this
     * encoding. The same principle is used for internal tags
     */
    private static function stringToExtendedAscii(string $str, array &$map): string
    {
        if (str_contains($str, '<')) {
            // remove change tracking tags
            $str = (new editor_Models_Segment_TrackChangeTag())->removeTrackChanges($str);

            // strip volatile attributes for internal tags
            $str = str_replace([' class="short"', ' class="full"'], '', $str);
        }

        // find all multibyte characters (cf. utf-8 encoding specs)
        $allMatches = $matches = [];
        if (preg_match_all('/[\xC0-\xF7][\x80-\xBF]+/', $str, $matches)) {
            $allMatches = $matches[0];
        }
        // find all internal tags
        if (str_contains($str, '<') && preg_match_all(
            editor_Models_Segment_InternalTag::REGEX_INTERNAL_TAGS,
            $str,
            $matches
        )) {
            $allMatches = array_merge($allMatches, $matches[0]);
        }

        if (empty($allMatches)) {
            return $str;
        }

        // update the encoding map with the characters/tags not already met
        foreach ($allMatches as $ch) {
            if (! isset($map[$ch])) {
                $map[$ch] = chr(128 + count($map));
            }
        }

        // finally remap non-ascii characters and tags
        return strtr($str, $map);
    }
}
