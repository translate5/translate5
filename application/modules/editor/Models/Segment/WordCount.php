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
 * Count the word in segment text
 */
class editor_Models_Segment_WordCount {

    /***
     * Segment entity
     * @var editor_Models_Segment
     */
    protected $segment;
    
    
    /***
     * List of non-word characters (empty spaces)
     * This list is the practical implementation of the https://www.xtm.cloud/manuals/gmx-v/GMX-V-2.0.html#Words standard.
     * @var array
     */
    protected $whiteSpaceChars=array(
            '00000009',
            '0000000A',
            '0000000B',
            '0000000C',
            '0000000D',
            '00000020',
            '00000085',
            '000000A0',
            '00001680',
            '00002000',
            '00002001',
            '00002002',
            '00002003',
            '00002004',
            '00002005',
            '00002006',
            '00002007',
            '00002008',
            '00002009',
            '0000200A',
            '0000200D',
            '00002028',
            '00002029',
            '0000202F',
            '0000205F',
            '00003000',
            '0000feff'
    );
    
    /***
     * Rfc language code for the segment
     * @var string
     */
    protected $rfcLanguage;
    
    public function __construct($rfcLanguage=""){
        $this->rfcLanguage=$rfcLanguage;
    }
    
    /**
     * Convert a string into an array of decimal Unicode code points.
     *
     * @param $string   [string] The string to convert to codepoints
     * @param $encoding [string] The encoding of $string
     *
     * @return [array] Array of decimal codepoints for every character of $string
     */
    protected function toCodePoint( $string, $encoding ){
        $utf32  = mb_convert_encoding( $string, 'UTF-32', $encoding );
        $length = mb_strlen( $utf32, 'UTF-32' );
        $result = [];
        
        
        for( $i = 0; $i < $length; ++$i ){
            $result[] = bin2hex( mb_substr( $utf32, $i, 1, 'UTF-32'  ));
        }
        return $result;
    }
    
    /***
     * Approximate word count based on averages of characters per word (East Asian languages by language)
     *
     * @param string $text
     * @param number $average
     * @return number
     */
    protected function getGraphemeCount($text,$average){
        $count = grapheme_strlen($text);
        return round($count/$average);
    }
    
    /***
     * Remove punctuation from text
     * 
     * @param unknown $text
     * @return mixed
     */
    protected function removePunctuation($text){
        $re = '/[\x{2000}-\x{206F}\x{2E00}-\x{2E7F}\\\\\'!"#$%&()*+,\-.\/:;<=>?@\[\]^_`§{|}~]/u';
        return preg_replace($re, " ", $text);
    }
    
    public function setSegment(editor_Models_Segment $segment){
        $this->segment = $segment;
    }
    
    /***
     * Get count of the words in source field in the segment
     * All segment tags, and punctuation will be removed before the segment words are counted.
     * For easter asian languages, the grapheme count based on average grapheme per word will be calculated
     * 
     * @return number|mixed
     */
    public function getSourceCount(){
        $text=$this->segment->stripTags($this->segment->getFieldOriginal('source'));
        $text=$this->removePunctuation($text);
        
        
        //average words in East Asian languages by language
        //Chinese (all forms): 2.8
        //Japanese: 3.0
        //Korean: 3.3
        //Thai: 6.0
        switch (strtolower($this->rfcLanguage)) {
            case 'zh':
            case 'zh-hk':
            case 'zh-mo':
            case 'zh-sg':
                return $this->getGraphemeCount($text, 2.8);
                break;
            case 'th-th':
                return $this->getGraphemeCount($text, 6.0);
            case 'ja-jp':
                return $this->getGraphemeCount($text, 3.0);
                break;
            case 'ko-kr':
                return $this->getGraphemeCount($text, 3.3);
                break;
            default:
                $retText=$this->toCodePoint($text,'UTF-8');
                $finalString="";
                foreach ($retText as $ret){
                    if(in_array($ret, $this->whiteSpaceChars)){
                        $ret='00000020';
                    }
                    $ret=hexdec($ret);//to decimal
                    
                    $ret=chr($ret);//get char
                    
                    $finalString.=$ret;
                }
                return $count = str_word_count($finalString, 0);
                break;
        }
        return 0;
    }
}