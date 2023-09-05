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
    
    /**
     * @var array
     */
    const WHITESPACE_TAGS = [
        'hardReturn',
        'softReturn',
        'macReturn',
        'space',
        'tab',
        'char',
    ];

    /**
     * general replacement character for unknown characters (U+FFFD)
     */
    const LABEL_REPLACEMENT_CHARACTER = '�';

    /**
     * Label for spaces
     * U+00B7      c2 b7       &middot;    &#183;      MIDDLE DOT
     */
    const LABEL_SPACE = '·';

    /**
     * Label for newlines
     * U+21B5      e2 86 b5    &crarr;     &#8629;     DOWNWARDS ARROW WITH CORNER LEFTWARDS
     */
    const LABEL_NEWLINE = '↵';

    /**
     * Label for tabs
     * U+2192      e2 86 92    &rarr;      &#8594;     RIGHTWARDS ARROW
     */
    const LABEL_TAB = '→';

    /**
     * Label for nbsb
     * U+23B5	⎵	e2 8e b5		&#9141;	⎵ 	BOTTOM SQUARE BRACKET
     */
    const LABEL_NBSP = '⎵';

    /**
     * Label for characters
     * U+25A1 White Square as replacement for characters
     */
    const LABEL_CHARACTER = '□';

    /**
     * Return search and replace map
     * @var array
     */
    protected array $protectedWhitespaceMap = [
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
    const PROTECTED_CHARACTERS = [
        //array semantics:
        // 'regex'     => ['utf8 encoding as done by our unpack', 'name as from https://www.compart.com/en/unicode/'],
        //NOT SPACE CHARACTERs
        '/\p{Co}/u'   => [], //All private use chars, as HTML &#xE000; empty array to trigger default text
        '/\x{0003}/u' => ['ts' => '03', 'text' => '[ETX]', 'title' => 'End of Text (ETX)'], //TS-240
        '/\x{0008}/u' => ['ts' => '08', 'text' => '[BS]', 'title' => 'Backspace (BS)'],

        //SPACE CHARACTERs - TODO move that whitespace types to the space type of internal tags
        '/\x{0009}/u' => ['ts' => '09', 'text' => '[HT]', 'title' => 'Character Tabulation (HT,TAB)'],
        '/\x{000B}/u' => ['ts' => '0b', 'text' => '[VT]', 'title' => 'Line Tabulation (VT)'],
        '/\x{000C}/u' => ['ts' => '0c', 'text' => '[FF]', 'title' => 'Form Feed (FF)'],
        '/\x{0085}/u' => ['ts' => 'c285', 'text' => '[NEL]', 'title' => 'Next Line (NEL)'],
        '/\x{00A0}/u' => ['ts' => 'c2a0', 'text' => self::LABEL_NBSP, 'title' => 'No-Break Space (NBSP)'],
        '/\x{1680}/u' => ['ts' => 'e19a80', 'text' => self::LABEL_CHARACTER, 'title' => 'Ogham Space Mark'],
        '/\x{180E}/u' => ['ts' => 'e1a08e', 'text' => '[MVS]', 'title' => 'Mongolian Vowel Separator (MVS)'],
        '/\x{2000}/u' => ['ts' => 'e28080', 'text' => self::LABEL_CHARACTER, 'title' => 'En Quad'],
        '/\x{2001}/u' => ['ts' => 'e28081', 'text' => self::LABEL_CHARACTER, 'title' => 'Em Quad'],
        '/\x{2002}/u' => ['ts' => 'e28082', 'text' => self::LABEL_CHARACTER, 'title' => 'En Space'],
        '/\x{2003}/u' => ['ts' => 'e28083', 'text' => self::LABEL_CHARACTER, 'title' => 'Em Space'],
        '/\x{2004}/u' => ['ts' => 'e28084', 'text' => self::LABEL_CHARACTER, 'title' => 'Three-Per-Em Space'],
        '/\x{2005}/u' => ['ts' => 'e28085', 'text' => self::LABEL_CHARACTER, 'title' => 'Four-Per-Em Space'],
        '/\x{2006}/u' => ['ts' => 'e28086', 'text' => self::LABEL_CHARACTER, 'title' => 'Six-Per-Em Space'],
        '/\x{2007}/u' => ['ts' => 'e28087', 'text' => self::LABEL_CHARACTER, 'title' => 'Figure Space'],
        '/\x{2008}/u' => ['ts' => 'e28088', 'text' => self::LABEL_CHARACTER, 'title' => 'Punctuation Space'],
        '/\x{2009}/u' => ['ts' => 'e28089', 'text' => self::LABEL_CHARACTER, 'title' => 'Thin Space'],
        '/\x{200A}/u' => ['ts' => 'e2808a', 'text' => self::LABEL_CHARACTER, 'title' => 'Hair Space'],
        '/\x{200B}/u' => ['ts' => 'e2808b', 'text' => '[ZWSP]', 'title' => 'Zero Width Space (ZWSP)'],
        '/\x{2028}/u' => ['ts' => 'e280a8', 'text' => '[LS]', 'title' => 'Line Separator'],
        '/\x{2029}/u' => ['ts' => 'e280a9', 'text' => '[PS]', 'title' => 'Paragraph Separator'],
        '/\x{202F}/u' => ['ts' => 'e280af', 'text' => '[NNBSP]', 'title' => 'Narrow No-Break Space (NNBSP)'],
        '/\x{205F}/u' => ['ts' => 'e2819f', 'text' => '[MMSP]', 'title' => 'Medium Mathematical Space (MMSP)'],
        '/\x{3000}/u' => ['ts' => 'e38080', 'text' => self::LABEL_CHARACTER, 'title' => 'Ideographic Space'],
        '/\x{FEFF}/u' => ['ts' => 'efbbbf', 'text' => '[BOM]', 'title' => 'Zero Width No-Break Space (BOM, ZWNBSP)'],
    ];

    /**
     * short tag incrementor, initialized from outside
     * @var int
     */
    private int $currentShortTagNumber;

    /**
     * tag map for usage in language resources
     * @var array
     */
    private array $tagShortcutNumberMap = [];

    private array $protectedCharLabels = [];

    /**
     * public flag if tagShortcutNumbers should be collected on usage into $this->tagShortcutNumberMap
     * @var bool
     */
    public bool $collectTagNumbers = false;

    public function __construct()
    {
        $labeledCharacters = array_filter(self::PROTECTED_CHARACTERS);
        $this->protectedCharLabels = array_combine(array_column($labeledCharacters, 'ts'), $labeledCharacters);
    }

    /**
     * protects all whitespace and special characters coming from the import formats
     * WARNING: should be called only on plain text fragments without tags!
     * @param string $textNode should not contain tags, since special characters in the tag content would also be protected then
     * @param string $entityHandling defaults to ENTITY_MODE_RESTORE, decides how XML Entities are encoded, see inline comments
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
            // 2022 additional Info - the here escaped characters 128 - 159 are non printable
            // control characters (C1 Controls) - therefore we escape them.
            // Attention caveat: copying the character &#128; into the browser vonverts it to € - assuming that not UTF8 but wincp was used!
            $textNode = preg_replace_callback('/&amp;#(128|129|1[3-5][0-9]);/', function ($match) {
                //always one single character is masked, so length = 1
                return $this->maskSpecialContent('char', '&#'.$match[1].';', 1);
            }, $textNode);
        }

        $regexList = array_keys(self::PROTECTED_CHARACTERS);
        return preg_replace_callback($regexList, function ($match) {
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
     * convert whitespace-tags with their, so to say, placeholder-characters.
     *
     * This is done for several reasons:
     * 1.Prevent 'hello<tab..>world' to be converted to 'helloworld', so no even ordinary space between those words is kept
     * 2.Make it more visually obvious for developers what's inside by looking at segment's *EditToSort-column contents directly in database
     * 3.Make it simplier for Editor.plugins.SpellCheck.controller.Editor.mindTags() to calculate words/phrases coords adjustments
     *
     * @param string $content
     * @return string
     */
    public function convertForStripping($content) {
        $pattern = '#<('.join('|', self::WHITESPACE_TAGS).')( ts="([A-Fa-f0-9]*)")?( length="([0-9]+)")? ?/>#';
        return preg_replace_callback($pattern, function ($match) {
            $wholeTag = $match[0];
            $tagName = $match[1];
            $length = (array_key_exists(5, $match) && strlen($match[5]) > 0) ? $match[5] : '1'; //if length not given defaults to 1
            $renderData = $this->getTagRenderData($tagName, $length, $wholeTag);
            $tagLabel = $renderData['text'];
            if($tagName == 'char' && mb_strlen($tagLabel) > 1) {
                //characters may not have a length, but labels with a longer text "[BOM]",
                // therefore we replace them with one replacement char
                return self::LABEL_REPLACEMENT_CHARACTER;
            }
            return $tagLabel;
        }, $content);
    }

    /**
     * replaces some of the nice labeled special characters back to their original content
     * @param string $content
     * @return string
     */
    public static function replaceLabelledCharacters(string $content): string {
        return str_replace([
            self::LABEL_NBSP,
            self::LABEL_NEWLINE,
            self::LABEL_TAB,
            self::LABEL_SPACE
            // Note: first item in the second arg is not an ordinary space having code 32 (20),
            // but is a non-breaking space having code 160 (C2A0)
        ], [" ", "\n", "\t", ' '], $content);
    }
    
    /**
     * Creates the internal Space/Tab/SpecialChar tags
     * @param string $type
     * @param string $toBeProtected
     * @param int $length
     * @return string
     */
    protected function maskSpecialContent(string $type, string $toBeProtected, int $length): string {
        return '<'.$type.' ts="' . implode(',', unpack('H*', $toBeProtected)) . '" length="'.(int)$length.'"/>';
    }

    /**
     * replaces protected tag placeholder tags with internal tags
     * @param string $type
     * @param string $id
     * @param string $content
     * @return editor_Models_Import_FileParser_Tag
     */
    protected function handleProtectedTags(string $type, string $id, string $content): editor_Models_Import_FileParser_Tag {
        $content = pack('H*', $content);

        //generate the html tag for the editor
        switch ($type) {
            case 'open':
                $type = editor_Models_Import_FileParser_Tag::TYPE_OPEN;
                $shortTag = $this->currentShortTagNumber++;
                $this->tagShortcutNumberMap[$id] = $shortTag;
                break;
            case 'close':
                //on tag protection it is ensured that tag pairs are wellformed, so on close we can rely that open nr exists:
                $type = editor_Models_Import_FileParser_Tag::TYPE_CLOSE;
                $shortTag = $this->tagShortcutNumberMap[$id];
                break;
            case 'single':
            default:
                $type = editor_Models_Import_FileParser_Tag::TYPE_SINGLE;
                $shortTag = $this->currentShortTagNumber++;
                break;
        }
        $tag = new editor_Models_Import_FileParser_Tag($type);
        $tag->originalContent = $content;
        $tag->tagNr = $shortTag;
        $tag->tag = 'protectedTag';
        $tag->id = $id;
        $tag->rid = $id;
        $tag->text = htmlspecialchars($content);
        $tag->renderTag();
        return $tag;
    }

    /**
     * replaces the placeholder tags (<protectedTag> / <hardReturn> / <char> / <space> etc) with an internal tag
     * @param string $segment
     * @param int $shortTagIdent
     * @param array $tagShortcutNumberMap shorttag numbers can be provided from outside (needed for language resource usage)
     * @return string
     */
    public function convertToInternalTagsFromService(string $segment, int &$shortTagIdent, array $tagShortcutNumberMap = []): string {
        //$tagShortcutNumberMap must be given explicitly here as non referenced variable from outside,
        // so that each call of the whitespaceTagReplacer function has its fresh list of tag numbers
        $this->tagShortcutNumberMap = $tagShortcutNumberMap;
        return $this->convertToInternalTags($segment,$shortTagIdent);
    }

    /**
     * replaces the placeholder tags (<protectedTag> / <hardReturn> / <char> / <space> etc) with an internal tag
     * @param string $segment
     * @param int $shortTagIdent
     * @param array $xmlChunks
     * @return string
     */
    public function convertToInternalTags(string $segment, int &$shortTagIdent, array &$xmlChunks = []): string {
        $this->currentShortTagNumber = &$shortTagIdent;

        $xml = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser', [['normalizeTags' => false]]);

        $xml->registerElement(join(', ', self::WHITESPACE_TAGS), null, function ($tagName, $key, $opener) use ($xml){
            //if there is no length attribute, use length = 1
            $length = $xml->getAttribute($opener['attributes'], 'length', 1);
            $xml->replaceChunk($key, $this->handleWhitespaceTags($xml->getChunk($key), $tagName, $length));
        });

        $xml->registerElement('protectedTag', null, function ($tag, $key, $opener) use ($xml){
            $type = $xml->getAttribute($opener['attributes'], 'data-type');
            $id = $xml->getAttribute($opener['attributes'], 'data-id');
            $content = $xml->getAttribute($opener['attributes'], 'data-content');
            $xml->replaceChunk($key, $this->handleProtectedTags($type, $id, $content));
        });

        $validTags = self::WHITESPACE_TAGS;
        $validTags[] = 'protectedTag';
        $result = $xml->parse($segment, true, $validTags);
        $xmlChunks = $xml->getAllChunks();
        return $result;
    }

    /**
     * replaces whitespace placeholder tags with internal tags
     * @param string $wholeTag
     * @param string $tagName
     * @param string $length
     * @return editor_Models_Import_FileParser_Tag
     */
    private function handleWhitespaceTags(string $wholeTag, string $tagName, string $length): editor_Models_Import_FileParser_Tag {
        //if collecting, we just collect and do not check the map
        if($this->collectTagNumbers) {
            $this->tagShortcutNumberMap[$wholeTag][] = $shortTagNumber = $this->currentShortTagNumber++;
        }
        //tag numbers are not collected, we just look into the map
        else {
            //either we get a reusable shortcut number in the map, or we have to increment one
            if(empty($this->tagShortcutNumberMap) || empty($this->tagShortcutNumberMap[$wholeTag])) {
                $shortTagNumber = $this->currentShortTagNumber++;
            }
            else {
                $shortTagNumber = array_shift($this->tagShortcutNumberMap[$wholeTag]);
            }
        }
        $title = '&lt;'.$shortTagNumber.'/&gt;: ';
        $renderData = $this->getTagRenderData($tagName, $length, $wholeTag);
        $title .= $renderData['title'];

        $tagObj = new editor_Models_Import_FileParser_WhitespaceTag();
        $tagObj->originalContent = $wholeTag;
        $tagObj->rawContent = $this->unprotectWhitespace($wholeTag);
        $tagObj->tagNr = $shortTagNumber;
        $tagObj->id = $tagName;
        $tagObj->tag = $tagName;
        $tagObj->text = $renderData['text'];
        //title: Only translatable with using ExtJS QTips in the frontend, as title attribute not possible
        $tagObj->renderTag($length, $title, $renderData['cls']);
        return $tagObj;
    }

    /**
     * resets the internal tag number map
     */
    public function resetTagNumberMap()
    {
        $this->tagShortcutNumberMap = [];
    }

    /**
     * generate the text, title and cls for the internal whitespace tag
     *
     * @param string $tagName
     * @param string $length
     * @param string $wholeTag
     * @return array
     */
    private function getTagRenderData(string $tagName, string $length, string $wholeTag): array
    {
        $cls = ' '.$tagName;
        switch ($tagName) {
            // ↵    U+21B5      e2 86 b5    &crarr;     &#8629;     DOWNWARDS ARROW WITH CORNER LEFTWARDS
            //'hardReturn' => ['text' => '&lt;↵ hardReturn/&gt;'], //in title irgendwas mit <hardReturn/>
            //'softReturn' => ['text' => '&lt;↵ softReturn/&gt;'], //in title irgendwas mit <softReturn/>
            //'macReturn' => ['text' => '&lt;↵ macReturn/&gt;'],  //in title irgendwas mit <macReturn/>
            case 'hardReturn':
            case 'softReturn':
            case 'macReturn':
                $cls = ' newline';
                $text = self::LABEL_NEWLINE;
                $title = 'Newline';
                break;
            case 'space':
                $text = str_repeat(self::LABEL_SPACE, $length);
                $title = $length . ' whitespace character' . ($length > 1 ? 's' : '');
                break;
            case 'tab':

                $text = str_repeat(self::LABEL_TAB, $length);
                $title = $length . ' tab character' . ($length > 1 ? 's' : '');
                break;
            case 'char':
            default:
                //'char' => ['text' => 'protected Special character'],
                $ts = preg_replace('/<[^>]+ts="([^"]+)"[^>]*>/', '$1', $wholeTag);

                if (isset($this->protectedCharLabels[$ts])) {
                    $text = $this->protectedCharLabels[$ts]['text'];
                    $title = $this->protectedCharLabels[$ts]['title'];
                } else {
                    $text = self::LABEL_REPLACEMENT_CHARACTER;
                    $title = 'protected Special-Character';
                }

                //special handling for non breaking spaces: U+00A0
                if ($ts == 'c2a0') {
                    $cls = ' nbsp';
                }
        }
        return ['cls' => $cls, 'text' => $text, 'title' => $title];
    }
}