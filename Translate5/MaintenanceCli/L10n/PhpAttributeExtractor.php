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

use MittagQI\ZfExtended\Localization\ExtractableLocalization;
use MittagQI\ZfExtended\Localization\LocalizableArrayProp;
use MittagQI\ZfExtended\Localization\LocalizableConfigValue;
use MittagQI\ZfExtended\Localization\LocalizableMsg;
use MittagQI\ZfExtended\Localization\LocalizableMsgArray;
use MittagQI\ZfExtended\Localization\LocalizableProp;
use MittagQI\ZfExtended\Localization\LocalizableString;
use MittagQI\ZfExtended\Localization\LocalizableTableColumn;

/**
 * Extracts Attribute definitions for localization from PHP classes
 */
class PhpAttributeExtractor
{
    public const CLASSNAME_PATTERN = '~\n[^/\n]*class\s+([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)[^{\n]*\s*{~';

    public const NAMESPACE_PATTERN = '~\n[\t\r ]*namespace\s+([a-zA-Z0-9_\x80-\xff\\\\]+)\s*;~';

    public static function getClassShortName(string $className): string
    {
        return substr($className, strrpos($className, '\\') + 1);
    }

    protected array $brokenMatches = [];

    protected array $classExtractors = [
        LocalizableString::class,
        LocalizableTableColumn::class,
        LocalizableConfigValue::class,
    ];

    protected array $propertyConstantExtractors = [
        LocalizableMsg::class,
        LocalizableMsgArray::class,
        LocalizableProp::class,
        LocalizableArrayProp::class,
    ];

    protected array $extractorShortnames = [];

    public function __construct(
        protected string $content,
        protected readonly string $absoluteFilePath,
    ) {
        foreach (array_merge($this->classExtractors, $this->propertyConstantExtractors) as $className) {
            $this->extractorShortnames[] = self::getClassShortName($className);
        }
    }

    /**
     * Extracts the localized source-strings from a PHP file by PHP attributes
     */
    public function extract(): array
    {
        $strings = [];
        // reflection is expensive, so only do it when there are possible matches ...
        // and exclude this class and the Attribute-classes
        if ($this->containsExtractableShortname($this->content)) {
            foreach ($this->extractClassNames() as $className) {
                try {
                    // crucial: Controller-Classes are loaded by zend and must be included before instantiation ...
                    if (! class_exists('\\' . $className)) {
                        include($this->absoluteFilePath);
                    }
                    // evaluate attributes with reflection
                    $reflector = new \ReflectionClass('\\' . $className);
                    // LocalizableString, LocalizableConfigValue, LocalizableTableColumn from class attributes
                    foreach ($reflector->getAttributes() as $attribute) {
                        switch ($attribute->getName()) {
                            case LocalizableString::class:
                            case LocalizableTableColumn::class:
                            case LocalizableConfigValue::class:
                                /** @var ExtractableLocalization $instance */
                                $instance = $attribute->newInstance();
                                foreach ($instance->extract($className) as $string) {
                                    // echo "\nFOUND " . $attribute->getName() . "(" . implode(', ', $attribute->getArguments()) . "): \"" . $string . '"';
                                    $strings[] = $string;
                                }

                                break;
                        }
                    }
                    // we need them to gather values of \ReflectionProperties
                    $defaultProps = $reflector->getDefaultProperties();
                    // LocalizableProp Attributes in constants
                    foreach ($reflector->getReflectionConstants() as $reflectionConstant) {
                        $this->extractFromConstantOrProperty($reflectionConstant, $defaultProps, $strings);
                    }
                    // LocalizableProp Attributes in properties
                    foreach ($reflector->getProperties() as $reflectionProperty) {
                        $this->extractFromConstantOrProperty($reflectionProperty, $defaultProps, $strings);
                    }
                } catch (\Throwable $e) {
                    $this->brokenMatches[] = 'ERROR EXTRACTING ATTRIBUTES:' . $e->getMessage() .
                        ' in file ' . $this->absoluteFilePath;
                }
            }
        }

        return $strings;
    }

    protected function containsExtractableShortname(string $content): bool
    {
        foreach ($this->extractorShortnames as $name) {
            if (str_contains($content, $name)) {
                return true;
            }
        }

        return false;
    }

    protected function extractFromConstantOrProperty(
        \ReflectionClassConstant|\ReflectionProperty $prop,
        array $defaultProps,
        array &$strings,
    ): void {
        $attributes = [];
        $extractor = '';
        $type = ($prop instanceof \ReflectionProperty) ? 'property' : 'constant';
        foreach ($this->propertyConstantExtractors as $className) {
            foreach ($prop->getAttributes($className) as $attribute) {
                $attributes[] = $attribute;
                $extractor = self::getClassShortName($className);
            }
        }
        if (count($attributes) > 1) {
            throw new \Exception(
                'Multiple attributes for property or constant  “' . $prop->getName() . '” in file ' .
                $this->absoluteFilePath
            );
        } elseif (count($attributes) < 1) {
            return;
        }
        $propName = $prop->getName();
        $isArray = str_contains($extractor, 'Array');
        if ($prop instanceof \ReflectionProperty) {
            if (array_key_exists($propName, $defaultProps)) {
                // echo "\n\nFOUND " . $extractor . ' in ' . $type . ' ' . $propName . ': "' . ($isArray ? implode('", "', $defaultProps[$propName]) : $defaultProps[$propName]) . '"';
                $value = $defaultProps[$propName];
            } else {
                throw new \Exception(
                    $extractor . ' attribute for ' . $type . ' without value “' . $propName . '” in file ' .
                    $this->absoluteFilePath
                );
            }
        } else {
            $value = $prop->getValue();
        }
        if ($isArray && ! is_array($value)) {
            throw new \Exception(
                $extractor . ' attribute for ' . $type . ' which is no array “' . $propName . '” in file ' .
                $this->absoluteFilePath
            );
        } elseif (! $isArray && (! is_string($value) || empty($value))) {
            throw new \Exception(
                $extractor . ' attribute for ' . $type . ' which is no string or empty “' . $propName . '” in file ' .
                $this->absoluteFilePath
            );
        }
        if ($isArray) {
            foreach (array_values($value) as $string) {
                $strings[] = (string) $string;
            }
        } else {
            $strings[] = $value;
        }
    }

    /**
     * Retrieves files where attributes seemed present but errors occured extracting them
     */
    public function getBrokenMatches(): array
    {
        return $this->brokenMatches;
    }

    protected function extractClassNames(): array
    {
        $classes = [];
        $namespace = '';
        $matches = [];
        if (preg_match(self::NAMESPACE_PATTERN, $this->content, $matches) === 1) {
            // echo "\nFOUND NAMESPACE: " . print_r($matches, true);
            $namespace = trim($matches[1], '\\') . '\\';
        }
        $result = preg_match_all(self::CLASSNAME_PATTERN, $this->content, $matches, PREG_SET_ORDER);
        if ($result !== false && $result > 0) {
            // echo "\nFOUND CLASSNAMES: " . print_r($matches, true);
            foreach ($matches as $match) {
                $classes[] = $namespace . $match[1];
            }
        }

        return $classes;
    }
}
