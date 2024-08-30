<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
             https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

namespace MittagQI\Translate5\Task\Overview\SegmentFormatter;

use editor_Models_Segment_InternalTag;
use editor_Models_Task as Task;

class ReplaceInternalTagWithSpanFormatter implements SegmentFormatterInterface
{
    public function __construct(
        private readonly string $messageAttr = 'data-message',
        private readonly string $color = 'rgba(207, 207, 207, 0.667)',
    ) {
    }

    public function __invoke(Task $task, string $segment, bool $isSource): string
    {
        $tagCount = preg_match_all(editor_Models_Segment_InternalTag::REGEX_INTERNAL_TAGS, $segment, $tags);

        if (! $tagCount) {
            return $segment;
        }

        foreach ($tags[0] as $tag) {
            $replacedTag = match (true) {
                $this->isContentProtectionTag($tag) => $this->getContentProtectionTagReplacement($tag, $isSource),
                $this->isWhitespaceTag($tag) => $this->getWhitespaceTagReplacement($tag),
                default => $this->getSimpleTagReplacement($tag),
            };

            $segment = str_replace($tag, $replacedTag, $segment);
        }

        return $segment;
    }

    private function isContentProtectionTag(string $tag): bool
    {
        return (bool) preg_match('#\sclass="(open|close|single)\s+([gxA-Fa-f0-9]*)[^"]*number\s#', $tag);
    }

    private function getContentProtectionTagReplacement(string $tag, bool $isSource): string
    {
        preg_match(
            '#<div[^>]+>\s*<span[^>]+class="short"\s*title="(.+)"[^>]*>[^<]*</span>\s*<span\s*class="full".*data-source="(.+)"\s*data-target="(.+)"\s*([^>]*)>([^<]*)</span>[\s]*</div>#miU',
            $tag,
            $matches
        );

        return sprintf(
            '<span %s="%s" style="background-color: %s">%s</span>',
            $this->messageAttr,
            $matches[1],
            $this->color,
            htmlentities($isSource ? $matches[2] : $matches[3])
        );
    }

    private function isWhitespaceTag(string $tag): bool
    {
        return (bool) preg_match(
            '#\sclass="(open|close|single)\s+([gxA-Fa-f0-9]*)[^"]*(nbsp|tab|space|newline|char)\s#',
            $tag
        );
    }

    private function getWhitespaceTagReplacement(string $tag): string
    {
        return preg_replace(
            '#<div[^>]+>\s*<span[^>]+class="short"\s*title="(.+)"[^>]*>[^<]*</span>\s*<span\s*class="full"\s*([^>]*)>([^<]*)</span>[\s]*</div>#miU',
            '<span ' . $this->messageAttr . '="$1" style="background-color: ' . $this->color . '">$3</span>',
            $tag
        );
    }

    private function getSimpleTagReplacement(string $tag): string
    {
        //remove full tag
        $tag = preg_replace('#<span[^>]+class="full"[^>]*>[^<]*</span>#i', '', $tag);

        return preg_replace(
            '#<div[^>]+>\s*<span\s*class="short"\s*title="(.+)"([^>]*)>([^<]*)</span>[\s]*</div>#miU',
            '<span ' . $this->messageAttr . '="$1" style="background-color: ' . $this->color . '">$3</span>',
            $tag
        );
    }
}
