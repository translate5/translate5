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
 */
class editor_Models_Segment_TagProtection {
    
    use editor_Models_Import_FileParser_TagTrait;
    
    
    public function __construct(){
        $this->initImageTags();
    }
    
    /**
     * counter for internal tags
     * @var integer
     */
    protected $shortTagIdent = 1;
    
    public function protectTags(string $textNode) {
        
        $this->shortTagIdent = 1;
        
        $textNode = html_entity_decode($textNode);
        //Dies <strong>ist ein</strong> Test. &nbsp;
        $textNode = $this->entityCleanup($textNode, true);
        //at the end of the string there is nbsp character not whitespace
        //the nbsp will be handled bellow
        //Dies &lt;strong&gt;ist ein&lt;/strong&gt; Test.  
        $textNode = str_replace(['&lt;','&gt;'], ['<','>'], $textNode);
        //Dies <strong>ist ein</strong> Test.  
        
        //TODO: here replace tags like in csv tag protection
        
        $tempXml = qp('<?xml version="1.0"?><segment>'.$textNode.'</segment>', NULL, array('format_output' => false));
        
        // mark single- or paired-tags
        foreach ($tempXml->find('segment *') as $element) {
            $tagType = 'singleTag';
            if (!empty($element->innerXml())) {
                $tagType = 'pairedTag';
            }
            
            $element->wrap('<'.$tagType.'_'.$this->shortTagIdent++.' data-tagname="'.$element->tag().'" />');
        }
        $textNode = $tempXml->find('segment')->innerXml();
        
//         $textNode = $this->parseReplaceSingleTags($textNode);
//         $textNode = $this->parseReplaceLeftTags($textNode);
//         $textNode = $this->parseReplaceRightTags($textNode);
        
        return $textNode;
    }
    
    /**
     * Does the entity encoding, see inline comments
     * @param string $textNode
     * @param bool $xmlBased
     * @return string
     */
    public function entityCleanup($textNode, $xmlBased = true) {
        //FIXME this is not the right place here, but here it is used for all imports.
        // It is important that we have no entities in our DB but their UTF8 characters instead,
        // since a XLF export of our segments would not be valid XML with the entities.
        // And the browsers are converting the entities anyway to UTF8 characters.
        // Refactor to a better place with TRANSLATE-296
        if($xmlBased) {
            // in a XML based format only the defined entities may exist
            // - for our major XML formats these are: &amp; &lt; &gt; only
            // - all other entities must be encoded back into their utf8 character: &zslig; into ß
            //   → otherwise our XLF export will fail with invalid XML
            //   → also the browser will convert the &zslig; into ß anyway, so we do this directly on the import
            // why using this encode(decode) see
            //  https://stackoverflow.com/questions/18039765/php-not-have-a-function-for-xml-safe-entity-decode-not-have-some-xml-entity-dec
            return htmlentities(html_entity_decode($textNode, ENT_HTML5|ENT_QUOTES), ENT_XML1);
        }
        // for non XML based formats (for example CSV) all content and its contained entities are displayed to the user as they were in the import file
        // therefore we have just to encode the < > & characters.
        // so if the CSV contains &amp; ß < this would be converted to &amp;amp; ß &gt; to be displayed correctly in the browser
        return htmlentities($textNode, ENT_XML1);
    }
    
    protected function getLeftPlaceholder(string $type, string $id, string $text){
        return str_replace(['{type}','{id}','{text}'], [$type,$id,$text], '<{type} id="{id}" text="{text}">');;
    }
    protected function getRightPlaceholder(string $type, string $id){
        return str_replace(['{type}','{id}'], [$type,$id], '</{type} id="{id}">');;
    }
    
    
    /**
     * Replace all special marked single-tags in $text.
     *
     * @param string $text
     * @return string
     */
    public function parseReplaceSingleTags($text) {
        if (preg_match_all('/<singleTag_([0-9]+).*?data-tagname="([^"]*)"[^>]*>(<[^>]+>)<\/singleTag_[0-9]+>/is', $text, $matches, PREG_SET_ORDER)) {
            
            foreach ($matches as $match) {
                $tag = $match[3];
                $tagId = $match[1];
                $tagName = $match[2];
                $p = $this->getTagParams($tag, $tagId, $tagName, $this->encodeTagsForDisplay($tag));
                $replace = $this->_singleTag->getHtmlTag($p);
                $text = str_replace($match[0], $replace, $text);
            }
        }
        return $text;
    }
    
