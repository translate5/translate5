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

use MittagQI\Translate5\Segment\TagRepair\Xliff\TranslationTagConverter;

class editor_Services_Connector_TagHandler_PairedTags extends editor_Services_Connector_TagHandler_PairedTagsSelfClosing
{
    /**
     * Translation service used to convert the xlff format tags to service tags and vice versa.
     * Defining this kind of layer save us from writing new parser to just do exactly the same thing.
     */
    private TranslationTagConverter $translationTagConverter;

    public function __construct(array $options = [])
    {
        parent::__construct($options);
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
            $restoredString = parent::restoreInResult($convertedString);

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
}
