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

namespace MittagQI\Translate5\T5Memory\Concordance;

class IdAttributeReplacer
{
    /**
     * Replaces all id attributes with x attributes within XML/HTML tags.
     *
     * This method only matches id attributes that are within tags, not in text content.
     * The t5:n tags are excluded from processing and their id attributes are preserved.
     *
     * For example: "test <ph id="101"/>" becomes "test <ph x="101"/>"
     * But: "Some text saying id="blabla" without tags" remains unchanged.
     * And: "<t5:n id="1"/>" remains unchanged.
     *
     * @param string $markup The XML/HTML markup to process
     * @return string The markup with id attributes replaced by x attributes (except in t5:n tags)
     */
    public function replace(string $markup): string
    {
        if (trim($markup) === '') {
            return $markup;
        }

        // Replace id="value" with x="value" only within XML/HTML tags
        // Pattern matches: < followed by tag content with id attribute, then >
        // This ensures we only match id attributes within tags, not in text content
        // Negative lookahead (?!t5:n) excludes t5:n tags from processing
        $markup = preg_replace(
            '/<(?!t5:n)([^>]*?)\bid\s*=\s*(["\'])([^"\']*)\2([^>]*)>/i',
            '<$1x=$2$3$2$4>',
            $markup
        );

        // Additionally, replace any remaining rid attribute with i attribute
        return preg_replace(
            '/<(?!t5:n)([^>]*?)\brid\s*=\s*(["\'])([^"\']*)\2([^>]*)>/i',
            '<$1i=$2$3$2$4>',
            $markup
        );
    }
}
