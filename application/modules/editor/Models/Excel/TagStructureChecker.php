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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

/**
 * @TODO: this class needs to be moved... but where ????
 */
class editor_Models_Excel_TagStructureChecker {
    
    /**
     * Regex to find all tags
     * @var string
     */
    const REGEX_TAG = '#<(.*?)([\/]{0,1})>#im';
    
    /**
     * text containing tags for tests.
     * this text will be checked if ($text = NULL, $testMode = TRUE) is submitted to the function check()
     * @var string
     */
    protected static $testText = 
    '<div class="myclass"><h1>Hello world!</h1><p style="font-weight: 700;">this is<br />only a test.</p><hr/></div>'; // valid
    //'<div class="myclass"><h1>Hello world!</h1><p style="font-weight: 700;">this is<br />only a test.</pipi><hr/></div>'; // invalid: with closing </pipi> instead of </p>
    //'no tags at all'; // valid also it contains no tags
    
    
    /**
     * container to store the number of found tags
     * @var integer
     */
    protected $countTags = 0;
    
    
    /**
     * internal list to hold all open tags in their upcoming order.
     * @var array
     */
    protected $keyChain = [];
    
    /**
     * Container to store te check-error
     * @var string
     */
    protected $error = '';
    
    
    /**
     * check the submitted $text if the tag structure is valid.
     * 
     * @param string $text
     * @param boolean $testMode
     * @return boolean
     */
    public function check($text = NULL, $testMode = FALSE) {
        // reset all internal parameter
        $this->reset();
        
        // if we want a test, the testText will be used
        if (is_null($text) && $testMode == TRUE) {
            $text = self::$testText;
        }
        
        // read all tags from $text into $tempTags
        preg_match_all(self::REGEX_TAG, $text, $tempTags, PREG_SET_ORDER);
        
        // store the number of found tags
        $this->countTags = count($tempTags);
        
        // loop through all tags, and check the different tags (opening, closing, selfclosing)
        foreach($tempTags as $tag) {
            // selfclosing tags contain an '/' on position 2
            if ($tag[2] == '/') {
                // they are not relevant for further checking
                continue;
            }
            
            // closing tags have a '/' at the fist place in position 1
            if (strpos($tag[1], '/') === 0) {
                // if a closing tag is detected, the last element in $this->keyChain must be its corresponding opening tag, therefor we
                // get (and remove) the last element of $this->keyChain
                $tempLastKeyChain = array_pop($this->keyChain);
                // and now we check if this last element exists and is equal to the tag-name of the detected closing tag
                if (!$tempLastKeyChain || $this->getTagName($tag[1]) != $tempLastKeyChain) {
                    $this->error = 'unpaired closing tag was detected.'."\n".$tag[0].' was paired with a tag of type "'.$tempLastKeyChain.'"';
                    return FALSE;
                }
                continue;
            }
            
            // the rest must be opening tags, so we add its name to the keychain
            $this->keyChain[] = $this->getTagName($tag[1]);
        }
        
        // if the keyChain is not empty at the end, there was at least one unpaired opening tag.
        if (!empty($this->keyChain)) {
            $this->error = 'opening tag without a paired closing tag ('.implode(', ', $this->keyChain).')';
            return FALSE;
        }
        
        // everything is OK
        return TRUE;
    }
    
    
    /**
     * get the count of found tags of the last check
     * @return int
     */
    public function getCount() : int {
        return $this->countTags;
    }
    
    /**
     * get the error of the last check
     * @param string $glue
     * @return string
     */
    public function getError() : string {
        return $this->error;
    }
    
    /**
     * rest all internal parameter
     */
    protected function reset() : void {
        $this->countTags = 0;
        $this->keyChain = [];
        $this->error = '';
    }
    
    /**
     * detect the tags name from the given $text.
     * @param string $text
     * @return string
     */
    protected function getTagName($text) : string {
        // remove leading '/' if there is one.
        if (strpos($text, '/') === 0) {
            $text = substr($text, 1);
        }
        $tempExplode = explode(' ', $text);
        
        return $tempExplode[0];
    }
}

