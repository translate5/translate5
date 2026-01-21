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

    public function __construct(
        private readonly string $content,
        private readonly string $absoluteFilePath,
    ) {
    }

    private array $brokenMatches = [];

    /**
     * Extracts the localized source-strings from a PHP file by PHP attributes
     */
    public function extract(): array
    {
        $strings = [];
        // reflection is expensive, so only do it when there are possible matches ...
        // and exclude this class and the Attribute-classes
        if (! str_contains($this->absoluteFilePath, 'MaintenanceCli/L10n/PhpAttributeExtractor') &&
            ! str_contains($this->absoluteFilePath, 'ZfExtended/Localization/Localizable') &&
            // TODO FIXME: checking classnames manuually is bad when adding new attributes, better ideas ?
            (str_contains($this->content, 'LocalizableArrayProp') ||
                str_contains($this->content, 'LocalizableProp') ||
                str_contains($this->content, 'LocalizableString') ||
                str_contains($this->content, 'LocalizableTableColumn') ||
                str_contains($this->content, 'LocalizableConfigValue'))
        ) {
            foreach ($this->extractClassNames() as $className) {
                try {
                    // crucial: Controller-Classes are loaded by zend and must be included first ...
                    if (! class_exists('\\' . $className)) {
                        include($this->absoluteFilePath);
                    }
                    // evaluate attributes with reflection
                    $reflector = new \ReflectionClass('\\' . $className);
                    // LocalizableString, LocalizableConfigValue, LocalizableTableColumn from class attributes
                    foreach ($reflector->getAttributes() as $attribute) {
                        // echo "\n\nCHECK ATTRIBUTE: " . $attribute->getName();
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
                    // LocalizableProp Attributes in constants
                    foreach ($reflector->getReflectionConstants() as $reflectionConstant) {
                        foreach ($reflectionConstant->getAttributes(LocalizableProp::class) as $attribute) {
                            // echo "\n\nFOUND LocalizableProp in constant " . $reflectionConstant->getName() . ': "' . $reflectionConstant->getValue() . '"';
                            $strings[] = (string) $reflectionConstant->getValue();
                        }
                        foreach ($reflectionConstant->getAttributes(LocalizableArrayProp::class) as $attribute) {
                            if (! is_array($reflectionConstant->getValue())) {
                                throw new \Exception(
                                    'LocalizableArrayProp attribute for constant which is no array “' .
                                    $reflectionConstant->getName() . '”'
                                );
                            }
                            // echo "\n\nFOUND LocalizableArrayProp in constant " . $reflectionConstant->getName() . ': "' . print_r($reflectionConstant->getValue(), true) . '"';
                            foreach (array_values($reflectionConstant->getValue()) as $value) {
                                $strings[] = $value;
                            }
                        }
                    }
                    // LocalizableProp Attributes in properties
                    $defaultProps = $reflector->getDefaultProperties();
                    foreach ($reflector->getProperties() as $reflectionProperty) {
                        foreach ($reflectionProperty->getAttributes(LocalizableProp::class) as $attribute) {
                            $propName = $reflectionProperty->getName();
                            if (array_key_exists($propName, $defaultProps)) {
                                // echo "\n\nFOUND LocalizableProp in property " . $propName . ': "' . $defaultProps[$propName] . '"';
                                $strings[] = (string) $defaultProps[$propName];
                            } else {
                                throw new \Exception(
                                    'LocalizableProp attribute for property without value “' . $propName . '”'
                                );
                            }
                        }
                        foreach ($reflectionProperty->getAttributes(LocalizableArrayProp::class) as $attribute) {
                            $propName = $reflectionProperty->getName();
                            if (array_key_exists($propName, $defaultProps)) {
                                if (! is_array($defaultProps[$propName])) {
                                    throw new \Exception(
                                        'LocalizableArrayProp attribute for property which is no array “' .
                                        $propName . '”'
                                    );
                                }
                                // echo "\n\nFOUND LocalizableArrayProp in property " . $propName . ': "' . implode(', ', $defaultProps[$propName]) . '"';
                                foreach (array_values($defaultProps[$propName]) as $value) {
                                    $strings[] = $value;
                                }
                            } else {
                                throw new \Exception(
                                    'LocalizableArrayProp attribute for property without value “' . $propName . '”'
                                );
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $this->brokenMatches[] = 'ERROR EXTRACTING ATTRIBUTES:' . $e->getMessage() .
                        ' in file ' . $this->absoluteFilePath;
                }
            }
        }

        return $strings;
    }

    /**
     * Retrieves files where attributes seemed present but errors occured extracting them
     */
    public function getBrokenMatches(): array
    {
        return $this->brokenMatches;
    }

    private function extractClassNames(): array
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
