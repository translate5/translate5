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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */


/**
 * XML Fileparser
 */
class editor_Models_Import_FileParser_XmlParser {
    const DEFAULT_HANDLER = '*';
    
    /**
     * contains all xml chunks (xml string split in text and nodes)
     * @var array
     */
    protected $xmlChunks;
    
    /**
     * index stack of the opened nodes
     * @var array
     */
    protected $xmlStack = [];
    
    /**
     * @var integer
     */
    protected $currentOffset;
    
    protected $handlerElementOpener = [];
    protected $handlerElementCloser = [];
    protected $handlerOther;
    protected $handlerError;
    
    /**
     * if >0 disables the processing of the registered handlers
     * @var integer
     */
    protected $disableHandlerCount = 0;
    
    /**
     * Initial $preserveWhitespace value
     * @var array
     */
    protected $preserveWhitespace;
    
    protected $nonXmlBlocks = [];
    
    /**
     * walks through the given XML string and fires the registered callbacks for each found node
     * Preserving whitespace in XML is defined by the xml:space attribute on each node.
     *  If the attribute is not given, the parent node is considered.
     *  The initial root value (preserve or ignore) is given here as boolean parameter
     * @param string $xml
     * @param bool $preserveWhitespaceRoot
     * @return string the parsed string with all callbacks applied
     */
    public function parse($xml, $preserveWhitespaceRoot = false) {
        $this->nonXmlBlocks = [];
        $xml = preg_replace_callback('/(<!\[CDATA\[.*?\]\]>)|(<!--.*?-->)/s', function($item){
            $id = count($this->nonXmlBlocks);
            if(empty($item[1]) && $item[1]!=="0" && (!empty($item[2]) || $item[2]!=="0")) {
                $key = '<xml-comment id="comment-'.$id.'"/>';
            }
            else {
                $key = '<xml-cdata id="cdata-'.$id.'"/>';
            }
            $this->nonXmlBlocks[$key] = $item[0];
            return $key;
        }, $xml);
        $this->parseList(preg_split('/(<[^>]+>)/i', $xml, null, PREG_SPLIT_DELIM_CAPTURE), $preserveWhitespaceRoot);
        return str_replace(array_keys($this->nonXmlBlocks), array_values($this->nonXmlBlocks), $this->__toString());
    }
    
    /**
     * walks through the given XML chunk array and fires the registered callbacks for each found node
     * @param array $chunks
     * @param bool $preserveWhitespaceRoot see method parse
     */
    public function parseList(array $chunks, $preserveWhitespaceRoot = false) {
        $this->xmlStack = [];
        $this->xmlChunks = $chunks;
        $this->preserveWhitespace = $preserveWhitespaceRoot;
        $this->disableHandlerCount = 0;
        foreach($this->xmlChunks as $key => $chunk) {
            $this->currentOffset = $key;
            if(!empty($chunk) && $chunk[0] === '<') {
                $isSingle = mb_substr($chunk, -2) === '/>';
                $parts = explode(' ', trim($chunk,'</> '));
                $tag = reset($parts);
                
                if($chunk[1] === '/'){
                    $this->handleElementEnd($key, $tag);
                    continue;
                }
                
                //due the the $matches structure of preg_match_all the array_merge of both results is much easier
                //  as trying to use the result of a combined regex
                $attributes = array_merge(
                //find attributes with " quotes:
                    $this->parseAttributes('/([^\s]+)="([^"]*)"/', $chunk),
                //find attributes with ' quotes:
                    $this->parseAttributes("/([^\s]+)='([^']*)'/", $chunk)
                );
                
                $this->handleElementStart($key, $tag, $attributes, $isSingle);
                if($isSingle) {
                    $this->handleElementEnd($key, $tag);
                }
                continue;
            }
            $this->handleOther($key, $chunk);
        }
    }
    
    /**
     * parses the chunks attributes via the given regex
     * @param string $regex
     * @param string $chunk
     */
    protected function parseAttributes($regex, $chunk) {
        $matches = [];
        //find attributes with " quotes:
        if(preg_match_all($regex, $chunk, $matches)){
            //ensure that all attribute keys are lowercase, original notation can be found in the orignal chunk
            $matches[1] = array_map('strtolower', $matches[1]);
            return array_combine($matches[1], $matches[2]);
        }
        return []; //$matches contains empty arrays, so it is not empty, we have to return a new empty array here
    }
    
