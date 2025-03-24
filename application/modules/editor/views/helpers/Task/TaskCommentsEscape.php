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

class View_Helper_TaskCommentsEscape extends Zend_View_Helper_Abstract
{
    public function taskCommentsEscape(?string $comment): ?string
    {
        if ($comment === null) {
            return '';
        }

        $comment = $this->escapeConentOfContentSpan($comment);

        return $this->escapeContentOfAuthorSpan($comment);
    }

    private function escapeConentOfContentSpan(string $comment): string
    {
        return preg_replace_callback(
            '/(<span class="content">)(.*?)(<\/span>)/',
            static fn ($matches) => $matches[1] . htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8') . $matches[3],
            $comment
        );
    }

    private function escapeContentOfAuthorSpan(?string $comment): string
    {
        return preg_replace_callback(
            '/(<span class="author">)(.*?)(<\/span>)/',
            static fn ($matches) => $matches[1] . htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8') . $matches[3],
            $comment
        );
    }
}
