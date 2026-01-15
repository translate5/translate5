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

    public function __construct(array $options = [])
    {
        $options['gTagPairing'] = false;
        parent::__construct($options);

        $this->contentProtector = new ContentProtector(
            [new WhitespaceProtector($this->utilities->whitespace)],
            [ProtectionTagsFilter::create()]
        );
        $this->translationTagConverter = new TranslationTagConverter($this->logger);
    }

    public function prepareQuery(string $queryString, bool $isSource = true): string
    {
        $convertedString = '';
        $preparedQuery = '';

        try {
            $preparedQuery = parent::prepareQuery($queryString, $isSource);
            $convertedString = $this->translationTagConverter->convertToServiceFormat(
                $preparedQuery,
            );

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
        $restoredString = '';

        try {
            $convertedString = $this->translationTagConverter->convertToOriginalFormat($resultString);
            $repairer = new XliffTagRepairer();
            $repairedText = $repairer->repairTranslation($this->getQuerySegment(), $convertedString);

            $restoredString = parent::restoreInResult($repairedText, $isSource);

            return $restoredString;
        } catch (Exception $e) {
            $this->logger->warn(
                'E1302',
                'Repairing or restoring of the query string failed. All tags will be removed from the result string See log for details.',
                [
                    'receivedString' => $resultString,
                    'convertedContent' => $convertedString,
                    'resultString' => $restoredString,
                    'querySegment' => $this->getQuerySegment(),
                    'exception' => $e->getMessage(),
                ]
            );

            return strip_tags($resultString);
        }
    }

    protected function convertQueryContent(string $queryString, bool $isSource = true): string
    {
        $this->highestShortcutNumber = 0;
        $this->shortcutNumberMap = [];

        $queryString = $this->trackChange->removeTrackChanges($queryString);

        if ($this->keepWhitespaceTags) {
            $tags = $this->utilities->internalTag->get($queryString);
            if (! empty($tags)) {
                $numbers = array_filter($this->utilities->internalTag->getTagNumbers($tags));
                if (! empty($numbers)) {
                    $this->highestShortcutNumber = max($numbers);
                }
            }

            return $queryString;
        }
        $queryString = $this->utilities->internalTag->restore(
            $queryString,
            $this->getTagsForRestore(),
            $this->highestShortcutNumber,
            $this->shortcutNumberMap
        );

        return $this->contentProtector->unprotect($queryString, $isSource);
    }
}
