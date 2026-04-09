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
use Zend_Registry;

/**
 * General helper for localization management code
 */
class L10nHelper
{
    public static function getBaseDir(): string
    {
        return rtrim(dirname(rtrim(APPLICATION_PATH, '/')), '/');
    }

    public static function getPluginDir(): string
    {
        return self::getBaseDir() . '/application/modules/editor/Plugins';
    }

    public static function getStore(): string
    {
        return APPLICATION_DATA . '/' . L10nConfiguration::DATA_DIR;
    }

    /**
     * @return string[]
     * @throws \Zend_Exception
     */
    public static function getAllPluginNames(): array
    {
        $manager = Zend_Registry::get('PluginManager');

        /** @var \ZfExtended_Plugin_Manager $manager */
        return array_keys($manager->getAvailable());
    }

    /**
     * @return string[]
     */
    public static function getAllLocales(): array
    {
        return array_merge([Localization::PRIMARY_LOCALE], Localization::SECONDARY_LOCALES);
    }

    /**
     * Creates the source-locale for bilingual localization files
     * TODO FIXME: For the primary locale we use "ha" as source not to create "en" => "en" tasks
     */
    public static function createSourceLocale(string $targetLocale): string
    {
        if ($targetLocale === Localization::PRIMARY_LOCALE) {
            return Localization::DEFAULT_SOURCE_LOCALE;
        }

        return Localization::PRIMARY_LOCALE;
    }

    public static function getModuleXliff(string $module, string $locale = null): string
    {
        if (! array_key_exists($module, L10nConfiguration::MODULES)) {
            throw new \ZfExtended_Exception('Module ' . $module . ' not found in L10nConfiguration::MODULES');
        }
        $path = self::getBaseDir() . L10nConfiguration::MODULES[$module]['xliff'];

        return ($locale === null) ? $path : str_replace('@locale@', $locale, $path);
    }

    public static function getModuleCodePathes(string $module): array
    {
        if (! array_key_exists($module, L10nConfiguration::MODULES)) {
            throw new \ZfExtended_Exception('Module ' . $module . ' not found in L10nConfiguration::MODULES');
        }

        return L10nConfiguration::MODULES[$module]['code'];
    }

    public static function createExportFileName(string $filePath): string
    {
        if (str_starts_with($filePath, self::getBaseDir())) {
            $filePath = substr($filePath, strlen(self::getBaseDir()));
        }
        $filename = basename($filePath);
        if (str_ends_with($filename, '.xliff')) {
            $filename = substr($filename, 0, -6) . Localization::FILE_EXTENSION_WITH_DOT;
        }

        return str_replace('/', '-', trim(dirname($filePath), './')) . '_' . $filename;
    }

    public static function createTaskName(string $locale): string
    {
        return 'Translate5-localization-' . \ZfExtended_Utils::getAppVersion() . '-' . $locale;
    }

    public static function createTaskZipName(string $locale): string
    {
        return 'Translate5-localization-' . \ZfExtended_Utils::getAppVersion() . '-' . $locale . '.zip';
    }

    public static function createTaskZipNameRegex(string $locale): string
    {
        return '~Translate5-localization-[a-zA-Z0-9][a-zA-Z0-9\.]+-' . $locale . '~';
    }

    /**
     * Changes Locales in JSON pathes
     */
    public static function createLocalizedJsonPath(string $filePath, string $currentLocale, string $newLocale): string
    {
        return substr($filePath, 0, -1 * strlen($currentLocale . '.json')) . $newLocale . '.json';
    }

    /**
     * Retrieves all ZXLIFFs for the given module
     * @throws \Zend_Exception
     */
    public static function evaluateXliffModule(string $module): ?array
    {
        $xliffs = [];
        if (array_key_exists($module, L10nConfiguration::MODULES)) {
            $xliff = self::getBaseDir() . L10nConfiguration::MODULES[$module]['xliff'];
        } elseif (
            in_array($module, self::getAllPluginNames()) &&
            file_exists(
                self::getPluginDir() . '/' . $module . '/locales/' . Localization::PRIMARY_LOCALE .
                Localization::FILE_EXTENSION_WITH_DOT
            )
        ) {
            $xliff = self::getPluginDir() . '/' . $module . '/locales/@locale@' . Localization::FILE_EXTENSION_WITH_DOT;
        } else {
            return null;
        }
        foreach (self::getAllLocales() as $locale) {
            $xliffs[] = str_replace('@locale@', $locale, $xliff);
        }

        return $xliffs;
    }
}
