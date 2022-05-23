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

namespace MittagQI\Translate5\Tools;

use joshtronic\LoremIpsum;

final class FakeTranslator
{
    /**
     * The average divergence in the number of words (5 => 20%)
     */
    const WORD_DIVERGENCE = 5;
    /**
     *  The average tag dropping rate 3 = 30%
     */
    const TAG_DROP_RATIO = 3;
    /**
     * @var LoremIpsum
     */
    static $loremIpsum = NULL;
    /**
     * Generates the Lorem-Ipsum instance. By doing this only once per request it is ensured, that the generated strings are not always "Lorem Ipsum dolor sit amet" ...
     * @return LoremIpsum
     */
    private static function _getLoremIpsum() : LoremIpsum {
        if(self::$loremIpsum == NULL){
            self::$loremIpsum = new LoremIpsum();
        }
        return self::$loremIpsum;
    }

    /**
     * Generates a single LoremIpsum word
     * @param bool $firstCharUppercase
     * @return string
     */
    public static function generateWord(bool $firstCharUppercase=false) : string {
        return self::uppercaseFirst(self::_getLoremIpsum()->word(), $firstCharUppercase);
    }

    /**
     * Generates multiple LoremIpsum words
     * @param int $numWords
     * @param bool $firstWordUppercase
     * @return string
     */
    public static function generateWords(int $numWords, bool $firstWordUppercase=false) : string {
        return self::uppercaseFirst(self::_getLoremIpsum()->words($numWords), $firstWordUppercase);
    }

    /**
     * Generates the passed number of Lorem Ipsum words as an array
     * @param int $numWords
     * @param bool $firstWordUppercase
     * @return array
     */
    public static function generateWordsArray(int $numWords, bool $firstWordUppercase=false) : array {
        if($numWords > 0){
            $words =  static::_getLoremIpsum()->words($numWords, false, true);
            $words[0] = self::uppercaseFirst($words[0], $firstWordUppercase);
            return $words;
        }
        return [];
    }

    /**
     * Generates the passed number of Lorem Ipsum sentences
     * @param int $numSentences
     * @return string
     */
    public static function generateSentences(int $numSentences) : string {
        return static::_getLoremIpsum()->sentences($numSentences);
    }

    /**
     * Decides if a character represents a sentence-end punctuation
     * @param string $char
     * @return bool
     */
    public static function isPunctuation(string $char) : bool {
        return in_array($char, [',', '.', '?', '!', ';', '-', '…', '·', '•', '–', '"', "'"]);
    }

    /**
     * Decides if a character represents a sentence-end punctuation
     * @param string $char
     * @return bool
     */
    public static function isSentenceEndPunctuation(string $char) : bool {
        return in_array($char, ['.', '?', '!', ';', ':']);
    }

