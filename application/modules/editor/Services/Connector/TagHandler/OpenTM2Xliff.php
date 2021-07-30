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
/**
 * protects the translate5 internal tags as XLIFF for language resource processing,
 * - for OpenTM2 communication we convert line breaks to <ph type="lb"/> tags for better matching
 *   since in OpenTM2 line breaks are handled as such tags
 * - Also we decode tags instead with an id with an mid, this improves matchrates tag matching too.
 *   It seems that a <x id="123"/> here does not match an <it type="struct"/> tag in the imported TMX,
 *   a <x mid="123"/> instead does match the it tag (same for bx and ex tags).
 */
class editor_Services_Connector_TagHandler_OpenTM2Xliff extends editor_Services_Connector_TagHandler_Xliff {

    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_TagHandler_Xliff::prepareQuery()
     */
    public function prepareQuery(string $queryString): string {
        $queryString = parent::prepareQuery($queryString);
        $queryString = str_replace(['<x id="','<ex id="','<bx id="'], ['<x mid="','<ex mid="','<bx mid="'], $queryString);
        return str_replace(["\r\n","\n","\r"], '<ph type="lb"/>', $queryString);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_TagHandler_Xliff::restoreInResult()
     */
    public function restoreInResult(string $resultString): ?string {
        return parent::restoreInResult(str_replace([
            '<x mid="',
            '<bx mid="',
            '<ex mid="',
            '<ph type="lb"/>',
            '<ph type="lb" />'
        ], ['<x id="','<bx id="','<ex id="', "\n","\n"], $resultString));
    }
}
