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

namespace MittagQI\Translate5\Tools\Tmx\ConvertFromAraya;

use editor_Models_Import_FileParser_XmlParser;
use ReflectionException;
use ZfExtended_Factory;

/**
 * See MITTAGQI-364 Convert tags in Araya-based TMX
 */
class TuParser
{
    public static string $leadLanguage = 'de';

    /**
     * local parser to analyse tu (aka TranslationUnit)
     */
    protected editor_Models_Import_FileParser_XmlParser $tuParser;

    protected int $idOffset = 0;

    protected int $iAttributCounter = 1;

    /**
     * The complete segment as string of the leadLanguage
     * Sample:
     * * '<seg>hello world</seg>'
     */
    protected string $leadSegmentContent = '';

    protected SegParser|null $leadSegment = null;

    /**
     * List of placeholder, representing opening oroginal-tags, to store iAttributCounter.
     *
     * @var PlaceholderDTO[]
     */
    protected array $leadSegmentOpenTagPlaceholder = [];

    /**
     * The complete segment as string of all other langugaes.
     * Structure is
     * language => content
     * Sample:
     * 'en' => '<seg>hello world</seg>'
     *
     * @car string[]
     */
    protected array $otherSegmentContents = [];

    /**
     * @var SegParser[]
     */
    protected array $otherSegments = [];

    /**
     * Some placeholder tags found in the segments are invalid.
     * So we simply remove those tags which is better than losing the complete segment.
     * Sample:
     * <seg>This <ph>&lt;/primary&gt;</ph> is <ph>&lt;secondary&gt;</ph> only a test</seg>
     * =>
     * <seg>This  is  only a test</seg>
     *
     * @var PlaceholderDTO[]
     */
    protected array $invalidPlaceholderTags = [];

    protected bool $hasNoLeadLanguage = false;

    protected bool $hasInvalidPlaceholderTags = false;

    protected int $countOfInvalidPlaceholderTags = 0;

    /**
     * global parser needed to write changed content
     */
    protected editor_Models_Import_FileParser_XmlParser $fileParser;

    /**
     * init the tuParser and all needed sub-parser.
     * Submitted $fileParser is needed to modify the elements in the original XML-file.
     * While we only examine the <tu>-tag we need the $idOffset to find the correct elements in the original XML-file.
     *
     * @throws ReflectionException
     */
    public function __construct(editor_Models_Import_FileParser_XmlParser $fileParser, int $idOffset)
    {
        $this->fileParser = $fileParser;
        $this->idOffset = $idOffset;

        // the tuParser will search all <seg>s and analyzes them
        $this->tuParser = ZfExtended_Factory::get(editor_Models_Import_FileParser_XmlParser::class);
        $this->tuParser->registerElement('seg', null, function ($tag, $idx, $opener) {
            // detect <tuv> parent of the segment which contains the language information
            $parentTuv = $this->tuParser->getParent('tuv');

            // create complete tag-content of the current <seg>-tag
            // this is not really needed for analyze, but is very helpful while development (and debug)
            $tagContent = $this->tuParser->join($this->tuParser->getRange($opener['openerKey'], $idx));

            // analyse the segment
            $segment = new SegParser();
            for ($id = $opener['openerKey']; $id <= $idx; $id++) {
                $segment->addEntry($id, $this->tuParser->getChunk($id));
            }

            // store segment data depending on language of the segment
            $tuvLanguage = $parentTuv['attributes']['xml:lang'];
            if ($tuvLanguage == self::$leadLanguage) {
                $this->leadSegment = $segment;
                $this->leadSegmentContent = $tagContent;
            } else {
                $this->otherSegments[$tuvLanguage] = $segment;
                $this->otherSegmentContents[$tuvLanguage] = $tagContent;
            }
        });
    }

    /**
     * parse the complete <tu>-tag submitted in $tuData.
     */
    public function parse($tuData): void
    {
        // parse <tu>-tag
        $this->tuParser->parse($tuData);

        // <tu>-tag without <seg>-tag in lead-language
        if (! $this->leadSegment) {
            $this->hasNoLeadLanguage = true;

            return;
        }

        // first handle placeholder tags in segment of lead-language to have a reference
        $placeholderTags = $this->leadSegment->getAnalyzeResults();
        foreach ($placeholderTags as $placeholderTag) {
            // do nothing for self-closing or "valid" placeholder tags
            if ($placeholderTag->isSelfCLosing || $placeholderTag->isPlaceholder) {
                continue;
            }
            // the other placeholder tags must be replaced
            $this->replacePlaceholderTags($placeholderTag, self::$leadLanguage);
        }

        // check if there are still some open tags in $this->leadSegmentOpenTagIAttributList
        // this would mean, that there are opening tags without closing tags
        if (! empty($this->leadSegmentOpenTagPlaceholder)) {
            foreach ($this->leadSegmentOpenTagPlaceholder as $placeholderTag) {
                // mark them as invalid
                $placeholderTag->setIsInvalid();
                // and add them to the list of invalid placeholder tags
                $this->invalidPlaceholderTags[] = $placeholderTag;
            }
        }

        // now handle all other languages
        foreach ($this->otherSegments as $language => $otherSegments) {
            $placeholderTags = $otherSegments->getAnalyzeResults();
            foreach ($placeholderTags as $placeholderTag) {
                // do nothing for self-closing placeholder tags
                if ($placeholderTag->isSelfCLosing || $placeholderTag->isPlaceholder) {
                    continue;
                }
                // the other placeholder tags must be replaced
                $this->replacePlaceholderTags($placeholderTag, $language);
            }
        }

        // restore opening placeholder which have been replaced by <bpt>
        // without a corresponding <ept> those placeholder should stay <ph> tags.
        $this->restoreInvalidPlaceholderTags();
    }

