<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

namespace MittagQI\Translate5\Test;

/**
 * Helper to sanitize data that will be used to compare JSON objects from the REST API with data from local JSON comparision files
 */
final class Sanitizer
{
    /**
     * Defines the attributes that are stripped from tags in the segment's field text
     * @var array
     */
    protected static array $attributesToDelete = ['data-t5qid', 'data-seq', 'data-usertrackingid', 'data-timestamp', 'data-tbxid'];

    /**
     * Sanitizes a segment's field text, especially the internal tags need to be sanitized
     * @param string|null $text
     * @return string|null
     */
    public static function fieldtext(?string $text): ?string
    {
        if (empty($text)) {
            return $text;
        }
        // return attributes from segment-tags that must not be compared as they contain e.g. DB-ids
        return preg_replace_callback('~<([a-z]+[0-9]*)[^>]*>~', 'MittagQI\Translate5\Test\Sanitizer::_sanitizeFieldtext', $text);
    }

    /**
     * Special sanitization for cases, where OKAPI replaces sequences of tags with a special "agglomeration" tag like <bpt id="1">[#$dp9]</bpt>
     * This is only needed for HTML-Imports
     * @param string|null $text
     * @return string|null
     */
    public static function okapifieldtext(?string $text): ?string
    {
        if (empty($text)) {
            return $text;
        }
        // return attributes from segment-tags that must not be compared as they contain e.g. DB-ids
        $text = preg_replace_callback('~<([a-z]+[0-9]*)[^>]*>~', 'MittagQI\Translate5\Test\Sanitizer::_sanitizeOkapiFieldtext', $text);
        // replace IDs of OKAPI that represent tag-agglomerations, they are represented with e.g. [#$dp65]
        return preg_replace('~\[#\$dp[0-9]+\]~', '[#$dpXX]', $text);
    }


    /**
     * Sanitizes a segment's comment
     * @param ?string $text
     * @return ?string
     */
    public static function comment(?string $text): ?string
    {
        if (empty($text)) {
            return $text;
        }
        return preg_replace('/<span class="modified">[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}</', '<span class="modified">NOT_TESTABLE<', $text);
    }

    /**
     * Sanitizes the segments metaCache object (just reorder the metacache data in a unified way)
     * @param string $jsonString
     * @return string|NULL
     */
    public static function metacache(?string $jsonString): ?string
    {
        // {"minWidth":null,"maxWidth":null,"maxNumberOfLines":null,"sizeUnit":"","font":"","fontSize":0,"additionalUnitLength":0,"additionalMrkLength":0,"siblingData":{"fakeSegId_3":{"nr":"3","length":{"targetEdit":34}}}}
        if (!empty($jsonString)) {
            $meta = json_decode($jsonString, true);
            if (!empty($meta['siblingData'])) {
                $data = [];
                foreach ($meta['siblingData'] as $sibling) {
                    $data['fakeSegId_' . $sibling['nr']] = $sibling;
                }
                ksort($data);
                $meta['siblingData'] = $data;
            }
            return json_encode($meta, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return NULL;
    }

    /**
     * Sanitizes a field by simply turning the field to the string "TEST"
     * @param string $text
     * @return string
     */
    public static function testtext(?string $text): string
    {
        return 'TEST';
    }

    /**
     * sanitizes a counter field by setting it's value to "1"
     * @param int $count
     * @return int
     */
    public static function onecounter(int $count): int
    {
        return 1;
    }

    /**
     * Callback handler for normal field text
     * @param $matches
     * @return string
     */
    protected static function _sanitizeFieldtext($matches): string
    {
        return static::_sanitizeFieldTags($matches, false);
    }

    /**
     * Callback handler for special okapi field text
     * @param $matches
     * @return string
     */
    protected static function _sanitizeOkapiFieldtext($matches): string
    {
        return static::_sanitizeFieldTags($matches, true);
    }

    /**
     * Removes id's and other attributes from segment-tags that cannot be compared
     * @param array $matches
     * @return string
     */
    private static function _sanitizeFieldTags($matches, bool $removeCssPayload): string
    {
        if (count($matches) > 1) {
            $isSingle = (substr(trim(rtrim($matches[0], '>')), -1) == '/');
            $tag = ($isSingle) ? \editor_Tag::unparse($matches[0]) : \editor_Tag::unparse($matches[0] . '</' . $matches[1] . '>');
            if ($tag == NULL) {
                return $matches[0];
            }
            foreach (self::$attributesToDelete as $attrName) {
                $tag->unsetAttribute($attrName);
            }
            // remove the crazy "content encoded as css-class" as these contents may contain OKAPI-specific ids
            //open 6270742069643d2231223e5b2324647031305d3c2f627074 internal-tag ownttip (the first tag can be open|close|single
            if ($removeCssPayload && $tag->hasClass('internal-tag')) {
                $classes = explode(' ', $tag->getClasses()); // the order is kept in the tag-parser
                if (count($classes) > 3 && $classes[2] === 'internal-tag' && $classes[3] === 'ownttip' && ($classes[0] === 'open' || $classes[0] === 'close' || $classes[0] === 'single')) {
                    $tag->setClasses($classes[0] . ' internal-tag ownttip');
                }
            }
            if ($isSingle) {
                return $tag->render();
            }
            return $tag->start();
        }
        return $matches[0];
    }
}