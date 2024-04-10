<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\Task;

use editor_Models_Import_FileParser;
use editor_Models_Import_FileParser_Csv;
use editor_Models_Import_FileParser_DisplayTextXml;
use editor_Models_Import_FileParser_Sdlxliff;
use editor_Models_Import_FileParser_Testcase;
use editor_Models_Import_FileParser_Transit;
use editor_Models_Import_FileParser_Xlf;
use editor_Models_Import_FileParser_XlfZend;
use editor_Models_Import_FileParser_Xml;
use editor_Models_Import_UploadProcessor;
use editor_Models_Task;
use SplFileInfo;
use ZfExtended_EventManager;
use ZfExtended_Factory;

/**
 * Centralizes the evaluation of which file-types (extensions) can be handled/parsed by translate5
 * Triggers an event on instantiation that enables plugins to register their extensions
 */
final class FileTypeSupport
{
    /**
     * TODO FIXME: Should better be a global definition or in a more basic class
     */
    public const SCOPE_CORE = 'Core';

    /**
     * Retrieves the global instance representing the general support for files
     * This usually represents the settings for the "defaultcustomer"
     * Please note, that e.g. for the import process, you will need a task-specific instance, use ::taskInstance then
     */
    public static function defaultInstance(): FileTypeSupport
    {
        if (! array_key_exists('DEFAULT', self::$_instances)) {
            self::$_instances['DEFAULT'] = new FileTypeSupport();
            // event to let plugins and other providers register their filetypes
            self::$events = ZfExtended_Factory::get(ZfExtended_EventManager::class, [self::class]);
            self::$events->trigger('registerSupportedFileTypes', self::$_instances['DEFAULT'], [
                'task' => null,
            ]);
        }

        return self::$_instances['DEFAULT'];
    }

    /**
     * Retrieves the FileType support for a concrete task. This is only accessible, if a task has a GUID
     */
    public static function taskInstance(editor_Models_Task $task): FileTypeSupport
    {
        $taskIdentifier = trim($task->getTaskGuid(), '{}');
        if (! array_key_exists($taskIdentifier, self::$_instances)) {
            self::$_instances[$taskIdentifier] = new FileTypeSupport();
            // event to let plugins and other providers register their filetypes
            self::$events = ZfExtended_Factory::get(ZfExtended_EventManager::class, [self::class]);
            self::$events->trigger('registerSupportedFileTypes', self::$_instances[$taskIdentifier], [
                'task' => $task,
            ]);
        }

        return self::$_instances[$taskIdentifier];
    }

    /**
     * Represents the instances by task-id (with $_instances[0] being the non-task specific instance
     * @var FileTypeSupport[]
     */
    private static array $_instances = [];

    private static ZfExtended_EventManager $events;

    private static array $coreExtensionsWithParser;

    private array $coreParsers = [
        editor_Models_Import_FileParser_Csv::class,
        editor_Models_Import_FileParser_DisplayTextXml::class,
        editor_Models_Import_FileParser_Sdlxliff::class,
        editor_Models_Import_FileParser_Testcase::class,
        editor_Models_Import_FileParser_Transit::class,
        editor_Models_Import_FileParser_Xlf::class,
        editor_Models_Import_FileParser_XlfZend::class,
        editor_Models_Import_FileParser_Xml::class,
    ];

    /**
     * The map of extensions mapped to their file parsers
     */
    private array $extensionsWithParser = [];

    /**
     * The list of extensions which can be imported via preprocessors (which convert the file then to format known by file parsers)
     */
    private array $extensionsSupported = [];

    /**
     * The list of extensions which should be ignored in import processing (and therefore do not produce a unprocessed warning)
     *  in other words, file is ignored in default import process, but still is used as secondary input file for special fileparsers like transit
     */
    private array $extensionsIgnored = [];

    /**
     * Enables Plugins to store further data
     */
    private array $pluginData = [];

    private function __construct()
    {
        // registers the core fileparsers - if not already done by another instance
        if (isset(self::$coreExtensionsWithParser)) {
            $this->extensionsWithParser = self::$coreExtensionsWithParser;
        } else {
            $this->registerCoreFileParsers();
            self::$coreExtensionsWithParser = $this->extensionsWithParser;
        }
        //ZIP is not provided by a specific fileparser, but is supported by the core as container format
        $this->register(editor_Models_Import_UploadProcessor::TYPE_ZIP, self::SCOPE_CORE);
    }

    /**
     * Registers the given file type to be handleable by translate5, but without a concrete parser
     *  due multiple pre-processing steps, this filetype is probably preprocessed and converted before giving finally to the FileParsers
     */
    public function register(string $extension, string $scope): void
    {
        if (! array_key_exists(strtolower($extension), $this->extensionsSupported)) {
            $this->extensionsSupported[strtolower($extension)] = [$scope];
        } elseif (! in_array($scope, $this->extensionsSupported[strtolower($extension)])) {
            $this->extensionsSupported[strtolower($extension)][] = $scope;
        }
    }

    /**
     * Can be used to add plugin-specific data
     */
    public function registerPluginData(mixed $data, string $pluginName): void
    {
        $this->pluginData[$pluginName] = $data;
    }