    /**
     * Generates a fake translation for Markup
     * It is assumed, the passed markup represents 1 or more sentences
     * @param string $markup
     * @param bool $dropTagsRandomly: if set, tags are dropped randomly, with a probability of 33%
     * @return string
     */
    public static function translateMarkup(string $markup, bool $dropTagsRandomly=false) : string {
        // first we need to split comments as they would be destroyed by the next step otherwise
        $parts = preg_split(Markup::COMMENT_PATTERN.'Us', $markup, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        $firstCharUppercase = true;
        foreach($parts as $part){
            if(preg_match(Markup::COMMENT_PATTERN.'s', $part) === 1){
                if(!$dropTagsRandomly || self::keepTagIf()){
                    $result .= $part;
                }
            } else {
                $innerparts = preg_split(Markup::PATTERN.'U', $markup, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
                foreach($innerparts as $innerpart){
                    if(preg_match(Markup::PATTERN, $innerpart) === 1){
                        if(!$dropTagsRandomly || self::keepTagIf()){
                            $result .= $innerpart;
                        }
                    } else {
                        $translated = Markup::escapeText(self::translateText(Markup::unescapeText($innerpart), $firstCharUppercase));
                        $result .= $translated;
                        $firstCharUppercase = self::lastCharIsSentenceEnd($translated);
                    }
                }
            }
        }
        return $result;
    }

    /**
     * fake translates a text without tags. Will use lorem Ipsum as result
     * @param string $text
     * @return string
     */
    public static function translateText(string $text, bool $firstCharUppercase=false) : string {
        // leading or trailing whitespace or punctuation must be removed, otherwise splitting creates problematic results
        $leading = '';
        $trailing = '';
        $strLength = mb_strlen($text);
        if($strLength > 0){
            // leading whitespace or punctuation
            $char = mb_substr($text, 0, 1, 'UTF-8');
            while(self::isPunctuationOrWhitespace($char) && $strLength > 0){
                $leading .= $char;
                $text = mb_substr($text, 1, NULL, 'UTF-8');
                $strLength--;
                $char = mb_substr($text, 0, 1, 'UTF-8');
            }
            // trailing whitespace or punctuation
            $char = mb_substr($text, -1, 1, 'UTF-8');
            while(self::isPunctuationOrWhitespace($char) && $strLength > 0){
                $trailing = $char.$trailing;
                $strLength--;
                $text = mb_substr($text, 0, $strLength, 'UTF-8');
                $char = mb_substr($text, -1, 1, 'UTF-8');
            }
        }
        if($strLength == 0){
            return $leading.$trailing;
        }
        // split to parts
        $parts = preg_split('/(\s+)/s', $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        // if only one part
        if(count($parts) == 1){
            return $leading.self::generateWord($firstCharUppercase).$trailing;
        }
        $numWordsOrig = 0;
        $blanks = [];
        $punctuations = [];
        foreach($parts as $part){
            if(empty($part) || preg_match('/\s+/', $part) === 1){
                // record blanks
                $blanks[] = $part;
            } else {
                // record punctuation
                if(self::lastCharIsPunctuation($part)){
                    $punctuations[] = ['idx' => $numWordsOrig, 'char' => mb_substr($part, -1, 1, 'UTF-8')];
                }
                $numWordsOrig++;
            }
        }
        $numWords = self::randomNumWords($numWordsOrig);
        $numBlanks = count($blanks);
        $words = self::generateWordsArray($numWords, $firstCharUppercase);
        // model for punctuations
        $lastChars = array_fill(0, $numWords, '');

        // adjust punctuations following the ratio of words
        if(count($punctuations) > 0){
            foreach($punctuations as $punctuation){
                if($punctuation['idx'] === 0){
                    $lastChars[0] = $punctuation['char'];
                } else if($punctuation['idx'] === ($numWordsOrig - 1)){
                    $lastChars[$numWords - 1] = $punctuation['char'];
                } else {
                    $idx = round($punctuation['idx'] * $numWords / $numWordsOrig);
                    $idx = ($idx >= ($numWords - 1)) ? ($idx - 1) : (($idx == 0) ? ($idx + 1) : $idx);
                    if($idx > 0 && $idx < ($numWords - 1)){
                        $lastChars[$idx] = $punctuation['char'];
                    }
                }
            }
        }
        // create "translated" phrase
        $lastWordWasSentenceEnd = false;
        $result = '';
        for($i = 0; $i < $numWords; $i++){
            if($result != ''){
                $result .= ($i < $numBlanks) ? $blanks[$i] : ' ';
            }
            $result .= self::uppercaseFirst($words[$i], $lastWordWasSentenceEnd) . $lastChars[$i];
            $lastWordWasSentenceEnd = self::isSentenceEndPunctuation($lastChars[$i]);
        }
        return $leading.$result.$trailing;
    }

    /**
     * Returns a random if with a probability of 1 - TAG_DROP_RATIO / 10
     * @return bool
     */
    private static function keepTagIf() : bool {
        return (mt_rand(0, 10) > self::TAG_DROP_RATIO);
    }

    /**
     * Randomizes the number of words to diverge within limits
     * @param int $numWords
     * @return int
     */
    private static function randomNumWords(int $numWords) : int {
        $divergence = ($numWords < self::WORD_DIVERGENCE) ? 1 : round($numWords / self::WORD_DIVERGENCE);
        $num = round(mt_rand($numWords - $divergence, $numWords + $divergence));
        return ($num < 1) ? 1 : $num;
    }

    /**
     * @param string $text
     * @param bool $flag
     * @return string
     */
    private static function uppercaseFirst(string $text, bool $flag) : string {
        if($flag && !empty($text)){
            return mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($text, 1, NULL, 'UTF-8');
        }
        return $text;
    }

    /**
     * @param string $text
     * @return bool
     */
    private static function lastCharIsPunctuation(string $text) : bool {
        if(!empty($text)){
            return self::isPunctuation(mb_substr($text, -1, 1, 'UTF-8'));
        }
        return false;
    }

    /**
     * @param string $text
     * @return bool
     */
    private static function lastCharIsSentenceEnd(string $text) : bool {
        if(!empty($text)){
            return self::isSentenceEndPunctuation(mb_substr($text, -1, 1, 'UTF-8'));
        }
        return false;
    }

    /**
     * @param string $char
     * @return bool
     */
    private static function isPunctuationOrWhitespace(string $char) : bool {
        return (ctype_space($char) || self::isPunctuation($char));
    }
}