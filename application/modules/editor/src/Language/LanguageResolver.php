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

namespace MittagQI\Translate5\Language;

use editor_Models_Languages;

class LanguageResolver
{
    public static function create(): self
    {
        return new self();
    }

    public function resolveLanguage(string|int $identifier): ?editor_Models_Languages
    {
        // TODO: redo this method using LanguageRepository. code below was extracted from model as is.

        $language = new editor_Models_Languages();

        //ignoring if already integer like value or empty
        try {
            //if empty a notFound is triggered
            if (empty($identifier) || (int) $identifier > 0) {
                $language->load($identifier);

                return $language;
            }
            $matches = [];
            if (preg_match('/^lcid-([0-9]+)$/i', $identifier, $matches)) {
                $language->loadByLcid($matches[1]);
            } else {
                $language->loadByRfc5646($identifier);
            }
        } catch (\ZfExtended_Models_Entity_NotFoundException $e) {
            return null;
        }

        return $language;
    }
}
