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

class editor_Models_SearchAndReplace_FindMatchesHtml{
    
    /***
     * The initial html text 
     * @var string
     */
    protected $html='';
    
    /***
     * Text without tags
     * @var string
     */
    protected $cleanText='';
    
    /***
     * Tags possition mapping
     * @var array
     */
    protected $tagMapping=[];
    
    
    /***
     * Current tag index used by the findPossition
     * @var integer
     */
    protected $currentTagIdx=null;
    
    /***
     * Case insensitive search
     * @var string
     */
    const MATCH_CASE_INSENSITIVE=true;
    
    public function __construct($html){
        $this->setHtml($html);
    }

    /***
     * Set the html text and tags mapping
     * @param string $html
     */
    public function setHtml($html){
        $this->html = $html;
        $regexp = '~<.*?>~su';
        preg_match_all($regexp, $html, $this->tagMapping, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE);
        $this->tagMapping = $this->tagMapping[0];
        
        //remove the tags from the html
        $this->cleanText = preg_replace($regexp, '', $html);
        
        //convert positions to plain content
        $sum_length = 0;
        foreach($this->tagMapping as &$tag){ 
            $tag['pos_in_content'] = $tag[1] - $sum_length;
            $tag['sum_length'    ] = $sum_length += strlen($tag[0]);
        }
        
        //zero length dummy tagMapping to mark start/end position of strings not beginning/ending with a tag
        array_unshift($this->tagMapping , [0 => '', 1 => 0, 'pos_in_content' => 0, 'sum_length' => 0 ]); 
        array_push($this->tagMapping , [0 => '', 1 => strlen($html)-1]); 
    }

    /***
     * Find the real pisition in the html text based on the match position from the text without html tags
     * @param integer $contentPosition
     * @return integer
     */
    public function findPosition($contentPosition){
        //binary search
        $idx = [true => 0, false => count($this->tagMapping)-1];
        while(1 < $idx[false] - $idx[true]){ 
            // integer half of both array indexes
            $i = ($idx[true] + $idx[false]) >>1;

            //$idx[$this->tagMapping[$i]['pos_in_content'] <= $contentPosition] = $i;
            // hold one index less and the other greater
            $idx[$this->tagMapping[$i]['pos_in_content'] < $contentPosition] = $i;
            
        }
        
        $this->currentTagIdx = $idx[true];
        return $this->tagMapping[$this->currentTagIdx]['sum_length'] + $contentPosition;
    }
    
    /***
     * Find matches in the html text.
     * The return value is an array with multiple fields:
     * text -> returns the match text (with html tags included between)
     * start -> start index of the match in the html string
     * end -> end index of the match in the html string
     * length -> the match content length (html tags included)
     * 
     * @param string $searchQuery the search string
     * @param boolean $isRegex is a regular expression search
     * @return array 
     */
    public function findContent($searchQuery,$isRegex=false){
        
        if(!$isRegex){
            $searchQuery= preg_quote($searchQuery, '~');
        }
        
        $icase = self::MATCH_CASE_INSENSITIVE ? 'i' : '';
        
        $searchPattern=$isRegex ? "#".$searchQuery.'#' : "~{$searchQuery}.*?~su$icase";

        //find matches in the text without html in it
        preg_match_all($searchPattern, $this->cleanText, $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE);
        
        $collectedValues=[];
        foreach($matches[0] as &$match){
            $posStart = $this->findPosition($match[1]);
            $posEnd   = $this->findPosition($match[1] + strlen($match[0]));
        
            $text =substr($this->html, $posStart, $posEnd- $posStart);
            
            $collectedValues[]=[
                    'text'=>$text,
                    'start'=>$posStart,
                    'end'=>$posEnd,
                    'length'=>$posEnd- $posStart
            ];
        };
        return $collectedValues;
    }
}