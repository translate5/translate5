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
declare(strict_types=1);

/**
 * Abstraction of an Placeholder-tag
 * The main use for this class is for transformations of the segment-markup
 * It creates a SINGULAR tag that renders to the defined placeholder
 * For a paired placeholder, another class should be created ...
 */
final class editor_Segment_PlaceholderTag extends editor_Segment_AnyTag
{
    /**
     * The rendered Markup of a Newline tag.
     * QUIRK: The blank before the space is against the HTML-Spec and superflous BUT termtagger does double img-tags if they do not have a blank before the trailing slash ...
     */
    private string $placeholder;

    public function __construct(int $startIndex, int $endIndex, string $placeholder)
    {
        $this->startIndex = $startIndex;
        $this->endIndex = $endIndex;
        $this->placeholder = $placeholder;
        $this->name = 'placeholder';
        $this->singular = true;
    }

    protected function createBaseClone(): static
    {
        return new static($this->startIndex, $this->endIndex, $this->placeholder);
    }

    /**
     * We want a defined Markup
     */
    public function render(array $skippedTypes = null): string
    {
        return $this->placeholder;
    }
}
