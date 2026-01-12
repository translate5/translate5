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

class L10nReimporter
{
    private string $basePath;

    private string $saveDir;

    private array $xliffPathes = [];

    private array $xliffReimports = [];

    private array $jsonReimports = [];

    private array $errors = [];

    public function __construct()
    {
        $this->basePath = L10nHelper::getBaseDir();
        $this->saveDir = L10nHelper::getStore();
        // find plugins
        $basePluginDir = L10nHelper::getPluginDir();
        foreach (L10nHelper::getAllPluginNames() as $pluginName) {
            $pluginXliff = $basePluginDir . '/' . $pluginName . '/locales/@locale@' . Localization::FILE_EXTENSION_WITH_DOT;
            $pluginPrimaryXliff = str_replace('@locale@', Localization::PRIMARY_LOCALE, $pluginXliff);
            if (file_exists($pluginPrimaryXliff)) {
                $this->xliffPathes[] = $pluginXliff;
            }
        }
        $this->xliffPathes[] = L10nHelper::getModuleXliff('default');
        $this->xliffPathes[] = L10nHelper::getModuleXliff('editor');
        $this->xliffPathes[] = L10nHelper::getModuleXliff('library');
        $this->xliffPathes[] = L10nHelper::getModuleXliff('erp');
    }

    public function process(bool $doCreateNonExistant = false): void
    {
        $existingZips = [];
        foreach (new DirectoryIterator($this->saveDir) as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'zip') {
                $existingZips[] = $fileInfo->getFilename();
            }
        }
        foreach (L10nHelper::getAllLocales() as $locale) {
            $sourceDir = $this->saveDir . '/' . $locale;
            $taskZip = $this->findSimilarTaskFile(L10nHelper::createTaskZipName($locale), $existingZips);
            if ($taskZip !== null) {
                $zip = new ZipArchive();
                if (is_dir($this->saveDir . '/' . $locale)) {
                    \ZfExtended_Utils::recursiveDelete($sourceDir);
                }
                if ($zip->open($this->saveDir . '/' . $taskZip) === true) {
                    $zip->extractTo($sourceDir);
                    $zip->close();
                }
            }
            if (is_dir($sourceDir)) {
                // import/update XLIFFs
                foreach ($this->xliffPathes as $xliffPathTpl) {
                    $xliffPath = str_replace('@locale@', $locale, $xliffPathTpl);
                    $xliffRepoPath = substr($xliffPath, strlen($this->basePath));
                    $importPath = $sourceDir . '/' .
                        L10nHelper::createExportFileName($xliffRepoPath);
                    if (file_exists($importPath)) {
                        if (! file_exists($xliffPath)) {
                            if ($doCreateNonExistant) {
                                // write an empty ZXLIFF file for the language based on the base-language
                                $readPath = str_replace('@locale@', Localization::PRIMARY_LOCALE, $xliffPathTpl);
                                $reader = new XliffParser($readPath);
                                $writer = new ZXliffWriter(dirname($xliffPath), $locale);
                                $writer->write($reader->getTranslations());
                            } else {
                                $this->errors[] = 'The localization-file “' . $xliffPath . '” does not exist';

                                continue;
                            }
                        }
                        $importer = new XliffImporter($xliffPath);
                        $importer->import($importPath);
                        $this->xliffReimports[] = $xliffRepoPath;
                    }
                }
                // import/update JSONs
                $finder = new JsonFiles($this->basePath);
                $files = $finder->findFiles($locale);
                if (empty($files)) {
                    if ($doCreateNonExistant) {
                        foreach ($finder->findFiles(Localization::PRIMARY_LOCALE) as $file) {
                            $files[] = substr($file, 0, -1 * strlen(Localization::PRIMARY_LOCALE . '.json')) .
                                $locale . '.json';
                        }
                    } else {
                        $this->errors[] = 'No JSON-files for locale “' . $locale . '” could be found';

                        continue;
                    }
                }
                foreach ($files as $file) {
                    $importPath = $sourceDir . '/' . L10nHelper::createExportFileName($file);
                    if (file_exists($importPath)) {
                        copy($importPath, $this->basePath . '/' . $file);
                        $this->jsonReimports[] = ltrim($file, '.');
                    }
                }
            }
        }
    }

    public function getReimportedPathes(): array
    {
        return array_merge($this->xliffReimports, $this->jsonReimports);
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    private function findSimilarTaskFile($taskZip, $existingZips): ?string
    {
        $filename = pathinfo($taskZip, PATHINFO_FILENAME);
        foreach ($existingZips as $existingZip) {
            if (str_starts_with($existingZip, $filename)) {
                return $existingZip;
            }
        }

        return null;
    }
}
