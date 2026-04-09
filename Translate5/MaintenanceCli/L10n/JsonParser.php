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

use MittagQI\ZfExtended\FileWriteException;
use MittagQI\ZfExtended\Localization;
use ZfExtended_Exception;

/**
 * Helper class to load translations from existing xliff files
 */
class JsonParser
{
    private string $content;

    private array $json;

    /**
     * @var array<string, string>
     */
    private array $stringMap;

    private bool $wasSaved = false;

    public function __construct(
        private readonly string $absoluteFilePath,
        private readonly string $locale
    ) {
        if (file_exists($this->absoluteFilePath)) {
            try {
                $this->content = file_get_contents($this->absoluteFilePath);
                $this->json = json_decode(
                    $this->content,
                    flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY,
                );
            } catch (\JsonException $e) {
                throw new ZfExtended_Exception(
                    'Invalid JSON file ' . $this->absoluteFilePath . ': ' . $e->getMessage()
                );
            }
            $this->stringMap = $this->flattenJson($this->json, $this->extractIdentifierKey());
        } else {
            $this->json = [];
            $this->stringMap = [];
        }
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return array<string, string>
     */
    public function getStringMap(): array
    {
        return $this->stringMap;
    }

    public function getJson(): array
    {
        return $this->json;
    }

    public function getIdentifier(): string
    {
        return implode('|', array_keys($this->stringMap));
    }

    public function fileChanged(): bool
    {
        return $this->wasSaved;
    }

    public function setStringMap(array $stringMap): void
    {
        // check if provided stringMap seems valid
        $identifier = $this->extractIdentifierKey();
        if (count($stringMap) > 0 && ! str_starts_with(array_keys($stringMap)[0], $identifier)) {
            throw new ZfExtended_Exception(
                'Trying to set a string-map with a different identifier on ' . $this->absoluteFilePath
            );
        }
        // update our JSON
        $this->updateJson($this->json, $stringMap, $identifier);
        // update our string-map
        foreach ($stringMap as $id => $string) {
            if (array_key_exists($id, $this->stringMap)) {
                $this->stringMap[$id] = $string;
            }
        }
    }

    public function setJson(array $json): void
    {
        // set new structure
        $this->json = $json;
        // transfer our string-map
        $this->setStringMap($this->stringMap);
        // recreate string-map
        $this->stringMap = $this->flattenJson($this->json, $this->extractIdentifierKey());
    }

    /**
     * @throws FileWriteException
     */
    public function flush(): bool
    {
        $newContent = json_encode(
            $this->json,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
        if ($newContent !== $this->content) {
            if (file_put_contents($this->absoluteFilePath, $newContent) === false) {
                throw new FileWriteException($this->absoluteFilePath);
            }
            $this->wasSaved = true;

            return true;
        }

        return false;
    }

    /**
     * Saves an ZXLIFF with the strings of the JSON as targete and the strings of the
     * Localization::PRIMARY_LOCALE-variant as source
     * @throws ZfExtended_Exception
     */
    public function saveAsImportZxliff(string $absoluteTargetPath): void
    {
        if (! str_ends_with($absoluteTargetPath, Localization::FILE_EXTENSION_WITH_DOT)) {
            throw new ZfExtended_Exception('JsonParser::saveAsImportZxliff can only be called with a ZXLIFF-path');
        }
        $xliffParser = new JsonXliffParser($absoluteTargetPath, $this->locale);
        if ($this->locale === Localization::PRIMARY_LOCALE) {
            // if we are the primary JSON-language
            $xliffParser->saveFromSourceTargetMaps($this->stringMap, $this->stringMap);
        } else {
            $primaryLoc = Localization::PRIMARY_LOCALE;
            $primaryPath = L10nHelper::createLocalizedJsonPath($this->absoluteFilePath, $this->locale, $primaryLoc);
            $parser = new JsonParser($primaryPath, $primaryLoc);
            $xliffParser->saveFromSourceTargetMaps($parser->getStringMap(), $this->stringMap);
        }
    }

    private function extractIdentifierKey(): string
    {
        $matches = [];
        $pattern = '~/([a-zA-Z0-9_\-]+)\.' . $this->locale . '\.json$~';
        if (preg_match($pattern, $this->absoluteFilePath, $matches) === 1) {
            return $matches[1];
        }
        $pattern = '~/([a-zA-Z0-9_\-]+)/locales/' . $this->locale . '\.json$~';
        if (preg_match($pattern, $this->absoluteFilePath, $matches) === 1) {
            return $matches[1];
        }

        throw new ZfExtended_Exception('JSON file ' . $this->absoluteFilePath . ' has an unknown naming pattern.');
    }

    /**
     * Creates the flat string-map for our JSON tree
     */
    private function flattenJson(array $data, string $prefix = ''): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $composedKey = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $result += $this->flattenJson($value, $composedKey);
            } else {
                $result[$composedKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Updates our JSON tree by the given string-map
     */
    private function updateJson(array &$data, array $stringMap, string $prefix): void
    {
        foreach ($data as $key => $value) {
            $composedKey = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $this->updateJson($data[$key], $stringMap, $composedKey);
            } elseif (is_string($value) && array_key_exists($composedKey, $stringMap)) {
                $data[$key] = $stringMap[$composedKey];
            }
        }
    }
}
