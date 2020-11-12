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
 * represents a HTML-Tag as PHP-Object
 * Expects all string values to be UTF-8
 */
class editor_Tag {
   
    /**
     * Escapes an CDATA attribute-value according to the HTML-Spec, replaces all tabs & newlines with blanks
     * NOTE that not all HTML attributes are CDATA and thus the using code is responsible not to produce illegal attributes
     * @param string $text
     * @return string
     */
    public static function escapeAttribute($text) : string {
        if(empty($text)){
            return '';
        }
        $text = str_replace("\r\n", ' ', $text);
        $text = str_replace("\n", ' ', $text);
        $text = str_replace("\r", ' ', $text);
        $text = str_replace("\t", ' ', $text);
        return str_replace(['"','&','<','>'],['&quot;','&amp;','&lt;','&gt;'], $text);
    }
    /**
     * creates the id-attribute for use in html-tags. Leading blank is added
     * @param string $id
     * @return string
     */
    public static function idAttr($id) : string {
        if(empty(trim($id))){
            return '';
        }
        return ' id="'.trim($id).'"';
    }
    /**
     * creates the class-attribute for use in html-tags. Leading blank is added
     * @param string $classnames
     * @return string
     */
    public static function classAttr($classnames) : string {
        if(empty(trim($classnames))){
            return '';
        }
        return ' class="'.trim($classnames).'"';
    }
    /**
     * creates the style-attribute for use in html-tags. Leading blank is added
     * @param string $inlinestyle
     * @return string
     */
    public static function styleAttr($inlinestyle) : string {
        if(empty(trim($inlinestyle))){
            return '';
        }
        return self::createAttribute('style', $inlinestyle);
    }
    /**
     * creates the href-attribute for use in html-tags. Leading blank is added
     * @param string $href
     * @return string
     */
    public static function hrefAttr($href) : string {
        if(empty(trim($href))){
            return '';
        }
        return self::createAttribute('href', $href);
    }
     /**
     * creates an attribute for use in html-tags. Leading blank is added
     * @param string $name
     * @param string $value
     * @return string
     */
    public static function createAttribute($name, $value='') : string {
        // attribs that do not need to be included when empty
        if((empty($value) || trim($value) == '') && ($name == 'style' || $name == 'id' || $name == 'class' || substr($name, 0, 2) == 'on'))
            return '';
        // name-only attribs
        if($name == 'controls' || $name == 'autoplay' || $name == 'allowfullscreen' || $name == 'loop' || $name == 'muted' || $name == 'novalidate' || $name == 'playsinline')
            return ' '.$name;
        return ' '.$name.'="'.self::escapeAttribute($value).'"';
    }
    /**
     * creates a Tag
     * @param string $tagName (a | div | span | p | ...)
     * @return editor_Tag
     */
    public static function create($tagName) : editor_Tag {
        return new editor_Tag($tagName);
    }
    /**
     * Creates a text-node
     * @param string $text
     * @return editor_TextNode
     */
    public static function createText($text) : editor_TextNode {
        return new editor_TextNode($text);
    }
    /**
     * Shortcut to create a Link-Tag
     * @param string $href
     * @param string $target
     * @param string $text
     * @return editor_Tag
     */
    public static function link($href=null, $target=null, $text='') : editor_Tag {
        $tag = editor_Tag::create('a')->addText($text);
        if($href != null){
            $tag->setHref($href);
        }
        if($target != null){
            $tag->setTarget($target);
        }
        return $tag;
    }
    /**
     * Shortcut to create a div-tag
     * @param string $text: text-content of the div
     * @return editor_Tag
     */
    public static function div($text='') : editor_Tag {
        return self::create('div')->addText($text);
    }
    /**
     * Shortcut to create a span-tag
     * @param string $text: text-content of the span
     * @return editor_Tag
     */
    public static function span($text='') : editor_Tag {
        return self::create('span')->addText($text);
    }
    /**
     * Shortcut to create an image-tag
     * @param string $src: the source of the image
     * @return editor_Tag
     */
    public static function img($src=null) : editor_Tag {
        $tag = self::create('img');
        if($src != null)
            $tag->src($src);
        return $tag;
    }
    /**
     * Unparses an HTML-String to an editor_Tag
     * IMPORTANT: This will handle the tags inner content as TEXT, so no nested tags will be parsed !
     * TODO: add support for nested tags
     * @param string $html
     * @return editor_Tag|NULL
     */
    public static function unparse($html){
        $dom = new editor_Utils_Dom();
        $dom->loadHTML(trim($html)); // the @ is to silence errors and misconfigures of HTML
        if($dom->isValid()){
            $node = $dom->firstChild;
            if($node != null && $node->nodeType == XML_ELEMENT_NODE){
                $tag = editor_Tag::create($node->nodeName);
                $tag->addText(editor_Utils_Dom::innerHTML($node));
                if($node->hasAttributes()){
                    foreach($node->setAttributes as $attr) {
                        $tag->addAttribute($attr->nodeName, $attr->nodeValue);
                    }
                }
                return $tag;
            }
        }
        return null;
    }
    /**
     * 
     * @var array
     */
    protected static $singularTypes = array('img','input','br','hr','wbr','area','col','embed','keygen','link','meta','param','source','track','command'); // TODO: not complete !
    /**
     * @var string
     */
    protected $name;
    /**
     * @var array
     */
    protected $attribs = array();
    /**
     * @var array
     */
    protected $classes = array();
    /**
     * 
     * @var editor_Tag[]
     */
    protected $children = [];
    /**
     *
     * @var editor_Tag
     */
    protected $parent = null;
    /**
     * @var boolean
     */
    protected $singular = false;

