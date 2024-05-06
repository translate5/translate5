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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

use MittagQI\Translate5\ContentProtection\ContentProtector;

/**
 * protects the translate5 internal tags by removing for language resource processing
 */
class editor_Services_Connector_TagHandler_Remover extends editor_Services_Connector_TagHandler_Abstract
{
    /**
     * protects the internal tags as xliff tags x,bx,ex and g pair
     */
    public function prepareQuery(string $queryString, bool $isSource = true): string
    {
        $this->handleIsInSourceScope = $isSource;
        $this->realTagCount = 0;

        //1. whitespace preparation
        $queryString = $this->convertQueryContent($queryString, $isSource);

        //2. strip all tags and set real tag count
        return strip_tags($this->utilities->internalTag->replace($queryString, '', -1, $this->realTagCount));
    }

    /**
     * protects the internal tags for language resource processing as defined in the class
     */
    public function restoreInResult(string $resultString): string
    {
        return $this->importWhitespaceFromTagLessQuery($resultString);
    }

    protected function importWhitespaceFromTagLessQuery(string $text): string
    {
        return $this->contentProtector->convertToInternalTagsWithShortcutNumberMap(
            $this->contentProtector->protect(
                $text,
                $this->handleIsInSourceScope,
                $this->sourceLang,
                $this->targetLang,
                ContentProtector::ENTITY_MODE_KEEP
            ),
            $this->shortTagIdent,
            $this->shortcutNumberMap
        );
    }
}
