<?php
/*
 START LICENSE AND COPYRIGHT
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
 
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/gpl.html
 http://www.translate5.net/plugin-exception.txt
 
 END LICENSE AND COPYRIGHT
 */

/**
 * Special tag used only in the Length-Check Code. Do not use anywhere else
 * Represents a <br/>-tag
 */
class editor_Segment_NewlineTag extends editor_Segment_AnyTag {
    
    /**
     * The rendered Markup of a Newline tag.
     * QUIRK: The blank before the space is against the HTML-Spec and superflous BUT termtagger does double img-tags if they do not have a blank before the trailing slash ...
     * @var string
     */
    const RENDERED = '<br />';
    /**
     * @param int $startIndex
     * @param int $endIndex
     * @param string $category
     * @param string $nodeName
     */
    public function __construct(int $startIndex, int $endIndex, string $category='', string $nodeName='br') {
        $this->startIndex = $startIndex;
        $this->endIndex = $endIndex;
        $this->category = $category;
        $this->name = 'br';
        $this->singular = true;
    }
    /**
     * {@inheritDoc}
     * @see editor_Tag::createBaseClone()
     * @return editor_Segment_AnyTag
     */
    protected function createBaseClone(){
        return new editor_Segment_NewlineTag($this->startIndex, $this->endIndex, $this->category, $this->name);
    }
    /**
     * We want a defined Markup
     * {@inheritDoc}
     * @see editor_Segment_Tag::render()
     */
    public function render(array $skippedTypes=NULL) : string {
        return self::RENDERED;
    }
}