    /**
     * 
     * @param string $nodeName
     */
    public function __construct(string $nodeName='span'){
        if(empty($nodeName)){
            throw new Exception('A tag must have a node name');
        }
        $this->name = $nodeName;
        $this->singular = in_array($nodeName, self::$singularTypes);
    }
    
    /* child API */
    
    /**
     * 
     * @param editor_Tag $child
     * @throws Exception
     * @return editor_Tag
     */
    public function addChild(editor_Tag $child){
        if($this->isSingular()){
            throw new Exception('Singular Tags can not hold children!');
        }
        $child->setParent($this);
        $this->children[] = $child;
        return $this;
    }
    /**
     * Adds text to the tag, which will be encapsulated into an text-node
     * @param string $text
     * @return editor_Tag
     */
    public function addText(string $text){
        if(!empty($text)){
            $this->addChild(editor_Tag::createText($text));
        }
        return $this;
    }
    /**
     * 
     * @return boolean
     */
    public function hasChildren(){
        return (count($this->children) > 0);
    }
    /**
     * 
     * @param editor_Tag $tag
     * @return editor_Tag
     */
    protected function setParent(editor_Tag $tag){
        $this->parent = $tag;
        return $this;
    }
    /**
     * 
     * @return editor_Tag
     */
    public function getParent(){
        return $this->parent;
    }
    
    /* classname API */
    
    /**
     * Sets the class-attribute. An existing class will be overwritten
     * unfortunately can not be called 'class' :-)
     * @param string $class
     * @return editor_Tag
     */
    public function setClasses($classnames) : editor_Tag {
        $this->classes = [];
        $this->addClasses($classnames);
        return $this;
    }
    /**
     * Ads one ore multiple classes
     * @param string $classname
     * @return editor_Tag
     */
    public function addClasses($classnames) : editor_Tag {
        foreach(explode(' ', trim($classnames)) as $cl){
            $this->addClass($cl);
        }
        return $this;
    }
    /**
     * Adds a class-attribute. Will be added to an existing class
     * @param string $class
     * @return editor_Tag
     */
    public function addClass($classname) : editor_Tag {
        $classname = trim($classname);
        if(!empty($classname) && !$this->hasClass($classname)){
            $this->classes[] = $classname;
        }
        return $this;
    }
    /**
     * Checks if the given classname is present
     * @param string $classname
     * @return bool
     */
    public function hasClass($classname) : bool {
        return in_array(trim($classname), $this->classes);
    }
    /**
     * Retrieves the classnames
     * @return string
     */
    public function getClasses(){
        sort($this->classes);
        return implode(' ', $this->classes);
    }

    /* attribute API */

    /**
     * Sets the id-attribute. An existing id will be overwritten
     * @param string $id
     * @return editor_Tag
     */
    public function setId($id) : editor_Tag {
        if(!empty(trim($id)))
            $this->setAttribute('id', trim($id));
        return $this;
    }
    
