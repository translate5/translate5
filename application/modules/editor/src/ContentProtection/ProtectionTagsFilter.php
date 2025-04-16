<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\ContentProtection;

use MittagQI\Translate5\ContentProtection\NumberProtection\Tag\NumberTag;

class ProtectionTagsFilter implements ProtectionTagsFilterInterface
{
    public function __construct(
        private readonly NumberProtector $protector,
    ) {
    }

    public static function create(): self
    {
        return new self(
            NumberProtector::create(),
        );
    }

    public function filterTagsInChunks(array &$sourceChunks, array &$targetChunks): void
    {
        if (empty($sourceChunks) || empty($targetChunks)) {
            return;
        }

        $sourceTags = $targetTags = [];

        foreach ($sourceChunks as $key => $sourceChunk) {
            if ($sourceChunk instanceof NumberTag) {
                $sourceTags[$key] = $sourceChunk;
            }
        }

        foreach ($targetChunks as $key => $targetChunk) {
            if ($targetChunk instanceof NumberTag) {
                $targetTags[$key] = $targetChunk;
            }
        }

        foreach ($sourceTags as $sourceKey => $sourceTag) {
            foreach ($targetTags as $targetKey => $targetTag) {
                if ($sourceTag->equals($targetTag)) {
                    $targetChunks[$targetKey] = clone $sourceTag;
                    unset($targetTags[$targetKey]);

                    continue 2;
                }
            }

            $sourceChunks[$sourceKey] = $sourceTag->source;
        }

        foreach ($targetTags as $targetKey => $targetTag) {
            $targetChunks[$targetKey] = $targetTag->source;
        }
    }

    public function filterTags(string &$source, string &$target): void
    {
        if ('' === $target || '' === $source) {
            return;
        }

        preg_match_all(NumberProtector::fullTagRegex(), $source, $sourceMatches, PREG_SET_ORDER);
        preg_match_all(NumberProtector::fullTagRegex(), $target, $targetMatches, PREG_SET_ORDER);

        if (! empty($sourceMatches) && empty($targetMatches)) {
            $source = $this->protector->unprotect($source, true);

            return;
        }

        if (empty($sourceMatches) && ! empty($targetMatches)) {
            $target = $this->protector->unprotect($target, true);

            return;
        }

        if (empty($sourceMatches) && empty($targetMatches)) {
            return;
        }

        $sourceTagsMap = $this->getMatchedTags($sourceMatches);
        $targetTagsMap = $this->getMatchedTags($targetMatches);

        $tagCount = 0;

        // if targetTags has same needle - replace target tag with source tag for amount of count of source
        // those tags that are out of count should be unprotected
        /** @var \SplQueue<string> $sourceTags */
        foreach ($sourceTagsMap as $needle => ['tags' => $sourceTags, 'content' => $sourceContent]) {
            if (! isset($targetTagsMap[$needle])) {
                foreach ($sourceTags as $sourceTag) {
                    $source = str_replace($sourceTag, $sourceContent, $source);
                }

                continue;
            }

            $targetTags = $targetTagsMap[$needle]['tags'];

            $replacementMap = [];

            while (! $sourceTags->isEmpty()) {
                if ($targetTags->isEmpty()) {
                    break;
                }

                $tagCount++;

                $regex = $this->tagToRegex($targetTags->dequeue());

                $replacement = "CP_TAG_REPLACEMENT_" . $tagCount;

                $replacementMap[$replacement] = $sourceTags->dequeue();

                $target = preg_replace_callback(
                    $regex,
                    fn ($matches) => $replacement,
                    $target,
                    1 // one at a time
                );
            }

            $target = str_replace(array_keys($replacementMap), array_values($replacementMap), $target);

            while (! $sourceTags->isEmpty()) {
                $regex = $this->lastTagRegex($sourceTags->dequeue());

                $source = preg_replace_callback(
                    $regex,
                    fn ($matches) => $sourceContent,
                    $source,
                    1 // one at a time
                );
            }

            while (! $targetTags->isEmpty()) {
                $regex = $this->lastTagRegex($targetTags->dequeue());

                $target = preg_replace_callback(
                    $regex,
                    fn ($matches) => $targetTagsMap[$needle]['content'],
                    $target,
                    1 // one at a time
                );
            }

            unset($targetTagsMap[$needle]);
        }

        foreach ($targetTagsMap as ['tags' => $targetTags, 'content' => $targetContent]) {
            foreach ($targetTags as $targetTag) {
                $target = str_replace($targetTag, $targetContent, $target);
            }
        }
    }

    /**
     * @return array<string, array{tags: \SplQueue<string>, content: string}>
     */
    public function getMatchedTags(array $matches): array
    {
        $tags = [];

        foreach ($matches as $match) {
            // needle = type + iso + target
            $needle = $match[1] . ':' . $match[4] . ':' . $match[5];

            if (! isset($tags[$needle])) {
                $tags[$needle] = [
                    'tags' => new \SplQueue(),
                    'content' => $match[3],
                ];
            }

            // same needle may refer to different rules
            $tags[$needle]['tags']->enqueue($match[0]);
        }

        return $tags;
    }

    private function tagToRegex(string $tag): string
    {
        return '~' . preg_quote($tag) . '~';
    }

    private function lastTagRegex(string $tag): string
    {
        $tag = preg_quote($tag);

        return "~$tag(?=(?!.*$tag))~";
    }
}
