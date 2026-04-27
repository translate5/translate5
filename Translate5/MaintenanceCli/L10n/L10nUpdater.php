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

use DirectoryIterator;
use MittagQI\ZfExtended\Localization;
use ZipArchive;

class L10nUpdater
{
    /**
     * If set, all JSON localizations will be saved regardless if changed
     * can be used to normalize the JSON files
     */
    private const bool FLUSH_ALL_JSON = false;

    private string $locale;

    private string $basePath;

    private string $saveDir;

    private array $localizedPluginPathes = [];

    private array $editorStrings;

    private array $brokenMatches = [];

    private bool $markMissing = false;

    private bool $prefillMissing = false;

    private array $missing;

    private array $missingByModule;

    private int $numXliffs;

    private int $numXliffsChanged;

    private array $jsons;

    private int $numJsons;

    private int $numJsonsChanged;

    public function __construct(
        private bool $doUpdateXliffs = false,
        private bool $doCollectData = false,
        private bool $collectWithEmptyTargets = false,
        private bool $doAmendMissing = false,
        private bool $markUntranslated = false,
        private bool $fillUntranslated = false,
        private ?string $markFillLocale = null,
    ) {
        $this->basePath = L10nHelper::getBaseDir();
        $this->saveDir = L10nHelper::getStore();
        // find plugins
        $basePluginDir = L10nHelper::getPluginDir();
        foreach (L10nHelper::getAllPluginNames() as $pluginName) {
            $pluginDir = $basePluginDir . '/' . $pluginName;
            $pluginXliff = $pluginDir . '/locales/' . Localization::PRIMARY_LOCALE . Localization::FILE_EXTENSION_WITH_DOT;
            if (file_exists($pluginXliff)) {
                $this->localizedPluginPathes[] = $pluginDir;
            }
        }
    }

    /**
     * @return array{ numXliffs: int, numXliffsChanged: int, numJsons: int, numJsonsChanged: int }
     * @throws \MittagQI\ZfExtended\FileWriteException
     * @throws \ZfExtended_Exception
     */
    public function process(): array
    {
        $this->jsons = [];
        $this->missing = [];
        $this->missingByModule = [];
        $this->numXliffs = 0;
        $this->numXliffsChanged = 0;
        $this->numJsons = 0;
        $this->numJsonsChanged = 0;

        if ($this->doCollectData) {
            if (is_dir($this->saveDir)) {
                \ZfExtended_Utils::recursiveDelete($this->saveDir);
            }
            mkdir($this->saveDir . '/' . Localization::PRIMARY_LOCALE, 0777, true);
            file_put_contents(
                $this->saveDir . '/task-config.ini',
                'runtimeOptions.plugins.Okapi.preserveGeneratedXlfFiles = 0' . PHP_EOL
            );
            foreach (Localization::SECONDARY_LOCALES as $locale) {
                mkdir($this->saveDir . '/' . $locale);
            }
        }
        $this->processJson();
        $this->processPrimary();
        $this->processSecondary();

        if ($this->doCollectData) {
            $this->createImportZips();
        }

        return [
            'numXliffs' => $this->numXliffs,
            'numXliffsChanged' => $this->numXliffsChanged,
            'numJsons' => $this->numJsons,
            'numJsonsChanged' => $this->numJsonsChanged,
        ];
    }

    public function getBrokenMatches(): array
    {
        return $this->brokenMatches;
    }

    public function hasBrokenMatches(): bool
    {
        return count($this->brokenMatches) > 0;
    }

    /**
     * generates the overall count and tabular data for missing translations
     * @return array{count: int, data: array}
     */
    public function getMissingData(): array
    {
        $data = [];
        $overall = 0;
        if (isset($this->missing) && count($this->missing) > 0) {
            foreach (array_keys($this->missing) as $locale) {
                $locData = [$locale, $this->missing[$locale]];
                $overall += $this->missing[$locale];
                $moduleData = [];
                foreach ($this->missingByModule as $module => $localeData) {
                    if ($localeData[$locale] > 0) {
                        $moduleData[] = $module . ': ' . $localeData[$locale];
                    }
                }
                $locData[] = implode(', ', $moduleData);
                $data[] = $locData;
            }
        }

        return [
            'count' => $overall,
            'data' => $data,
        ];
    }

