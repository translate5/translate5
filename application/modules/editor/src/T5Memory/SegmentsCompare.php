<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\T5Memory;

use editor_Models_Segment_InternalTag;
use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\ContentProtection\NumberProtector;

class SegmentsCompare
{
    public function __construct(
        private readonly editor_Models_Segment_InternalTag $internalTagHelper,
        private readonly ContentProtector $contentProtector,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new editor_Models_Segment_InternalTag(),
            ContentProtector::create(),
        );
    }

    public function areSegmentsEqual(string $segmentA, string $segmentB): bool
    {
        return $this->normalizeSegment($segmentA) === $this->normalizeSegment($segmentB);
    }

    private function normalizeSegment(string $segment): string
    {
        $segment = preg_replace_callback(
            \editor_Models_Segment_InternalTag::REGEX_INTERNAL_TAGS,
            fn ($match) => str_replace(\editor_Models_Segment_InternalTag::IGNORE_CLASS, '', $match[0]),
            $segment,
        );

        $segment = $this->internalTagHelper->restore($segment);
        $segment = $this->contentProtector->unprotect($segment, true, NumberProtector::alias());

        return $this->removeAttributesFromMarkup($segment);
    }

    private function removeAttributesFromMarkup(string $markup): string
    {
        if (trim($markup) === '') {
            return $markup;
        }

        $internalErrors = libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8"?>' . $markup, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $xpath = new \DOMXPath($document);

        // Remove all attributes from x, bx, and ex tags
        foreach ($xpath->query('//x | //bx | //ex | //ph | //g | //bpt | //ept') as $node) {
            /** @var \DOMElement $node */
            while ($node->attributes && $node->attributes->length > 0) { // @phpstan-ignore-line
                $node->removeAttribute($node->attributes->item(0)->nodeName); // @phpstan-ignore-line
            }
        }

        $body = $document->getElementsByTagName('body')->item(0);
        if ($body) {
            $content = '';
            foreach ($body->childNodes as $child) {
                $content .= $document->saveHTML($child);
            }

            $content = trim($content);
        } else {
            $html = $document->saveHTML();
            // Strip XML declaration and unwanted p tags added by loadHTML
            $html = preg_replace('/<\?xml[^?]*\?>/', '', $html);
            $html = preg_replace('/<\/?p>/', '', $html);
            $content = trim($html);
        }

        $content = str_replace(
            [
                '<bpt>',
                '<ept>',
                '</bpt>',
                '</ept>',
            ],
            [
                '<bx>',
                '<ex>',
                '</bx>',
                '</ex>',
            ],
            $content
        );

        $content = str_replace(
            [
                '</ph>',
                '</g>',
                '</i>',
            ],
            '</x>',
            $content
        );

        $content = str_replace(
            [
                '<ph>',
                '<g>',
                '<i>',
            ],
            '<x>',
            $content
        );

        return $content;
    }
}