    /**
     * Registers the given file type to be ignored by translate5,
     *  useful if file is needed by the fileparser as additional data source, but should not be listed in file list
     */
    public function registerIgnored(string $extension): void
    {
        //only add if it does not already exist
        if (! in_array(strtolower($extension), $this->extensionsIgnored)) {
            $this->extensionsIgnored[] = strtolower($extension);
        }
    }

    /**
     * Registers a file parser
     */
    public function registerFileParser(string $extension, string $importFileParserClass, string $pluginName): void
    {
        if (! array_key_exists(strtolower($extension), $this->extensionsWithParser)) {
            $this->extensionsWithParser[strtolower($extension)] = [];
        }
        $this->extensionsWithParser[strtolower($extension)][] = [
            'parser' => $importFileParserClass,
            'scope' => $pluginName,
        ];
    }

    /**
     * returns a list of supported file extensions (extensions with parser + supported extensions)
     * @return string[]
     */
    public function getSupportedExtensions(): array
    {
        //array_values needed for later JSON encode (with array_unique there may be gaps in the index, which results in objects instead arrays
        return array_values(array_unique(array_merge(array_keys($this->extensionsWithParser), array_keys($this->extensionsSupported))));
    }

    /**
     * Retrieves the list of supported extensions pointing to the plugins/scope it comes from
     * This is needed in the import wizard to evaluate the resulting support when file-format settings are changed
     */
    public function getSupportedExtensionsList(): array
    {
        $list = [];
        foreach ($this->extensionsWithParser as $extension => $parsers) {
            if (! empty($parsers)) {
                $list[$extension] = [];
                foreach ($parsers as $parser) {
                    $list[$extension][] = $parser['scope'];
                }
            }
        }
        foreach ($this->extensionsSupported as $extension => $scopes) {
            if (! array_key_exists($extension, $list)) {
                $list[$extension] = [];
            }
            foreach ($scopes as $scope) {
                $list[$extension][] = $scope;
            }
        }

        return $list;
    }

    /**
     * returns all registered extensions (the supported + the ignored)
     * @return string[]
     */
    public function getRegisteredExtensions(): array
    {
        //array_values needed for later JSON encode (with array_unique there may be gaps in the index, which results in objects instead arrays
        return array_values(array_unique(array_merge($this->getSupportedExtensions(), $this->extensionsIgnored)));
    }

    /**
     * gets the rgistered data for the given plugin
     */
    public function getRegisteredPluginData(string $pluginName): mixed
    {
        if (array_key_exists($pluginName, $this->pluginData)) {
            return $this->pluginData[$pluginName];
        }

        return null;
    }

    /**
     * returns the suitable parser for extension and a given concrete file
     */
    public function hasSupportedParser(string $ext, SplFileInfo $file, array &$errorMessages = []): ?string
    {
        $fileObject = $file->openFile();
        $fileHead = $fileObject->fread(512);

        if ($fileHead === false) {
            return null;
        }

        return $this->hasSupportedParserByContent($ext, $fileHead, $errorMessages);
    }

    /**
     * returns the parser class names to the given extension
     * @return string[] possible parser class names
     */
    public function getParsers(string $extension): array
    {
        $parsers = [];
        if (array_key_exists(strtolower($extension), $this->extensionsWithParser)) {
            foreach ($this->extensionsWithParser[strtolower($extension)] as $parserData) {
                $parsers[] = $parserData['parser'];
            }
        }

        return $parsers;
    }

    /**
     * Return all extensions of the supported native parsers
     * @return string[]
     */
    public function getNativeParserExtensions(): array
    {
        return array_keys($this->extensionsWithParser);
    }

    /**
     * returns true if file extension is supported natively by a fileparser (no pre conversion like Okapi is needed for that file).
     */
    public function hasParser(string $extension): bool
    {
        if (array_key_exists(strtolower($extension), $this->extensionsWithParser)) {
            return count($this->extensionsWithParser[strtolower($extension)]) > 0;
        }

        return false;
    }

    /**
     * returns true if extension as to be ignored by the directory parser at all
     * @return boolean
     */
    public function isIgnored(string $extension): bool
    {
        return in_array(strtolower($extension), $this->extensionsIgnored);
    }

    private function hasSupportedParserByContent(string $extension, string $fileHead, array &$errorMessages): ?string
    {
        $errorMsg = '';
        $parserClasses = $this->getParsers(strtolower($extension));
        foreach ($parserClasses as $parserClass) {
            if (is_subclass_of($parserClass, editor_Models_Import_FileParser::class)
                && $parserClass::isParsable($fileHead, $errorMsg)) {
                // if the first found file parser to that extension may parse it, we use it
                return $parserClass;
            }
            if (! empty($errorMsg)) {
                $errorMessages[$parserClass] = $errorMsg;
            }
        }

        return null;
    }

    /**
     * registers all core file parsers found in the fileparser directory
     */
    private function registerCoreFileParsers(): void
    {
        foreach ($this->coreParsers as $parserCls) {
            $extensions = $parserCls::getFileExtensions();
            foreach ($extensions as $extension) {
                $this->registerFileParser($extension, $parserCls, self::SCOPE_CORE);
            }
        }
    }
}