    /**
     * return next chunk, null if there is no more
     * @return string | null
     */
    public function getNextChunk() {
        $idx = $this->currentOffset + 1;
        if(isset($this->xmlChunks[$idx])){
            return $this->xmlChunks[$idx];
        }
        return null;
    }
    
    /**
     * return one or more chunks by index(offset) and length
     * @param int $offset
     * @param int $length
     */
    public function getChunk($index) {
        return $this->xmlChunks[$index];
    }
    
    /**
     * replaces the chunk at the given index with the given replacement
     * @param int $index the chunk index to replace
     * @param string|callable $replacement the new chunk string content, or a callable (array|Closure) which receives the following parameters: int $index, string $oldContent
     * @param int $length repeats the replacement for the amount if chunks as specified in $length, defaults to 1
     */
    public function replaceChunk($index, $replacement, $length = 1) {
        for ($i = 0; $i < $length; $i++) {
            $idx = $index + $i;
            if(!is_string($replacement) && is_callable($replacement)) {
                $replacement = call_user_func($replacement, $idx, $this->xmlChunks[$idx]);
            }
            $this->xmlChunks[$idx] = $replacement;
        }
    }
    
    /**
     * return one or more chunks by index(offset) and length
     * @param int $offset
     * @param int $length
     * @return array
     */
    public function getChunks($offset, $length = 1) {
        return array_slice($this->xmlChunks, $offset, $length);
    }
    
    /**
     * return one or more chunks by start index(offset) and end index(offset)
     * lower end offset as start offset results in empty result
     * @param int $startOffset
     * @param int $endOffset
     * @param bool $asString defaults to false
     * @return array|string depends on parameter $asString
     */
    public function getRange($startOffset, $endOffset, $asString = false) {
        if($startOffset <= $endOffset) {
            $chunks = $this->getChunks($startOffset, $endOffset-$startOffset + 1);
        }
        else {
            $chunks = [];
        }
        if($asString) {
            return $this->join($chunks);
        }
        return $chunks;
    }
    
    /**
     * registers handlers to the given tag type
     * The handlers can be null, if only one of both is needed
     * @param string $tag CSS selector like tag definition, see $this->parseSelector
     * @param callable $opener Parameters: string $tag, array $attributes, int $key, bool $isSingle
     * @param callable $closer Parameters: string $tag, int $key, array $opener where opener is an assoc array: ['openerKey' => $key,'tag' => $tag,'attributes' => $attributes]
     * @return [int] a list of the indizes of the added handlers
     */
    public function registerElement($tag, callable $opener = null, callable $closer = null) {
        //splits the tag selector into multiple handlers if a , is given
        $tags = preg_split('/,(?=[^\]])/', $tag);
        foreach($tags as $tag) {
            $this->registerSingleElement($tag, $opener, $closer);
        }
    }
    
    /**
     * registers handlers to the given tag type
     * The handlers can be null, if only one of both is needed
     * @param string $selector tag selector which should be handled, or empty string to handle all other non registered tags
     * @param callable $opener Parameters: string $tag, array $attributes, int $key, bool $isSingle
     * @param callable $closer Parameters: string $tag, int $key, array $opener where opener is an assoc array: ['openerKey' => $key,'tag' => $tag,'attributes' => $attributes]
     */
    protected function registerSingleElement($selector, callable $opener = null, callable $closer = null) {
        $tag = $this->parseSelector($selector, $filter);
        if($tag === false) {
            //see parseSelector for possible selectors!
            throw new ZfExtended_Exception('The given XML tag selector could not be parsed: '.$selector);
        }
        if(!empty($opener)) {
            settype($this->handlerElementOpener[$tag], 'array');
            $this->handlerElementOpener[$tag][$selector] = ['callback' => $opener, 'filter' => $filter];
        }
        if(!empty($closer)) {
            settype($this->handlerElementCloser[$tag], 'array');
            $this->handlerElementCloser[$tag][$selector] = ['callback' => $closer, 'filter' => $filter];
        }
    }
    
    
    /**
     * Warning: parentTag works only if a handler is registered for parentTag
     * leave $handler empty to unregister it
     * @param callable $handler Parameters: $other, $key
     */
    public function registerOther(callable $handler = null) {
        $this->handlerOther = $handler;
    }
    
