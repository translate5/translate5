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

use MittagQI\ZfExtended\Tools\Markup;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Dom\Node\AbstractNode;
use PHPHtmlParser\Dom\Node\HtmlNode;
use PHPHtmlParser\DTO\Tag\AttributeDTO;
use PHPHtmlParser\Options;

/**
 * represents a HTML-Tag as PHP-Object
 * It in some kinds is a simpler & easier seriazable version of DOMElement or PHPHtmlParser\Dom
 * The classname-attribute has an own datamodel that resembles it's array nature
 * Expects all string values to be UTF-8 encoded
 * All Attribute-values will be unescaped when setting and rendered escaped that means the internal data is always
 * unescaped
 */
class editor_Tag
{
    /**
     * If set to true, PHP DOM is used to parse Markup, otherwise PHPHtmlParser
     * This affects the handling of escaping, since PHPHtmlParser leaves the escaping untouched
     * but DOMDocument strictly escapes all attributes and contents
     * Also, DOMDocument turns all double-escaped entities in attributes to single-escaped (why is that?)
     * @var boolean
     */
    public const USE_DOM_DOCUMENT = false;
    
    /**
     * Enodes our attributes (hashtable) to a json-capable structure
     * @param string[] $attribs
     * @return array[][]
     */
    public static function encodeAttributes($attribs)
    {
        $data = [];
        foreach ($attribs as $key => $val) {
            $data[] = [
                'name' => $key,
                'value' => $val,
            ];
        }

        return $data;
    }

    /**
     * Decodes encoded attributes back to a hashtable
     * @param stdClass[] $data
     * return string[]
     */
    public static function decodeAttributes(array $data)
    {
        $attribs = [];
        foreach ($data as $ele) {
            $attribs[$ele->name] = $ele->value;
        }

        return $attribs;
    }

    /**
     * Checks if a given text is a text worthy to create a node of
     * @return boolean
     */
    public static function isNodeText(string $text): bool
    {
        return ($text !== null && mb_strlen(strval($text)) > 0);
    }

    /**
     * creates the id-attribute for use in html-tags. Leading blank is added
     * @param string $id
     */
    public static function idAttr($id): string
    {
        if (\ZfExtended_Utils::emptyString(trim($id))) {
            return '';
        }

        return static::createAttribute('id', trim($id));
    }

    /**
     * creates the class-attribute for use in html-tags. Leading blank is added
     * @param string $classnames
     */
    public static function classAttr($classnames): string
    {
        if (empty(trim($classnames))) {
            return '';
        }

        return ' class="' . trim($classnames) . '"';
    }

    /**
     * creates the style-attribute for use in html-tags. Leading blank is added
     * @param string $inlinestyle
     */
    public static function styleAttr($inlinestyle): string
    {
        if (empty(trim($inlinestyle))) {
            return '';
        }

        return static::createAttribute('style', $inlinestyle);
    }

    /**
     * creates the href-attribute for use in html-tags. Leading blank is added
     * @param string $href
     */
    public static function hrefAttr($href): string
    {
        if (empty(trim($href))) {
            return '';
        }

        return static::createAttribute('href', $href);
    }

    /**
     * creates an attribute for use in html-tags. Leading blank is added
     * @param string $name
     * @param string $value
     */
    public static function createAttribute($name, $value = ''): string
    {
        // attribs that do not need to be included when empty
        if ((Markup::isEmpty($value) || trim($value) === '') &&
            ($name == 'style' || $name == 'id' || $name == 'class' || substr($name, 0, 2) == 'on')) {
            return '';
        }
        // name-only attribs
        if ($name == 'controls' || $name == 'autoplay' || $name == 'allowfullscreen' || $name == 'loop' ||
            $name == 'muted' || $name == 'novalidate' || $name == 'playsinline') {
            return ' ' . $name;
        }

        return ' ' . $name . '="' . Markup::escapeForAttribute($value) . '"';
    }

    /**
     * creates a Tag
     * @param string $tagName (a | div | span | p | ...)
     */
    public static function create($tagName): editor_Tag
    {
        return new editor_Tag($tagName);
    }

    /**
     * Creates a text-node
     * @param string $text
     */
    public static function createText($text): editor_TextNode
    {
        return new editor_TextNode($text);
    }

