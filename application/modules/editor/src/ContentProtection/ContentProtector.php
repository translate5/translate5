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

use editor_Models_Segment_Utility as SegmentUtility;
use editor_Models_Segment_Whitespace as Whitespace;
use MittagQI\Translate5\ContentProtection\DTO\ConversionToInternalTagResult;
use MittagQI\Translate5\Segment\EntityHandlingMode;

class ContentProtector
{
    /**
     * @var array<string, ProtectorInterface>
     */
    private array $protectors = [];

    /**
     * @var ProtectionTagsFilterInterface[]
     */
    private array $tagFilters;

    /**
     * @param  ProtectorInterface[] $protectors
     */
    public function __construct(array $protectors, array $tagFilters)
    {
        $this->tagFilters = $tagFilters;

        foreach ($protectors as $protector) {
            $this->protectors[$protector::alias()] = $protector;
        }
    }

    public function validateFormat(string $type, string $format): bool
    {
        /** @var NumberProtector $numberProtector */
        $numberProtector = $this->protectors[NumberProtector::alias()];

        return $numberProtector->validateFormat($type, $format);
    }

    public function getFormatedExample(string $type, string $format): string
    {
        /** @var NumberProtector $numberProtector */
        $numberProtector = $this->protectors[NumberProtector::alias()];

        return $numberProtector->getFormatedExample($type, $format);
    }

    public static function create(?Whitespace $whitespace = null): self
    {
        $whitespace ??= new Whitespace();

        return new self(
            [
                NumberProtector::create(),
                new WhitespaceProtector($whitespace),
            ],
            [
                ProtectionTagsFilter::create(),
            ]
        );
    }

    /**
     * Checks content protection tags in source and target
     * Unprotect those that don't have a pair
     */
    public function filterTags(string $source, string $target): array
    {
        if ('' === $target || '' === $source) {
            return [$source, $target];
        }

        foreach ($this->tagFilters as $filter) {
            [$source, $target] = $filter->filterTags($source, $target);
        }

        return [$source, $target];
    }

    public function protect(
        string $text,
        bool $isSource,
        int $sourceLang,
        int $targetLang,
        EntityHandlingMode $entityHandling = EntityHandlingMode::Restore,
        string ...$exceptProtectors
    ): string {
        if (str_starts_with($text, 'translate5-unique-id')) {
            return $text;
        }

        $text = SegmentUtility::entityCleanup($text, $entityHandling);

        foreach ($this->protectors as $protector) {
            if (in_array($protector::alias(), $exceptProtectors, true)) {
                continue;
            }

            if ($protector->hasEntityToProtect($text, $sourceLang)) {
                $text = $protector->protect($text, $isSource, $sourceLang, $targetLang, $entityHandling);
            }
        }

        return $text;
    }

    public function tagList(): array
    {
        $tags = [];

        foreach ($this->protectors as $protector) {
            $tags[] = $protector->tagList();
        }

        return array_unique(array_merge(...$tags));
    }

    public function unprotect(string $text, bool $isSource, string ...$exceptProtectors): string
    {
        foreach ($this->protectors as $protector) {
            if (in_array($protector::alias(), $exceptProtectors, true)) {
                continue;
            }

            $text = $protector->unprotect($text, $isSource);
        }

        return $text;
    }

    public function convertForSorting(string $text, bool $isSource): string
    {
        foreach ($this->protectors as $protector) {
            $text = $protector->convertForSorting($text, $isSource);
        }

        return $text;
    }

    public function hasTagsToConvert(string $segment): bool
    {
        foreach ($this->protectors as $protector) {
            if ($protector->hasTagsToConvert($segment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * replaces the placeholder tags (<protectedTag> / <hardReturn> / <char> / <number> etc) with an internal tag
     */
    public function convertToInternalTags(string $segment, int &$shortTagIdent): string
    {
        foreach ($this->protectors as $protector) {
            if ($protector->hasTagsToConvert($segment)) {
                $segment = $protector->convertToInternalTags(
                    $segment,
                    $shortTagIdent,
                );
            }
        }

        return $segment;
    }

    public function convertToInternalTagsWithShortcutNumberMapCollecting(
        string $segment,
        int $shortTagIdent,
    ): ConversionToInternalTagResult {
        $shortcutNumberMap = [];

        foreach ($this->protectors as $protector) {
            if ($protector->hasTagsToConvert($segment)) {
                $dto = $protector->convertToInternalTagsWithShortcutNumberMapCollecting(
                    $segment,
                    $shortTagIdent,
                );
                $segment = $dto->segment;
                $shortTagIdent = $dto->shortTagIdent;
                $shortcutNumberMap[] = $dto->shortcutNumberMap;
            }
        }

        return new ConversionToInternalTagResult(
            $segment,
            $shortTagIdent,
            array_merge(...$shortcutNumberMap),
        );
    }

    public function convertToInternalTagsWithShortcutNumberMap(
        string $segment,
        int $shortTagIdent,
        array $shortcutNumberMap
    ): string {
        foreach ($this->protectors as $protector) {
            if ($protector->hasTagsToConvert($segment)) {
                $dto = $protector->convertToInternalTagsWithShortcutNumberMap(
                    $segment,
                    $shortTagIdent,
                    $shortcutNumberMap
                );
                $segment = $dto->segment;
            }
        }

        return $segment;
    }

    public function protectAndConvert(
        string $text,
        bool $isSource,
        int $sourceLang,
        int $targetLang,
        int &$shortTagIdent,
        EntityHandlingMode $entityHandling = EntityHandlingMode::Restore
    ): string {
        return $this->convertToInternalTags(
            $this->protect($text, $isSource, $sourceLang, $targetLang, $entityHandling),
            $shortTagIdent
        );
    }

    /**
     * @return string[]|\editor_Models_Import_FileParser_Tag[]
     */
    public function convertToInternalTagsInChunks(
        string $segment,
        int &$shortTagIdent,
        array &$shortcutNumberMap = [],
    ): array {
        $tagsPattern = '/<.+\/>/U';
        // we assume that tags that we interested in are all single tags
        if (! preg_match_all($tagsPattern, $segment, $matches)) {
            return [$segment];
        }
        $strings = preg_split($tagsPattern, $segment);
        $tags = $matches[0];
        $chunkStorage = [];
        $matchCount = count($tags);
        for ($i = 0; $i <= $matchCount; $i++) {
            if (isset($strings[$i]) && '' !== $strings[$i]) {
                $chunkStorage[] = [$strings[$i]];
            }
            if (! isset($tags[$i])) {
                continue;
            }
            foreach ($this->protectors as $protector) {
                if ($protector->hasTagsToConvert($tags[$i])) {
                    $chunkStorage[] = $protector->convertToInternalTagsInChunks(
                        $tags[$i],
                        $shortTagIdent,
                        $shortcutNumberMap,
                    );

                    continue 2;
                }
            }
        }

        return array_values(array_filter(array_merge(...$chunkStorage), fn ($v) => '' !== $v));
    }
}
