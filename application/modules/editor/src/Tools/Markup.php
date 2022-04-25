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


class Markup {

    /**
     * @var string
     */
    const PATTERN = '~(</{0,1}[a-zA-Z][^>]*/{0,1}>)~';
    /**
     * works only if ungreedy !
     * @var string
     */
    const COMMENT_PATTERN = '~(<!--.*-->)~';
    /**
     * Evaluates if a text contains Markup
     * @param string $text
     * @return bool
     */
    public static function isMarkup(string $text) : bool{
        return (strip_tags($text) != $text);
    }
    /**
     * Check if the text is valid markup i.e. can be parsed with our Markup-Parsers
     * Note, that this is tolerant against smaller Problems like <img src="SOMESOURCE"> -> missing self-closing delimiter
     * @param string $text
     * @return bool
     */
    public static function isValid(string $markup) : bool {
        // if there is markup, we have to make sure it's valid
        if(strip_tags($markup) != $markup){
            $domDocument = new \editor_Utils_Dom();
            $domDocument->loadUnicodeMarkup($markup);
            return $domDocument->isValid(false);
        }
        return true;
    }
    /**
     * escapes markup, leaves the tags and comments alive but escape any text inbetween to XML standards
     * Obviously this expect the markup to be valid...
     * @param string $markup
     * @return string
     */
    public static function escape(string $markup) : string {
        // first we need to escape comments as they would be destroyed by the next step otherwise
        $parts = preg_split(self::COMMENT_PATTERN.'Us', $markup, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        foreach($parts as $part){
            if(preg_match(self::COMMENT_PATTERN.'s', $part) === 1){
                $result .= $part;
            } else {
                $result .= self::escapePureMarkup($part);
            }
        }
        return $result;
    }
   /**
     * Escapes Markup that is expected to contain no comments
     * @param string $markup
     * @return string
     */
   private static function escapePureMarkup(string $markup) : string {
      $parts = preg_split(self::PATTERN.'U', $markup, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
       $result = '';
       foreach($parts as $part){
           if(preg_match(self::PATTERN, $part) === 1){
               $result .= $part;
           } else {
               $result .= self::escapeText($part);
           }
       }
       return $result;
   }
   /**
    * Unescapes markup escaped with ::escape
    * Be aware that this may creates invalid Markup !
    * @param string $markup
    * @return string
    */
   public static function unescape(string $markup) : string {
       // first we need to unescape comments as they would be destroyed by the next step otherwise
       $parts = preg_split(self::COMMENT_PATTERN.'Us', $markup, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
       $result = '';
       foreach($parts as $part){
           if(preg_match(self::COMMENT_PATTERN.'s', $part) === 1){
               $result .= $part;
           } else {
               $result .= self::unescapePureMarkup($part);
           }
       }
       return $result;
   }
    /**
     * Unescapes markup escaped with ::escape
     * Be aware that this may creates invalid Markup !
     * @param string $markup
     * @return string
     */
    private static function unescapePureMarkup(string $markup) : string {
        $parts = preg_split(self::PATTERN.'U', $markup, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        foreach($parts as $part){
            if(preg_match(self::PATTERN, $part) === 1){
                $result .= $part;
            } else {
                $result .= self::unescapeText($part);
            }
        }
        return $result;
    }
   /**
    * Escapes text to XML conformity that is known to contain no tags
    * @param string $textWithoutTags
    * @return string
    */
   public static function escapeText(string $textWithoutTags) : string {
       return htmlspecialchars($textWithoutTags, ENT_XML1 | ENT_COMPAT, null, false);
   }
   /**
    * Unescapes text that was escaped with our ::escape API
    * @param string $text
    * @return string
    */
   public static function unescapeText(string $text) : string {
       return htmlspecialchars_decode($text, ENT_XML1 | ENT_COMPAT);
   }
}