    /**
     * Shortcut to create a Link-Tag
     * @param string $href
     * @param string $target
     * @param string $text
     */
    public static function link($href = null, $target = null, $text = ''): editor_Tag
    {
        $tag = editor_Tag::create('a');
        $tag->addText($text);
        if ($href != null) {
            $tag->setHref($href);
        }
        if ($target != null) {
            $tag->setTarget($target);
        }

        return $tag;
    }

    /**
     * Shortcut to create a div-tag
     */
    public static function div($text = ''): editor_Tag
    {
        $tag = static::create('div');
        $tag->addText($text);

        return $tag;
    }

    /**
     * Shortcut to create a span-tag
     */
    public static function span($text = ''): editor_Tag
    {
        $tag = static::create('span');
        $tag->addText($text);

        return $tag;
    }

    /**
     * Shortcut to create an image-tag
     */
    public static function img($source = null): editor_Tag
    {
        $tag = static::create('img');
        if ($source != null) {
            $tag->setSource($source);
        }

        return $tag;
    }

    /**
     * Creates a Dom Object and sets some crucial options. This API should always be used to
     * @return \PHPHtmlParser\Dom
     */
    public static function createDomParser()
    {
        $dom = new Dom();
        $options = new Options();
        $options->setCleanupInput(false);
        $options->setRemoveDoubleSpace(false);
        $options->setPreserveLineBreaks(true);
        $dom->setOptions($options);

        return $dom;
    }

    /**
     * Unparses an HTML-String to an editor_Tag. If pure text is passed, a text-node will be returned. If markup with
     * multiple tags is returned, only the first tag is returned
     * @param string $html
     * @return editor_Tag|null
     */
    public static function unparse($html)
    {
        /** @phpstan-ignore-next-line */
        if (self::USE_DOM_DOCUMENT) {
            // implementation using PHP DOM
            $dom = new ZfExtended_Dom();
            $node = $dom->loadUnicodeElement($html);
            if ($node != null) {
                return static::fromDomElement($node);
            }

            return null;
        }
        // implementation using PHPHtmlParser
        $dom = static::createDomParser();
        $dom->loadStr($html);
        if ($dom->countChildren() != 1) {
            return null;
        }
        $node = $dom->firstChild();
        if (is_a($node, HtmlNode::class)) {
            return static::fromHtmlNode($node);
        }
        if ($node->isTextNode() && ! \ZfExtended_Utils::emptyString($node->text())) {
            return new editor_TextNode($node->text());
        }

        return null;
    }

    /**
     * Creates a editor_Tag out of a AbstractNode
     * @param HtmlNode $node
     * @return editor_Tag|null
     */
    protected static function fromNode(AbstractNode $node)
    {
        if (is_a($node, HtmlNode::class)) {
            return static::fromHtmlNode($node);
        }
        if ($node->isTextNode() && ! \ZfExtended_Utils::emptyString($node->text())) {
            return new editor_TextNode($node->text());
        }

        return null;
    }

    /**
     * Creates a editor_Tag out of a HtmlNode
     * @return editor_Tag
     */
    protected static function fromHtmlNode(HtmlNode $node)
    {
        $domTag = $node->getTag();
        $tag = editor_Tag::create($domTag->name());
        foreach ($domTag->getAttributes() as $name => $attrib) {
            /* @var $attrib AttributeDTO */
            $tag->addAttribute($name, $attrib->getValue());
        }
        if ($node->hasChildren()) {
            foreach ($node->getChildren() as $childNode) {
                /* @var $childNode AbstractNode */
                $child = static::fromNode($childNode);
                if ($child != null) {
                    $tag->addChild($child);
                }
            }
        }

        return $tag;
    }

    /**
     * Creates a editor_Tag out of a HtmlNode
     * @return editor_Tag
     */
    protected static function fromDomElement(DOMElement $node)
    {
        $tag = editor_Tag::create($node->nodeName);
        if ($node->hasAttributes()) {
            foreach ($node->attributes as $attr) {
                $tag->addAttribute($attr->nodeName, $attr->nodeValue);
            }
        }
        if ($node->hasChildNodes()) {
            for ($i = 0; $i < $node->childNodes->length; $i++) {
                $child = $node->childNodes->item($i);
                if ($child->nodeType == XML_TEXT_NODE) {
                    // CRUCIAL: the nodeValue always is escaped Markup!
                    $tag->addText(Markup::escapeText($child->nodeValue));
                } elseif ($child->nodeType == XML_ELEMENT_NODE) {
                    $tag->addChild(static::fromDomElement($child));
                }
            }
        }

        return $tag;
    }

