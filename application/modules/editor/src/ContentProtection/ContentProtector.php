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

class ContentProtector
{
    /**
     * All entities are restored to their applicable characters (&_szlig; => ß),
     * only the XML relevant &<> are encoded (ready for GUI)
     * @var string
     */
    public const ENTITY_MODE_RESTORE = 'restore';

    /**
     * Nothing is restored, but encoded (&_szlig; => &_amp;szlig;),
     * only the XML relevant &<> are encoded (ready for GUI)
     * @var string
     */
    public const ENTITY_MODE_KEEP = 'keep';

    /**
     * Entity handling is disabled, entities must be handled elsewhere!
     * @var string
     */
    public const ENTITY_MODE_OFF = 'off';

    private array $shortcutNumberMap = [];
    private bool $collectShortcutMap = false;

    /**
     * @param  ProtectorInterface[] $protectors
     */
    public function __construct(private array $protectors)
    {
    }

    public static function create(Whitespace $whitespace): self
    {
        return new self([
            new WhitespaceProtector($whitespace, true),
            NumberProtector::create(),
            new WhitespaceProtector($whitespace)
        ]);
    }

    public function resetShortcutMap(): void
    {
        $this->shortcutNumberMap = [];
    }

    public function switchShortcutMapCollection(bool $collect): void
    {
        $this->collectShortcutMap = $collect;
    }

    public function protect(
        string $text,
        int $sourceLang,
        int $targetLang,
        string $entityHandling = self::ENTITY_MODE_RESTORE,
        string ...$exceptProtectors
    ): string {
        if (0 === strpos($text, 'translate5-unique-id')) {
            return $text;
        }

        if ($entityHandling !== self::ENTITY_MODE_OFF) {
            $text = SegmentUtility::entityCleanup($text, $entityHandling === self::ENTITY_MODE_RESTORE);
        }

        foreach ($this->protectors as $protector) {
            if (in_array($protector::alias(), $exceptProtectors, true)) {
                continue;
            }

            if ($protector->hasEntityToProtect($text, $sourceLang)) {
                $text = $protector->protect($text, $sourceLang, $targetLang);
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

    public function unprotect(string $text, string ...$exceptProtectors): string
    {
        foreach ($this->protectors as $protector) {
            if (in_array($protector::alias(), $exceptProtectors, true)) {
                continue;
            }

            $text = $protector->unprotect($text);
        }

        return $text;
    }

    public function convertForSorting(string $text): string
    {
        foreach ($this->protectors as $protector) {
            $text = $protector->convertForSorting($text);
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
                    $this->collectShortcutMap,
                    $this->shortcutNumberMap
                );
            }
        }

        return $segment;
    }

    public function convertToInternalTagsWithShortcutNumberMap(
        string $segment,
        int &$shortTagIdent,
        array $shortcutNumberMap
    ): string {
        foreach ($this->protectors as $protector) {
            if ($protector->hasTagsToConvert($segment)) {
                $segment = $protector->convertToInternalTagsWithShortcutNumberMap(
                    $segment,
                    $shortTagIdent,
                    $shortcutNumberMap
                );
            }
        }

        return $segment;
    }

    public function protectAndConvert(
        string $text,
        int $sourceLang,
        int $targetLang,
        int &$shortTagIdent,
        string $entityHandling = self::ENTITY_MODE_RESTORE
    ): string {
        return $this->convertToInternalTags(
            $this->protect($text, $sourceLang, $targetLang, $entityHandling),
            $shortTagIdent
        );
    }

    /**
     * @return string[]|\editor_Models_Import_FileParser_Tag[]
     */
    public function convertToInternalTagsInChunks(string $segment, int &$shortTagIdent): array
    {
        $tagsPattern = '/<.+\/>/U';
        // we assume that tags that we interested in are all single tags
        if (!preg_match_all($tagsPattern, $segment, $matches)) {
            return [$segment];
        }

        $strings = preg_split($tagsPattern, $segment);
        $tags = $matches[0];

        $chunkStorage = [];

        $matchCount = count($tags);

        for ($i = 0; $i <= $matchCount; $i++) {
            if (!empty($strings[$i])) {
                $chunkStorage[] = [$strings[$i]];
            }

            if (!isset($tags[$i])) {
                continue;
            }

            foreach ($this->protectors as $protector) {
                if ($protector->hasTagsToConvert($tags[$i])) {
                    $chunkStorage[] = $protector->convertToInternalTagsInChunks(
                        $tags[$i],
                        $shortTagIdent,
                        $this->collectShortcutMap,
                        $this->shortcutNumberMap
                    );

                    continue 2;
                }
            }
        }

        return array_values(array_filter(array_merge(...$chunkStorage)));
    }
}