    /**
     * Replace all special marked left-tags in $text.
     *
     * @param string $text
     * @return string
     */
    public function parseReplaceLeftTags($text) {
        if (preg_match_all('/<pairedTag_([0-9]+).*?data-tagname="([^"]*)"[^>]*>(<[^>]+>)/is', $text, $matches, PREG_SET_ORDER)) {
            
            foreach ($matches as $match) {
                $tag = $match[3];
                $tagId = $match[1];
                $tagName = $match[2];
                
                $p = $this->getTagParams($tag, $tagId, $tagName, $this->encodeTagsForDisplay($tag));
                $replace = $this->_leftTag->getHtmlTag($p);
                
                $text = str_replace($match[0], $replace, $text);
            }
        }
        return $text;
    }
    
    /**
     * Replace all special marked right-tags in $text.
     *
     * @param string $text
     * @return string
     */
    public function parseReplaceRightTags($text) {
        if (preg_match_all('/(<[^>]+>)<\/pairedTag_([0-9]+)>/is', $text, $matches, PREG_SET_ORDER)) {
            
            foreach ($matches as $match) {
                $tag = $match[1];
                $tagId = $match[2];
                $tagName = preg_replace('/<[\/]*([^ ]*).*>/i', '$1', $tag);
                
                $p = $this->getTagParams($tag, $tagId, $tagName, $this->encodeTagsForDisplay($tag));
                $replace = $this->_rightTag->getHtmlTag($p);
                $text = str_replace($match[0], $replace, $text);
            }
        }
        return $text;
    }
    
    /**
     * Replace all special marked single-tags in $text.
     *
     * @param string $text
     * @return string
     */
    public function xxxparseReplaceSingleTags($text) {
        if (preg_match_all('/<singleTag_([0-9]+).*?data-tagname="([^"]*)"[^>]*>(<[^>]+>)<\/singleTag_[0-9]+>/is', $text, $matches, PREG_SET_ORDER)) {
            
            foreach ($matches as $match) {
                $tag = $match[3];
                $tagId = $match[1];
                $tagName = $match[2];
                //TODO: in the new class do not use getTagParams and getHtmlTag. Create separate placeholder
                $p = $this->getTagParams($tag, $tagId, $tagName, $this->encodeTagsForDisplay($tag));
                $replace = $this->_singleTag->getHtmlTag($p);
                $text = str_replace($match[0], $replace, $text);
            }
        }
        return $text;
    }
    
    /**
     * Replace all special marked left-tags in $text.
     *
     * @param string $text
     * @return string
     */
    public function xxxparseReplaceLeftTags($text) {
        if (preg_match_all('/<pairedTag_([0-9]+).*?data-tagname="([^"]*)"[^>]*>(<[^>]+>)/is', $text, $matches, PREG_SET_ORDER)) {
            
            foreach ($matches as $match) {
                $tag = $match[3];
                $tagId = $match[1];
                $tagName = $match[2];
                $p = $this->getTagParams($tag, $tagId, $tagName, $this->encodeTagsForDisplay($tag));
                
//                 [class] => 7374726f6e67
//                 [text] => &lt;strong&gt;
//                 [shortTag] => 1
//                 [id] => strong
                
                //$replace = $this->_leftTag->getHtmlTag($p);
                $replace = $this->getLeftPlaceholder('pairedTag',$p['id'],$p['text']);
                $text = str_replace($match[0], $replace, $text);
            }
        }
        return $text;
    }
    
    /**
     * Replace all special marked right-tags in $text.
     *
     * @param string $text
     * @return string
     */
    public function xxxparseReplaceRightTags($text) {
        if (preg_match_all('/(<[^>]+>)<\/pairedTag_([0-9]+)>/is', $text, $matches, PREG_SET_ORDER)) {
            
            foreach ($matches as $match) {
                $tag = $match[1];
                $tagId = $match[2];
                $tagName = preg_replace('/<[\/]*([^ ]*).*>/i', '$1', $tag);
                
                $p = $this->getTagParams($tag, $tagId, $tagName, $this->encodeTagsForDisplay($tag));
                $replace = $this->getRightPlaceholder('pairedTag', $tagId);
                //$replace = $this->_rightTag->getHtmlTag($p);
                $text = str_replace($match[0], $replace, $text);
            }
        }
        return $text;
    }
    
    protected function encodeTagsForDisplay($text) {
        return str_replace(array('"',"'",'<','>'),array('&quot;','&#39;','&lt;','&gt;'),$text);
    }
}