<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
			 https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/

/* 
 */
class editor_Utils {
    
    /**
     * Some general mappings to turn UTF chars to ascii chars (e.g. ü => ue, ß => sz, etc)
     * among other adjustments for unwanted special chars like +, (, )
     * TODO FIXME: this overlaps with the list of ligatures and digraphs, unify ...
     * @var array
     */
    private static $asciiMap = [
        'Ä' => 'ae', 'Ü' => 'ue', 'Ö' => 'oe', 'ä' => 'ae', 'ü' => 'ue', 'ö' => 'oe', 'ß' => 'ss', 'Þ' => 'th', 'þ' => 'th', 'Ð' => 'dh', 'ð' => 'dh', 'Œ' => 'oe', 'œ' => 'oe', 'Æ' => 'ae', 'æ' => 'ae', 'µ' => 'u', 'Š' => 's', 'Ž' => 'z', 'š' => 's',
        'ž' => 'z', 'Ÿ' => 'y', 'À' => 'a', 'Á' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Å' => 'a', 'Ç' => 'c', 'Č' => 'c', 'È' => 'e', 'É' => 'e', 'Ê' => 'e', 'Ë' => 'e', 'Ì' => 'i', 'Í' => 'i', 'Î' => 'i', 'Ï' => 'i', 'Ñ' => 'n', 'Ò' => 'o', 'Ó' => 'o',
        'Ô' => 'o', 'Õ' => 'o', 'Ø' => 'o', 'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ů' => 'u', 'Ý' => 'y', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'ç' => 'c', 'č' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i',
        'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o', 'ù' => 'u', 'ů' => 'u', 'û' => 'u', 'û' => 'u', 'ý' => 'y', 'ÿ' => 'y',
        '(' => '-', ')' => '-', '+' => 'plus', '&' => 'and', '#' => '-', '?' => '' ];
    /**
     * List of "funny" (unusual) whitespace characters in Hex-notation. These Characters usually are treated as normal whitespace and need to be replaced e.g. when segmenting the visual review files
     * @var array
     */
    private static $whitespaceChars = [
        '\u0009', //Hex UTF-8 bytes or codepoint of horizontal tab 	
        '\u000B', //Hex UTF-8 bytes or codepoint of vertical tab 
        '\u000C', //Hex UTF-8 bytes or codepoint of page feed 
        '\u0085', //Hex UTF-8 bytes or codepoint of control sign for next line 
        '\u00A0', //Hex UTF-8 bytes or codepoint of protected space  
        '\u1680', //Hex UTF-8 bytes or codepoint of Ogam space  
        '\u180E', //Hex UTF-8 bytes or codepoint of mongol vocal divider ᠎
        '\u2028', //Hex UTF-8 bytes or codepoint of line separator
        '\u202F', //Hex UTF-8 bytes or codepoint of small protected space  
        '\u205F', //Hex UTF-8 bytes or codepoint of middle mathematical space  
        '\u3000', //Hex UTF-8 bytes or codepoint of ideographic space 　
        // according to https://en.wikibooks.org/wiki/Unicode/Character_reference/2000-2FFF
        // all chars \u200[0..F] should/can be handled as special spaces (??)
        // so the rest of the chars are added.
        '\u2000',
        '\u2001',
        '\u2002',
        '\u2003',
        '\u2004',
        '\u2005',
        '\u2006',
        '\u2007',
        '\u2008',
        '\u2009',
        '\u200A',
        '\u200B', // ​
        '\u200C', // ‌
        '\u200D', // ‍
        '\u200E', // ‎
        '\u200F' // ‏
    ];
    /**
     * List of problematic characters (Characters that presumably have no meaning for the text) in Hex-notation
     * This are the following characters: , , , , , , , , , , , 
     * @var array
     */
    private static $problematicChars = [
        '\uE003',
        '\ue005',
        '\uE009',
        '\uE015',
        '\uE016',
        '\uE01B',
        '\uE01C',
        '\uE01D',
        '\uE01E',
        '\uE01F',
        '\uE043',
        '\uE062'
    ];
    /**
     * List of Ligatures with their Ascii replacements in Hex-notation
     * See: https://en.wikipedia.org/wiki/Typographic_ligature#Ligatures_in_Unicode_.28Latin_alphabets.29
     * @var array
     */
    private static $ligatures = [
        '\uA732' => 'AA', // Ꜳ
        '\uA733' => 'aa', // ꜳ
        '\u00C6' => 'AE', // Æ
        '\u00E6' => 'ae', // æ
        '\uA734' => 'AO', // Ꜵ
        '\uA735' => 'ao', // ꜵ
        '\uA736' => 'AU', // Ꜷ
        '\uA737' => 'au', // ꜷ
        '\uA738' => 'AV', // Ꜹ
        '\uA739' => 'av', // ꜹ
        '\uA73A' => 'AV', // Ꜻ
        '\uA73B' => 'av', // ꜻ
        '\uA73C' => 'AY', // Ꜽ
        '\uA73D' => 'ay', // ꜽ
        '\u1F670' => 'et', // ὧ0
        '\uFB00' => 'ff', // ﬀ
        '\uFB03' => 'ffi', // ﬃ
        '\uFB04' => 'ffl', // ﬄ
        '\uFB01' => 'fi', // ﬁ
        '\uFB02' => 'fl', // ﬂ
        '\u0152' => 'OE', // Œ
        '\u0153' => 'oe', // œ
        '\uA74E' => 'OO', // Ꝏ
        '\uA74F' => 'oo', // ꝏ
        '\u1E9E' => 'ſs', // ẞ
        '\u00DF' => 'ſz', // ß
        '\uFB06' => 'st', // ﬆ
        '\uFB05' => 'ſt', // ﬅ
        '\uA728' => 'TZ', // Ꜩ
        '\uA729' => 'tz', // ꜩ
        '\u1D6B' => 'ue', // ᵫ
        '\uA760' => 'VY', // Ꝡ
        '\uA761' => 'vy', // ꝡ
    ];
    /**
     * List of Digraphs with their Ascii replacements in Hex-notation
     * See https://en.wikipedia.org/wiki/Digraph_(orthography)#In_Unicode
     * @var array
     */
    private static $digraphs = [
        '\u01F1' => 'DZ', // Ǳ
        '\u01F2' => 'Dz', // ǲ
        '\u01F3' => 'dz', // ǳ
        '\u01C4' => 'DŽ', // Ǆ
        '\u01C5' => 'Dž', // ǅ
        '\u01C6' => 'dž', // ǆ
        '\u0132' => 'IJ', // Ĳ
        '\u0133' => 'ij', // ĳ
        '\u01C7' => 'LJ', // Ǉ
        '\u01C8' => 'Lj', // ǈ
        '\u01C9' => 'lj', // ǉ
        '\u01CA' => 'NJ', // Ǌ
        '\u01CB' => 'Nj', // ǋ
        '\u01CC' => 'nj', // ǌ
    ];
    /**
     * Ascifies a string
     * The string may still contains UTF chars after the conversion, this is just a "first step" ...
     * @param string $name
     * @return string
     */
    public static function asciify($name){
        $name = trim($name);
        $name = strtr($name, self::$asciiMap);
        $name = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('-', '.', ''), $name);
        return trim(preg_replace('/[\-]+/i', '-', $name), '-');
    }
    /**
     * Generates a websafe filename out of any string
     * The resulting string is lowercase and has all whitespace stripped, blanks are replaced with "-"
     * Take into account the returned string may be empty !
     * @param string $name
     * @return string
     */
    public static function secureFilename($name){
        // first, some ASCII transformations
        $name = self::asciify($name);
        // now for security and lowercase
        $name = strtolower(urlencode($name));
        $name = preg_replace('/%[0-9a-z][0-9a-z]/', '',  $name);
        $name = preg_replace('/-{2,}/', '-',  $name);
        return $name;
    }
    /**
     * Normalizes all sequences of whitespace to the given replacement (default: single blank)
     * @param string $text
     * @param string $replacement
     * @return string
     */
    public static function normalizeWhitespace($text, $replacement=' '){
        return preg_replace('/\s+/', $replacement, self::replaceFunnyWhitespace($text, $replacement));
    }
    /**
     * Replaces all funny whitespace chars (characters representing whitespace that are no blanks " ") with the replacement (default: single blank)
     * @param string $text
     * @param string $replacement
     * @return string
     */
    public static function replaceFunnyWhitespace($text, $replacement=' '){
        foreach(self::$whitespaceChars as $wsc){
            $text = str_replace(json_decode('"'.$wsc.'"'), $replacement, $text);
        }
        return $text;
    }
    /**
     * Replaces Ligatures with their ascii-representation in text
     * @param string $text
     * @return string
     */
    public static function replaceLigatures($text){
        foreach(self::$ligatures as $ligature => $replacement){
            $text = str_replace(json_decode('"'.$ligature.'"'), $replacement, $text);
        }
        return $text;
    }
    /**
     * Replaces Digraphs with their ascii-representation in text
     * @param string $text
     * @return string
     */
    public static function replaceDigraphs($text){
        foreach(self::$digraphs as $digraph => $replacement){
            $text = str_replace(json_decode('"'.$digraph.'"'), $replacement, $text);
        }
        return $text;
    }
    /**
     * Removes/Replace some chars which have no meaning for textual content and presumably are only "non printable characters"
     * While these are multibyte chars, a segmentation may be written inside these bytes an then the charcters are damaged
     * FIXME Stephan: Please add some DOC here how these chars affected the segmentation and how you found that out
     * @param string $text
     * @param string $replacement
     * @return string
     */
    public static function replaceProblematicChars($text, $replacement='') {
        foreach(self::$problematicChars as $char){
            $text = str_replace(json_decode('"'.$char.'"'), $replacement, $text);
        }
        return $text;
    }
    /**
     * splits the text up into HTML tags / entities on one side and plain text on the other side
     * The order in the array is important for the following wordBreakUp, since there are HTML tags and entities ignored.
     * Caution: The ignoring is done by the array index calculation there!
     * So make no array structure changing things between word and tag break up!
     *
     * @param string $text
     * @return array $text
     */
    public static  function tagBreakUp($text,$tagRegex='/(<[^<>]*>|&[^;]+;)/'){
        return preg_split($tagRegex, $text, NULL,  PREG_SPLIT_DELIM_CAPTURE);
    }
    /**
     * Zerlegt die Wortteile des segment-Arrays anhand der Wortgrenzen in ein Array,
     * welches auch die Worttrenner als jeweils eigene Arrayelemente enthält
     *
     * - parst nur die geraden Arrayelemente, denn dies sind die Wortbestandteile
     *   (aber nur weil tagBreakUp davor aufgerufen wurde und sonst das array nicht in der Struktur verändert wurde)
     *
     * @param array $segment
     * @return array $segment
     */
    public static function wordBreakUp($segment){
        $config = Zend_Registry::get('config');
        $regexWordBreak = $config->runtimeOptions->editor->export->wordBreakUpRegex;
        
        //by adding the count($split) and the $i++ only the array entries containing text (no tags) are parsed
        //this implies that only tagBreakUp may be called before and
        // no other array structure manipulating method may be called between tagBreakUp and wordBreakUp!!!
        for ($i = 0; $i < count($segment); $i++) {
            $split = preg_split($regexWordBreak, $segment[$i], NULL, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            array_splice($segment, $i, 1, $split);
            $i = $i + count($split);
        }
        return $segment;
    }

    /***
     * Add weekdays/business-days to the inputDate. The daysToAdd can be integer or float (the number will be converted to seconds and add to toe inputDate).
     * If the inputDate is without time (00:00:00), the current time will be used
     * @param string $inputDate : string as date in Y-m-d H:i:s or Y-m-d format
     * @param number $daysToAdd
     * @return string
     */
    public static function addBusinessDays(string $inputDate ,  $daysToAdd){

        $daysDecimal = $daysToAdd - (int)$daysToAdd;
        $secondsToAdd = $daysDecimal > 0 ? (' +'.(24*$daysDecimal*3600).' seconds') : '';

        $inputDateChunks = explode(' ',$inputDate);
        // if no timestamp is provided for the inputDate, use the current timestamp
        if(count($inputDateChunks) === 1 || $inputDateChunks[1] === '00:00:00'){
            $dateAndTime = explode(" ", NOW_ISO);
            $inputDate = date('Y-m-d',strtotime($inputDate)).' '.array_pop($dateAndTime);
        }

        // add seconds if required
        if(!empty($secondsToAdd)){
            $inputDate = date ('Y-m-d H:i:s' , strtotime($inputDate.$secondsToAdd));
        }
        // this must be done because the time is set to 00:00:00 when the date contains time in it
        // probably it is php bug
        $inputDate = explode(' ',$inputDate);

        $weekdaysTemplate = $inputDate[0].' +'.((int)$daysToAdd).' Weekday';
        return date ('Y-m-d' , strtotime($weekdaysTemplate)).' '.$inputDate[1];
    }
}