    /**
     * Clones changes from the primary JSON-files over to the secondary ones
     */
    private function processJson(): void
    {
        $finder = new JsonFiles($this->basePath);
        $primaryJsons = $finder->findFiles(Localization::PRIMARY_LOCALE);
        $this->jsons[Localization::PRIMARY_LOCALE] = $primaryJsons;
        $secondaryLocales = array_diff(L10nHelper::getAllLocales(), [Localization::PRIMARY_LOCALE]);
        foreach ($secondaryLocales as $locale) {
            $this->jsons[$locale] = [];
        }

        foreach ($primaryJsons as $jsonFile) {
            if (! str_ends_with($jsonFile, Localization::PRIMARY_LOCALE . '.json')) {
                throw new \ZfExtended_Exception('Invalidly named JSON localization file: ' . $jsonFile);
            }
            $parser = new JsonParser($jsonFile, Localization::PRIMARY_LOCALE);
            $primaryJson = $parser->getJson();
            $primaryIdentifier = $parser->getIdentifier();
            $this->numJsons++;
            if (self::FLUSH_ALL_JSON && $parser->flush()) { // @phpstan-ignore-line
                $this->numJsonsChanged++;
            }
            // process secondary JSON locales
            foreach ($secondaryLocales as $locale) {
                // the file will end with <locale>.json
                $secondaryFile = L10nHelper::createLocalizedJsonPath(
                    $jsonFile,
                    Localization::PRIMARY_LOCALE,
                    $locale
                );
                $this->jsons[$locale][] = $secondaryFile;
                $parser = new JsonParser($secondaryFile, $locale);
                $this->numJsons++;
                // set structure of primary to secondary if changed
                if ($parser->getIdentifier() !== $primaryIdentifier) {
                    $parser->setJson($primaryJson);
                    if ($parser->flush()) {
                        $this->numJsonsChanged++;
                    }
                } elseif (self::FLUSH_ALL_JSON && $parser->flush()) { // @phpstan-ignore-line
                    $this->numJsonsChanged++;
                }
            }
        }
    }

    /**
     * Updates the primary locale from source-files
     * @throws \MittagQI\ZfExtended\FileWriteException
     * @throws \ZfExtended_Exception
     */
    private function processPrimary(): void
    {
        $this->locale = Localization::PRIMARY_LOCALE;
        $this->prefillMissing = $this->fillUntranslated && $this->markFillLocale === $this->locale;
        $this->markMissing = ! $this->prefillMissing && $this->markUntranslated && $this->markFillLocale === $this->locale;

        // first get all translations
        $primaryXliffs = $this->collectAllXliffs($this->locale);
        $primaryTranslations = $this->collectAllTranslations($primaryXliffs);

        // extract all strings from the editor
        $this->editorStrings = $this->extractModuleString(
            L10nConfiguration::MODULES['editor']['code'],
            $this->localizedPluginPathes,
            true
        );

        // library/ZfExtended
        // often belong to the main editor module
        $this->processModule(
            'ZfExtended',
            L10nHelper::getModuleXliff('library', $this->locale),
            L10nHelper::getModuleCodePathes('library'),
            $primaryTranslations
        );

        // localized Plugins
        foreach ($this->localizedPluginPathes as $pluginDir) {
            $this->processModule(
                basename($pluginDir),
                $pluginDir . '/locales/' . $this->locale . Localization::FILE_EXTENSION_WITH_DOT,
                [substr($pluginDir, strlen($this->basePath))],
                $primaryTranslations
            );
        }
        // default module
        $this->processModule(
            'default',
            L10nHelper::getModuleXliff('default', $this->locale),
            L10nHelper::getModuleCodePathes('default'),
            $primaryTranslations,
            true
        );
        // erp
        $this->processModule(
            'erp',
            L10nHelper::getModuleXliff('erp', $this->locale),
            L10nHelper::getModuleCodePathes('erp'),
            $primaryTranslations,
            true
        );

        // write editor / base module
        $editorXliffPath = L10nHelper::getModuleXliff('editor', $this->locale);
        $xliffUpdater = new XliffUpdater($editorXliffPath, $this->prefillMissing, $this->markMissing);
        $xliffUpdater->update($this->editorStrings, $primaryTranslations, $this->doUpdateXliffs);
        $this->numXliffs++;
        if ($xliffUpdater->fileChanged()) {
            $this->numXliffsChanged++;
        }
        if ($this->doCollectData) {
            $xliffUpdater->saveAsImport($this->createExportSaveName($editorXliffPath), $this->collectWithEmptyTargets);
            // add all JSON files if we collect data
            $this->copyJsonFiles();
        }
        $this->addNumUntranslated('editor', $this->locale, $xliffUpdater->getNumUntranslated());
    }

