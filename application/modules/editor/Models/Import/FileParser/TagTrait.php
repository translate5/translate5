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
        '"\x{0009}"u', //Hex UTF-8 bytes or codepoint of horizontal tab
        '"\x{000B}"u', //Hex UTF-8 bytes or codepoint of vertical tab
        '"\x{000C}"u', //Hex UTF-8 bytes or codepoint of page feed
        '"\x{0085}"u', //Hex UTF-8 bytes or codepoint of control sign for next line
        '"\x{00A0}"u', //Hex UTF-8 bytes or codepoint of protected space
        '"\x{1680}"u', //Hex UTF-8 bytes or codepoint of Ogam space
        '"\x{180E}"u', //Hex UTF-8 bytes or codepoint of mongol vocal divider
        '"\x{2028}"u', //Hex UTF-8 bytes or codepoint of line separator
        '"\x{202F}"u', //Hex UTF-8 bytes or codepoint of small protected space
        '"\x{205F}"u', //Hex UTF-8 bytes or codepoint of middle mathematical space
        '"\x{3000}"u', //Hex UTF-8 bytes or codepoint of ideographic space
        '"[\x{2000}-\x{200A}]"u', //Hex UTF-8 bytes or codepoint of eleven different small spaces, Haarspatium and em space
    ]; //Hex UTF-8 bytes 	E2 80 9C//von mssql nicht vertragen
    
    /**
     *
     * @var array
     */
    protected $whitespaceTagList = [
        '#<hardReturn/>#',
        '#<softReturn/>#',
        '#<macReturn/>#',
        '#<space ts="[^"]*"/>#',
    ];
    
    /**
     * defines the GUI representation of internal used tags for masking special characters  
     * @var array
     */
    protected $_tagMapping = array(
        'hardReturn' => array('text' => '&lt;hardReturn/&gt;', 'imgText' => '<hardReturn/>'),
        'softReturn' => array('text' => '&lt;softReturn/&gt;', 'imgText' => '<softReturn/>'),
        'macReturn' => array('text' => '&lt;macReturn/&gt;', 'imgText' => '<macReturn/>'),
        'space' => array('text' => '&lt;space/&gt;', 'imgText' => '<space/>'));

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
            $tagName = preg_replace('"<([^/ ]*).*>"', '\\1', $tag);
            if(!isset($this->_tagMapping[$tagName])) {
                trigger_error('The used tag ' . $tagName .' is undefined! Segment: '.$segment, E_USER_ERROR);
            }
            $fileNameHash = md5($this->_tagMapping[$tagName]['imgText']);
            
            //generate the html tag for the editor
            $p = $this->getTagParams($tag, $this->shortTagIdent++, $tagName, $fileNameHash);
            $tag = $this->_singleTag->getHtmlTag($p);
            $this->_singleTag->createAndSaveIfNotExists($this->_tagMapping[$tagName]['imgText'], $fileNameHash);
            return $tag;
        }, $segment);
    }
    
    /**
     * returns the parameters for creating the HtmlTags used in the GUI
     * @param string $tag
     * @param string $shortTag
     * @param string $tagId
     * @param string $fileNameHash
     * @param string $text
     */
    protected function getTagParams($tag, $shortTag, $tagId, $fileNameHash, $text = false) {
        if($text === false) {
            $text = $this->_tagMapping[$tagId]['text'];
        }
        return array(
            'class' => $this->parseSegmentGetStorageClass($tag),
            'text' => $text,
            'shortTag' => $shortTag,
            'id' => $tagId, //mostly original tag id
            'filenameHash' => $fileNameHash,
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
            $textNode = htmlentities(html_entity_decode($textNode), ENT_XML1);
        }
        else {
            // for non XML based formats (for example CSV) all content and its contained entities are displayed to the user as they were in the import file
            // therefore we have just to encode the < > & characters.
            // so if the CSV contains &amp; ß < this would be converted to &amp;amp; ß &gt; to be displayed correctly in the browser
            $textNode = htmlentities($textNode, ENT_XML1);
        }
        
        //replace only on real text
        $textNode = str_replace($this->protectedWhitespaceMap['search'], $this->protectedWhitespaceMap['replace'], $textNode);
        
        //protect multispaces and tabs
        $textNode = preg_replace_callback('/ ( +)|(\t+)/', function ($match) {
            //depending on the regex use the found spaces (match[1]) or the tabs (match[2]).
            $content = empty($match[1]) ? $match[2] : $match[1];
            $result = $this->makeInternalSpace($content);
            if(empty($match[2])){
                //prepend the remaining whitespace before the space tag. 
                // Only the additional spaces are replaced as a tag
                // One space must remain in the content
                //Pay attention to the leading space on refactoring!
                return ' '.$result;
            }
            //tab(s) are completly replaced with an tag
            return $result;
        }, $textNode);
        
        return preg_replace_callback($this->protectedUnicodeList, function ($match) {
            return $this->makeInternalSpace($match[0]);
        }, $textNode);
    }
    
    /**
     * Creates the internal Space tag
     * @param string $toBeProtected
     * @return string
     */
    protected function makeInternalSpace($toBeProtected) {
        return '<space ts="' . implode(',', unpack('H*', $toBeProtected)) . '"/>';
    }
}