    /**
     * Sets the style. An existing style will be overwritten
     * @param string $style
     * @return editor_Tag
     */
    public function setStyle($style) : editor_Tag {
        if(!empty(trim($style)))
            $this->setAttribute('style', trim($style));
        return $this;
    }
    /**
     * Adds a style. Will be added to the existing styles
     * @param string $style
     * @return editor_Tag
     */
    public function addStyle($style) : editor_Tag {
        if(!empty(trim($style)))
            $this->addAttribute('style', trim($style));
        return $this;
    }
    /**
     * Sets the href-attribute of a link-tag
     * @param string $link
     * @return editor_Tag
     */
    public function setHref($link) : editor_Tag {
        $this->setAttribute('href', $link);
        return $this;
    }
    /**
     * Sets the target-attribute of a link-tag
     * @param string $target
     * @return editor_Tag
     */
    public function setTarget($target){
        $this->setAttribute('target', $target);
        return $this;
    }
    /**
     * Sets the source-attribute of e.g an image-tag
     * @param string $source
     * @return editor_Tag
     */
    public function setSource($source) : editor_Tag {
        $this->setAttribute('src', $source);
        return $this;
    }
    /**
     * Sets the title-attribute
     * @param string $title
     * @return editor_Tag
     */
    public function setTitle($title) : editor_Tag {
        $this->setAttribute('title', $title);
        return $this;
    }
    /**
     * Sets the alt-attribute of an image-tag or a video-tag
     * @param string $alt
     * @return editor_Tag
     */
    public function setAlt($alt) : editor_Tag {
        $this->setAttribute('alt', $alt);
        return $this;
    }
    /**
     * Sets an attribute with the given name to the given value. An existing attribute will be overwritten
     * @param string $name
     * @param string $val
     * @return editor_Tag
     */
    public function setAttribute($name, $val) : editor_Tag {
        if(empty($name)){
            return $this;
        }
        if($name == 'class'){
            return $this->setClasses($val);
        }
        $this->attribs[$name] = trim($val);
        return $this;
    }
    /**
     * Sets an data-attribute with the given name to the given value. An existing data-attribute will be overwritten
     * @param string $name
     * @param string $val
     * @return editor_Tag
     */
    public function setData($name, $val) : editor_Tag {
        if($name == '')
            return $this;
        $this->attribs['data-'.$name] = trim($val);
        return $this;
    }
    /**
     * Retrieves the value of an data-attribute
     * @param string $name
     * @return string
     */
    public function getData($name){
        return $this->getAttribute('data-'.$name);
    }
    /**
     * Adds an Event-Handler to the tag. The handler-names are added the jquery-style without the "on", eg "click" or "change"
     * @param string $name
     * @param string $jsCall
     * @return editor_Tag
     */
    public function addOnEvent($name, $jsCall) : editor_Tag {
        if(!empty(trim($jsCall))){
            return $this->addAttribute('on'.$name, $jsCall);
        }
        return $this;
    }
    /**
     * Adds an attribute of the given name
     * if the attribute already exits, the value will be added to the existing value seperated by a blank (" ")
     * @param string $name
     * @param string $val
     * @return editor_Tag
     */
    public function addAttribute($name, $val=null) : editor_Tag {
        if($name == 'class'){
            return $this->addClasses($val);
        }
        if(array_key_exists($name, $this->attribs)){
            if(!empty($val)){
                $this->attribs[$name] .= ' '.trim($val);
            }
        } else {
            $this->attribs[$name] = (empty($val)) ? '' : trim($val);
        }
        return $this;
    }    
    /**
     * removes an attribute of the given name
     * @param string $name
     * @return editor_Tag
     */
    public function unsetAttribute($name) : editor_Tag {
        if(array_key_exists($name, $this->attribs)){
            unset($this->attribs[$name]);
        }
        return $this;
    }
    /**
     * retrieves the given attribute-value. An empty string is returned, if there is no attribute with the given name
     * @param string $name
     * @return string|NULL
     */
    public function getAttribute($name){
        if(array_key_exists($name, $this->attribs)){
            return $this->attribs[$name];
        }
        return null;
    }
    /**
     * checks if the given attribute is present
     * @param string $name
     * @return boolean
     */
    public function hasAttribute($name) : bool {
        return array_key_exists($name, $this->attribs);
    }