    /**
     * Registers a handler for XML structure errors. If no handler is registered, a exception is thrown instead
     * leave $handler empty to unregister it
     * @param callable $handler Parameters: $currentOpener, $receivedTag, $currentKey
     *                          Result: If handler returns false, the processing of the current end tag is stopped as there was no tag in the chunk
     *                                  Otherwise the returned value is used as new current tag value.
     *                                  The returned value is NOT added to the chunklist automatically, this must be done by hand in the handler!
     */
    public function registerError(callable $handler = null) {
        $this->handlerError = $handler;
    }
    
    /**
     * returns a parent node, matching to the given selector, null if no match found
     * @param string $selector
     * @return NULL|mixed
     */
    public function getParent($selector) {
        $result = $this->getParents($selector, 1);
        if(empty($result)) {
            return null;
        }
        return array_shift($result);
    }
    
    /**
     * returns all parent nodes, matching to the given selector, empty array if no match found
     * @param string $selector
     * @param integer $limit defaults to 0, which means no limit; limits the count of given found parents
     * @return array
     */
    public function getParents(string $selector, int $limit = 0): array {
        $stackIndex = count($this->xmlStack) - 1; //remove current node
        $selectorParts = [];
        $tag = $this->parseSelector($selector, $selectorParts);
        $result = [];
        if($stackIndex <= 0 || empty($selectorParts)) {
            //if current node is root node, there is no parent
            return $result;
        }
        $i = 0;
        while($stackIndex >= 0) {
            $node = $this->xmlStack[$stackIndex];
            if($node['tag'] !== $tag) {
                $stackIndex--;
                continue;
            }
            //for the selector match the Index has to be increased again, since the doesSelectorMatch decreases it
            if($this->doesSelectorMatch($selectorParts, $stackIndex + 1)) {
                $result[] = $node;
                $i++; //faster as count($result)
                if($limit > 0 && $limit >= $i) {
                    return $result;
                }
            }
            $stackIndex--;
        }
        return $result;
    }
    
    /**
     * returns the current element on the stack
     * @return mixed
     */
    public function current() {
        return end($this->xmlStack);
    }
    
    /**
     * parses the CSS like selector
     * currently supported:
     *    element                   a simple type selector
     *    elementA elementB         the descendant selector
     *    elementA > elementB       the direct descendant selector
     *    elementA[attr]            attribute existence selector
     *    elementA[attr=value]      attribute equals selector
     *    elementA, elementB        splits up the selector at , and uses each expression as one selector
     *    → all other selectors (especially about attributes must be developed as they are needed!)
     
     * returns the last matched tag as string, needed for our streamed based parsing
     *
     * @param string $tagSelector
     * @param array $selectorParts
     * @return string
     */
    protected function parseSelector($tagSelector, &$selectorParts) {
        $selectorParts = [];
        $tagSelector = trim($tagSelector);
        if($tagSelector === self::DEFAULT_HANDLER) {
            return $tagSelector;
        }
        
        //regex to get the single selector parts
        $regex = '/(([-\w_:.]+)(\[[^\]]+])?)([>\s]+)?/';
        
        $res = preg_match_all($regex, $tagSelector, $parts, PREG_SET_ORDER );
        //0 matches or false, both is wrong here
        if(!$res) {
            return false;
        }
        
        $selector = end($parts);
        
        if(!empty($selector[4])){
            //after the last tag in the selector, there may not be any operator
            throw new ZfExtended_Exception('after the last tag there may not be any operator, given selector: '.$tagSelector);
        }
        
        $selectorParts = $parts; //return the selectorparts as referenced variable
        return $this->normalizeTag($selector[2]);
    }
    
    /**
     * normalizes a given tag
     * @return string
     */
    protected function normalizeTag($tag) {
        return strtolower($tag);
    }
    
