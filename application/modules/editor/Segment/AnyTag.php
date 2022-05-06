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
 * 
 * @method editor_Segment_AnyTag clone(bool $withDataAttribs=false, bool $withId=false)
 * @method editor_Segment_AnyTag cloneProps(editor_Tag $tag, bool $withDataAttribs=false, bool $withId=false)
 */
class editor_Segment_AnyTag extends editor_Segment_Tag {
    
    protected static $type = editor_Segment_Tag::TYPE_ANY;
    
    protected static $identificationClass = editor_Segment_Tag::TYPE_ANY;
    /**
     * @param int $startIndex
     * @param int $endIndex
     * @param string $category
     * @param string $nodeName
     */
    public function __construct(int $startIndex, int $endIndex, string $category='', string $nodeName='span') {
        $this->startIndex = $startIndex;
        $this->endIndex = $endIndex;
        $this->category = $category;
        $this->name = strtolower($nodeName);
        $this->singular = in_array($nodeName, static::$singularTypes);
    }
    /**
     * {@inheritDoc}
     * @see editor_Tag::createBaseClone()
     * @return editor_Segment_AnyTag
     */
    protected function createBaseClone(){
        return new editor_Segment_AnyTag($this->startIndex, $this->endIndex, $this->category, $this->name);
    }
    /**
     * ANY Internal tags shall not be be consolidated
     * {@inheritDoc}
     * @see editor_Segment_Tag::isEqualType()
     */
    public function isEqualType(editor_Tag $tag) : bool {
        return false;
    }
    /**
     * We do not want "ANY" tags to be skipped
     * {@inheritDoc}
     * @see editor_Segment_Tag::render()
     */
    public function render(array $skippedTypes=NULL) : string {
        return $this->renderStart().$this->renderChildren($skippedTypes).$this->renderEnd();
    }
}
