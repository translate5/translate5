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

use MittagQI\ZfExtended\FileWriteException;
use MittagQI\ZfExtended\Localization;

class L10nExchanger
{
    private string $basePath;

    private array $localizedPluginPathes = [];

    private array $brokenMatches = [];

    private array $sourceMap = [];

    private array $exchangedStrings = [];

    public function __construct(
        private readonly bool $doWriteFiles = false
    ) {
        $this->basePath = L10nHelper::getBaseDir();
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
     * @throws \MittagQI\ZfExtended\FileWriteException
     * @throws \ZfExtended_Exception
     */
    public function process(): void
    {
        $this->collectSourceMap();
        $this->extractAndExchange();
        $this->exchangeInJavaScript();
        $this->rewriteXliffs();
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
     * Genereates the source map - basically the english translations
     * @throws \Exception
     */
    private function collectSourceMap(): void
    {
        // first get all translations
        $primaryXliffs = $this->collectAllXliffs(Localization::PRIMARY_LOCALE);
        $this->sourceMap = $this->collectAllTranslations($primaryXliffs);

        // clean up quotes - if possible
        foreach ($this->sourceMap as $source => $newSource) {
            // missing english translations will be amended with the source
            // this assumes, that we recently only used english localization strings
            if ($newSource === '' || $newSource === null) {
                $newSource = $source;
                $this->sourceMap[$source] = $newSource;
            }
            if (str_contains($newSource, '\'')) {
                $newSource = preg_replace('~\'\{([^\']+)\}\'~i', '“{$1}”', $newSource);
                $this->sourceMap[$source] = $newSource;
            }
            if (str_contains($newSource, '"')) {
                $newSource = preg_replace('~"\{([^"]+)\}"~i', '“{$1}”', $newSource);
                $this->sourceMap[$source] = $newSource;
            }
            // cleanup not properly possible: end conversion
            if (str_contains(strip_tags($newSource), '"') || str_contains(strip_tags($newSource), '\'')) {
                throw new \Exception(
                    'The english translations contain a translation with unwanted quotes: "' . $newSource . '"'
                );
            }
        }
        // write the source-map to an own file, we need it to adjust client-specific translations after the release ...
        if ($this->doWriteFiles) {
            $this->createSourceMapFile();
        }
    }

    /**
     * The source-map must be cached in the file-system as base to adjust client-specific translations
     */
    private function createSourceMapFile(): void
    {
        $sourceMapFile = APPLICATION_DATA . '/' . L10nConfiguration::EXCHANGE_MAP_PATH;
        $content = "<?php\n/* programmatic created php file to store rewritten localization sources */\nreturn [";
        foreach ($this->sourceMap as $source => $target) {
            if (PhpExchanger::seemsValidTranslation($source)) {
                $content .= "\n" . '    ' .
                    PhpExchanger::toDoubleQuotedString(str_replace(["\n", '$'], ['\n', '\$'], $source)) .
                    ' => ' .
                    PhpExchanger::toDoubleQuotedString(str_replace(["\n", '$'], ['\n', '\$'], $target)) .
                    ',';
            }
        }
        $content .= "\n];\n";
        if (file_put_contents($sourceMapFile, $content) === false) {
            throw new FileWriteException($sourceMapFile);
        }
    }

    /**
     * Extracts and exchanges the sources in the PHP/PHTML files
     * @throws \Exception
     * @throws \MittagQI\ZfExtended\FileWriteException
     * @throws \ZfExtended_Exception
     */
    private function extractAndExchange(): void
    {
        // extract and exchange all strings in the editor
        $this->extractAndExchangeInModule(
            L10nHelper::getModuleCodePathes('editor'),
            $this->localizedPluginPathes
        );

        // extract and exchange in library/ZfExtended
        // often belong to the main editor module
        $this->extractAndExchangeInModule(L10nHelper::getModuleCodePathes('library'));

        // extract and exchange in localized Plugins
        foreach ($this->localizedPluginPathes as $pluginDir) {
            $this->extractAndExchangeInModule([substr($pluginDir, strlen($this->basePath))]);
        }

        // extract and exchange in default module
        $this->extractAndExchangeInModule(L10nHelper::getModuleCodePathes('default'));

        // extract and exchange in erp
        $this->extractAndExchangeInModule(L10nHelper::getModuleCodePathes('erp'));
    }

    /**
     * Rewrites all xliff-files for a locale to the new sources
     * @throws \Exception
     * @throws \MittagQI\ZfExtended\FileWriteException
     * @throws \ZfExtended_Exception
     */
    private function rewriteXliffs(): void
    {
        foreach (Localization::getAvailableLocales() as $locale) {
            // create xliff pathes
            $xliffs = [];
            $xliffs[] = L10nHelper::getModuleXliff('erp', $locale);
            $xliffs[] = L10nHelper::getModuleXliff('default', $locale);
            $xliffs[] = L10nHelper::getModuleXliff('library', $locale);
            foreach ($this->localizedPluginPathes as $pluginDir) {
                $xliffs[] = $pluginDir . '/locales/' . $locale . Localization::FILE_EXTENSION_WITH_DOT;
            }
            // editor last to overwrite translations from submodules
            $xliffs[] = L10nHelper::getModuleXliff('editor', $locale);

            // now exchange the sources in all the xliffs
            foreach ($xliffs as $xliff) {
                $cloner = new XliffCloner($xliff, $xliff);
                $cloner->exchange($this->sourceMap, $this->exchangedStrings, $this->doWriteFiles);
            }
        }
    }

    /**
     * @throws \Exception
     * @throws \MittagQI\ZfExtended\FileWriteException
     * @throws \ZfExtended_Exception
     */
    private function extractAndExchangeInModule(
        array $modulePathes,
        array $excludedDirs = [],
    ): void {
        foreach ($modulePathes as $modulePath) {
            $files = new PhpFiles($this->basePath . $modulePath);
            foreach ($files->findFiles($excludedDirs) as $file) {
                $exchanger = new PhpExchanger($file);
                if ($exchanger->exchange($this->sourceMap, $this->doWriteFiles)) {
                    $this->brokenMatches = array_merge($this->brokenMatches, $exchanger->getBrokenMatches());
                    $this->exchangedStrings = array_merge($this->exchangedStrings, $exchanger->getExchangedStrings());
                }
            }
        }
    }

    private function exchangeInJavaScript(): void
    {
        $jsMap = JavaScriptExchanger::createSourceMap($this->sourceMap);
        $finder = new JavaScriptFiles($this->basePath);
        foreach ($finder->findFiles() as $file) {
            $exchanger = new JavaScriptExchanger($file);
            $exchanger->exchange($jsMap, $this->doWriteFiles);
            if ($exchanger->hasBrokenMatches()) {
                $this->brokenMatches = array_merge($this->brokenMatches, $exchanger->getBrokenMatches());
            }
        }
    }

    private function collectAllTranslations(array $xliffs): array
    {
        $translations = [];
        foreach ($xliffs as $xliff) {
            $parser = new XliffParser($xliff);
            $translations = array_merge($translations, $parser->getTranslations());
        }

        return $translations;
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
}