    /**
     * @var array
     */
    protected static $singularTypes = [
        'img',
        'input',
        'br',
        'hr',
        'wbr',
        'area',
        'col',
        'embed',
        'keygen',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'command',
    ]; // TODO: not complete !

    /**
     * QUIRK: The blank before the space is against the HTML-Spec and superflous BUT termtagger does double img-tags if
     * they do not have a blank before the trailing slash ...
     * @var string
     */
    protected static $selfClosingMarker = ' /';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $attribs = [];

    /**
     * @var array
     */
    protected $classes = [];

    /**
     * @var editor_Tag[]
     */
    protected $children = [];

    /**
     * @var editor_Tag
     */
    protected $parent = null;

    /**
     * @var boolean
     */
    protected $singular = false;

    public function __construct(string $nodeName = 'span')
    {
        if (empty($nodeName)) {
            throw new Exception('A tag must have a node name');
        }
        $this->name = strtolower($nodeName);
        $this->singular = in_array($nodeName, static::$singularTypes);
    }

    /* child API */

    /**
     * Adds a child-node. Returns the success of the action
     * @return boolean
     * @throws Exception
     */
    public function addChild(editor_Tag $child): bool
    {
        if ($this->isSingular()) {
            throw new Exception('Singular Tags can not hold children (' . get_class($this) . ') !');
        }
        $child->setParent($this);
        $this->children[] = $child;

        return true;
    }

    /**
     * Adds text to the tag, which will be encapsulated into an text-node.  Returns the success of the action
     * @return boolean
     */
    public function addText(string $text): bool
    {
        if (static::isNodeText($text)) {
            $this->addChild(editor_Tag::createText($text));

            return true;
        }

        return false;
    }

    /**
     * Retrieves the first child if there are any
     * @return boolean
     */
    public function hasChildren()
    {
        return (count($this->children) > 0);
    }

    /**
     * Retrieves the last child if there are any
     * @return editor_Tag|null
     */
    public function getLastChildsTextLength(): int
    {
        if ($this->hasChildren()) {
            return $this->children[count($this->children) - 1]->getTextLength();
        }

        return 0;
    }

    /**
     * @return editor_Tag
     */
    protected function setParent(editor_Tag $tag)
    {
        $this->parent = $tag;

        return $this;
    }

    /**
     * @return editor_Tag
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Retrieves our children
     * @return editor_Tag[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Resets our children
     */
    public function resetChildren()
    {
        $this->children = [];
    }

    /* classname API */

    /**
     * Sets the class-attribute. An existing class will be overwritten
     * unfortunately can not be called 'class' :-)
     */
    public function setClasses($classnames): editor_Tag
    {
        $this->classes = [];
        $this->addClasses($classnames);

        return $this;
    }

    /**
     * Ads one ore multiple classes
     */
    public function addClasses($classnames): editor_Tag
    {
        foreach (explode(' ', trim($classnames)) as $cl) {
            $this->addClass($cl);
        }

        return $this;
    }

    /**
     * Adds a class-attribute. Will be added to an existing class
     */
    public function addClass($classname): editor_Tag
    {
        $classname = trim($classname);
        if ($classname != '' && ! $this->hasClass($classname)) {
            $this->classes[] = $classname;
        }

        return $this;
    }

    /**
     * Adds a class-attribute. Will be added to an existing class
     * IF POSSIBLE, DO NOT USE THIS TO KEEP CODE INDEPENDENT OF CLASSNAME-ORDER
     */
    public function prependClass($classname): editor_Tag
    {
        $classname = trim($classname);
        if ($classname != '' && ! $this->hasClass($classname)) {
            array_unshift($this->classes, $classname);
        }

        return $this;
    }

    /**
     * Removes a class
     * @param string $classname
     */
    public function removeClass($classname): editor_Tag
    {
        if ($this->hasClass($classname)) {
            $clss = [];
            foreach ($this->classes as $cname) {
                if ($cname != $classname) {
                    $clss[] = $cname;
                }
            }
            $this->classes = $clss;
        }

        return $this;
    }

    /**
     * Checks if the given classname is present
     * @param string $classname
     */
    public function hasClass($classname): bool
    {
        return in_array(trim($classname), $this->classes);
    }

