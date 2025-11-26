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

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\ContentProtection;

use MittagQI\Translate5\ContentProtection\T5memory\T5NTag;

class QueryStringGuesser
{
    public static function create(): self
    {
        return new self();
    }

    /**
     * Filters tags from request source which are not present in memory source
     *
     * @return array{string, array<string>} returns the filtered source and the list of removed tags
     */
    public function filterExtraTags(string $requestSource, string $memorySource): array
    {
        $tagsInRequestCount = preg_match_all(T5NTag::fullTagRegex(), $requestSource, $requestMatches, PREG_SET_ORDER);

        if ($tagsInRequestCount === 0) {
            return [$requestSource, []];
        }

        $tagsInMemoryCount = preg_match_all(T5NTag::fullTagRegex(), $memorySource, $memoryMatches, PREG_SET_ORDER);

        if ($tagsInMemoryCount === 0) {
            return [
                preg_replace(T5NTag::fullTagRegex(), '$3', $requestSource),
                array_map(
                    fn ($match) => T5NTag::fromMatch($match)->content,
                    $requestMatches,
                ),
            ];
        }

        $requestTags = [];
        $memoryTags = [];

        foreach ($requestMatches as $match) {
            $tag = T5NTag::fromMatch($match);
            $requestTags[$tag->rule][] = [
                'render' => $match[0],
                'tag' => T5NTag::fromMatch($match),
            ];
        }

        foreach ($memoryMatches as $match) {
            $tag = T5NTag::fromMatch($match);
            $memoryTags[$tag->rule][] = [
                'render' => $match[0],
                'tag' => T5NTag::fromMatch($match),
            ];
        }

        $skippedTags = [];

        foreach ($requestTags as $rule => $requestRuleTags) {
            if (! isset($memoryTags[$rule])) {
                $requestSource = $this->revertTags($requestSource, ...$requestRuleTags);

                $skippedTags[] = array_map(
                    fn ($tag) => $tag->content,
                    array_column($requestRuleTags, 'tag')
                );

                continue;
            }

            if (count($requestRuleTags) === count($memoryTags[$rule])) {
                continue;
            }

            /** @var array{render: string, tag: T5NTag} $requestRuleTag */
            foreach ($requestRuleTags as $requestRuleTag) {
                $found = false;
                foreach ($memoryTags[$rule] as $memoryRuleTag) {
                    if ($requestRuleTag['tag']->content === $memoryRuleTag['tag']->content) {
                        $found = true;

                        break;
                    }
                }

                if ($found) {
                    continue;
                }

                // regex to get render of tag with the closest words before and after the tag
                $regex = sprintf(
                    '/(\w+)?\s*%s\s*(\w+)?/uU',
                    preg_quote($requestRuleTag['render'], '/')
                );

                if (! preg_match($regex, $requestSource, $matches)) {
                    continue;
                }

                $tagWithSurroundingWords = $matches[0];

                $reverted = $this->revertTags($tagWithSurroundingWords, $requestRuleTag);

                if (str_contains($memorySource, $reverted)) {
                    $requestSource = $this->revertTags($requestSource, $requestRuleTag);

                    $skippedTags[] = [$requestRuleTag['tag']->content];
                }
            }
        }

        return [$requestSource, array_merge(...$skippedTags)];
    }

    private function revertTags(string $requestSource, array ...$tags): string
    {
        foreach ($tags as $tag) {
            $requestSource = str_replace($tag['render'], $tag['tag']->content, $requestSource);
        }

        return $requestSource;
    }
}
