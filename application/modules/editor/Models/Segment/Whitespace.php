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
 * Helper Class which encapsulates segment whitespace handling
 */
class editor_Models_Segment_Whitespace {
    /**
     * All entities are restored to their applicable characters (&_szlig; => ß), only the XML relevant &<> are encoded (ready for GUI)
     * @var string
     */
    const ENTITY_MODE_RESTORE = 'restore';
    
    /**
     * Nothing is restored, but encoded (&_szlig; => &_amp;szlig;), only the XML relevant &<> are encoded (ready for GUI)
     * @var string
     */
    const ENTITY_MODE_KEEP = 'keep';
    
    /**
     * Entity handling is disabled, entities must be handled elsewhere!
     * @var string
     */
    const ENTITY_MODE_OFF = 'off';
    
    const WHITESPACE_TAGS = ['hardReturn', 'softReturn', 'macReturn', 'space', 'tab', 'char'];
    
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
        '/\x{0003}/u', //Hex UTF-8 bytes or codepoint of END OF TEXT //TS-240
        '/\x{0008}/u', //Hex UTF-8 bytes or codepoint of backspace
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
        '/\x{200B}/u', //Hex UTF-8 bytes or codepoint of zero width space
        '/\x{FEFF}/u', //Hex UTF-8 bytes or codepoint of ZERO WIDTH NO-BREAK SPACE / unicode BOM
        '/[\x{2000}-\x{200A}]/u', //Hex UTF-8 bytes or codepoint of eleven different small spaces, Haarspatium and em space
    ]; //Hex UTF-8 bytes 	E2 80 9C//von mssql nicht vertragen
    
    
    /**
     * protects all whitespace and special characters coming from the import formats
     * WARNING: should be called only on plain text fragments without tags!
     * @param string $textNode should not contain tags, since special characters in the tag content would also be protected then
     * @param bool $xmlBased defaults to true, decides how XML Entities are encoded, see inline comments
     */
    public function protectWhitespace($textNode, $entityHandling = self::ENTITY_MODE_RESTORE) {
        //definition how entities are handled:
        if($entityHandling != self::ENTITY_MODE_OFF) {
            $textNode = editor_Models_Segment_Utility::entityCleanup($textNode, $entityHandling == self::ENTITY_MODE_RESTORE);
        }
        
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
            
        //in XML based import formats we have to extend the list about some HTML entities representing some none printable characters in UTF8
        if($entityHandling == self::ENTITY_MODE_RESTORE) {
            //see https://stackoverflow.com/questions/9587751/decoding-numeric-html-entities-via-php
            // and https://caves.org.uk/charset_test.html  Section: Another Problem with PHP's htmlentities()
            //since entityCleanup was called aready, we have to begin the regex with &amp; instead &
            $textNode = preg_replace_callback('/&amp;#(128|129|1[3-5][0-9]);/', function ($match) {
                //always one single character is masked, so length = 1
                return $this->maskSpecialContent('char', '&#'.$match[1].';', 1);
            }, $textNode);
        }
        
        return preg_replace_callback($this->protectedUnicodeList, function ($match) {
            //always one single character is masked, so length = 1
            return $this->maskSpecialContent('char', $match[0], 1);
        }, $textNode);
    }
    
    /**
     * unprotects tag protected whitespace inside the given segment content
     * keep attention to the different invocation points for this method!
     * @param string $content
     * @return string
     */
    public function unprotectWhitespace($content) {
        $search = array(
            '<hardReturn/>',
            '<softReturn/>',
            '<macReturn/>',
            '<hardReturn />',
            '<softReturn />',
            '<macReturn />',
            //the string "EFBBBF" "ZERO WIDTH NO-BREAK SPACE" BOM can be savly removed, since it was inserted by the frontend as internal marker which was not removed properly
            chr(0xEF).chr(0xBB).chr(0xBF),
        );
        $replace = array(
            "\r\n",
            "\n",
            "\r",
            "\r\n",
            "\n",
            "\r",
            '',
        );
        $content = str_replace($search, $replace, $content);
        return preg_replace_callback('#<(space|char|tab) ts="([A-Fa-f0-9]*)"( length="[0-9]+")?/>#', function ($match) {
            return pack('H*', $match[2]);
        }, $content);
    }
    
    /**
     * Creates the internal Space/Tab/SpecialChar tags
     * @param string $type
     * @param string $toBeProtected
     * @param int $length
     * @return string
     */
    protected function maskSpecialContent($type, $toBeProtected, $length) {
        return '<'.$type.' ts="' . implode(',', unpack('H*', $toBeProtected)) . '" length="'.(int)$length.'"/>';
    }
}