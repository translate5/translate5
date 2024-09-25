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

namespace MittagQI\Translate5\Plugins\TMMaintenance\Overwrites;

class T5MemoryXliff extends \editor_Services_Connector_TagHandler_T5MemoryXliff
{
    public function restoreInResult(string $resultString, bool $isSource = true): ?string
    {
        $restoredResult = parent::restoreInResult($resultString);

        $pattern = '/<div class="([^"]*)\bignoreInEditor\b([^"]*)">/';
        $replacement = '<div class="$1$2">';
        // Normalize spaces in the class attribute
        $replacement = preg_replace('/\s+/', ' ', $replacement);
        // Replace ignoreInEditor class
        $updatedHtml = preg_replace($pattern, $replacement, $restoredResult);

        return preg_replace('/\s+/', ' ', $updatedHtml);
    }
}