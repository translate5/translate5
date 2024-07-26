<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Abstraction for an Internal tag of variable type. This usually covers Tags, that are no real internal tags or even markup of other source
 * The main use for this class is for testing purposes
 * In "real life" there should be no unknown segment tags and we could add error logging here to detect such mishaps
 * @phpstan-consistent-constructor
 */
class editor_Segment_AnyTag extends editor_Segment_Tag
{
    protected static ?string $type = editor_Segment_Tag::TYPE_ANY;

    protected static ?string $identificationClass = editor_Segment_Tag::TYPE_ANY;

    public function __construct(int $startIndex, int $endIndex, string $category = '', string $nodeName = 'span')
    {
        $this->startIndex = $startIndex;
        $this->endIndex = $endIndex;
        $this->category = $category;
        $this->name = strtolower($nodeName);
        $this->singular = in_array($nodeName, static::$singularTypes);
    }

    protected function createBaseClone(): static
    {
        return new static($this->startIndex, $this->endIndex, $this->category, $this->name);
    }

    /**
     * ANY Internal tags shall not be be consolidated
     */
    public function isEqualType(editor_Tag $tag): bool
    {
        return false;
    }

    /**
     * We do not want "ANY" tags to be skipped
     */
    public function render(array $skippedTypes = null): string
    {
        return $this->renderStart() . $this->renderChildren($skippedTypes) . $this->renderEnd();
    }
}
