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
 * trait containing tag mapping logic of import process
 * 
 * For refactoring the import process to a better understandable structure some code is moved into traits to keep refactoring steps small! 
 */
trait editor_Models_Import_FileParser_TagTrait {
    /**
     * counter for internal tags
     * @var integer
     */
    protected $shortTagIdent = 1;
    
    /**
     * @var editor_ImageTag_Left
     */
    protected $_leftTag = NULL;

    /**
     * @var editor_ImageTag_Right
     */
    protected $_rightTag = NULL;

    /**
     * @var editor_ImageTag_Single
     */
    protected $_singleTag = NULL;
    
    /**
     * @var editor_Models_Segment_Whitespace
     */
    protected $whitespaceHelper;
    
    /**
     *
     * @var array
     */
    protected $whitespaceTagList = [
        '#<(hardReturn)/>#',
        '#<(softReturn)/>#',
        '#<(macReturn)/>#',
        '#<(char) ts="[^"]*"( length="([0-9]+)")?/>#',
        '#<(tab) ts="[^"]*"( length="([0-9]+)")?/>#',
        '#<(space) ts="[^"]*"( length="([0-9]+)")?/>#',
    ];
    
    protected function initHelper(){
        if(empty($this->whitespaceHelper)) {
            $this->whitespaceHelper = ZfExtended_Factory::get('editor_Models_Segment_Whitespace');
        }
    }
    
    /**
     * to be called in the constructors
     */
    protected function initImageTags(){
        $this->_leftTag = ZfExtended_Factory::get('editor_ImageTag_Left');
        $this->_rightTag = ZfExtended_Factory::get('editor_ImageTag_Right');
        $this->_singleTag = ZfExtended_Factory::get('editor_ImageTag_Single');
    }
    
    /**
     * replaces whitespace placeholder tags with internal tags
     * @param string $segment
     * @return string
     */
    protected function whitespaceTagReplacer($segment) {
        return preg_replace_callback($this->whitespaceTagList, function($match) use ($segment) {
            $tag = $match[0];
            $tagName = $match[1];
            $cls = ' '.$tagName;
            $title = '&lt;'.$this->shortTagIdent.'/&gt;: ';
            
            //if there is no length attribute, use length = 1
            if(empty($match[2])) {
                $length = 1;
            }
            else {
                $length = $match[3]; //else use the stored length value
            }
            //generate the html tag for the editor
            switch ($match[1]) {
                // ↵    U+21B5      e2 86 b5    &crarr;     &#8629;     DOWNWARDS ARROW WITH CORNER LEFTWARDS
                //'hardReturn' => ['text' => '&lt;↵ hardReturn/&gt;'], //in title irgendwas mit <hardReturn/> 
                //'softReturn' => ['text' => '&lt;↵ softReturn/&gt;'], //in title irgendwas mit <softReturn/>
                //'macReturn' => ['text' => '&lt;↵ macReturn/&gt;'],  //in title irgendwas mit <macReturn/>
                case 'hardReturn':
                case 'softReturn':
                case 'macReturn':
                    $cls = ' newline';
                    $text = '↵';
                    $title .= 'Newline';
                    break;
                case 'space':
                    // ·    U+00B7      c2 b7       &middot;    &#183;      MIDDLE DOT
                    //'space' => ['text' => '&lt;·/&gt;'],
                    $text = str_repeat('·',$length);
                    $title .= $length.' whitespace character'.($length>1?'s':'');
                    break;
                case 'tab':
                    // →    U+2192      e2 86 92    &rarr;      &#8594;     RIGHTWARDS ARROW
                    //'tab' => ['text' => '&lt;→/&gt;'],
                    $text = str_repeat('→',$length);
                    $title .= $length.' tab character'.($length>1?'s':'');
                    break;
                case 'char':
                default:
                    //'char' => ['text' => 'protected Special character'],
                    if($tag == '<char ts="c2a0" length="1"/>'){
                //new type non breaking space: U+00A0
                //symbolyzed in word as: 
                //U+00B0	°	c2 b0	&deg;	° 	&#176;	° 	DEGREE SIGN
                //in unix tools:
                //U+23B5	⎵	e2 8e b5		&#9141;	⎵ 	BOTTOM SQUARE BRACKET
                        $text = '⎵';
                        $cls = ' nbsp';
                        $title .= 'Non breaking space';
                    }
                    else {
                        $text = 'protected Special-Character';
                        $title .= 'protected Special-Character';
                    }
            }
            $p = $this->getTagParams($tag, $this->shortTagIdent++, $tagName, $text);
            //FIXME refactor whole tagparams stuff!
            $p['class'] .= $cls; 
            $p['length'] = $length;
            $p['title'] = $title; //Only translatable with using ExtJS QTips in the frontend, as title attribute not possible!
            
            $tag = $this->_singleTag->getHtmlTag($p);
            return $tag;
        }, $segment);
    }
    
    /**
     * returns the parameters for creating the HtmlTags used in the GUI
     * @param string $tag
     * @param string $shortTag
     * @param string $tagId
     * @param string $text
     */
    protected function getTagParams($tag, $shortTag, $tagId, $text) {
        return array(
            'class' => $this->parseSegmentGetStorageClass($tag),
            'text' => $text,
            'shortTag' => $shortTag,
            'id' => $tagId, //mostly original tag id
        );
    }
    
    /**
     * Hilfsfunktion für parseSegment: Verpackung verschiedener Strings zur Zwischenspeicherung als HTML-Klassenname im JS
     *
     * @param string $tag enthält den Tag als String
     * @param string $tagName enthält den Tagnamen
     * @param boolean $locked gibt an, ob der übergebene Tag die Referenzierung auf einen gesperrten inline-Text im sdlxliff ist
     * @return string $id ID des Tags im JS
     */
    protected function parseSegmentGetStorageClass($tag) {
        $tagContent = preg_replace('"^<(.*)>$"', '\\1', $tag);
        if($tagContent == $tag){
            trigger_error('The Tag ' . $tag .
                    ' has not the structure of a tag.', E_USER_ERROR);
        }
        return implode('', unpack('H*', $tagContent));
    }
    
    /**
     * protects whitespace inside a segment with a tag
     *
     * @param string $segment
     * @param callable $textNodeCallback callback which is applied to the text node after protecting the whitespace
     * @return string $segment
     */
    protected function parseSegmentProtectWhitespace($segment, callable $textNodeCallback = null) {
        $split = preg_split('#(<[^\s][^>]+>)#', $segment, null, PREG_SPLIT_DELIM_CAPTURE);
        
        $i = 0;
        foreach($split as $idx => $chunk) {
            if($i++ % 2 === 1 || strlen($chunk) == 0) {
                //ignore found tags in the content or empty chunks
                continue; 
            }
            
            $split[$idx] = $this->whitespaceHelper->protectWhitespace($chunk, true);
            if(!empty($textNodeCallback)) {
                $split[$idx] = call_user_func($textNodeCallback, $split[$idx]);
            }
        }
        return join($split);
    }
}
