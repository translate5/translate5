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

class FakeTranslator
{
    /**
     * Generates a fake translation for Markup
     * @param string $markup
     * @param bool $dropTagsRandomly: if set, tags are dropped randomly, with a probability of 33%
     * @return string
     */
    public static function translateMarkup(string $markup, bool $dropTagsRandomly=false) : string {
        // first we need to split comments as they would be destroyed by the next step otherwise
        $parts = preg_split(Markup::COMMENT_PATTERN.'Us', $markup, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        foreach($parts as $part){
            if(preg_match(Markup::COMMENT_PATTERN.'s', $part) === 1){
                if(!$dropTagsRandomly || self::randomIf()){
                    $result .= $part;
                }
            } else {
                $innerparts = preg_split(Markup::PATTERN.'U', $markup, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
                foreach($innerparts as $innerpart){
                    if(preg_match(Markup::PATTERN, $innerpart) === 1){
                        if(!$dropTagsRandomly || self::randomIf()){
                            $result .= $innerpart;
                        }
                    } else {
                        $result .= self::translateText($innerpart);
                    }
                }
            }
        }
        return $result;
    }

    /**
     * fake translates a text without tags. This will only rotate the characters ...
     * @param string $text
     * @return string
     */
    public static function translateText(string $text) : string {
        $result = '';
        $parts = preg_split('/(\s+)/s', $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        foreach($parts as $part){
            if(empty($part) || preg_match('/\s+/', $part) === 1){
                $result .= $part;
            } else {
                $result .= str_rot13($part);
            }
        }
        return $result;
    }

    /**
     * @return bool
     */
    private static function randomIf() : bool {
        return (mt_rand(0, 3) > 1);
    }
}