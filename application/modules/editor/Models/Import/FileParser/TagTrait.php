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
     * Return search and replace map
     * @var array
     */
    protected $protectedWhitespaceMap = [
        'search' => [
          "\r\n",  
          "\n",  
          "\r"
        ],
        'replace' => [
          '<hardReturn/>',
          '<softReturn/>',
          '<macReturn/>'
        ]
    ];
    
    /**
     * List of unicode characters to be protected 
     * @var array
     */
    protected $protectedUnicodeList = [
        '/\p{Co}/u', //Alle private use chars
        '/\x{2028}/u', //Hex UTF-8 bytes or codepoint 	E2 80 A8//schutzbedürftiger Whitespace + von mssql nicht vertragen
        '/\x{2029}/u', //Hex UTF-8 bytes 	E2 80 A9//schutzbedürftiger Whitespace + von mssql nicht vertragen
        //we do not escape that any more - mssql not in use '"\x{201E}"u', //Hex UTF-8 bytes 	E2 80 9E //von mssql nicht vertragen
        //we do not escape that any more - mssql not in use '"\x{201C}"u' //Hex UTF-8 bytes 	E2 80 9C//von mssql nicht vertragen
        '/\x{0009}/u', //Hex UTF-8 bytes or codepoint of horizontal tab
        '/\x{000B}/u', //Hex UTF-8 bytes or codepoint of vertical tab
        '/\x{000C}/u', //Hex UTF-8 bytes or codepoint of page feed
        '/\x{0085}/u', //Hex UTF-8 bytes or codepoint of control sign for next line
        '/\x{00A0}/u', //Hex UTF-8 bytes or codepoint of protected space
        '/\x{1680}/u', //Hex UTF-8 bytes or codepoint of Ogam space
        '/\x{180E}/u', //Hex UTF-8 bytes or codepoint of mongol vocal divider
        '/\x{2028}/u', //Hex UTF-8 bytes or codepoint of line separator
        '/\x{202F}/u', //Hex UTF-8 bytes or codepoint of small protected space
        '/\x{205F}/u', //Hex UTF-8 bytes or codepoint of middle mathematical space
        '/\x{3000}/u', //Hex UTF-8 bytes or codepoint of ideographic space
        '/[\x{2000}-\x{200A}]/u', //Hex UTF-8 bytes or codepoint of eleven different small spaces, Haarspatium and em space
    ]; //Hex UTF-8 bytes 	E2 80 9C//von mssql nicht vertragen
    
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
            $title = '<'.$this->shortTagIdent.'/>: ';
            
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
     * @return string $segment
     */
    protected function parseSegmentProtectWhitespace($segment) {
        $split = preg_split('#(<[^\s][^>]+>)#', $segment, null, PREG_SPLIT_DELIM_CAPTURE);
        
        $i = 0;
        foreach($split as $idx => $chunk) {
            if($i++ % 2 === 1 || strlen($chunk) == 0) {
                //ignore found tags in the content or empty chunks
                continue; 
            }
            
            $split[$idx] = $this->protectWhitespace($chunk, true);
        }
        return join($split);
    }
    
    /**
     * protects all whitespace and special characters coming from the import formats
     * @param string $textNode should not contain tags, since special characters in the tag content would also be protected then
     * @param bool $xmlBased defaults to true, decides how XML Entities are encoded, see inline comments 
     */
    protected function protectWhitespace($textNode, $xmlBased = true) {
        $textNode = $this->entityCleanup($textNode, $xmlBased);
        
        //replace only on real text
        $textNode = str_replace($this->protectedWhitespaceMap['search'], $this->protectedWhitespaceMap['replace'], $textNode);
        
        //protect multispaces and tabs
        $textNode = preg_replace_callback('/ ( +)|(\t+)/', function ($match) {
            //depending on the regex use the found spaces (match[1]) or the tabs (match[2]).
            if(empty($match[2])){
                //prepend the remaining whitespace before the space tag. 
                // Only the additional spaces are replaced as a tag
                // One space must remain in the content
                //Pay attention to the leading space on refactoring!
                return ' '.$this->maskSpecialContent('space', $match[1], strlen($match[1]));
            }
            //tab(s) are completely replaced with a tag
            return $this->maskSpecialContent('tab', $match[2], strlen($match[2]));
        }, $textNode);
        
        return preg_replace_callback($this->protectedUnicodeList, function ($match) {
            //always one single character is masked, so length = 1 
            return $this->maskSpecialContent('char', $match[0], 1);
        }, $textNode);
    }
    
    /**
     * Does the entity encoding, see inline comments
     * @param string $textNode
     * @param boolean $xmlBased
     * @return string
     */
    protected function entityCleanup($textNode, $xmlBased = true) {
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
    
    /**
     * Creates the internal Space/Tab/SpecialChar tags
     * @param string $type
     * @param string $toBeProtected
     * @param integer $length
     * @return string
     */
    protected function maskSpecialContent($type, $toBeProtected, $length) {
        return '<'.$type.' ts="' . implode(',', unpack('H*', $toBeProtected)) . '" length="'.(int)$length.'"/>';
    }
}
