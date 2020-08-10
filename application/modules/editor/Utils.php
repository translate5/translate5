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
     * @var array
     */
    private static $asciiMap = [
        'Ä' => 'ae', 'Ü' => 'ue', 'Ö' => 'oe', 'ä' => 'ae', 'ü' => 'ue', 'ö' => 'oe', 'ß' => 'ss', 'Þ' => 'th', 'þ' => 'th', 'Ð' => 'dh', 'ð' => 'dh', 'Œ' => 'oe', 'œ' => 'oe', 'Æ' => 'ae', 'æ' => 'ae', 'µ' => 'u', 'Š' => 's', 'Ž' => 'z', 'š' => 's',
        'ž' => 'z', 'Ÿ' => 'y', 'À' => 'a', 'Á' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Å' => 'a', 'Ç' => 'c', 'Č' => 'c', 'È' => 'e', 'É' => 'e', 'Ê' => 'e', 'Ë' => 'e', 'Ì' => 'i', 'Í' => 'i', 'Î' => 'i', 'Ï' => 'i', 'Ñ' => 'n', 'Ò' => 'o', 'Ó' => 'o',
        'Ô' => 'o', 'Õ' => 'o', 'Ø' => 'o', 'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ů' => 'u', 'Ý' => 'y', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'ç' => 'c', 'č' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i',
        'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o', 'ù' => 'u', 'ů' => 'u', 'û' => 'u', 'û' => 'u', 'ý' => 'y', 'ÿ' => 'y',
        '(' => '-', ')' => '-', '+' => 'plus', '&' => 'and', '#' => '-', '?' => '' ];
    
    /**
     * Ascifies a string
     * The string may still contains UTF chars after the conversion, this is just a "first step" ...
     * @param string $name
     * @return string
     */
    public static function asciify($name){
        $name = trim($name);
        // Only using array map does ensure correct multibyte character handling
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
}
