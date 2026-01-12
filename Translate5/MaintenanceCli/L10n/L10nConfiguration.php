<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace Translate5\MaintenanceCli\L10n;

use MittagQI\ZfExtended\Localization;

/**
 * General configuration of the localization-extraction
 */
class L10nConfiguration
{
    /**
     * Can be used to get more detailed info when extracting
     */
    public const DO_DEBUG = false;

    /**
     * The directory the extracted translations will be saved in APPLICATION_DATA
     */
    public const DATA_DIR = 'l10n';

    /**
     * The existing localizations and the connected code-dirs
     * HINT: Plugin-dirs will be evaluated programmatically
     */
    public const MODULES = [
        'default' => [
            'xliff' => '/application/modules/default/locales/@locale@' . Localization::FILE_EXTENSION_WITH_DOT,
            'code' => [
                '/application/modules/default',
            ],
        ],
        'editor' => [
            'xliff' => '/application/modules/editor/locales/@locale@' . Localization::FILE_EXTENSION_WITH_DOT,
            'code' => [
                '/application/modules/editor',
                '/Translate5',
            ],
        ],
        'erp' => [
            'xliff' => '/application/modules/erp/locales/@locale@' . Localization::FILE_EXTENSION_WITH_DOT,
            'code' => [
                '/application/modules/erp',
            ],
        ],
        'library' => [
            'xliff' => '/library/ZfExtended/locales/@locale@' . Localization::FILE_EXTENSION_WITH_DOT,
            'code' => [
                '/library/ZfExtended',
            ],
        ],
    ];

    /**
     * An optional marker for untranslated strings/targets
     */
    public const UNTRANSLATED = '~UNTRANSLATED~';

    /**
     * A marker-comment to seperate the existing translations from the untranslated strings in a XLIFF
     */
    public const UNTRANSLATED_SECTION = '<!-- TRANSLATIONS MISSING -->';
}
