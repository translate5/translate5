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

use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\ContentProtection\ProtectionTagsFilter;
use MittagQI\Translate5\ContentProtection\WhitespaceProtector;
use MittagQI\Translate5\Segment\TagRepair\Xliff\TranslationTagConverter;
use MittagQI\Translate5\Segment\TagRepair\Xliff\XliffTagRepairer;

class editor_Services_Connector_TagHandler_PairedTags extends editor_Services_Connector_TagHandler_Xliff
{
    /**
     * Translation service used to convert the xlff format tags to service tags and vice versa.
     * Defining this kind of layer save us from writing new parser to just do exactly the same thing.
     */
    private TranslationTagConverter $translationTagConverter;

    /**
     * Maps XLIFF tag IDs to their whitespace original content.
     * E.g., [3 => '<softReturn/>', 5 => '<tab ts="09" length="1"/>']
     * This is populated during prepareQuery by looking at the tag map.
     */
    private array $whitespaceIdMap = [];

    public function __construct(array $options = [])
    {
        $options['gTagPairing'] = false;
        parent::__construct($options);
        $protectors = [];

        if ($this->keepWhitespaceTags === false) {
            $protectors[] = new WhitespaceProtector($this->utilities->whitespace);
        }
        $this->contentProtector = new ContentProtector($protectors, [ProtectionTagsFilter::create()]);
        $this->translationTagConverter = new TranslationTagConverter($this->logger);
    }

    public function prepareQuery(string $queryString, bool $isSource = true): string
    {
        $convertedString = '';
        $preparedQuery = '';

        try {
            // reset whitespace tracking for each query
            $this->whitespaceIdMap = [];

            $preparedQuery = parent::prepareQuery($queryString, $isSource);

            // Build whitespace ID map from the tag map. This identifies which XLIFF IDs correspond to whitespace tags
            if (! $this->keepWhitespaceTags) {
                $this->buildWhitespaceIdMap();
            }

            // Convert to service format (e.g., <x id="1"/> -> <t5x_1 />)
            $convertedString = $this->translationTagConverter->convertToServiceFormat($preparedQuery);

            // Convert whitespace XLIFF tags (now in service format) to actual whitespace
            // This is what gets sent to the translation service
            if (! $this->keepWhitespaceTags) {
                $convertedString = $this->convertWhitespaceServiceFormatToActual($convertedString);
            }

            return $convertedString;
        } catch (Exception $e) {
            $this->logger->warn(
                'E1302',
                'Preparing query string failed. See log for details.',
                [
                    'querySegment' => $queryString,
                    'convertedString' => $convertedString,
                    'preparedQuery' => $preparedQuery,
                    'exception' => $e->getMessage(),
                ]
            );

            return strip_tags($preparedQuery);
        }
    }

    public function restoreInResult(string $resultString, bool $isSource = true): ?string
    {
        $convertedString = '';

        try {
            $convertedString = $this->translationTagConverter->convertToOriginalFormat($resultString);

            // Convert actual whitespace back to XLIFF tags BEFORE the repairer runs.
            // This prevents the repairer from seeing whitespace as "missing" and adding duplicates.
            // The whitespace was converted to actual characters in prepareQuery(), and the MT/LLM
            // typically returns them as-is, so we need to restore them to XLIFF format first.
            if (! $this->keepWhitespaceTags) {
                $convertedString = $this->convertActualWhitespaceToXliff($convertedString);
            }

            $repairer = new XliffTagRepairer();

            $repairedText = $repairer->repairTranslation($this->getQuerySegment(), $convertedString);

            // do not convert whitespace XLIFF to actual characters here.
            // let parent::restoreInResult() convert XLIFF tags back to internal tags so whitespace is preserved as internal tags
            return parent::restoreInResult($repairedText, $isSource);
        } catch (Exception $e) {
            $this->logger->warn(
                'E1302',
                'Repairing or restoring of the query string failed. All tags will be removed from the result string See log for details.',
                [
                    'receivedString' => $resultString,
                    'convertedContent' => $convertedString,
                    'querySegment' => $this->getQuerySegment(),
                    'exception' => $e->getMessage(),
                ]
            );

            return strip_tags($resultString);
        }
    }

    protected function convertQueryContent(string $queryString, bool $isSource = true): string
    {
        $queryString = $this->trackChange->removeTrackChanges($queryString);

        return $queryString;
    }

