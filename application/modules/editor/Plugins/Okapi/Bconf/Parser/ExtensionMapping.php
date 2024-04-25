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

/**
 * Class providing base functionality to parse an Extension-Mapping extracted from a bconf
 */
class editor_Plugins_Okapi_Bconf_Parser_ExtensionMapping
{
    /**
     * @var string
     */
    public const INVALID_IDENTIFIER = 'INVALID';

    protected array $map = [];

    public function __construct(array $unpackedLines)
    {
        $this->unpackContent($unpackedLines, []);
    }

    public function getMap(): array
    {
        return $this->map;
    }

    /**
     * @return string[]
     */
    public function getAllFilters(): array
    {
        $filters = [];
        foreach ($this->map as $extension => $identifier) {
            $filters[$identifier] = true;
        }

        return array_keys($filters);
    }

    public function getAllExtensions(): array
    {
        return array_keys($this->map);
    }

    public function hasExtension(string $extension): bool
    {
        return array_key_exists(ltrim($extension, '.'), $this->map);
    }

    public function hasEntries(): bool
    {
        return (count($this->map) > 0);
    }

    /**
     * Internal API to parse our contents from unpacked bconf data (on import)
     */
    protected function unpackContent(array $unpackedLines, array $replacementMap): void
    {
        foreach ($unpackedLines as $line) {
            $identifier = array_key_exists($line[1], $replacementMap) ? $replacementMap[$line[1]] : $line[1];
            if ($identifier != self::INVALID_IDENTIFIER) {
                $this->map[ltrim($line[0], '.')] = $identifier;
            }
        }
    }
}
