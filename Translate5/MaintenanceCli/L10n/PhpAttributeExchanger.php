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

use MittagQI\ZfExtended\Localization\LocalizableMsg;
use MittagQI\ZfExtended\Localization\LocalizableMsgArray;

/**
 * Exchanges PHP attribute-definitions for localization in PHP classes
 */
class PhpAttributeExchanger extends PhpAttributeExtractor
{
    /**
     * @var string[]
     */
    private array $exchangedStrings = [];

    /**
     * Any class-extractor will be exchanged: far too dangerous
     */
    protected array $classExtractors = [
    ];

    /**
     * Only property/constant extractors will be exchanged, that are explicitly flagged as messages,
     * everything else is too dangerous
     */
    protected array $propertyConstantExtractors = [
        LocalizableMsg::class,
        LocalizableMsgArray::class,
    ];

    /**
     * Exchanges the extracted string according to the given source-map
     * @param array<string, string> $sourceMap
     */
    public function exchange(array $sourceMap): bool
    {
        $content = $this->content;
        $replaced = 0;
        $strings = [];
        // reflection is expensive, so only do it when there are possible matches ...
        // and exclude this class and the Attribute-classes
        if ($this->containsExtractableShortname($content)) {
            foreach ($this->extractClassNames() as $className) {
                try {
                    // crucial: Controller-Classes are loaded by zend and must be included first ...
                    if (! class_exists('\\' . $className)) {
                        include($this->absoluteFilePath);
                    }
                    // evaluate attributes with reflection
                    $reflector = new \ReflectionClass('\\' . $className);
                    // LocalizableMsg Attributes in constants for real messages
                    foreach ($reflector->getReflectionConstants() as $reflectionConstant) {
                        foreach ($reflectionConstant->getAttributes(LocalizableMsg::class) as $attribute) {
                            // echo "\n\nFOUND LocalizableMsg in constant " . $reflectionConstant->getName() . ': "' . $reflectionConstant->getValue() . '"';
                            $strings[] = (string) $reflectionConstant->getValue();
                        }
                        foreach ($reflectionConstant->getAttributes(LocalizableMsgArray::class) as $attribute) {
                            if (is_array($reflectionConstant->getValue())) {
                                // echo "\n\nFOUND LocalizableMsgArray in constant " . $reflectionConstant->getName() . ': "' . print_r($reflectionConstant->getValue(), true) . '"';
                                foreach (array_values($reflectionConstant->getValue()) as $value) {
                                    $strings[] = $value;
                                }
                            }
                        }
                    }
                    // LocalizableMsg Attributes in properties for real messages
                    $defaultProps = $reflector->getDefaultProperties();
                    foreach ($reflector->getProperties() as $reflectionProperty) {
                        foreach ($reflectionProperty->getAttributes(LocalizableMsg::class) as $attribute) {
                            $propName = $reflectionProperty->getName();
                            if (array_key_exists($propName, $defaultProps)) {
                                // echo "\n\nFOUND LocalizableMsg in property " . $propName . ': "' . $defaultProps[$propName] . '"';
                                $strings[] = (string) $defaultProps[$propName];
                            }
                        }
                        foreach ($reflectionProperty->getAttributes(LocalizableMsgArray::class) as $attribute) {
                            $propName = $reflectionProperty->getName();
                            if (array_key_exists($propName, $defaultProps)) {
                                if (is_array($defaultProps[$propName])) {
                                    // echo "\n\nFOUND LocalizableMsgArray in property " . $propName . ': "' . implode(', ', $defaultProps[$propName]) . '"';
                                    foreach (array_values($defaultProps[$propName]) as $value) {
                                        $strings[] = $value;
                                    }
                                }
                            } else {
                                $this->brokenMatches[] = 'LocalizableMsgArray attribute for property without value “' .
                                    $propName . '” in file ' . $this->absoluteFilePath;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    throw new \Exception(
                        'ERROR EXTRACTING ATTRIBUTES:' . $e->getMessage() . ' in file ' . $this->absoluteFilePath
                    );
                }
            }
            $this->exchangedStrings = [];
            foreach ($strings as $string) {
                if (! empty($string) && ! array_key_exists($string, $this->exchangedStrings)) {
                    $exists = array_key_exists($string, $sourceMap);
                    $valid = PhpExchanger::seemsValidTranslation($string);
                    if ($valid && $exists && $string !== $sourceMap[$string]) {
                        // first, evaluate what quote/delimiter we have. This cannot be gathered by reflection ...
                        if (substr_count($content, PhpExchanger::toSingleQuotedString($string)) > 0) {
                            $quote = '\'';
                        } elseif (substr_count($content, PhpExchanger::toDoubleQuotedString($string)) > 0) {
                            $quote = '"';
                        } else {
                            throw new \Exception(
                                'DELIMITER/QUOTE for string "' . $string . '" could not be evaluated in file' .
                                $this->absoluteFilePath
                            );
                        }
                        $replacements = PhpExchanger::searchReplaceInCode(
                            $content,
                            [$string],
                            [$sourceMap[$string]],
                            [],
                            $quote
                        );
                        if ($replacements === 0) {
                            throw new \Exception(
                                'ATTRIBUTE STRING "' . $string . '" extracted but not found in file' . $this->absoluteFilePath
                            );
                        } elseif ($replacements > 1) {
                            // happens frequently ...
                            if (PhpExchanger::REPORT_MULTI_RPLACEMENTS) { // @phpstan-ignore-line
                                $this->brokenMatches[] =
                                    'Attribute string "' . PhpExchanger::prepareForDebug([$string]) .
                                    '" replaced more than once in file' . $this->absoluteFilePath;
                            }
                            $replaced++;
                        } else {
                            $replaced++;
                        }
                        $this->exchangedStrings[$string] = 1;
                    } elseif ($valid && ! $exists) {
                        throw new \Exception(
                            'ATTRIBUTE STRING "' . $string . '" not found in source-map, file' . $this->absoluteFilePath
                        );
                    }
                }
            }
        }
        if ($replaced > 0) {
            $this->content = $content;

            return true;
        }

        return false;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string[]
     */
    public function getExchangedStrings(): array
    {
        return $this->exchangedStrings;
    }
}