    /**
     * replace the placeholder tags in the original XML-file.
     * opening tags will be replaced by <bpt>...</bpt>,
     * closing tags by <ept>...</ept>
     */
    protected function replacePlaceholderTags(
        PlaceholderDTO $placeholderTag,
        string $language,
    ): void {
        // make difference if placeholder is around an opening- or a closing-tag
        $replacementTagName = ($placeholderTag->isOpeningTag) ? 'bpt' : 'ept';

        // handle / detect i-attribut counter for lead-language
        if ($language == self::$leadLanguage) {
            if ($placeholderTag->isOpeningTag) {
                $iAttributCounter = $this->iAttributCounter++;
                $placeholderTag->setIAttributCounter($iAttributCounter);
                $this->leadSegmentOpenTagPlaceholder[] = $placeholderTag;
            } else {
                $iAttributCounter = $this->retrieveIAttributeCounter($placeholderTag->getOrgTagName());
                if (! $iAttributCounter) {
                    $this->countOfInvalidPlaceholderTags++;
                    $placeholderTag->setIsInvalid();

                    return;
                }
            }
            $placeholderTag->setIAttributCounter($iAttributCounter);
        } // handle / detect i-attribut counter for other-language(s)
        else {
            $iAttributCounter = $this->getIAttributCounterFromLeadLanguage($placeholderTag, $language);
            if (! $iAttributCounter) {
                $this->countOfInvalidPlaceholderTags++;
                $placeholderTag->setIsInvalid();

                return;
            }
        }

        // get the id(s) of the original entry in file
        $orgId = $placeholderTag->startId + $this->idOffset;
        $orgEndId = $orgId + 2;

        // replace opening placeholder tag <ph> or <ph type="xyz">
        $replacementForOpeningPlaceholder = '<' . $replacementTagName
            . ' i="' . $iAttributCounter . '"'
            . substr($placeholderTag->phTag, 3);
        $this->fileParser->replaceChunk($orgId, $replacementForOpeningPlaceholder);

        // replace closing placeholder tags </ph>
        $this->fileParser->replaceChunk($orgEndId, '</' . $replacementTagName . '>');
    }

    /**
     * get the i-attribute counter for an ept-tag from the stored list of bpt-tags
     * Only used for lead-language
     */
    protected function retrieveIAttributeCounter($tagName): int|false
    {
        foreach ($this->leadSegmentOpenTagPlaceholder as $key => $placeholderTag) {
            if ($tagName == $placeholderTag->getOrgTagName()) {
                unset($this->leadSegmentOpenTagPlaceholder[$key]);

                return $placeholderTag->getIAttributCounter();
            }
        }

        return false;
    }

    /**
     * iAttribut counter for NON-lead language must be synchron to the counter in lead-language.
     * Therefor we look up the counter for NON-lead languages in the list of stored counter of the lead-language.
     */
    protected function getIAttributCounterFromLeadLanguage(
        PlaceholderDTO $tag,
        string $language,
    ): int|false {
        $leadLanguagePlaceholderTags = $this->leadSegment->getAnalyzeResults();
        foreach ($leadLanguagePlaceholderTags as $leadLanguagePlaceholderTag) {
            if ($tag->getOrgTagName() == $leadLanguagePlaceholderTag->getOrgTagName()
                && $tag->phTag == $leadLanguagePlaceholderTag->phTag
                && ! $leadLanguagePlaceholderTag->isInvalid()
                && ! $leadLanguagePlaceholderTag->isUsedInLanguages($language)
            ) {
                $leadLanguagePlaceholderTag->setUsedInLanguages($language);

                return $leadLanguagePlaceholderTag->getIAttributCounter();
            }
        }

        // what to do if no iAttributCounter can be found !?!
        // echo '.. no counter can be found for NON-lead-language tag '.print_r($tag, true)."\n";

        return false;
    }

    /**
     * When we recognise that there was a placeholder replaced by a <bpt>, but this has no corresponding <ept>
     * we have to restore the original placeholder tag again.
     */
    protected function restoreInvalidPlaceholderTags(): void
    {
        if (empty($this->invalidPlaceholderTags)) {
            return;
        }

        $this->hasInvalidPlaceholderTags = true;

        foreach ($this->invalidPlaceholderTags as $invalidPlaceholderTag) {
            $this->countOfInvalidPlaceholderTags++;
            $orgId = $invalidPlaceholderTag->startId + $this->idOffset;
            $this->fileParser->replaceChunk(
                $orgId,
                $invalidPlaceholderTag->phTag
            ); // we restore the original opening <ph>-tag
            $this->fileParser->replaceChunk($orgId + 2, '</ph>'); // and we restore the closing </ph> tag
        }
    }

    public function hasNoLeadLanguage(): bool
    {
        return $this->hasNoLeadLanguage;
    }

    public function hasInvalidPlaceholderTags(): bool
    {
        return $this->hasInvalidPlaceholderTags;
    }

    public function getCountOfInvalidPlaceholderTags(): int
    {
        return $this->countOfInvalidPlaceholderTags;
    }
}
