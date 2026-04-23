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
use MittagQI\ZfExtended\Localization\LocalizableConfigValue;
use MittagQI\ZfExtended\Localization\LocalizableString;
use MittagQI\ZfExtended\Localization\LocalizableTableColumn;

/**
 * Extracts Attribute Vlues that cannot be changed in the source-code
 */
class PhpAttributeUnchangableExtractor extends PhpAttributeExtractor
{
    protected array $propertyConstantExtractors = [];

    /**
     * Extracts the unchangable strings (that cannot be adjusted in the source-code)
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
                } catch (\Throwable $e) {
                    $this->brokenMatches[] = 'ERROR EXTRACTING ATTRIBUTES:' . $e->getMessage() .
                        ' in file ' . $this->absoluteFilePath;
                }
            }
        }

        return $strings;
    }
}
