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
 * Simple Converter to normalize Xliff to ZXliff files in a given folder
 */
class L10nXliffZXliffConverter
{
    /**
     * @var array<string, string>>
     */
    private array $exchangeMap;

    /**
     * Unfortunately, the conversion to english sources may converted strings that must not be converted
     * this array will fix those
     * @var array<string, string>>
     */
    private array $unExchangeMap;

    public function __construct(
        private readonly string $directory
    ) {
        $this->exchangeMap = include APPLICATION_DATA . '/' . L10nConfiguration::EXCHANGE_MAP_PATH;
        $this->unExchangeMap = include APPLICATION_DATA . '/locales/revert-exchanged-strings.php';
    }

    public function upgrade(bool $doUpgrade = true): array
    {
        $messages = [];
        foreach (L10nHelper::getAllLocales() as $locale) {
            $xliffPath = rtrim($this->directory, '/') . '/' . $locale . '.xliff';
            $zxliffPpath = rtrim($this->directory, '/') . '/' . $locale . Localization::FILE_EXTENSION_WITH_DOT;
            $adjusted = false;
            $zxliffExists = file_exists($zxliffPpath);
            if (file_exists($xliffPath) || $zxliffExists) {
                $writer = new XliffWriter(dirname($xliffPath), $locale);
                $parser = $zxliffExists ? new XliffParser($zxliffPpath) : new XliffParser($xliffPath);
                $translations = [];
                foreach ($parser->getTranslations() as $source => $target) {
                    if (array_key_exists($source, $this->unExchangeMap)) {
                        // fixes strings that were unwantedly changed
                        $adjusted = true;
                        $translations[$this->unExchangeMap[$source]] = $target;
                    } elseif (array_key_exists($source, $this->exchangeMap)) {
                        // fixes strings that were exchanged to english
                        $adjusted = true;
                        $translations[$this->exchangeMap[$source]] = $target;
                    } else {
                        $translations[$source] = $target;
                    }
                }
                if ($doUpgrade && ($adjusted || ! $zxliffExists)) {
                    $writer->write($translations);
                }
                if (! $zxliffExists) {
                    $messages[] = 'The localization-file “' . $xliffPath . '” was upgraded to “' . $zxliffPpath . '”';
                } elseif ($adjusted) {
                    $messages[] = 'The localization-file “' . $zxliffPpath . '” needed to be adjusted';
                } else {
                    $messages[] = 'The localization-file “' . $zxliffPpath . '” was already upgraded';
                }
            }
        }

        return $messages;
    }
}
