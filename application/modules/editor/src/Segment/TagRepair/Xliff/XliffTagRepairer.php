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

namespace MittagQI\Translate5\Segment\TagRepair\Xliff;

/**
 * XliffTagRepairer is responsible for repairing and normalizing XLIFF tags in translated text.
 *
 * This class implements multiple repair techniques to translated text
 * where XLIFF tags may have been corrupted, misplaced, or modified during translation.
 *
 * The repairer works by:
 * 1. Extracting tags from both source and translated text using a TagParser
 * 2. Comparing the tag structures to determine if repair is needed
 * 3. Applying a series of repair strategies in sequence to fix common issues:
 *    - Correcting tag types (TagTypeRepair)
 *    - Fixing RID attributes for paired tags (RidAttributeRepair)
 *    - Removing extra tags (added by the service for example) (RemoveExtraTags)
 *    - Adding missing tags that should be present (AddMissingTags)
 *
 * The class is extensible, allowing additional repair strategies to be added as needed!
 */

class XliffTagRepairer
{
    private const DEBUG = false;

    private TagParserInterface $parser;

    /**
     * @var array<RepairInterface>
     */
    private array $strategies = [];

    public static function create(): self
    {
        return new self(
            // register default strategies in order
            [
                new TagTypeRepair(),
                new RidAttributeRepair(),
                new RemoveExtraTags(),
                new AddMissingTags(),
            ],
            // default it to regex
            new RegexTagParser()
        );
    }

    public static function createWithRepairers(array $repairers): self
    {
        return new self(
            $repairers,
            // default it to regex
            new RegexTagParser()
        );
    }

    /**
     * Constructor
     */
    public function __construct(array $repairers, TagParserInterface $parser)
    {
        $this->parser = $parser;

        foreach ($repairers as $repairer) {
            $this->add($repairer);
        }
    }

    public function add(RepairInterface $strategy): self
    {
        $this->strategies[] = $strategy;

        return $this;
    }

    /**
     * Repair XLIFF tags in translated text using source text as reference
     *
     * @param string $sourceText Original text with correct tag structure
     * @param string $translatedText Translated text with potentially incorrect tags
     */
    public function repairTranslation(string $sourceText, string $translatedText): string
    {
        if ($this->shouldDebug()) {
            error_log("Repairing translation started:");
            error_log("Source: $sourceText");
            error_log("Translated: $translatedText");
        }

        if ($sourceText === $translatedText) {
            if ($this->shouldDebug()) {
                error_log("Source and translated text are identical, nothing to repair");
            }

            return $translatedText;
        }

        // extract tags from source and translated texts
        $sourceTags = $this->parser->extractTags($sourceText);
        $translatedTags = $this->parser->extractTags($translatedText);

        // if no tags in source, nothing to reference for repair
        // TODO: come back to me again and think
        if (empty($sourceTags)) {
            if ($this->shouldDebug()) {
                error_log("No tags in source, nothing to repair");
            }

            return $translatedText;
        }

        // Pre-condition check: Determine if repair is actually needed
        if ($this->tagsAreEquivalent($sourceTags, $translatedTags)) {
            if ($this->shouldDebug()) {
                error_log("Tags are equivalent, nothing to repair");
            }

            return $translatedText; // No repair needed
        }

        if ($this->shouldDebug()) {
            error_log("Source tags: " . print_r($sourceTags, true));
        }

        $repairedText = $translatedText;

        foreach ($this->strategies as $strategy) {
            if ($this->shouldDebug()) {
                error_log("Applying repair strategy: " . get_class($strategy));
            }

            // re-extract tags on each repair
            $currentTags = $this->parser->extractTags($repairedText);

            if ($this->shouldDebug()) {
                error_log("Current tags: " . print_r($currentTags, true));
            }

            $repairedText = $strategy->apply($repairedText, $sourceTags, $currentTags);

            if ($this->shouldDebug()) {
                error_log("Repaired text: " . $repairedText);
            }
        }

        if ($this->shouldDebug()) {
            error_log("Repaired text: " . $repairedText);
            error_log("Repairing translation finished");
        }

        return $repairedText;
    }

    /**
     * Create a sequence string representing the order of tags with their complete information
     *
     * @param array<TagInterface> $tags Tags to create sequence for
     * @return string A string representing the ordered sequence with full tag information
     */
    private function createTagSequence(array $tags): string
    {
        // Create a sequence of tag IDs, types, and rids
        $sequence = [];
        foreach ($tags as $tag) {
            // Include the rid in the sequence - this captures pairing relationships
            $sequence[] = sprintf(
                '%s:%s:%s',
                $tag->getId(),
                $tag->getType(),
                $tag->getRid() ?? 'null'
            );
        }

        return implode(',', $sequence);
    }

    /**
     * Check if two sets of tags are equivalent including their relative order
     * This determines if repair is actually needed
     *
     * @param array<TagInterface> $sourceTags Tags from source text
     * @param array<TagInterface> $translatedTags Tags from translated text
     * @return bool True if tags are equivalent, false otherwise
     */
    private function tagsAreEquivalent(array $sourceTags, array $translatedTags): bool
    {
        // different tag count -> check repair required
        if (count($sourceTags) !== count($translatedTags)) {
            return false;
        }

        $sourceIds = array_map(function ($tag) {
            return $tag->getId();
        }, $sourceTags);
        $translatedIds = array_map(function ($tag) {
            return $tag->getId();
        }, $translatedTags);

        // different tag ids -> check repair required
        if ($sourceIds !== $translatedIds) {
            return false;
        }

        // create unique sequence for source and translated tags (out of the tag type id and rid if exist)
        $sourceSequence = $this->createTagSequence($sourceTags);
        $translatedSequence = $this->createTagSequence($translatedTags);

        // if the sequences do nott match, the tag order or nesting is different
        return $sourceSequence === $translatedSequence;
    }

    private function shouldDebug(): bool
    {
        return self::DEBUG;
    }
}