    /* content */

    /**
     * Returns the tag-name
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }
    /**
     * checks, if the actual Tag is a link
     * @return boolean
     */
    public function isLink() : bool {
        return ($this->getName() == 'a');
    }
    /**
     * checks, if the actual Tag is a empty/non-rendered tag
     * @return boolean
     */
    public function isBlank() : bool {
        return ($this->getName() == '');
    }
    /**
     * An empty Tag means a Tag, that renders to an empty string ...
     * @return boolean
     */
    public function isEmpty() : bool {
        return ($this->getName() == '' && $this->html == '');
    }
    /**
     * Retrieves, if the tag is a sungular tag like <tag /> or a complete tag with opening and closing part
     * @return bool
     */
    public function isSingular() : bool {
        return $this->singular;
    }
    /**
     * Tags are seen as equal if they have the same node-name, the same classes & the same attributes apart from data-aatributes
     * The data-attributes and the children of the tag will not count for comparision
     * @return boolean
     */
    public function isEqual(editor_Tag $tag){
        if($tag->getName() != $this->getName() || $tag->getClasses() != $this->getClasses()){
            return false;
        }
        foreach($this->attribs as $key => $val){
            if(substr($key, 0, 5) != 'data-' && (!$tag->hasAttribute($key) || $tag->getAttribute($key) != $val)){
                return false;
            }
        }
        return true;
    }
    /**
     * Creates a clone of the tag. Does not copy/clone the children and if not specified otherwise does not copy data-attributes
     * This is no deep-clone!
     * @param boolean $withDataAttribs
     * @return editor_Tag
     */
    public function clone($withDataAttribs=false){
        return $this->cloneProps($this->createBaseClone(), $withDataAttribs);
    }
    /**
     * Helper to create a basic cloned object (with empty props)
     * This can be used in overwriting classes to create the matching class instance with the suitable constructor arguments
     * @return editor_Tag
     */
    protected function createBaseClone(){
        return editor_Tag::create($this->getName());
    }
    /**
     * Helper clone our properties
     * @param editor_Tag $tag
     * @param boolean $withDataAttribs
     * @return editor_Tag
     */
    protected function cloneProps(editor_Tag $tag, $withDataAttribs=false){
        $tag->setClasses($this->getClasses());
        foreach($this->attribs as $name => $val){
            if($withDataAttribs || substr($name, 0, 5) != 'data-'){
                $tag->setAttribute($name, $val);
            }
        }
        return $tag;
    }

    /* render */

    /**
     * renders the complete tag with its contents
     * @return string
     */
    public function render() : string {
        return $this->renderStart().$this->renderChildren().$this->renderEnd();
    }
    /**
     * renders the complete starting tag without inner html
     * @param boolean $withDataAttribs: if set to false, no data-attributes will be rendered
     * @return string
     */
    public function start($withDataAttribs=true) : string {
        return $this->renderStart($withDataAttribs);
    }
    /**
     * renders the complete end-portion of the tag
     * @return string
     */
    public function end() : string {
        return $this->renderEnd();
    }
    /**
     * renders the complete tag with all attributes & content
     * @return string
     */
    public function __toString() : string {
        return $this->render();
    }
    /**
     *
     * @return string
     */
    public function renderChildren() : string {
        $html = '';
        if($this->hasChildren()){
            foreach($this->children as $child){
                $html .= $child->render();
            }
        }
        return $html;
    }
    /**
     *
     * @param boolean $withDataAttribs
     * @return string
     */
    protected function renderStart($withDataAttribs=true) : string {
        if($this->getName() == ''){
            return '';
        }
        $tag = '<'.$this->getName();
        $tag .= self::classAttr($this->getClasses());
        foreach($this->attribs as $name => $val){
            if($withDataAttribs || substr($name, 0, 5) != 'data-'){
                $tag .= self::createAttribute($name, $val);
            }
        }
        if($this->isSingular()){
            return $tag.'/>';
        }
        return $tag.'>';
    }
    /**
     * 
     * @return string
     */
    protected function renderEnd() : string {
        if($this->isSingular()){
            return '';
        }
        return '</'.$this->getName().'>';
    }
}
