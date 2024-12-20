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

namespace MittagQI\Translate5\Plugins\Okapi\Bconf\Parser;

use stdClass;
use ZfExtended_Exception;
use ZfExtended_NotFoundException;

/**
 * Class Parsing the Properties-files used in the okapi eco-system
 * Note, that this parser might not parse all of the possible features of a properties file
 * A typical properties-fbased fprm has this structure:
 *
 * #v1
 * extractIsolatedStrings.b=false
 * codeFinderRules.rule0=</?([A-Z0-9a-z]*)\b[^>]*>
 * extractAllPairs.b=true
 * genericMetaRules=
 * codeFinderRules.count.i=1
 *
 * Types can be defined by appending (.i|.b) to the variable-name, all variables without type are string
 * .i = integer, .b = boolean
 *
 * Reference:
 * net.sf.okapi.common.ParametersString
 * net.sf.okapi.common.StringParameters
 * net.sf.okapi.common.BaseParameters
 */

final class PropertiesParser
{
    private array $map = [];

    private array $errors = [];

    public function __construct(string $content = null)
    {
        if ($content !== null) {
            $this->parse($content);
        }
    }

    /**
     * Returns a hashtable of the decoded properties
     */
    public function getProperties(): array
    {
        return $this->map;
    }

    /**
     * Retrieves our property-names
     */
    public function getPropertyNames(): array
    {
        return array_keys($this->map);
    }

    public function isValid(): bool
    {
        return (count($this->errors) === 0);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorString(string $seperator = "\n"): string
    {
        return implode($seperator, $this->errors);
    }

    /**
     * Retrieves the type of a variable by it's name as defined in
     * See net.sf.okapi.common.ParametersString
     * @return string integer|boolean|string
     */
    public function getDataType($propName): string
    {
        $type = (strlen($propName) > 2) ? substr($propName, -2) : null;
        if ($type === '.b') {
            return 'boolean';
        }
        if ($type === '.i') {
            return 'integer';
        }

        return 'string';
    }

    /**
     * The communication with the frontend is with data.ids. This generates them
     * must matche the frontend API Editor.plugins.Okapi.view.fprm.Properties.getPropertyId
     */
    public function getDataId($propName): string
    {
        if (strlen($propName) > 2 && (str_ends_with($propName, '.i') || str_ends_with($propName, '.b'))) {
            $propName = substr($propName, 0, -2);
        }

        return str_replace('.', '_', $propName);
    }

    public function has(string $propName): bool
    {
        return array_key_exists($propName, $this->map);
    }

    /**
     * @throws ZfExtended_NotFoundException
     */
    public function get(string $propName): string|bool|int
    {
        if ($this->has($propName)) {
            return $this->map[$propName];
        }

        throw new ZfExtended_NotFoundException('Property ' . $propName . ' not found');
    }

    /**
     * @throws ZfExtended_Exception
     * @throws ZfExtended_NotFoundException
     */
    public function set(string $propName, string|bool|int $value): void
    {
        if ($this->has($propName) && $this->validateProp($propName, $value)) {
            $this->map[$propName] = $value;
        } else {
            throw new ZfExtended_NotFoundException('Property ' . $propName . ' not found');
        }
    }

    /**
     * @throws ZfExtended_Exception
     */
    public function add(string $propName, string|bool|int $value): void
    {
        if (! $this->has($propName) && $this->validateProp($propName, $value)) {
            $this->map[$propName] = $value;
        } else {
            throw new ZfExtended_Exception('Property ' . $propName . ' already exists');
        }
    }

    /**
     * Removes a property
     */
    public function remove(string $propName): void
    {
        if ($this->has($propName)) {
            unset($this->map[$propName]);
        }
    }

    /**
     * Generates content out of our parsed map (which will clean all comments & empty lines)
     */
    public function unparse(): string
    {
        $content = "#v1";
        foreach ($this->map as $varName => $val) {
            $type = $this->getDataType($varName);
            if ($type === 'boolean') {
                $val = ($val === true) ? 'true' : 'false';
            } elseif ($type === 'integer') {
                $val = strval($val);
            } else {
                $val = $this->escape($val);
            }
            $content .= "\n" . $varName . '=' . $val;
        }

        return $content;
    }

    /**
     * Retrieves our contents as a json-object adjusted for the frontend
     * This means, the type-hints (".b", ".i") will be removed from the property-names and
     * the '.' is replaced by '_' in the remaining name
     */
    public function getJson(): stdClass
    {
        $json = new stdClass();
        foreach ($this->map as $key => $val) {
            $key = $this->getDataId($key);
            $json->$key = $val;
        }

        return $json;
    }

    /**
     * Applies data in the format of our getJson-API back
     */
    public function setFromJson(stdClass $json): void
    {
        foreach ($this->map as $key => $val) {
            $jsonKey = $this->getDataId($key);
            if (property_exists($json, $jsonKey)) {
                $this->set($key, $json->$jsonKey);
            }
        }
    }

    /**
     * Parses a content-string and validates the lines for correct data-types.
     * and creates a map of the parsed contents
     */
    private function parse(string $content): void
    {
        $content = rtrim(str_replace("\r", '', $content)); // for robustness
        $lines = explode("\n", $content);
        if (rtrim($lines[0]) != '#v1') {
            $this->errors[] = 'Invalid version header "' . rtrim($lines[0]) . '"';
        }
        foreach ($lines as $line) {
            if (! empty($line) && ! str_starts_with($line, '#')) {
                $pos = strpos($line, '=');
                if ($pos !== false && $pos > 0) {
                    $varName = trim(substr($line, 0, $pos));
                    $val = substr($line, ($pos + 1));
                    $type = $this->getDataType($varName);
                    if ($type === 'boolean') {
                        // boolean
                        $val = trim($val);
                        if ($val != 'true' && $val != 'false') {
                            $this->errors[] = 'Found invalid boolean value (' . $varName . '=' . $val . ')';
                        } else {
                            $this->map[$varName] = ($val == 'true') ? true : false;
                        }
                    } elseif ($type === 'integer') {
                        // integer
                        $val = trim($val);
                        if (! preg_match("/^-?[0-9]+$/", $val)) {
                            $this->errors[] = 'Found invalid integer value (' . $varName . '=' . $val . ')';
                        } else {
                            $this->map[$varName] = intval($val);
                        }
                    } else {
                        // strings are not trimmed but unescaped
                        $this->map[$varName] = $this->unescape($val);
                    }
                } else {
                    $this->errors[] = 'Found invalid line (' . $line . ')';
                }
            }
        }
    }

    /**
     * Escapes a String for usage in a properties-file
     * See net.sf.okapi.common.ParametersString
     */
    private function escape(string $value): string
    {
        return str_replace(["\r", "\n"], ["$0d$", "$0a$"], $value);
    }

    /**
     * Unscapes a String for usage in a properties-file
     * See net.sf.okapi.common.ParametersString
     */
    private function unescape(string $value): string
    {
        return str_replace(["$0d$", "$0a$"], ["\r", "\n"], $value);
    }

    /**
     * Validates, the value type matches the variable name
     * @throws ZfExtended_Exception
     */
    private function validateProp(string $propName, string|bool|int $value): bool
    {
        $type = $this->getDataType($propName);
        if (($type === 'boolean' && ! is_bool($value)) ||
            ($type === 'integer' && ! is_int($value)) ||
            ($type === 'string' && ! is_string($value))
        ) {
            throw new ZfExtended_Exception('Property ' . $propName . ' is not of the right type ' . $type);
        }

        return true;
    }
}
