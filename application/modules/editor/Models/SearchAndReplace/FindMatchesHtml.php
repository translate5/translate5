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

/***
 * This class is used to find matches in the original segment text(html tags included) and return there start and end index.
 * The start and end index can be used as replace range.
 * The findContent function will return also the range content and the range content character length.
 * 
 * @author aleksandar
 *
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
    public $matchCase=false;
    
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
        
        //zero length dummy tag to mark start/end position of strings not beginning/ending with a tag
        array_unshift($this->tagMapping , [0 => '', 1 => 0, 'pos_in_content' => 0, 'sum_length' => 0 ]); 
        array_push($this->tagMapping , [0 => '', 1 => strlen($html)-1]); 
    }

    /***
     * Find the real pisition in the html text based on the match position from the text without html tags
     * 
     * @param int $contentPosition
     * @param bool $isEnd - search for end index
     * @return integer
     */
    public function findPosition($contentPosition,$isEnd=false){
        //binary search
        $idx = [true => 0, false => count($this->tagMapping)-1];
        while(1 < $idx[false] - $idx[true]){
            // integer half of both array indexes
            $i = ($idx[true] + $idx[false]) >>1;

            if($isEnd){
                $idx[$this->tagMapping[$i]['pos_in_content'] < $contentPosition] = $i;
            }else{
                $idx[$this->tagMapping[$i]['pos_in_content'] <= $contentPosition] = $i;
            }
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
     * @param bool $isRegex is a regular expression search
     * @return array 
     */
    public function findContent($searchQuery,$searchType){
        
        $isRegex = ($searchType === "regularExpressionSearch" || $searchType === "wildcardsSearch");
        if($searchType === "wildcardsSearch"){

            //posible search special characters special characters
            $special_chars = "\.+^$[]()|{}/'#";
            $special_chars = str_split($special_chars);
            $escape = array();
            foreach ($special_chars as $char){
                $escape[$char] = "\\$char";
            } 
            
            //escape the special chars if there are some
            $searchQuery= strtr($searchQuery, $escape);
            $searchQuery= strtr($searchQuery, array(
                    '*' => '.*', // 0 or more (lazy) - asterisk (*)
                    '?' => '.', // 1 character - question mark (?)
            ));
        }
        
        if(!$isRegex){
            $searchQuery= preg_quote($searchQuery, '~');
        }
        
        $inCase = $this->matchCase ? '' : 'i';
        
        //create the search patern
        $searchPattern=$isRegex ? "#".$searchQuery.'#'.$inCase : "~{$searchQuery}.*?~su$inCase";

        //find matches in the text without html in it
        preg_match_all($searchPattern, $this->cleanText, $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE);
        
        $collectedValues=[];
        //for each match, find the start and end ingex in the original segment text
        foreach($matches[0] as &$match){
            //get the start position index
            $posStart = $this->findPosition($match[1]);
            
            //get the end position ingex
            $posEnd   = $this->findPosition($match[1] + strlen($match[0]),true);
        
            //substrakt the range piece from the original text
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