    /**
     * Builds the whitespace ID map from the tag map. This identifies which XLIFF IDs correspond to whitespace internal tags.
     */
    private function buildWhitespaceIdMap(): void
    {
        $tagMap = $this->getTagMap();
        $whitespaceTags = editor_Models_Segment_Whitespace::WHITESPACE_TAGS;

        foreach ($tagMap as $xliffTag => $tagInfo) {
            $internalTag = $tagInfo[1] ?? '';

            // Use InternalTag helper to parse the internal tag and get match data
            $matches = $this->utilities->internalTag->getMatches($internalTag);
            if (empty($matches)) {
                continue;
            }

            $match = $matches[0];
            // $match[3] is the data-originalid value (e.g., 'softReturn', 'char', 'tab')
            $originalId = $match[3] ?? '';

            if (in_array($originalId, $whitespaceTags, true)
                && preg_match('/<x\s+id="(\d+)"\s*\/>/', $xliffTag, $idMatch) // Extract the ID from the XLIFF tag
            ) {
                $id = (int) $idMatch[1];
                $decodedTag = editor_Models_Segment_InternalTag::decodeTagContent($match);

                // Fallback for tags without hex-encoded content (test/mock tags return '<>')
                if ($decodedTag === '<>') {
                    $decodedTag = '<' . $originalId . '/>';
                }

                $this->whitespaceIdMap[$id] = $decodedTag;
            }
        }
    }

    /**
     * Converts whitespace in service format (<t5x_N />) to actual whitespace characters.
     */
    private function convertWhitespaceServiceFormatToActual(string $content): string
    {
        if (empty($this->whitespaceIdMap)) {
            return $content;
        }

        // match service format single tags
        $pattern = '#<t5x_(\d+)\s*/>#';

        return preg_replace_callback($pattern, function ($match) {
            $id = (int) $match[1];

            if (isset($this->whitespaceIdMap[$id])) {
                $xmlTag = $this->whitespaceIdMap[$id];

                return $this->utilities->whitespace->unprotectWhitespace($xmlTag);
            }

            // Not a whitespace tag, keep as is
            return $match[0];
        }, $content);
    }

    /**
     * Converts actual whitespace characters back to XLIFF format tags.
     * This is the reverse of convertWhitespaceServiceFormatToActual().
     * Used to restore whitespace as XLIFF tags before the repairer runs,
     * so the repairer doesn't see them as "missing" and add duplicates.
     */
    private function convertActualWhitespaceToXliff(string $content): string
    {
        if (empty($this->whitespaceIdMap)) {
            return $content;
        }

        // Build mapping from actual whitespace characters to their XLIFF IDs (in source order)
        // Since we may have multiple whitespace of the same type, we track IDs in order
        $whitespaceToIds = [];
        foreach ($this->whitespaceIdMap as $id => $xmlTag) {
            $actualWhitespace = $this->utilities->whitespace->unprotectWhitespace($xmlTag);
            if (! isset($whitespaceToIds[$actualWhitespace])) {
                $whitespaceToIds[$actualWhitespace] = [];
            }
            $whitespaceToIds[$actualWhitespace][] = $id;
        }

        // For each whitespace type, replace occurrences with their XLIFF tags in order
        foreach ($whitespaceToIds as $actualWhitespace => $ids) {
            foreach ($ids as $id) {
                // Replace the first occurrence of the actual whitespace with the XLIFF tag
                $xliffTag = '<x id="' . $id . '"/>';
                $pos = strpos($content, $actualWhitespace);
                if ($pos !== false) {
                    $content = substr_replace($content, $xliffTag, $pos, strlen($actualWhitespace));
                }
            }
        }

        return $content;
    }

    /**
     * Returns the whitespace ID map for batch processing.
     * This allows BatchTrait to store the map per segment.
     */
    public function getWhitespaceIdMap(): array
    {
        return $this->whitespaceIdMap;
    }

    /**
     * Sets the whitespace ID map for batch processing.
     * This allows BatchTrait to restore the map per segment before calling restoreInResult.
     */
    public function setWhitespaceIdMap(array $whitespaceIdMap): void
    {
        $this->whitespaceIdMap = $whitespaceIdMap;
    }
}