    /**
     * Updates the secondary locales
     * @throws \MittagQI\ZfExtended\FileWriteException
     * @throws \ZfExtended_Exception
     */
    private function processSecondary(): void
    {
        foreach (Localization::SECONDARY_LOCALES as $secondaryLocale) {
            $this->locale = $secondaryLocale;

            $this->prefillMissing = $this->fillUntranslated && $this->markFillLocale === $this->locale;
            $this->markMissing = ! $this->prefillMissing && $this->markUntranslated &&
                $this->markFillLocale === $this->locale;

            // create xliff pathes
            $xliffs = [];
            $xliffs[L10nHelper::getModuleXliff('erp', $this->locale)] = [
                'name' => 'erp',
                'path' => L10nHelper::getModuleXliff('erp', Localization::PRIMARY_LOCALE),
            ];
            $xliffs[L10nHelper::getModuleXliff('default', $this->locale)] = [
                'name' => 'default',
                'path' => L10nHelper::getModuleXliff('default', Localization::PRIMARY_LOCALE),
            ];
            $xliffs[L10nHelper::getModuleXliff('library', $this->locale)] = [
                'name' => 'ZfExtended',
                'path' => L10nHelper::getModuleXliff('library', Localization::PRIMARY_LOCALE),
            ];
            foreach ($this->localizedPluginPathes as $pluginDir) {
                $xliffs[$pluginDir . '/locales/' . $this->locale . Localization::FILE_EXTENSION_WITH_DOT] = [
                    'name' => basename($pluginDir),
                    'path' => $pluginDir . '/locales/' . Localization::PRIMARY_LOCALE . Localization::FILE_EXTENSION_WITH_DOT,
                ];
            }
            // editor last to overwrite translations from submodules
            $xliffs[L10nHelper::getModuleXliff('editor', $this->locale)] = [
                'name' => 'editor',
                'path' => L10nHelper::getModuleXliff('editor', Localization::PRIMARY_LOCALE),
            ];
            // collect translations
            $translations = $this->collectAllTranslations(array_keys($xliffs));

            // now clone all the xliffs
            foreach ($xliffs as $xliff => $data) {
                $cloner = new XliffCloner($xliff, $data['path'], $this->prefillMissing, $this->markMissing);
                $cloner->clone($translations, $this->doUpdateXliffs);
                $this->numXliffs++;
                if ($cloner->fileChanged()) {
                    $this->numXliffsChanged++;
                }
                if ($this->doCollectData) {
                    $cloner->saveAsImport($this->createExportSaveName($xliff), $this->collectWithEmptyTargets);
                }
                $this->addNumUntranslated($data['name'], $this->locale, $cloner->getNumUntranslated());
            }

            if ($this->doCollectData) {
                $this->copyJsonFiles();
            }
        }
    }

    private function createImportZips(): void
    {
        foreach (L10nHelper::getAllLocales() as $locale) {
            $zipArchive = new ZipArchive();
            $zipArchive->open($this->saveDir . '/' . L10nHelper::createTaskZipName($locale), ZipArchive::CREATE);
            $zipArchive->addFromString(
                'task-config.ini',
                'runtimeOptions.plugins.Okapi.preserveGeneratedXlfFiles = 0' . PHP_EOL
            );
            foreach (new DirectoryIterator($this->saveDir . '/' . $locale) as $fileInfo) {
                if ($fileInfo->isFile()) {
                    $zipArchive->addFile(
                        $fileInfo->getPathname(),
                        \editor_Models_Import_Configuration::WORK_FILES_DIRECTORY . '/' . $fileInfo->getFilename()
                    );
                }
            }
            $zipArchive->close();
        }
    }