    /**
     * checks if the given selector parts are matched by the current XML Stack (including the current Node)
     * @param array $selectorParts
     * @param int $startStackIndex optional, defaults to the idx of the last item in the stack
     * @return boolean
     */
    protected function doesSelectorMatch(array $selectorParts, $startStackIndex = null) {
        if(empty($selectorParts)){
            return true;
        }
        $selector = $this->popSelector($selectorParts);
        //the operator of the last tag must always be the direct operator since it must match
        $selector['operator'] = '>';
        
        if(is_null($startStackIndex)) {
            $startStackIndex = count($this->xmlStack);
        }
        while($startStackIndex && !empty($selector)) {
            $node = $this->xmlStack[--$startStackIndex];
            $match = $node['tag'] == $selector['tag']
                && $this->doesAttributesFilterMatch($node['attributes'], $selector['attrFilter']);
            
            if($match) {
                $selector = $this->popSelector($selectorParts);
                if(empty($selector)) {
                    //no more selectors, the previous matched, that means return true
                    return true;
                }
                continue; //proceed with next selector and next node in the XML stack
            }
            //if it was no match but the direct descendant operator was set this means false
            // if the direct descendant operator was not set, we can bubble up in the XML Stack
            if($selector['operator'] == '>') {
                return false;
            }
            //bubble up in the XML Stack, keep the selector
        }
        //if we bubbled up without a match, we return false
        return false;
    }
    
    /**
     * converts the result of the preg match to a usable structure
     * @param array $selector
     * @return string[]|null
     */
    protected function popSelector(array &$selectorParts) {
        $selector = array_pop($selectorParts);
        if(!$selector) {
            return $selector;
        }
        $selector = [
            'tag' => $this->normalizeTag($selector[2]),
            'attrFilter' => empty($selector[3]) ? '' : $selector[3],
            'operator' => empty($selector[4]) ? '' : trim($selector[4]),
        ];
        return $selector;
    }
    
    /**
     * checks if the given attribute filter matches the given attributes
     * @param array $attributes
     * @param string $filter
     * @return boolean
     */
    protected function doesAttributesFilterMatch(array $attributes, $filter) {
        $filter = trim($filter, '[]');
        if(empty($filter)) {
            return true;
        }
        
        if(preg_match('/([^\^=$*]+)([^\^=$*]{0,1}=)([^=]+)$/', $filter, $parts)) {
            $attribute = $parts[1];
            $operator = $parts[2];
            $comparator = $parts[3];
        }
        else {
            //empty filter is excluded above, so reaching here can mean only the "existence" operator
            $attribute = $filter;
            $operator = 'has';
        }
        
        if(!array_key_exists($attribute, $attributes)) {
            return false;
        }
        
        $value = $attributes[$attribute];
        
        switch ($operator) {
            // tag[attr] attribute existence
            case 'has':
                return true; //for false see above
                
            // tag[attr=foo] attribute value equals
            case '=':
                return $value == $comparator;
                
            // tag[attr^=foo] attribute value starts with
            case '^=':
                return mb_strpos($value, $comparator) === 0;
                
            // tag[attr$=foo] attribute value ends with
            case '$=':
                return mb_strpos(strrev($value), strrev($comparator)) === 0;
                
            // tag[attr*=foo] attribute value contains
            case '*=':
                return mb_strpos($value, $comparator) !== false;
        }
        return false;
    }
    
    /**
     * Handles a start tag (single tags are a start and an end tag at once)
     * @param int $key the tag offset in the node list
     * @param string $tag the found tag
     * @param array $attributes the tag attributes
     * @param bool $isSingle is true if it is a single tag
     */
    protected function handleElementStart($key, $tag, $attributes, $isSingle) {
        $tag = $handleTag = $this->normalizeTag($tag);
        $this->log("START#".$tag.'#');
        $previousNode = end($this->xmlStack);
        //get the parent boolean preserve whitespace value
        $preserve = empty($previousNode) ? $this->preserveWhitespace : $previousNode['preserveWhitespace'];
        //get the XML attribute of the current node, which could not exist at all
        $preserve = $this->getAttribute($attributes, 'xml:space', $preserve ? 'preserve' : 'default');
        $this->xmlStack[] = [
            'openerKey' => $key,
            'tag' => $tag,
            'attributes' => $attributes,
            'isSingle' => $isSingle,
            'preserveWhitespace' => $preserve == 'preserve' || $this->preserveWhitespace,
        ];
        
        if($this->disableHandlerCount > 0) {
            return;
        }
        
        if(empty($this->handlerElementOpener[$handleTag])) {
            if(empty($this->handlerElementOpener[self::DEFAULT_HANDLER])){
                return;
            }
            $handleTag = self::DEFAULT_HANDLER;
        }
        
        foreach($this->handlerElementOpener[$handleTag] as $handler) {
            if($this->doesSelectorMatch($handler['filter'])){
                call_user_func($handler['callback'], $tag, $attributes, $key, $isSingle);
            }
        }
        $this->log("ATTR#".print_r($attributes,1).'#');
    }
    
