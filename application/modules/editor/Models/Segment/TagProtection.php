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

/**
 * Protects XML/HTML content / tags as internal tags
 */
use \QueryPath\DOMQuery;
class editor_Models_Segment_TagProtection {
    
    /**
     * @var array
     */
    protected $html5Tags = [];
    
    public function __construct(){
        $html5TagFile = APPLICATION_PATH.'/modules/editor/Models/Import/FileParser/html5-tags.txt';
        $this->html5Tags = file($html5TagFile, FILE_IGNORE_NEW_LINES);
    }
    
    /**
     * counter for internal tags
     * @var integer
     */
    protected $tagId = 0;
    
    /**
     * decodes encoded entities, and protects the resulting tags then
     * @param string $textNode A string plain containing XML/HTML
     * @param bool $entityCleanup
     * @return string
     */
    public function protectTags(string $textNode, bool $entityCleanup = true) {
        //$textNode is now: Dies <strong>ist ein</strong> Test. &nbsp;
        
        $this->tagId = 1;
        
        //if there were cdata or comment blocks in the encoded area, we encode them and their content as a single tag
        $textNode = preg_replace_callback('/(<!\[CDATA\[.*?\]\]>)|(<!--.*?-->)/s', function($item){
            $originalTag = editor_Models_Segment_InternalTag::encodeTagContent($item[0]);
            return $this->makeProtectedTag('single', $this->tagId++, $originalTag);
        }, $textNode);
        
        if($entityCleanup) {
            $textNode = editor_Models_Segment_Utility::foreachSegmentTextNode($textNode, function($text){
                return editor_Models_Segment_Utility::entityCleanup($text);
                //$text is now: Dies <strong>ist ein</strong> Test. _
            });
        }
        
        if (strpos($textNode, '<') === false) {
            return $textNode;
        }
            
        try {
            $tempXml = qp('<?xml version="1.0"?><segment>'.$textNode.'</segment>', NULL, array('format_output' => false));
            /* @var $tempXml \QueryPath\DOMQuery */
        }
        catch (Exception $e) {
            return $this->parseSegmentProtectInvalidHtml5($textNode);
        }

        // mark single- or paired-tags
        foreach ($tempXml->find('segment *') as $element) {
            $tagType = 'singleTag';
            if($element->tag() == 'protectedTag') {
                //if it is already a protectedTag, we just do nothing with it
                continue;
            }

            if (!ZfExtended_Utils::emptyString($element->innerXml())) {
                $tagType = 'pairedTag';
            }
            $element->wrap('<'.$tagType.'_'.$this->tagId++.'/>');
        }
        $textNode = $tempXml->find('segment')->innerXml();

        return $this->convertToPlaceholderTag($textNode);
        //result is now: Dies <protectedTag>ist ein</protectedTag> Test. _ (Where _ is the nbsp as character!)
    }
    
    /**
     * Replace all special marked single-tags in $text.
     *
     * @param string $text
     * @return string
     */
    protected function convertToPlaceholderTag($text) {
        $regexMap = [
            'single' => '/<singleTag_([0-9]+)[^>]*>(<[^>]+>)<\/singleTag_[0-9]+>/is',
            'open' => '/<pairedTag_([0-9]+)[^>]*>(<[^>]+>)/is',
            'close' => '/(<[^>]+>)<\/pairedTag_([0-9]+)>/is',
        ];
        $matches = [];
        foreach($regexMap as $type => $regex) {
            if (preg_match_all($regex, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    if($type == 'close') {
                        $id = $match[2];
                        $originalTag = $match[1];
                    }
                    else {
                        $id = $match[1];
                        $originalTag = $match[2];
                    }
                    $originalTag = editor_Models_Segment_InternalTag::encodeTagContent($originalTag);
                    $text = str_replace($match[0], $this->makeProtectedTag($type, $id, $originalTag), $text);
                }
            }
        }
        return $text;
    }
    
    /**
     * Fallback if content is not wellformed
     */
    protected function parseSegmentProtectInvalidHtml5($segment) {
        $replacer = function ($matches){
            $tagName = preg_replace('/<[\/]*([^ ]*).*>/is', '$1', $matches[0]);
            // only replace HTML5 tags, keep protectedTag in any case as original
            if ($tagName == 'protectedTag') {
                return $matches[0];
            }
            if(!in_array($tagName, $this->html5Tags)) {
                //everything else is returned as encoded to string since it seems not to be a valid tag
                return htmlspecialchars($matches[0], ENT_XML1);
            }
            
            $originalTag = editor_Models_Segment_InternalTag::encodeTagContent($matches[0]);
            return $this->makeProtectedTag('single', $this->tagId++, $originalTag);
        };
        
        return preg_replace_callback('/(<[^><]+>)/is', $replacer, $segment);
    }
    

    /**
     * Creates a protected tag out of given data
     * @param string $type
     * @param string $id
     * @param string $originalTag
     * @return string
     */
    protected function makeProtectedTag(string $type, string $id, string $originalTag): string {
        $placeholder = '<protectedTag data-type="%1$s" data-id="%2$d" data-content="%3$s"/>';
        return sprintf($placeholder, $type, $id, $originalTag);
    }
    
    /**
     * restores the protected entities (tags are restored automatically from the tags)
     * ONLY NEEDED FOR XLF BASED FORMATS!
     */
    public function unprotect($segment) {
        $chunks = preg_split('/(<[^>]*>)/', $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        $count = count($chunks);
        for ($i = 0; $i < $count; $i++) {
            //the imported content contained encoded HTML specialchars and te following characters as encoded entities
            $chunks[$i] = str_replace([' ', '"', "'"], ['&nbsp;','&quot;','&#039;'], $chunks[$i]);
            $i++; //get only the odd elements which contain the textual content
        }
        return htmlspecialchars(implode('', $chunks), ENT_XML1);
    }
}