    private function processModule(
        string $moduleName,
        string $xliffPath,
        array $modulePathes,
        array $translations,
        bool $skipEditorTranslations = false,
    ): void {
        $strings = $this->extractModuleString($modulePathes, [], $skipEditorTranslations);
        $xliffUpdater = new XliffUpdater($xliffPath, $this->prefillMissing, $this->markMissing);
        $xliffUpdater->update($strings, $translations, $this->doUpdateXliffs);
        $this->numXliffs++;
        if ($xliffUpdater->fileChanged()) {
            $this->numXliffsChanged++;
        }
        if ($this->doCollectData) {
            $xliffUpdater->saveAsImport($this->createExportSaveName($xliffPath), $this->collectWithEmptyTargets);
        }
        $this->addNumUntranslated($moduleName, $this->locale, $xliffUpdater->getNumUntranslated());
    }

    private function collectAllXliffs(string $locale): array
    {
        $xliffs = [];
        $xliffs[] = L10nHelper::getModuleXliff('erp', $locale);
        $xliffs[] = L10nHelper::getModuleXliff('default', $locale);
        $xliffs[] = L10nHelper::getModuleXliff('library', $locale);
        foreach ($this->localizedPluginPathes as $pluginDir) {
            $xliffs[] = $pluginDir . '/locales/' . $locale . Localization::FILE_EXTENSION_WITH_DOT;
        }
        // editor last to overwrite translations from submodules
        $xliffs[] = L10nHelper::getModuleXliff('editor', $locale);

        return $xliffs;
    }

    private function collectAllTranslations(array $xliffs): array
    {
        $translations = [];
        foreach ($xliffs as $xliff) {
            $parser = new XliffParser($xliff);
            $translations = array_merge($translations, $parser->getTranslations());
        }

        // amends translatiomns from the former ".xliff"-files if wanted
        // this is important for merging branches that were started before the introduction of MITTAGQI-367
        if ($this->doAmendMissing) {
            $amendments = [];
            foreach ($xliffs as $xliff) {
                $oldPath = substr($xliff, 0, -1 * strlen(Localization::FILE_EXTENSION_WITH_DOT)) . '.xliff';
                if (file_exists($oldPath)) {
                    $parser = new XliffParser($oldPath);
                    $amendments = array_merge($amendments, $parser->getTranslations());
                }
            }
            if (! empty($amendments)) {
                // CRUCIAL: the current translations must overwrite the amendments to have priority ...
                $translations = array_merge($amendments, $translations);
            }
        }

        return $translations;
    }

    private function extractModuleString(
        array $modulePathes,
        array $excludedDirs = [],
        bool $skipEditorTranslations = false,
    ): array {
        $strings = [];
        foreach ($modulePathes as $modulePath) {
            $files = new PhpFiles($this->basePath . $modulePath);
            foreach ($files->findFiles($excludedDirs) as $file) {
                $extractor = new PhpExtractor($file);
                foreach ($extractor->extract() as $string) {
                    if (
                        ! in_array($string, $strings) &&
                        ($skipEditorTranslations || ! in_array($string, $this->editorStrings))
                    ) {
                        $strings[] = $string;
                    }
                }
                $this->brokenMatches = array_merge($this->brokenMatches, $extractor->getBrokenMatches());
            }
        }

        return $strings;
    }

    private function copyJsonFiles(): void
    {
        foreach ($this->jsons[$this->locale] as $file) {
            $exportJsonpath = $this->createExportSaveName($file);
            $parser = new JsonParser($file, $this->locale);
            $parser->saveAsImportZxliff(
                $exportJsonpath . Localization::FILE_EXTENSION_WITH_DOT,
                $this->collectWithEmptyTargets
            );
        }
    }

    private function createExportSaveName(string $file): string
    {
        return $this->saveDir . '/' . $this->locale . '/' .
            L10nHelper::createExportFileName(substr($file, strlen($this->basePath)));
    }

    private function addNumUntranslated(string $moduleName, string $locale, int $numUntranslated): void
    {
        if (! array_key_exists($locale, $this->missing)) {
            $this->missing[$locale] = 0;
        }
        if (! array_key_exists($moduleName, $this->missingByModule)) {
            $this->missingByModule[$moduleName] = [];
        }
        if (! array_key_exists($locale, $this->missingByModule[$moduleName])) {
            $this->missingByModule[$moduleName][$locale] = 0;
        }
        $this->missing[$locale] += $numUntranslated;
        $this->missingByModule[$moduleName][$locale] += $numUntranslated;
    }
}