    /**
     * Handles a end tag
     * @param int $key the tag offset in the node list
     * @param string $tag the found tag
     * @throws ZfExtended_Exception
     */
    protected function handleElementEnd($key, $tag) {
        $opener = end($this->xmlStack);
        $tag = $handleTag = $this->normalizeTag($tag);
        $this->log("END#".$tag.'#');
        
        if($opener['tag'] !== $tag) {
            if(empty($this->handlerError)){
                throw new editor_Models_Import_FileParser_InvalidXMLException('E1024',[
                    'closingTag' => $opener['tag'],
                    'receivedTag' => $tag,
                    'openingTag' => print_r($opener,1),
                    'parseStack' => print_r($this->xmlStack,1),
                ]);
            }
            else {
                $tag = call_user_func($this->handlerError, $opener, $tag, $key);
                if($tag === false) {
                    return;
                }
            }
        }
        if(!empty($opener['disableUntilEndTag'])) {
            $this->disableHandlerCount--;
            $this->disableHandlerCount = max($this->disableHandlerCount, 0); //ensure value not to be <0
        }
        if($this->disableHandlerCount > 0) {
            array_pop($this->xmlStack);
            return;
        }
        
        if(empty($this->handlerElementCloser[$handleTag])) {
            if(empty($this->handlerElementCloser[self::DEFAULT_HANDLER])){
                array_pop($this->xmlStack);
                return;
            }
            $handleTag = self::DEFAULT_HANDLER;
        }
        foreach($this->handlerElementCloser[$handleTag] as $handler) {
            if($this->doesSelectorMatch($handler['filter'])){
                call_user_func($handler['callback'], $tag, $key, $opener);
            }
        }
        array_pop($this->xmlStack);
    }
    
    protected function handleOther($key, $other) {
        //TODO For the current needs the text handler may not be disabled through disableHandler functionality
        // if this will be needed in the future, the disableHandler functionality must be extended with a "disable type" or something
        //if($this->disableHandlerCount > 0) {
            //return;
        //}
        if(!empty($this->handlerOther)){
            call_user_func($this->handlerOther, $other, $key);
        }
        $this->log("Other#".$other.'#');
    }
    
    /**
     * returns true if handlers are disabled at the moment, false otherwise
     * @return boolean
     */
    public function isHandlingDisabled() {
        return $this->disableHandlerCount > 0;
    }
    
    protected function log($msg) {
        //error_log($msg);
    }
    
    /**
     * disables all registered handlers until the end tag of the current node is reached
     */
    public function disableHandlersUntilEndtag() {
        $this->disableHandlerCount++;
        //sets a marker in the start tag to, which is checked and disabled on the corresponding end tag
        $this->xmlStack[count($this->xmlStack) - 1]['disableUntilEndTag'] = true;
    }
    
    /**
     * convenience method to get one attribute of the attributes array, returns the given default if not existent
     * @param array $attributes
     * @param string $attribute
     * @param string $default
     * @return mixed
     */
    public function getAttribute($attributes, $attribute, $default = false) {
        //we are storing all attributes in lower case
        $attribute = strtolower($attribute);
        if(array_key_exists($attribute, $attributes)) {
            return $attributes[$attribute];
        }
        return $default;
    }
    
    /**
     * merges the separated XML chunks back into a string
     * @return string
     */
    public function __toString() {
        return $this->join($this->xmlChunks);
    }
    
    public function join(array $chunks) {
        return join('', $chunks);
    }
}
