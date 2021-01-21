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
 * TODO instead of using this trait the tag creation should go into editor_Models_Import_FileParser_Tag
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
        '#<(char) ts="([^"]*)"( length="([0-9]+)")?/>#',
        '#<(tab) ts="([^"]*)"( length="([0-9]+)")?/>#',
        '#<(space) ts="([^"]*)"( length="([0-9]+)")?/>#',
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
     *
     * @param string $segment
     * @return string
     */
    protected function protectedTagReplacer(string $segment): string {
        $shortTagNrMap = [];
        return preg_replace_callback('#<protectedTag data-type="([^"]*)" data-id="([0-9]+)" data-content="([^"]*)"/>#', function($match) use (&$shortTagNrMap) {
            $type = $match[1];
            $id = $match[2];
            $content = pack('H*', $match[3]);
            
            $p = $this->getTagParams($content, 0, $id, htmlspecialchars($content));
            
            //generate the html tag for the editor
            switch ($type) {
                case 'open':
                    $p['shortTag'] = $this->shortTagIdent++;
                    $shortTagNrMap[$id] = $p['shortTag'];
                    return $this->_leftTag->getHtmlTag($p);
                case 'close':
                    //on tag protection it is ensured that tag pairs are wellformed, so on close we can rely that open nr exists:
                    $p['shortTag'] = $shortTagNrMap[$id];
                    return $this->_rightTag->getHtmlTag($p);
                case 'single':
                default:
                    $p['shortTag'] = $this->shortTagIdent++;
                    return $this->_singleTag->getHtmlTag($p);
            }
        }, $segment);
    }
    
    /**
     * replaces whitespace placeholder tags with internal tags
     * @param string $segment
     * @param array $tagShortcutNumberMap
     * @return string
     */
    protected function whitespaceTagReplacer($segment, array $tagShortcutNumberMap = []) {
        
        //FIXME should be called separatly
        $segment = $this->protectedTagReplacer($segment);
        
        //$tagShortcutNumberMap must be given explicitly here as non referenced variable from outside,
        // so that each call of the whitespaceTagReplacer function has its fresh list of tag numbers
        return preg_replace_callback($this->whitespaceTagList, function($match) use (&$tagShortcutNumberMap,$segment) {
            $tag = $match[0];
            $tagName = $match[1];
            $cls = ' '.$tagName;
            
            //either we get a reusable shortcut number in the map, or we have to increment one
            if(empty($tagShortcutNumberMap) || empty($tagShortcutNumberMap[$tag])) {
                $shortTagNumber = $this->shortTagIdent++;
            }
            else {
                $shortTagNumber = array_shift($tagShortcutNumberMap[$tag]);
            }
            $title = '&lt;'.$shortTagNumber.'/&gt;: ';
            
            
            //if there is no length attribute, use length = 1
            if(empty($match[3])) {
                $length = 1;
            }
            else {
                $length = $match[4]; //else use the stored length value
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
            $p = $this->getTagParams($tag, $shortTagNumber, $tagName, $text);
            //FIXME refactor whole tagparams stuff!
            $p['class'] .= $cls;
            $p['length'] = $length;
            $p['title'] = $title; //Only translatable with using ExtJS QTips in the frontend, as title attribute not possible!
            
            return $this->_singleTag->getHtmlTag($p);
        }, $segment);
    }
    
    /**
     * returns the parameters for creating the HtmlTags used in the GUI
     * @param string $tag
     * @param string $shortTag
     * @param string $tagId
     * @param string $text
     * @param boolean $xmlTags optional, by default true, may be disabled for example if no XML content should be replaced by regex with a custom tag
     */
    protected function getTagParams($tag, $shortTag, $tagId, $text, $xmlTags = true) {
        return [
            'class' => $this->parseSegmentGetStorageClass($tag, $xmlTags),
            'text' => $text,
            'shortTag' => $shortTag,
            'id' => $tagId, //mostly original tag id
        ];
    }
    
    /**
     * helper for parseSegment: encode the tag content without leading and trailing <>
     * checks if $tag starts with < and ends with >
     *
     * @param string $tag contains the tag
     * @param boolean $xmlTags true if the tags are XMLish (so starting and ending with < and >)
     * @return string encoded tag content
     */
    protected function parseSegmentGetStorageClass(string $tag, bool $xmlTags): string {
        if($xmlTags) {
            if(substr($tag, 0, 1) !== '<' || substr($tag, -1) !== '>'){
                trigger_error('The Tag ' . $tag . ' has not the structure of a tag.', E_USER_ERROR);
            }
            //we store the tag content without leading < and trailing >
            //since we expect to cut of just two ascii characters no mb_ function is needed, the UTF8 content inbetween is untouched
            $tag = substr($tag, 1, -1);
        }
        return implode('', unpack('H*', $tag));
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
            
            $split[$idx] = $this->whitespaceHelper->protectWhitespace($chunk, true,true);
            if(!empty($textNodeCallback)) {
                $split[$idx] = call_user_func($textNodeCallback, $split[$idx]);
            }
        }
        return join($split);
    }
}
