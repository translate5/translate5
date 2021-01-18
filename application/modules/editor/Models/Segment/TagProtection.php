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
    
    public function protectTags(string $textNode) {
        
        $this->tagId = 1;
        
        try {
            $tempXml = qp('<?xml version="1.0"?><segment>'.$textNode.'</segment>', NULL, array('format_output' => false));
        }
        catch (Exception $e) {
            return $this->parseSegmentProtectInvalidHtml5($textNode);
        }
        
        // mark single- or paired-tags
        foreach ($tempXml->find('segment *') as $element) {
            $tagType = 'singleTag';
            if (!empty($element->innerXml())) {
                $tagType = 'pairedTag';
            }
            
            $element->wrap('<'.$tagType.'_'.$this->tagId++.'/>');
        }
        $textNode = $tempXml->find('segment')->innerXml();
        return $this->convertToPlaceholderTag($textNode);
    }
    
    /**
     * Replace all special marked single-tags in $text.
     *
     * @param string $text
     * @return string
     */
    protected function convertToPlaceholderTag($text) {
        $placeholder = '<protectedTag data-type="%1$s" data-id="%2$d" data-content="%3$s"/>';
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
//FIXME this unpack is used multiple times at multiple places!
                    $originalTag = implode('', unpack('H*', $originalTag));
                    $text = str_replace($match[0], sprintf($placeholder, $type, $id, $originalTag), $text);
                }
            }
        }
        return $text;
    }
    
    protected function parseSegmentProtectInvalidHtml5($segment) {
        $replacer = function ($matches){
            $tagName = preg_replace('/<[\/]*([^ ]*).*>/i', '$1', $matches[0]);
            // only replace HTML5 tags
            if (!in_array($tagName, $this->html5Tags)) {
                return $matches[0];
            }
            $id = $this->tagId++;
//FIXME this unpack is used multiple times at multiple places!
            $originalTag = implode('', unpack('H*', $matches[0]));
            return str_replace($matches[0], '<protectedTag data-type="single" data-id="'.$id.'" data-content="'.$originalTag.'"/>', $matches[0]);
        };
        
        return preg_replace_callback('/(<[^><]+>)/is', $replacer, $segment);
    }
}