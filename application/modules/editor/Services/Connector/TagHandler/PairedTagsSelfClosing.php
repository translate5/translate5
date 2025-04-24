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
use MittagQI\Translate5\Segment\TagRepair\Xliff\XliffTagRepairer;

/**
 * Language resources tag handler with option to repair missing xliff tags from the remote service.
 * The prepare query will convert the query string to xliff segment structure and this segment will be used to query
 * the remote service. Based on the source string, repairing logic will be applied in case the remote service returns
 * invalid tag structure or tags are missing. For more info check: XliffTagRepairer class
 */
class editor_Services_Connector_TagHandler_PairedTagsSelfClosing extends editor_Services_Connector_TagHandler_Xliff
{
    //private const DEBUG = false;

    public function __construct(array $options = [])
    {
        $options['gTagPairing'] = false;
        parent::__construct($options);
        $protectors = [];

        if ($this->keepWhitespaceTags === false) {
            $protectors[] = new WhitespaceProtector($this->utilities->whitespace);
        }
        $this->contentProtector = new ContentProtector($protectors, [ProtectionTagsFilter::create()]);
    }

    public function prepareQuery(string $queryString, bool $isSource = true): string
    {
        try {
            //if (self::DEBUG) {
            //    error_log("Prepare query: " . $beforeRequest);
            //}
            return parent::prepareQuery($queryString, $isSource);
        } catch (Exception $e) {
            $this->logger->warn(
                'E1302',
                'Preparing query string failed. See log for details.',
                [
                    'querySegment' => $queryString,
                    'exceptionMessage' => $e->getMessage(),
                ]
            );

            return strip_tags($queryString);
        }
    }

    /**
     * Apply tag repair on service results before restoring the result to segment content.
     */
    public function restoreInResult(string $resultString, bool $isSource = true): ?string
    {
        try {
            $repairer = new XliffTagRepairer();
            $repairedText = $repairer->repairTranslation($this->getQuerySegment(), $resultString);

            //if (self::DEBUG) {
            //    error_log("Requested: " . $this->getQuerySegment());
            //    error_log("Result     :  $resultString");
            //    error_log("Repaired:  : $repairedText");
            //}

            return parent::restoreInResult($repairedText);
        } catch (Exception $e) {
            $this->logger->warn(
                'E1302',
                'Repairing or restoring of the query string failed. All tags will be removed from the result string See log for details.',
                [
                    'givenContent' => $resultString,
                    'querySegment' => $this->getQuerySegment(),
                    'resultString' => $repairedText ?? '',
                    'exceptionMessage' => $e->getMessage(),
                ]
            );

            return strip_tags($resultString);
        }
    }

    protected function convertQueryContent(string $queryString, bool $isSource = true): string
    {
        if ($this->keepWhitespaceTags) {
            return $queryString;
        }
        $queryString = $this->utilities->internalTag->restore(
            $this->trackChange->removeTrackChanges($queryString),
            $this->getTagsForRestore(),
            $this->highestShortcutNumber,
            $this->shortcutNumberMap
        );
        $whitespace = WhitespaceProtector::create();

        return $whitespace->unprotect($queryString, false);
    }
}
