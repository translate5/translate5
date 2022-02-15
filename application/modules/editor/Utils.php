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

/* 
 */
class editor_Utils {

    /**
     * Regular expressions patterns for common usage
     *
     * @var array
     */
    protected static $_rex = [
        'email' => '/^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,6}|[0-9]{1,3})(\]?)$/',
        'date' => '/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/',
        'zerodate' => '/^[0\.\-\/ ]*$/',
        'year' => '/^[0-9]{4}$/',
        'hrgb' => '/^[0-9]{3}#([0-9a-fA-F]{6})$/',
        'rgb' => '/^#([0-9a-fA-F]{6})$/',
        'htmleventattr' => 'on[a-zA-Z]+\s*=\s*"[^"]+"',
        'php' => '/<\?/',
        'phpsplit' => '/(<\?|\?>)/',
        'int11' => '/^(-?[1-9][0-9]{0,9}|0)$/',
        'int11lz' => '/^-?[0-9]{1,10}$/', // with possibility of leading zero
        'int11list' => '/^[1-9][0-9]{0,9}(,[1-9][0-9]{0,9})*$/',
        'bool' => '/^(0|1)$/',
        'time' => '/^[0-9]{2}:[0-9]{2}:[0-9]{2}$/',
        'double72' => '/^([1-9][0-9]{0,6}|[0-9])(\.[0-9]{1,2})?$/',
        'decimal112' => '/^(-?([1-9][0-9]{1,7}|[0-9]))(\.[0-9]{1,2})?$/',
        'decimal143' => '/^(-?([1-9][0-9]{1,9}|[0-9]))(\.[0-9]{1,3})?$/',
        'datetime' => '/^[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/',
        'url' => '/^(ht|f)tp(s?)\:\/\/(([a-zA-Z0-9\-\._]+(\.[a-zA-Z0-9\-\._]+)+)|localhost)(\/?)([a-zA-Z0-9\-\.\?\,\'\/\\\+&amp;%\$#_]*)?([\d\w\.\/\%\+\-\=\&amp;\?\:\\\&quot;\'\,\|\~\;]*)$/',
        'varchar255' => '/^([[:print:]]{0,255})$/u',
        'varchar255s' => '/^([[:print:]\s]{0,255})$/u',
        'dir' => ':^([A-Z][\:])?/.*/$:', // directory (Windows-style 'C:/xxx' also supported)
        'grs' => '/^[a-zA-Z0-9]{15}$/', // generated random sequence
        'phone' => '/^\+7 \([0-9]{3}\) [0-9]{3}-[0-9]{2}-[0-9]{2}$/', // ?
        'coords' => '/^([0-9]{1,3}+\.[0-9]{1,12})\s*,\s*([0-9]{1,3}+\.[0-9]{1,12}+)$/',
        'timespan' => '/^[0-9]{2}:[0-9]{2}-[0-9]{2}:[0-9]{2}$/',
        'ipv4' => '~^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$~',
        'base64' => '^~(?:[A-Za-z0-9+/]{4})*(?:[A-Za-z0-9+/]{2}==|[A-Za-z0-9+/]{3}=)?$~',
        'wskey' => '~^[A-Za-z0-9+/]{22}==$~',
        'rfc5646' => '~^[a-z]{2,3}(-[a-zA-Z0-9]{2,4}|)(-[a-zA-Z]{2}|)$~',
        'ext' => '~\.([a-z0-9]+)$~i',
        'json' => '/
          (?(DEFINE)
             (?<number>   -? (?= [1-9]|0(?!\d) ) \d+ (\.\d+)? ([eE] [+-]? \d+)? )
             (?<boolean>   true | false | null )
             (?<string>    " ([^"\n\r\t\\\\]* | \\\\ ["\\\\bfnrt\/] | \\\\ u [0-9a-f]{4} )* " )
             (?<array>     \[  (?:  (?&json)  (?: , (?&json)  )*  )?  \s* \] )
             (?<pair>      \s* (?&string) \s* : (?&json)  )
             (?<object>    \{  (?:  (?&pair)  (?: , (?&pair)  )*  )?  \s* \} )
             (?<json>   \s* (?: (?&number) | (?&boolean) | (?&string) | (?&array) | (?&object) ) \s* )
          )
          \A (?&json) \Z
          /six',
        'xmlid' => '~(?(DEFINE)
            (?<first>   (:|[0-9]|[A-Z]|_|[a-z]|[\x{C0}-\x{D6}]|[\x{D8}-\x{F6}]|[\x{F8}-\x{2FF}]|[\x{370}-\x{37D}]|[\x{37F}-\x{1FFF}]|[\x{200C}-\x{200D}]|[\x{2070}-\x{218F}]|[\x{2C00}-\x{2FEF}]|[\x{3001}-\x{D7FF}]|[\x{F900}-\x{FDCF}]|[\x{FDF0}-\x{FFFD}]|[\x{10000}-\x{EFFFF}]) )
            (?<other>   (-|\.|[0-9]|\x{B7}|[\x{0300}-\x{036F}]|[\x{203F}-\x{2040}]) )
            (?<second>  (?&first) | (?&other)  )
            (?<xmlid>   (?&first)   (?&second)* )
        )^(?&xmlid)$~ixu',
        'rfc5646list' => '~(?(DEFINE)
            (?<rfc5646>       ([a-z]{2,3}(-[a-zA-Z0-9]{2,4}|)(-[a-zA-Z]{2}|)) )
            (?<rfc5646list>   (?&rfc5646)  (?: , (?&rfc5646)  )* )
        )^(?&rfc5646list)$~xu',
    ];

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
     * A replacement for escapeshellarg that does NOT do any locale-specific UTF-8 stripping
     * 
     * escapeshellarg() adds single quotes around a string and quotes/escapes any existing single quotes allowing you to pass a string directly to a shell function and having it be treated as a single safe argument. This function should be used to escape individual arguments to shell functions coming from user input.
     * The shell functions include exec(), system() and the backtick operator.
     *
     * On Windows, escapeshellarg() instead replaces percent signs, exclamation marks (delayed variable substitution) and double quotes with spaces and adds double quotes around the string. 
     * Furthermore, each streak of consecutive backslashes (\) is escaped by one additional backslash. 
     *
     * @param string $command
     * @return string
     */
    public static function escapeshellarg(string $command) : string {
        // Windows specific escaping: remove 
        if(PHP_OS_FAMILY == 'Windows'){
            // QUIRK: on windows the PHP implementation is used since I cannot test the result.
            // TODO/FIXME: Replicate Windows logic if neccessary / if the UTF-8 stripping happens on Windows too
            return escapeshellarg($command);
        }
        // UNIX specific escaping: only Ticks
        return "'".implode("'\''", explode("'", $command))."'";
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

    /**
     * Check props, stored in $data arg to match rules, given in $ruleA arg
     * Example usage:
     * $_ = editor_Utils::jcheck([
     *        'propName1,propName2' => [
     *            'ruleName1' => 'ruleValue1',
     *            'ruleName2' => 'ruleValue2',
     *        ],
     *        'prop1' => [
     *            ...
     *        ]
     *        'prop3' => [
     *            ...
     *        ]
     *    ], [
     *        'propName1' => 'propValue1',
     *        'propName2' => 'propValue2',
     *        'propName3' => 'propValue3',
     *    ]);
     *
     * In most cases this method does not return any value, unless there are 'key', 'ext' or 'rex' == 'json' rules used for any of props.
     * If any validation failed, the ZfExtended_Mismatch exception is thrown immediately.
     * Currently supported rule names and their possible values are:
     *  'req' - Required. If rule value is truly - then prop value is required, e.g. should have non-zero length.
     *  'rex' - Regular expression. Prop value should match regular expression. Rule value can be raw expression
     *          or preset name, like 'int11', 'email' or other (see self::$_rex). If 'json'-preset is used as
     *          a rule value, and prop value is a valid json-encoded string - it will be decoded into a php array
     *          and accessible within return value using the following mapping: $_['propNameX']
     *  'eql' - Equal. Prop value should be equal to given rule value. Equality check is done using non-strict comparison, e.g '!='
     *  'key' - Key(s) pointing to existing record(s). Possible rule values:
     *
     *          - 'tableName'                             - Single row mode. Here columnName is not given, so 'id' assumed
     *          - 'tableName.columnName'                  - Single row mode. Here columnName is explicitly given
     *
     *          - 'tableName*'                            - Multiple rows mode. Prop value should be comma-separated list or array
     *          - 'tableName.columnName*'                   of values, that can be, for example, ids, slugs or others kind of data
     *
     *          - 'editor_Models_MyModelName'             - Single row mode. Model class name can be used
     *          - 'editor_Models_MyModelName.columnName'  - Single row mode. Model class name can be used
     *          - $this->entity                           - Single row mode. Model class instance can be used
     *
     *          Found data is accessible within return value using the same mapping: $_['propNameX'],
     *          and is represented as:
     *
     *          - an array with single record data - in case of rule value was 'tableName' or 'tableName.columnName'
     *              ['id' => 123, 'colName1' => 'valueX', ...]
     *
     *          - an array with multiple records data in case of rule was 'tableName*' or 'tableName.columnName*'
     *              [
     *                ['id' => 123, 'colName1' => 'valueX', ...],
     *                ['id' => 124, 'colName1' => 'valueY', ...]
     *              ]
     *
     *          - a model class instance having ->row property loaded with Zend_Db_Table_Row instance, in case of rule
     *            value was 'editor_Models_MyModelName' or $this->entity
     *  'fis' - FIND_IN_SET(). No sql queries will be made, it's just quick way to remember such rule name.
     *          Prop value should be in the list of allowed values, given by rule value.
     *          Rule value should be comma-separated list or array of values
     *  'dis' - Disabled. Prop value should NOT be in the list of disabled values, given by rule value. Rule value
     *          should be comma-separated list or array of disabled values
     *  'ext' - Extension of an uploaded file. Rule value can be:
     *            - regular expression, for example '~^(png|jpe?g|gif)$~'
     *            - single extension, for example 'jpg'
     *            - comma-separated list of extensions, for example 'gif,jpg'
     *          Prop value should be given as just $_FILES['propNameX'], and if validation is ok - prop value is attached
     *          to return value under $_['propNameX'] mapping
     *
     * Todo: Add support for 'min' and 'max' rules, that would work for strings, numbers and file sizes
     * @param $ruleA
     * @param array|stdClass|ZfExtended_Models_Entity_Abstract $data Data to checked
     * @return array
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Mismatch
     */
    public static function jcheck($ruleA, $data) {

        // Declare $rowA array
        $rowA = [];

        // If $data is an object
        if (is_object($data)) {

            // If $data arg is a model instance - convert it to array
            if (is_subclass_of($data, 'ZfExtended_Models_Entity_Abstract')) $data = $data->toArray();

            // Else if $data arg is an instance of stdClass - convert it to array as well
            else if ($data instanceof stdClass) $data = (array) $data;
        }

        // Foreach prop having mismatch rules
        foreach ($ruleA as $props => $rule) foreach (self::ar($props) as $prop) {

            // Explicitly set up the rule-type keys for which not exist
            foreach (['req', 'rex', 'ext', 'unq', 'key', 'fis', 'dis'] as $type)
                if (!isset($rule[$type]))
                    $rule[$type] = '';

            // Shortcut to $data[$prop]
            $value = $data[$prop] ?? null;

            // Get meta
            $meta = isset($data['_meta'][$prop]) ? $data['_meta'][$prop] : [];

            // Get label, or use $prop if label/meta is not given
            $label = $meta['fieldLabel'] ?? $prop;

            // If prop is required, but has empty/null/zero value - flush error
            if (($rule['req'] || $rule['unq'])
                && ((!is_array($value) && !strlen($value)) || (!$value && $rule['key'])))
                throw new ZfExtended_Mismatch('E2000', [$label]);

            // If prop's value should match certain regular expression, but it does not - flush error
            if ($rule['rex'] && strlen($value) && !self::rexm($rule['rex'], $value))
                throw new ZfExtended_Mismatch('E2001', [$value, $label]);

            // If file's extension should match certain regular expression, but it does not - flush error
            if ($rule['ext']) {

                // Get extension
                $ext = strtolower(self::rexm('ext', $value['name'], 1));

                // Check if rule is a regular expression, and not just one extension or comma-separated list of extensions
                $rex = preg_match('~,~', $rule['ext']) || preg_match('~^[a-z0-9]+$~', $rule['ext']) ? false : true;

                // If extension is allowed
                if ($rex ? self::rexm($rule['ext'], $ext) : in_array($ext, self::ar($rule['ext']))) {

                    // Pass file info into return value
                    $rowA[$prop] = $value;

                    // Append extension
                    $rowA[$prop]['ext'] = $ext;

                    // Append extension prepended with dot
                    $rowA[$prop]['.ext'] = '.' . $ext;
                }

                // Else throw an exception
                else throw new ZfExtended_Mismatch('E2007', [$ext, $label]);
            }

            // If value should be a json-encoded expression, and it is - decode
            if ($rule['rex'] == 'json') $rowA[$prop] = json_decode($value);

            // If prop's value should be equal to some certain value, but it's not equal - flush error
            if (array_key_exists('eql', $rule)
                && $value != $rule['eql'])
                throw new ZfExtended_Mismatch('E2003', [$rule['eql'], $value]);

            // If value should be in the list of allowed values, but it's not  - flush error
            if ($rule['fis'] && $value) {

                // Array of input values
                $input = is_array($value) ? $value : explode(',', $value);

                // Array of allowed values
                $allowed = is_array($rule['fis']) ? $rule['fis'] : explode(',', $rule['fis']);

                // If we deducted allowed values from input values, and the result is not empty
                // it means that result contains those of input values that were not in array of allowed,
                // so flush error
                if ($restricted = array_diff($input, $allowed))
                    throw new ZfExtended_Mismatch('E2004', [
                        $restricted ? implode(',', $restricted) : $value,
                        $label
                    ]);
            }

            // If value should not be in the list of disabled values - flush error
            if ($rule['dis'] && in_array($value, self::ar($rule['dis'])))
                throw new ZfExtended_Mismatch('E2005', [$value, $label]);

            // If prop's value should be an identifier of an existing database record
            if ($rule['key'] && strlen($value) && $value != '0') {

                // If the rule value is a string
                if (is_string($rule['key'])) {

                    // Get table name
                    $table = preg_replace('/\*$/', '', $rule['key']);

                    // Setup $isSingleRow as a flag indicating whether *_Row (single row) or *_Rowset should be fetched
                    $isSingleRow = $table == $rule['key'];

                    // Get key's target table and column
                    $target = explode('.', $table); $table = $target[0]; $column = $target[1] ?? 'id';

                // Else if
                } else {

                    //
                    $isSingleRow = true;

                    //
                    $table = $rule['key'];
                }

                // If $rule['key'] arg is a class name (or an instance) of model, that is a subclass of ZfExtended_Models_Entity_Abstract
                if (is_subclass_of($table, 'ZfExtended_Models_Entity_Abstract')) {

                    // If rule value is string
                    if (is_string($rule['key'])) {

                        // Get model
                        $m = ZfExtended_Factory::get($table);

                        // If single row mode
                        if ($isSingleRow) {

                            // Load row
                            $m->loadRow("$column = ?", $value);

                        // Else
                        } else {
                             // Not yet supported
                        }

                    // Else if rule value is a model instance
                    } else {

                        // Get model
                        $m = $rule['key'];

                        // If single row mode
                        if ($isSingleRow) {

                            // Load row
                            $m->load($value);

                        // Else
                        } else {
                            // Not yet supported
                        }
                    }

                    // Assign model into return value
                    $rowA[$prop] = $m->getId() ? $m : false;

                // Else
                } else {

                    // Setup WHERE clause and method name to be used for fetching
                    $where = $isSingleRow
                        ? self::db()->quoteInto('`' . $column . '` = ?', $value)
                        : self::db()->quoteInto('`' . $column . '` IN (?)', self::ar($value));

                    // Prepare statement
                    $stmt = self::db()->query('
                        SELECT * 
                        FROM `' . $table . '` 
                        WHERE ' . $where . self::rif($isSingleRow, '
                        LIMIT 1'
                    ));

                    // Fetch
                    $rowA[$prop] = $isSingleRow ? $stmt->fetch() : $stmt->fetchAll();
                }

                // If no *_Row was fetched, or empty *_Rowset was fetched - flush error
                if (!$rowA[$prop])
                    throw new ZfExtended_Mismatch('E2002', [is_string($rule['key']) ? $rule['key'] : get_class($rule['key']), $value]);
            }

            // If prop's value should be unique within the whole database table, but it's not - flush error
            /*if ($rule['unq']
                && count($_ = explode('.', $rule['unq'])) == 2
                && ZfExtended_Factory::get($_[0])->fetchRow(['`' . $_[1] . '` = "' . $value . '"']))
                throw new ZfExtended_Mismatch('E2006', [$value, $label]);*/
        }

        // Return *_Row objects, collected for props, that have 'key' rule
        return $rowA;
    }

    /**
     * Return regular expressions pattern, stored within $this->_rex property under $alias key
     *
     * @param $alias
     * @return null
     */
    public static function rex($alias){
        return $alias ? (self::$_rex[$alias] ?? null) : null;
    }

    /**
     * Call preg_match() using pattern, stored within self::$_rex array under $rex key and using given $subject.
     * If no pre-defined pattern found in self::$_rex under $rex key, function will assume that $rex is a raw regular
     * expression.
     *
     * @static
     * @param $rex
     * @param $subject
     * @param null $sub If regular expression contains submask(s), $sub arg can be used as
     *                  a way to specify a submask index, that you need to pick the value at
     * @return array|null|string
     */
    public static function rexm($rex, $subject, $sub = null){

        // Check that self::$_rex array has a value under $rex key
        if ($_ = self::rex($rex)) $rex = $_;

        // Match
        preg_match($rex, $subject, $found);

        // Return
        return $found ? (func_num_args() == 3 ? $found[$sub] : $found) : ($found ?: '');
    }

    /**
     * Flush the json-encoded message, containing `success` property, and other optional properties
     *
     * Usages:
     * jflush(true, 'OK')  -> {success: true, msg: "OK"}
     * jflush(['success' => true, 'param1' => 'value1', 'param2' => 'value2']) -> {success: true, param1: "value1", param2: "value2"}
     * jflush(true, ['param1' => 'value1', 'param2' => 'value2']) -> {success: true, param1: "value1", param2: "value2"}
     *
     * @param $success
     * @param mixed $msg1
     * @param mixed $msg2
     * @param bool $die
     */
    public static function jflush($success, $msg1 = null, $msg2 = null) {

        // Start building data for flushing
        $flush = is_array($success) && array_key_exists('success', $success) ? $success : ['success' => $success];

        // Deal with first data-argument
        if (func_num_args() > 1 && func_get_arg(1) != null)
            $mrg1 = is_object($msg1)
                ? (in('toArray', get_class_methods($msg1)) ? $msg1->toArray() : (array) $msg1)
                : (is_array($msg1) ? $msg1 : ['msg' => $msg1]);

        // Deal with second data-argument
        if (func_num_args() > 2 && func_get_arg(2) != null)
            $mrg2 = is_object($msg2)
                ? (in('toArray', get_class_methods($msg2)) ? $msg2->toArray() : (array) $msg2)
                : (is_array($msg2) ? $msg2 : ['msg' => $msg2]);

        // Merge the additional data to the $flush array
        if (isset($mrg1)) $flush = array_merge($flush, $mrg1);
        if (isset($mrg2)) $flush = array_merge($flush, $mrg2);

        // Send headers
        if (!headers_sent()) {

            // Send '400 Bad Request' status code if user agent is not IE
            if ($flush['success'] === false && !self::isIE()) header('HTTP/1.1 400 Bad Request');

            // Send '200 OK' status code
            if ($flush['success'] === true) header('HTTP/1.1 200 OK');

            // Send content type
            header('Content-Type: '. (self::isIE() ? 'text/plain' : 'application/json'));
        }

        // Flush json
        echo json_encode($flush, JSON_UNESCAPED_UNICODE);

        // Exit
        exit;
    }

    /**
     * @return Zend_Db_Adapter_Abstract
     */
    public static function db() {
        return Zend_Db_Table_Abstract::getDefaultAdapter();
    }

    /**
     * Wrap all urls with <a href="..">
     * Code got from: http://stackoverflow.com/questions/1188129/replace-urls-in-text-with-html-links
     *
     * Testing text: <<<EOD

    Here are some URLs:
    stackoverflow.com/questions/1188129/pregreplace-to-detect-html-php
    Here's the answer: http://www.google.com/search?rls=en&q=42&ie=utf-8&oe=utf-8&hl=en. What was the question?
    A quick look at http://en.wikipedia.org/wiki/URI_scheme#Generic_syntax is helpful.
    There is no place like 127.0.0.1! Except maybe http://news.bbc.co.uk/1/hi/england/surrey/8168892.stm?
    Ports: 192.168.0.1:8080, https://example.net:1234/.
    Beware of Greeks bringing internationalized top-level domains: xn--hxajbheg2az3al.xn--jxalpdlp.
    And remember.Nobody is perfect.

    <script>alert('Remember kids: Say no to XSS-attacks! Always HTML escape untrusted input!');</script>
    EOD;

     *
     * @param $text
     * @return string
     */
    public static function url2a($text) {

        // Regexps
        $rexProtocol = '(https?://)?';
        $rexDomain   = '((?:[-a-zA-Z0-9а-яА-Я]{1,63}\.)+[-a-zA-Z0-9а-яА-Я]{2,63}|(?:[0-9]{1,3}\.){3}[0-9]{1,3})';
        $rexPort     = '(:[0-9]{1,5})?';
        $rexPath     = '(/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?';
        $rexQuery    = '(\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
        $rexFragment = '(#[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';

        // Valid top-level domains
        $validTlds = array_fill_keys(explode(' ', '.aero .asia .biz .cat .com .coop .edu .gov .info .int .jobs .mil .mobi '
            . '.museum .name .net .org .pro .tel .travel .ac .ad .ae .af .ag .ai .al .am .an .ao .aq .ar .as .at .au .aw '
            . '.ax .az .ba .bb .bd .be .bf .bg .bh .bi .bj .bm .bn .bo .br .bs .bt .bv .bw .by .bz .ca .cc .cd .cf .cg '
            . '.ch .ci .ck .cl .cm .cn .co .cr .cu .cv .cx .cy .cz .de .dj .dk .dm .do .dz .ec .ee .eg .er .es .et .eu '
            . '.fi .fj .fk .fm .fo .fr .ga .gb .gd .ge .gf .gg .gh .gi .gl .gm .gn .gp .gq .gr .gs .gt .gu .gw .gy .hk '
            . '.hm .hn .hr .ht .hu .id .ie .il .im .in .io .iq .ir .is .it .je .jm .jo .jp .ke .kg .kh .ki .km .kn .kp '
            . '.kr .kw .ky .kz .la .lb .lc .li .lk .lr .ls .lt .lu .lv .ly .ma .mc .md .me .mg .mh .mk .ml .mm .mn .mo '
            . '.mp .mq .mr .ms .mt .mu .mv .mw .mx .my .mz .na .nc .ne .nf .ng .ni .nl .no .np .nr .nu .nz .om .pa .pe '
            . '.pf .pg .ph .pk .pl .pm .pn .pr .ps .pt .pw .py .qa .re .ro .rs .ru .rw .sa .sb .sc .sd .se .sg .sh .si '
            . '.sj .sk .sl .sm .sn .so .sr .st .su .sv .sy .sz .tc .td .tf .tg .th .tj .tk .tl .tm .tn .to .tp .tr .tt '
            . '.tv .tw .tz .ua .ug .uk .us .uy .uz .va .vc .ve .vg .vi .vn .vu .wf .ws .ye .yt .yu .za .zm .zw '
            . '.xn--0zwm56d .xn--11b5bs3a9aj6g .xn--80akhbyknj4f .xn--9t4b11yi5a .xn--deba0ad .xn--g6w251d '
            . '.xn--hgbk6aj7f53bba .xn--hlcj6aya9esc7a .xn--jxalpdlp .xn--kgbechtv .xn--zckzah .arpa .рф .xn--p1ai'), true);

        // Start output buffering
        ob_start();

        // Position
        $position = 0;

        // Split given $text by urls
        while (preg_match("~$rexProtocol$rexDomain$rexPort$rexPath$rexQuery$rexFragment(?=[?.!,;:\"]?(\s|$))~u",
            $text, $match, PREG_OFFSET_CAPTURE, $position)) {

            // Extract $url and $urlPosition from match
            list($url, $urlPosition) = $match[0];

            // Print the text leading up to the URL.
            print(htmlspecialchars(substr($text, $position, $urlPosition - $position)));

            // Pick domain, port and path from matches
            $domain = $match[2][0];
            $port   = $match[3][0];
            $path   = $match[4][0];

            // Get top-level domain
            $tld = mb_strtolower(strrchr($domain, '.'), 'utf-8');

            // Check if the TLD is valid - or that $domain is an IP address.
            if (preg_match('{\.[0-9]{1,3}}', $tld) || isset($validTlds[$tld])) {

                // Prepend http:// if no protocol specified
                $completeUrl = $match[1][0] ? $url : 'http://' . $url;

                // Print the hyperlink.
                printf('<a href="%s">%s</a>', htmlspecialchars($completeUrl), htmlspecialchars("$domain$port$path"));

                // Else if not a valid URL.
            } else print(htmlspecialchars($url));

            // Continue text parsing from after the URL.
            $position = $urlPosition + strlen($url);
        }

        // Print the remainder of the text.
        print(htmlspecialchars(substr($text, $position)));

        // Return
        return ob_get_clean();
    }

    /**
     * Try to detect if request was made using Internet Explorer
     *
     * @return bool
     */
    public static function isIE() {
        return !!preg_match('/(MSIE|Trident|rv:)/', $ua = $_SERVER['HTTP_USER_AGENT']) && !preg_match('~Firefox~', $ua);
    }


    /**
     * Comma-separated values to array converter
     *
     * @param $items
     * @param $allowEmpty - If $items arg is an empty string, function will return an array containing that empty string
     *                      as a first item, rather than returning empty array
     * @return array
     */
    public static function ar($items, $allowEmpty = false) {

        // If $items arg is already an array - return it as is
        if (is_array($items)) return $items;

        // Else if $items arg is strict null - return array containing that null as a first item
        if ($items === null) return $allowEmpty ? array(null) : array();

        // Else if $items arg is a boolean value - return array containing that boolean value as a first item
        if (is_bool($items)) return array($items);

        // Else if $items arg is an object we either return result of toArray() call on that object,
        // or return result, got by php's native '(array)' cast-prefix expression, depending whether
        // or not $items object has 'toArray()' method
        if (is_object($items)) return in_array('toArray', get_class_methods($items)) ? $items->toArray(): (array) $items;

        // Else we assume $items is a string and return an array by comma-exploding $items arg
        if (is_string($items)) {

            // If $items is an empty string - return empty array
            if (!strlen($items) && !$allowEmpty) return array();

            // Explode $items arg by comma
            foreach ($items = explode(',', $items) as $i => $item) {

                // Convert strings 'null', 'true' and 'false' items to their proper types
                if ($item == 'null') $items[$i] = null;
                if ($item == 'true') $items[$i] = true;
                if ($item == 'false') $items[$i] = false;
            }

            // Return normalized $items
            return $items;
        }

        // Else return array, containing $items arg as a single item
        return array($items);
    }

    /**
     * Return $then or $else arg depending on whether $if arg is truthy
     *
     * @param bool $if
     * @param string $then
     * @param string $else
     * @return string
     */
    public static function rif($if, $then, $else = '') {
        return $if ? str_replace('$1', is_scalar($if) ? $if : '$1', $then) : $else;
    }

    /***
     * Add weekdays/business-days to the inputDate. The daysToAdd can be integer or float (the number will be converted to seconds and add to toe inputDate).
     * If the inputDate is without time (00:00:00), the current time will be used
     * @param string $inputDate : string as date in Y-m-d H:i:s or Y-m-d format
     * @param mixed $daysToAdd
     * @return string
     */
    public static function addBusinessDays(string $inputDate, $daysToAdd): string{

        $daysDecimal = $daysToAdd - (int)$daysToAdd;
        $secondsToAdd = $daysDecimal > 0 ? (' +'.(24*$daysDecimal*3600).' seconds') : '';

        $inputDateChunks = explode(' ',$inputDate);
        // if no timestamp is provided for the inputDate, or the time is 00:00:00 -> use the current timestamp
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
    /**
     * 
     * @param string $url
     * @return string
     */
    public static function removeQueryString(string $url) : string {
        if(strpos($url,'?') !== false) {
            return explode('?',$url)[0];
        }
        return $url;
    }
    /**
     * 
     * @param string $url
     * @return string
     */
    public static function removeFragment(string $url) : string {
        if(strpos($url,'#') !== false) {
            return explode('#',$url)[0];
        }
        return $url;
    }
    /**
     * Removes query-string & fragment from an URL
     * @param string $url
     * @return string
     */
    public static function cleanUrl(string $url) : string {
        return(self::removeQueryString(self::removeFragment($url)));
    }
    /**
     * 
     * @param string $url
     * @return bool
     */
    public static function urlIsAccessible(string $url) : bool {
        if(!empty($url)){
            $opts = array(
                'http' => array(
                    'method' => 'HEAD'
                )
            );
            $context = stream_context_create($opts);
            $headers = get_headers($url, 0, $context);
            $code = -1;
            $matches = [];
            if ($headers != false && count($headers) > 0) {
                foreach($headers as $header){
                    // grabs the last $header $code, in case of redirect(s):
                    if(preg_match("/^HTTP.+\s(\d\d\d)\s/", $header, $matches)){
                        $code = $matches[1];
                    }
                }
            }
            if($code >= 200 && $code <= 300){
                return true;
            }
        }
        return false;
    }
}

