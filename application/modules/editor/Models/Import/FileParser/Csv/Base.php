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
 * Base Class with functionality to protect tags for further processing by using placeholders
 */
abstract class editor_Models_Import_FileParser_Csv_Base extends editor_Models_Import_FileParser {

    /**
     *
     * @var string special placeholder needed in the loop that protects different kind of strings and tags in csv for the editing process
     */
    protected string $placeholderPrefix = '𓇽𓇽𓇽𓇽𓇽𓇽';

    /**
     *
     * @var array syntax: array('𓇽𓇽𓇽𓇽𓇽𓇽1' => '<div class="single 73706163652074733d2263326130222f"><span title="<space/>" class="short" id="ext-gen1796">&lt;1/&gt;</span><span id="space-2-b31345d64a8594d0e7b79852d022c7f2" class="full">&lt;space/&gt;</span></div>');
     *      explanation: key: the string that is the placeholder for the actual to be protected string
     *                   value: the to be protected string already converted to a translate5 internal tag
     */
    protected array $protectedStrings = array();

    /**
     * Insert Placeholders for the passed regex
     * be careful: if segment does not contain a "<", this method will simply return the segment (for performance reasons)
     * @param string $segment
     * @param string $tagToReplaceRegex - should contain a regex, that stands for a tag, that should be hidden for parsing reasons by a placeholder.
     * @return string
     */
    protected function parseSegmentInsertPlaceholders(string $segment, string $tagToReplaceRegex) : string {
        if(strpos($segment, '<') === false){
            return $segment;
        }
        $str_replace_first = function($search, $replace, $subject) {
            $pos = strpos($subject, $search);
            if ($pos !== false) {
                $subject = substr_replace($subject, $replace, $pos, strlen($search));
            }
            return $subject;
        };
        preg_match_all($tagToReplaceRegex, $segment, $matches, PREG_PATTERN_ORDER);
        //"<div\s*class=\"([a-z]*)\s+([gxA-Fa-f0-9]*)\"\s*.*?(?!</div>)<span[^>]*id=\"([^-]*)-.*?(?!</div>).</div>"s
        $protectedStringCount = count($this->protectedStrings);
        foreach ($matches[0] as $match) {
            $placeholder = $this->placeholderPrefix.$protectedStringCount;
            $this->protectedStrings[$placeholder] = $match;
            $segment = $str_replace_first($match,$placeholder,$segment);
            $protectedStringCount++;
        }
        return $segment;
    }

    /**
     * Restores all inserted placeholders
     * @param string $segment
     * @return string
     */
    protected function parseSegmentReplacePlaceholders(string $segment) : string {
        $placeholders = array_keys($this->protectedStrings);
        $tags = array_values($this->protectedStrings);
        $this->protectedStrings = array();
        return str_replace($placeholders, $tags, $segment);
    }
}
