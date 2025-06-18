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

namespace MittagQI\Translate5\T5Memory;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;

class MemoryNameGenerator
{
    public const NEXT_SUFFIX = '_next-';

    public function generateNextMemoryName(
        LanguageResource $languageResource,
        string $usedName = null,
        bool $forceNextSuffix = false,
    ): string {
        $pattern = sprintf('/%s(\d+)$/', self::NEXT_SUFFIX);

        if (null !== $usedName && preg_match($pattern, $usedName, $matches)) {
            return $this->generateTmFilename($languageResource) . self::NEXT_SUFFIX . ((int) $matches[1] + 1);
        }

        $memories = $languageResource->getSpecificData('memories', parseAsArray: true);

        if (empty($memories) && ! $forceNextSuffix) {
            return $this->generateTmFilename($languageResource);
        }

        $currentMax = 0;
        foreach ($memories ?: [] as $memory) {
            if (! preg_match($pattern, $memory['filename'], $matches)) {
                continue;
            }

            $currentMax = $currentMax > $matches[1] ? $currentMax : (int) $matches[1];
        }

        return $this->generateTmFilename($languageResource) . self::NEXT_SUFFIX . ($currentMax + 1);
    }

    public function generateTmFilename(LanguageResource $languageResource): string
    {
        return 'ID' . $languageResource->getId() . '-' . $this->filterName($languageResource->getName());
    }

    /**
     * Replaces not allowed characters with "_" in memory names
     * @param string $name
     * @return string
     */
    private function filterName($name)
    {
        //since we are getting Problems on the OpenTM2 side with non ascii characters in the filenames,
        // we strip them all. See also OPENTM2-13.
        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);

        return preg_replace('/[^a-zA-Z0-9 _-]/', '_', $name);
    }
}