    /**
     * Checks if at least one of the given classnames is present
     * If no classnames are passed the evaluation is regarded as positive/match
     * @param string[] $classNames
     */
    public function hasClasses(array $classNames): bool
    {
        if (count($classNames) == 0) {
            return true;
        }
        foreach ($classNames as $classname) {
            if ($this->hasClass($classname)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves the classnames
     */
    public function getClasses(): string
    {
        return implode(' ', $this->classes);
    }

    /**
     * Retrieves the sorted classnames, can be used to compare tags by classnames
     */
    public function getSortedClasses(): string
    {
        $classes = $this->classes;
        sort($classes);

        return implode(' ', $classes);
    }

    /* attribute API */

    /**
     * Sets the id-attribute. An existing id will be overwritten
     * @param string $id
     */
    public function setId($id): editor_Tag
    {
        if (! \ZfExtended_Utils::emptyString(trim($id))) {
            $this->setAttribute('id', trim($id));
        }

        return $this;
    }

    /**
     * Sets the style. An existing style will be overwritten
     * @param string $style
     */
    public function setStyle($style): editor_Tag
    {
        if (! empty(trim($style))) {
            $this->setAttribute('style', trim($style));
        }

        return $this;
    }

    /**
     * Adds a style. Will be added to the existing styles
     * @param string $style
     */
    public function addStyle($style): editor_Tag
    {
        if (! empty(trim($style))) {
            $this->addAttribute('style', trim($style));
        }

        return $this;
    }

    /**
     * Sets the href-attribute of a link-tag
     * @param string $link
     */
    public function setHref($link): editor_Tag
    {
        $this->setAttribute('href', $link);

        return $this;
    }

    /**
     * Sets the target-attribute of a link-tag
     * @param string $target
     * @return editor_Tag
     */
    public function setTarget($target)
    {
        $this->setAttribute('target', $target);

        return $this;
    }

    /**
     * Sets the source-attribute of e.g an image-tag
     * @param string $source
     */
    public function setSource($source): editor_Tag
    {
        $this->setAttribute('src', $source);

        return $this;
    }

    /**
     * Sets the title-attribute
     * @param string $title
     */
    public function setTitle($title): editor_Tag
    {
        $this->setAttribute('title', $title);

        return $this;
    }

    /**
     * Sets the alt-attribute of an image-tag or a video-tag
     * @param string $alt
     */
    public function setAlt($alt): editor_Tag
    {
        $this->setAttribute('alt', $alt);

        return $this;
    }

    /**
     * Sets an attribute with the given name to the given value. An existing attribute will be overwritten
     * @param string $name
     * @param string $val
     */
    public function setAttribute($name, $val): editor_Tag
    {
        if (empty($name)) {
            return $this;
        }
        if ($name == 'class') {
            return $this->setClasses($val);
        }
        $this->attribs[$name] = Markup::unescapeFromAttribute(trim($val));

        return $this;
    }

    /**
     * Sets an attribute with the given name to the RAW given value without escaping. An existing attribute will be
     * overwritten
     * @param string $name
     * @param string $val
     */
    public function setUnescapedAttribute($name, $val): editor_Tag
    {
        if (empty($name)) {
            return $this;
        }
        if ($name == 'class') {
            return $this->setClasses($val);
        }
        $this->attribs[$name] = trim($val);

        return $this;
    }

    /**
     * Retrieves the value of an data-attribute
     * @param string $name
     * @return string
     */
    public function getData($name)
    {
        return $this->getAttribute('data-' . $name);
    }

    /**
     * Sets an data-attribute with the given name to the given value. An existing data-attribute will be overwritten
     * @param string $name
     * @param string $val
     */
    public function setData($name, $val): editor_Tag
    {
        if ($name == '') {
            return $this;
        }
        $this->attribs['data-' . $name] = Markup::unescapeFromAttribute(trim($val));

        return $this;
    }

    /**
     * Checks if an data-attribute is set
     * @param string $name
     * @return boolean
     */
    public function hasData($name)
    {
        return $this->hasAttribute('data-' . $name);
    }

    /**
     * Adds an Event-Handler to the tag. The handler-names are added the jquery-style without the "on", eg "click" or
     * "change"
     * @param string $name
     * @param string $jsCall
     */
    public function addOnEvent($name, $jsCall): editor_Tag
    {
        if (! empty(trim($jsCall))) {
            return $this->addAttribute('on' . $name, $jsCall);
        }

        return $this;
    }

    /**
     * Adds an attribute of the given name
     * if the attribute already exits, the value will be added to the existing value seperated by a blank (" ")
     * @param string $name
     * @param string $val
     */
    public function addAttribute($name, $val = null): editor_Tag
    {
        if ($name == 'class') {
            return $this->addClasses($val);
        }
        if (array_key_exists($name, $this->attribs)) {
            if ($val != null) {
                $this->attribs[$name] .= ' ' . Markup::unescapeFromAttribute(trim($val));
            }
        } else {
            $this->attribs[$name] = ($val == null) ? '' : Markup::unescapeFromAttribute(trim($val));
        }

        return $this;
    }

    /**
     * removes an attribute of the given name
     * @param string $name
     */
    public function unsetAttribute($name): editor_Tag
    {
        if (array_key_exists($name, $this->attribs)) {
            unset($this->attribs[$name]);
        }

        return $this;
    }

    /**
     * retrieves the given attribute-value. NULL is returned, if there is no attribute with the given name
     * @param string $name
     * @return string|null
     */
    public function getAttribute($name)
    {
        if (array_key_exists($name, $this->attribs)) {
            return Markup::escapeForAttribute($this->attribs[$name]);
        }

        return null;
    }

    /**
     * retrieves the given raw attribute-value. NULL is returned, if there is no attribute with the given name
     * @param string $name
     * @return string|null
     */
    public function getUnescapedAttribute($name)
    {
        if (array_key_exists($name, $this->attribs)) {
            return $this->attribs[$name];
        }

        return null;
    }

    /**
     * checks if the given attribute is present
     * @param string $name
     * @return boolean
     */
    public function hasAttribute($name): bool
    {
        return array_key_exists($name, $this->attribs);
    }

    /* content */

    /**
     * Retrieves our textual content without markup
     * @return string
     */
    public function getText()
    {
        $text = '';
        if ($this->hasChildren()) {
            foreach ($this->children as $child) {
                $text .= $child->getText();
            }
        }

        return $text;
    }

    /**
     * Returns our text length / number of characters
     * @return int
     */
    public function getTextLength()
    {
        $length = 0;
        if ($this->hasChildren()) {
            foreach ($this->children as $child) {
                $length += $child->getTextLength();
            }
        }

        return $length;
    }

    /**
     * Returns the tag-name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * checks, if the actual Tag is a Text-Node (editor_TextNode)
     * @return boolean
     */
    public function isText(): bool
    {
        return false;
    }

    /**
     * checks, if the actual Tag is a link
     * @return boolean
     */
    public function isLink(): bool
    {
        return ($this->getName() == 'a');
    }

    /**
     * checks, if the actual Tag is a empty/non-rendered tag
     * @return boolean
     */
    public function isBlank(): bool
    {
        return ($this->getName() == '');
    }

    /**
     * An empty Tag means a Tag, that renders to an empty string ...
     * @return boolean
     */
    public function isEmpty(): bool
    {
        return ($this->getName() == '' && count($this->children) == 0);
    }

    /**
     * Retrieves, if the tag is a singular tag like <tag /> or a complete tag with opening and closing part
     */
    public function isSingular(): bool
    {
        return $this->singular;
    }

    /**
     * Tags are seen as equal if they have the same node-name, the same classes & the same attributes (apart from
     * data-attributes if set) The data-attributes and the children of the tag will not count for comparision
     */
    public function isEqual(editor_Tag $tag, bool $withDataAttribs = true): bool
    {
        if (! $this->hasEqualName($tag) || ! $this->hasEqualClasses($tag)) {
            return false;
        }
        foreach ($this->attribs as $key => $val) {
            if (($withDataAttribs || substr($key, 0, 5) != 'data-') && (! $tag->hasAttribute(
                $key
            ) || $tag->getUnescapedAttribute($key) != $val)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Implementation used only for Internal tag
     */
    public function isEqualType(editor_Tag $tag): bool
    {
        return false;
    }

    /**
     * Checks if the passed tag has the same classes
     */
    public function hasEqualClasses(editor_Tag $tag): bool
    {
        return ($tag->getSortedClasses() == $this->getSortedClasses());
    }

    /**
     * Checks if the passed tag has the same node-name
     */
    public function hasEqualName(editor_Tag $tag): bool
    {
        return ($tag->getName() == $this->getName());
    }

    /**
     * Creates a clone of the tag. Does not copy/clone the children and if not specified data-attributes
     * This is no deep-clone!
     * @param boolean $withDataAttribs
     * @return editor_Tag
     */
    public function clone(bool $withDataAttribs = false, bool $withId = false)
    {
        return $this->cloneProps($this->createBaseClone(), $withDataAttribs, $withId);
    }

    /**
     * Clones our attributes & classes to a different tag-object
     * @param boolean $withDataAttribs
     * @param boolean $withId
     * @return editor_Tag
     */
    public function transferProps(editor_Tag $tag, bool $withDataAttribs = false, bool $withId = false)
    {
        return $this->cloneProps($tag, $withDataAttribs, $withId);
    }

    /**
     * Helper to create a basic cloned object (with empty props)
     * This can be used in overwriting classes to create the matching class instance with the suitable constructor
     * arguments
     * @return editor_Tag
     */
    protected function createBaseClone()
    {
        return editor_Tag::create($this->getName());
    }

    /**
     * Helper clone our properties. Does not clone the ID
     * @param boolean $withDataAttribs
     * @param boolean $withId
     * @return editor_Tag
     */
    protected function cloneProps(editor_Tag $tag, bool $withDataAttribs = false, bool $withId = false)
    {
        $tag->setClasses($this->getClasses());
        foreach ($this->attribs as $name => $val) {
            if (($withDataAttribs || substr($name, 0, 5) != 'data-') && ($withId || $name != 'id')) {
                $tag->setUnescapedAttribute($name, $val);
            }
        }

        return $tag;
    }

    /* render */

    /**
     * renders the complete tag with its contents
     */
    public function render(array $skippedTypes = null): string
    {
        return $this->renderStart() . $this->renderChildren($skippedTypes) . $this->renderEnd();
    }

    /**
     * renders the complete starting tag without inner html
     */
    public function start($withDataAttribs = true): string
    {
        return $this->renderStart($withDataAttribs);
    }

    /**
     * renders the complete end-portion of the tag
     */
    public function end(): string
    {
        return $this->renderEnd();
    }

    /**
     * renders the complete tag with all attributes & content
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Renders the children (text-nodes & markup-nodes)
     */
    public function renderChildren(array $skippedTypes = null): string
    {
        $html = '';
        if ($this->hasChildren()) {
            foreach ($this->children as $child) {
                $html .= $child->render($skippedTypes);
            }
        }

        return $html;
    }

    /**
     * Renders the replaced contents what usually means without markup
     * (only internal tags may have markup depending on the mode)
     */
    public function renderReplaced(string $mode): string
    {
        $html = '';
        if ($this->hasChildren()) {
            foreach ($this->children as $child) {
                $html .= $child->renderReplaced($mode);
            }
        }

        return $html;
    }

    /**
     * @param boolean $withDataAttribs
     */
    protected function renderStart(bool $withDataAttribs = true): string
    {
        if ($this->getName() == '') {
            return '';
        }
        $tag = '<' . $this->getName() . $this->renderAttributes($withDataAttribs);
        if ($this->isSingular()) {
            return $tag . static::$selfClosingMarker . '>';
        }

        return $tag . '>';
    }

    protected function renderEnd(): string
    {
        if ($this->isSingular()) {
            return '';
        }

        return '</' . $this->getName() . '>';
    }

    /**
     * Creates all our attributes as string starting with a blank
     * @param boolean $withDataAttribs
     */
    protected function renderAttributes(bool $withDataAttribs = true): string
    {
        $attribs = static::classAttr($this->getClasses());
        foreach ($this->attribs as $name => $val) {
            if ($withDataAttribs || substr($name, 0, 5) != 'data-') {
                $attribs .= static::createAttribute($name, $val);
            }
        }

        return $attribs;
    }

    /**
     * Helper to debug nested tags
     */
    public function debugStructure(string $indentation = ''): string
    {
        $text = $indentation . ' ' . get_class($this) . ' ' . $this->debugProps() . "\n";
        if ($this->hasChildren()) {
            foreach ($this->getChildren() as $child) {
                $text .= $child->debugStructure('**' . $indentation);
            }
        }

        return $text;
    }

    /**
     * Helper to create debug structure
     */
    public function debugProps(): string
    {
        return '';
    }
}
