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

namespace MittagQI\Translate5\Tools;

use ZfExtended_Utils;

/**
 * Helper to centralize regex based cleanup of common T5 objects (tm-matches, terms, ...
 */
class CharCleanup
{
    /**
     * Unwanted characters in terminology to cleanup for use in e.g. glossaries or MTs
     */
    public const TERM_REGEX = [
        '/[[:cntrl:]]/', // Matches characters that are often used to control text presentation, including newlines, null characters, tabs and the escape character
        '/\r|\n/', // tab and new line is not allowed
        '~\R~u', // all kind of line brakes are not allowed
    ];

    public static function cleanTermForMT(string $term): string
    {
        return ZfExtended_Utils::replaceC0C1ControlCharacters(
            trim((string) preg_replace(self::TERM_REGEX, '', $term))
        );
    }
}
