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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
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
    const DEFAULT_HANDLER = '';
    
    /**
     * contains all xml chunks (xml string split in text and nodes)
     * @var array
     */
    public $xmlChunks; //FIXME protected, just for debuggin public
    
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
    
    /**
     * walks through the given XML string and fires the registered callbacks for each found node 
     * @param string $xml
     */
    public function parse($xml) {
        $this->parseList(preg_split('/(<[^>]+>)/i', $xml, null, PREG_SPLIT_DELIM_CAPTURE));
    }
    
    /**
     * walks through the given XML chunk array and fires the registered callbacks for each found node 
     * @param array $chunks
     */
    public function parseList(array $chunks) {
        $this->xmlChunks = $chunks;
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
                if(preg_match_all('/([^\s]+)="([^"]*)"/', $chunk, $matches)){
                    //ensure that all attribute keys are lowercase, original notation can be found in the orignal chunk
                    $matches[1] = array_map('strtolower', $matches[1]);
                    $attributes = array_combine($matches[1], $matches[2]);
                }
                else {
                    $attributes = [];
                }
                
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
     * @param integer $offset
     * @param integer $length
     */
    public function getChunk($index) {
        return $this->xmlChunks[$index];
    }
    
    /**
     * replaces the chunk at the given index with the given replacement
     * @param integer $index the chunk index to replace
     * @param string|callable $replacement the new chunk string content, or a callable which receives the following parameters: integer $index, string $oldContent
     * @param integer $length repeats the replacement for the amount if chunks as specified in $length, defaults to 1
     * @return string the new chunk
     */
    public function replaceChunk($index, $replacement, $length = 1) {
        for ($i = 0; $i < $length; $i++) {
            $idx = $index + $i;
            if(is_callable($replacement)) {
                $replacement = call_user_func($replacement, $idx, $this->xmlChunks[$idx]);
            }
            $this->xmlChunks[$idx] = $replacement;
        }
    }
    
    /**
     * return one or more chunks by index(offset) and length
     * @param integer $offset
     * @param integer $length
     * @return array
     */
    public function getChunks($offset, $length = 1) {
        return array_slice($this->xmlChunks, $offset, $length);
    }
    
    /**
     * return one or more chunks by start index(offset) and end index(offset)
     * @param integer $startOffset
     * @param integer $endOffset
     * @param boolean $asString defaults to false
     * @return array|string depends on parameter $asString
     */
    public function getRange($startOffset, $endOffset, $asString = false) {
        $chunks = $this->getChunks($startOffset, $endOffset-$startOffset + 1);
        if($asString) {
            return $this->join($chunks);
        }
        return $chunks;
    }
    
    /**
     * registers handlers to the given tag type
     * The handlers can be null, if only one of both is needed
     * @param string $tag tagname which should be handled, or empty string to handle all other non registered tags
     * @param callable $opener Parameters: string $tag, array $attributes, integer $key, boolean $isSingle
     * @param callable $closer Parameters: string $tag, integer $key, array $opener where opener is an assoc array: ['openerKey' => $key,'tag' => $tag,'attributes' => $attributes]
     */
    public function registerElement($tag, callable $opener = null, callable $closer = null) {
        $tag = $this->normalizeTag($tag);
        if(!empty($opener)) {
            $this->handlerElementOpener[$tag] = $opener;
        }
        if(!empty($closer)) {
            $this->handlerElementCloser[$tag] = $closer;
        }
    }
    
    /**
     * Warning: parentTag works only of a handler is registered for parentTag
     * @param callable $handler Parameters: $other, $key
     */
    public function registerOther(callable $handler) {
        $this->handlerOther = $handler;
    }
    
    /**
     * returns true if the given tag is a parent of the current
     * @param string $tag
     * @return boolean
     */
    public function hasParent($tag) {
        //FIXME implement me look up in $this->xmlChunks
        return true;
    }
    
    protected function normalizeTag($tag) {
        return strtolower($tag);
    }
    
    protected function handleElementStart($key, $tag, $attributes, $isSingle) {
        $tag = $handleTag = $this->normalizeTag($tag);
        $this->log("START#".$tag.'#');
        $this->xmlStack[] = [
            'openerKey' => $key,
            'tag' => $tag,
            'attributes' => $attributes,
        ];
        
        if(empty($this->handlerElementOpener[$handleTag])) {
            if(empty($this->handlerElementOpener[self::DEFAULT_HANDLER])){
                return;
            }
            $handleTag = self::DEFAULT_HANDLER;
        }
        
        call_user_func($this->handlerElementOpener[$handleTag], $tag, $attributes, $key, $isSingle);
        $this->log("ATTR#".print_r($attributes,1).'#');
    }
    
    protected function handleElementEnd($key, $tag) {
        $opener = end($this->xmlStack);
        $tag = $handleTag = $this->normalizeTag($tag);
        $this->log("END#".$tag.'#');
        
        if($opener['tag'] !== $tag) {
            //if you got here because of an XML error: use an external tool like xmllint to get more details!
            throw new ZfExtended_Exception('Invalid XML: expected closing "'.$opener['tag'].'" tag, but got tag "'.$tag.'". Opening tag was: '.print_r($opener,1));
        }
        
        if(empty($this->handlerElementCloser[$handleTag])) {
            if(empty($this->handlerElementCloser[self::DEFAULT_HANDLER])){
                array_pop($this->xmlStack);
                return;
            }
            $handleTag = self::DEFAULT_HANDLER;
        }
        call_user_func($this->handlerElementCloser[$handleTag], $tag, $key, $opener);
        array_pop($this->xmlStack);
    }
    
    protected function handleOther($key, $other) {
        if(!empty($this->handlerOther)){
            call_user_func($this->handlerOther, $other, $key);
        }
        $this->log("Other#".$other.'#');
    }
    
    protected function log($msg) {
        //error_log($msg);
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
