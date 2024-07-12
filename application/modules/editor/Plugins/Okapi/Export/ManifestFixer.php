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

namespace MittagQI\Translate5\Plugins\Okapi\Export;

/**
 * This class implements a temporary fix to solve problems with Okapi
 * failing to export filters with customized subfilters
 * See https://jira.translate5.net/browse/TRANSLATE-4080
 * As soon as this is fixed in OKAPI, this code can be thrown away ...
 */
final class ManifestFixer
{
    public const SETTINGS_PATTERN = '~<doc[^>]+docId="1"[^>]+>([A-Za-z0-9+/=]+)</doc>~U';

    public const SUBFILTER_PATTERN = '~global_cdata_subfilter\s*:\s*okf_[a-z0-9]+@\S*~';

    public static function checkAndFix(string $manifestPath): void
    {
        $content = @file_get_contents($manifestPath);
        if ($content !== false) {
            $matches = [];
            preg_match(self::SETTINGS_PATTERN, $content, $matches);

            if (count($matches) > 1) {
                // found base-64 encoded filter-settings
                $encoded = $matches[1];
                $settings = base64_decode($encoded);
                // find subfilter in settings
                if (preg_match(self::SUBFILTER_PATTERN, $settings) === 1) {
                    // replace subfilter in settings
                    $settings = preg_replace_callback(self::SUBFILTER_PATTERN, function ($matches) {
                        $parts = explode('@', $matches[0]);

                        return $parts[0];
                    }, $settings);
                    // replace base64 encoded tweaked settings
                    $content = str_replace($encoded, base64_encode($settings), $content);
                    // ... and save tweaked manifest
                    file_put_contents($manifestPath, $content);
                }
            }
        }
    }
}
