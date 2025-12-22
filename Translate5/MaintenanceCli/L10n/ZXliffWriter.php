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

namespace Translate5\MaintenanceCli\L10n;

use MittagQI\ZfExtended\Localization;
use ZfExtended_Exception;

/**
 * Writes a new ZXLIFF file for the given locale into the given dir
 */
class ZXliffWriter extends AbstractXliffProcessor
{
    public function __construct(string $absoluteFolderPath, string $locale)
    {
        if (! is_dir($absoluteFolderPath)) {
            throw new ZfExtended_Exception('Directory does not exist ' . $this->absoluteFilePath);
        }
        $this->absoluteFilePath = rtrim($absoluteFolderPath, '/') . '/' . $locale . Localization::FILE_EXTENSION_WITH_DOT;
        $this->untranslatedEmpty = false;
        $this->markUntranslated = false;

        $sourceLocale = ($locale === Localization::PRIMARY_LOCALE) ?
            Localization::DEFAULT_SOURCE_LOCALE : Localization::PRIMARY_LOCALE;

        $this->header =
            '<?xml version="1.0" ?>' . "\n" .
            '<xliff xmlns="urn:oasis:names:tc:xliff:document:1.1" version="1.1">' . "\n" .
            '    <file original="php-sourcecode" source-language="' . $sourceLocale . '" target-language="' . $locale . '" datatype="php">' . "\n" .
            '        <body>';

        $this->footer = "\n" .
            '        </body>' . "\n" .
            '    </file>' . "\n" .
            '</xliff>';
    }

    public function write($translations): void
    {
        foreach ($translations as $source => $target) {
            $this->addTransUnit($source, $target);
        }
        $this->flush();
    }

    public function getPath(): string
    {
        return $this->absoluteFilePath;
    }

    public function getFileName(): string
    {
        return basename($this->absoluteFilePath);
    }
}
