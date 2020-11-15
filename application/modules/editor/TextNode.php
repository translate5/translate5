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
 * represents a HTML TextNode as PHP-Object
 * A text node has an empty Node-name
 * 
 * @method editor_TextNode clone(boolean $withDataAttribs)
 * @method editor_TextNode createBaseClone()
 * @method editor_TextNode cloneProps(editor_Tag $tag, boolean $withDataAttribs)
 */
final class editor_TextNode extends editor_Tag {
    
    /**
     * 
     * @var string
     */
    private $text = '';
    /**
     *
     * @param string $text
     */
    public function __construct($text){
        if(empty($text)){
            throw new Exception('A text-node must have a non-empty text');
        }
        parent::__construct('TEXTNODE');
        $this->text = $text;
        $this->name = '';
        $this->singular = true;
    }

    public function getText(){
        return $this->text;
    }

    public function getTextLength(){
        return mb_strlen($this->text);
    }
    
    public function addChild(editor_Tag $child){
        throw new Exception('Text nodes can not hold children!');
    }

    public function addClass($classname) : editor_Tag {
        throw new Exception('Text nodes can not have classes');
    }
    
    public function setAttribute($name, $val) : editor_Tag {
        throw new Exception('Text nodes can not have attributes');
    }

    public function setData($name, $val) : editor_Tag {
        throw new Exception('Text nodes can not have data attributes');
    }

    public function addAttribute($name, $val=null) : editor_Tag {
        throw new Exception('Text nodes can not have attributes');
    }

    public function isEqual(editor_Tag $tag){
        if(get_class($tag) == 'editor_TextNode' && $tag->getText() == $this->text){
            return true;
        }
        return false;
    }
    /**
     * {@inheritDoc}
     * @see editor_Tag::createBaseClone()
     * @return editor_TextNode
     */
    protected function createBaseClone(){
        return editor_Tag::createText($this->text);
    }

    public function render() : string {
        return $this->text;
    }

    public function renderChildren() : string {
        return $this->text;
    }

    protected function renderStart($withDataAttribs=true) : string {
        return '';
    }

    protected function renderEnd() : string {
        return '';
    }
}