class ZfExtended_Mismatch extends ZfExtended_ErrorCodeException {
    use ZfExtended_ResponseExceptionTrait;

    /**
     * @var integer
     */
    protected $httpReturnCode = 400;

    /**
     * By default we log that as INFO, if created as response then the level is set to DEBUG
     *
     * @var integer
     */
    protected $level = ZfExtended_Logger::LEVEL_INFO;

    /**
     * @var array
     */
    protected static $localErrorCodes = [
        'E2000' => 'Param "{0}" - is not given',                                           // REQ
        'E2001' => 'Value "{0}" of param "{1}" - is in invalid format',                    // REX
        'E2002' => 'No object of type "{0}" was found by key "{1}"',                       // KEY
        'E2003' => 'Wrong value',                                                          // EQL
        'E2004' => 'Value "{0}" of param "{1}" - is not in the list of allowed values',    // FIS
        'E2005' => 'Value "{0}" of param "{1}" - is in the list of disabled values',       // DIS
        'E2006' => 'Value "{0}" of param "{1}" - is not unique. It should be unique.',     // UNQ
        'E2007' => 'Extension "{0}" of file "{1}" - is not in the list of allowed values', // EXT
    ];
}

/**
 * Include functions file
 */
include __DIR__ . '/Utils/func.php';