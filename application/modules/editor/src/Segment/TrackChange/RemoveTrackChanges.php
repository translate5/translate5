<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

declare(strict_types=1);

namespace MittagQI\Translate5\Segment\TrackChange;

use DOMDocument;
use DOMNode;
use DOMXPath;

class RemoveTrackChanges
{
    public function remove(string $segment): string
    {
        if (! str_contains($segment, '<del') && ! str_contains($segment, '<ins')) {
            return $segment;
        }

        libxml_use_internal_errors(true);

        // --- 0) Protect &quot; so DOM won’t normalize it in text nodes
        // Use a very unlikely marker (could randomize if you want)
        $quotPh = '__PRESERVE_DQ_e1f3b7__';
        $segment = str_replace('&quot;', $quotPh, $segment);

        // Wrap in a neutral inline container to preserve leading spaces
        $segment = '<div id="__root__">' . $segment . '</div>';
        $html = '<?xml encoding="UTF-8">' . $segment;

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($doc);

        /** 1) Remove all <del> (drop content) */
        $delNodes = $xpath->query('//del');
        /** @var DOMNode[] $toRemove */
        $toRemove = [];
        foreach ($delNodes as $n) {
            $toRemove[] = $n;
        }
        foreach ($toRemove as $del) {
            $parent = $del->parentNode;
            if (! $parent) {
                continue;
            }

            // Remember neighbors *before* removal
            $prev = $del->previousSibling;
            $next = $del->nextSibling;

            // Normalize boundary whitespace between $prev and $next
            $leftText = ($prev && $prev->nodeType === XML_TEXT_NODE) ? $prev : null;
            $rightText = ($next && $next->nodeType === XML_TEXT_NODE) ? $next : null;

            // Remove the <del> node
            $parent->removeChild($del);

            // Detect if there was whitespace at either side
            $leftHadWS = $leftText ? (bool) preg_match('/\s$/u', $leftText->nodeValue) : false;
            $rightHadWS = $rightText ? (bool) preg_match('/^\s/u', $rightText->nodeValue) : false;

            if (! $leftText && ! $rightText) {
                continue;
            }

            if (! $leftHadWS && ! $rightHadWS) {
                continue;
            }

            // Trim trailing on left, leading on right
            if ($leftText && $rightHadWS) {
                $leftText->nodeValue = preg_replace('/\s+$/u', '', $leftText->nodeValue);
            }
            if ($rightText) {
                $rightText->nodeValue = preg_replace('/^\s+/u', ' ', $rightText->nodeValue);
            }
        }

        /** 2) Unwrap all <ins> (preserve content) */
        $insNodes = $xpath->query('//ins');
        $toUnwrap = [];
        foreach ($insNodes as $n) {
            $toUnwrap[] = $n;
        }
        foreach ($toUnwrap as $ins) {
            $parent = $ins->parentNode;
            if (! $parent) {
                continue;
            }
            while ($ins->firstChild) {
                $parent->insertBefore($ins->firstChild, $ins);
            }
            $parent->removeChild($ins);
        }

        // Extract innerHTML of our wrapper (preserves leading space)
        $root = $doc->getElementById('__root__');
        if (! $root) {
            // Fallback to whole doc if wrapper somehow missing
            return str_replace($quotPh, '&quot;', $doc->saveHTML());
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveXML($child);
        }

        // first replacement is neccessary for outdated TermTagger,
        // who can not process singular tags without trailing space !
        return str_replace('"/>', '" />', str_replace($quotPh, '&quot;', $out));
    }
}
