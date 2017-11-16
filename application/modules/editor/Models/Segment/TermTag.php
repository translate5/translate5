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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Segment Term Tag Helper Class
 * This class contains the regex definition and related helper methods to term tags of translate5
 * 
 * TO BE COMPLETED: There are several more places in translate5 which can make use of this class
 * 
 */
class editor_Models_Segment_TermTag {
    
    /**
     * @var string
     */
    const REGEX_TERM_TAG_START = '/<div[^>]+((class="([^"]*)"[^>]+data-tbxid="([^"]*)")|(data-tbxid="([^"]*)"[^>]+class="([^"]*)"))[^>]*>/';
    // just for historcal documentation: in the export the following regex was used: $termRegex = '/<div[^>]+class="term([^"]+)"\s+data-tbxid="([^"]+)"[^>]*>/s';
    const STRING_TERM_TAG_END = '</div>';
    
    /**
     * Helpers decoding TrackChange-Nodes in a Term-tagged text.
     * 
     * Example for an ins-Node at position 4:
     * $arrTrackChangeNodes[$textId][4] = '<ins...>'
     * ($textId: We will need to assign the found TrackChange-Nodes to the original text later.
     * So we have to remember which text the found TrackChange-Nodes belong to!)
     */
    private $arrTrackChangeNodes = array();
    // For fetching the TrackChanges and TermTags:
    const REGEX_DEL     = '/<del[^>]*>.*?<\/del>/i';    // del-Tag:  including their content!
    const REGEX_INS     = '/<\/?ins[^>]*>/i';           // ins-Tag:  only the tags without their content
    const REGEX_TERMTAG = '/<\/?div[^>]*>/i';           // term-Tag: only the tags without their content (all other divs have been masked already)
    // for the process of decoding a given text:
    private $text;
    private $arrTrackChangeNodesInText = array();
    private $arrTermTagsInText = array();
    private $posInText;
    private $trackChangeNodeStatus;
    private $logText; // TODO: remove after development
    
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTags;
    
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTagHelper;
    
    /**
     * Optional internalTag Instance, if not given it is created internally
     * @param editor_Models_Segment_InternalTag $internalTag
     */
    public function __construct(editor_Models_Segment_InternalTag $internalTag = null) {
        if(!empty($internalTag)) {
            $this->internalTags = $internalTag;
        }
        $this->internalTagHelper = ZfExtended_Factory::get('editor_Models_Segment_InternalTag'); // TODO: use $this->internalTags instead, but that throws an error?!??! (also after calling initInternalTagHelper()... )
    }
    
    /**
     * Lazy instantiation of the internal tags helper
     */
    protected function initInternalTagHelper() {
        if(!empty($this->internalTags)) {
            $this->internalTags = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        }
    }
    
    /**
     * replaces term tags with either the callback or the given scalar
     * @see preg_replace
     * @see preg_replace_callback
     * @param string $segment
     * @param string|Callable $startTagReplacer If callable, parameters: $wholeMatch, $tbxId, array $cssClasses, $wholeSegment
     * @param string $endTagReplacer scalar only, since str_replace is used insted of preg_replace
     * @return mixed 
     */
    public function replace(string $segment, $startTagReplacer, string $endTagReplacer, $preserveInternal = false) {
        if($preserveInternal) {
            $this->initInternalTagHelper();
            $segment = $this->internalTags->protect($segment);
        }
        //if using a callback, we have to prepare matches to be the parameters
        if(is_callable($startTagReplacer)) {
            $replacer = function($match) use ($startTagReplacer, $segment) {
                $result = array_values($this->parseMatches($match));
                array_unshift($result, $match[0]);
                $result[] = $segment;
                return call_user_func_array($startTagReplacer, $result);
            };
        }
        else {
            $replacer = $startTagReplacer;
        }
        $segment = preg_replace_callback(self::REGEX_TERM_TAG_START, $replacer, $segment);
        $segment = str_replace(self::STRING_TERM_TAG_END, $endTagReplacer, $segment);
        if($preserveInternal) {
            return $this->internalTags->unprotect($segment);
        }
        return $segment;
    }
    
    /**
     * Removes term tags. Warning: if unsure if your content contains internal tags set parameter preserveInternal to true!
     * @param string $segment the segment content
     * @param boolean $preserveInternal if true, internal tags are masked before removing term tags.
     */
    public function remove(string $segment, $preserveInternal = false) {
        if($preserveInternal) {
            $this->initInternalTagHelper();
            $segment = $$this->internalTags->protect($segment);
        }
        $segment = preg_replace(self::REGEX_TERM_TAG_START, '', $segment);
        //This str_replace destroys our internal tags! so ensure that the content does not contain internal tags!
        // Either by masking or by removing before
        $segment = str_replace(self::STRING_TERM_TAG_END, '', $segment);
        if($preserveInternal) {
            return $this->internalTags->unprotect($segment);
        }
        return $segment;
    }
    
    /**
     * parses all term tags and returns a list with the tbxid (mid) and the css classes as array
     * @param string $segment
     * @return array
     */
    public function getInfos($segment) {
        preg_match_all(self::REGEX_TERM_TAG_START, $segment, $matches, PREG_SET_ORDER);
        $result = array();
        foreach($matches as $match) {
            $result[] = $this->parseMatches($match);
        }
        return $result;
    }
    
    /**
     * parses the preg_ matches array for the term start tag regex
     * @param array $matches
     */
    protected function parseMatches(array $match) {
        //class before data-tbxid
        if(empty($match[5])) {
            $mid = $match[4];
            $classes = $match[3];
        }
        //data-tbxid before class 
        else {
            $mid = $match[6];
            $classes = $match[7];
        }
        return array('mid' => $mid, 'classes' => explode(' ', $classes));
    }
    
    /**
     * categorizes the given css classes, removes the default "term" class
     * @param array $classes
     */
    public function parseClasses(array $classes) {
        $result = [
            'status',
            'translated',
            'other',
        ];
    }
    
    // ================================================================================
    // Helper for removing and re-inserting TrackChange-Nodes in Term-tagged texts.
    // Needed before and after texts are sent to the TermTag-Server for finding the terms.
    // - First step (before): Store all TrackChange-Nodes and their positions; then remove them from the text.
    // - Second step (after): Re-insert the stored TrackChange-Nodes at the stored positions in the text that now includes the TermTags, too.
    // ================================================================================
    
    /**
     * Store all TrackChange-Nodes and their positions; then remove them from the text.
     * @param string $text
     * @param string $textId
     * @return string
     */
    public function encodeTrackChanges($text, $textId) {        
        $this->initInternalTagHelper();
        $text = $this->internalTagHelper->protect($text);
        $this->fetchTrackChangeNodes($text, $textId);
        $text = $this->internalTagHelper->unprotect($text);
        // Return the text without the TrackChanges.
        return $this->internalTagHelper->removeTrackChanges($text);
    }
    
    /**
     * Re-insert the stored TrackChange-Nodes at the stored positions in the text (that now includes the TermTags, too).
     * @param string $text
     * @param string $textId
     * @return string
     */
    public function decodeTrackChanges($text, $textId) {
        
        if (!array_key_exists($textId, $this->arrTrackChangeNodes)) {
            //throw new ZfExtended_Exception('Decoding TrackChanges failed because there is no information about the original version (textId: ' . $textId . '): ' . $text);
            error_log('Decoding TrackChanges failed because there is no information about the original version (textId: ' . $textId . '): ' . $text);
            return $text;
        }
        
        $this->text = $text;
        $this->text = $this->internalTagHelper->protect($this->text);
        
        $this->logText = "\n-----------\n" . $text . "\n";
        
        $this->arrTrackChangeNodesInText = $this->arrTrackChangeNodes[$textId]; // TrackChange-Markup: stored before cleaning the text BEFORE using the TermTagger-Server
        $this->arrTermTagsInText = $this->fetchTermTags($this->text, $textId);  // Term-Tags: fetched now from the text AFTER using the TermTagger-Server
        
        // Re-insert the stored TrackChange-Nodes:
        
        $this->trackChangeNodeStatus = null;
        $this->posInText = 0;
        $posEnd = strlen($this->text);
        while ($this->posInText < $posEnd) {
            $posAtTheBeginningOfThisStep = $this->posInText;
            $foundTermTag = array_key_exists($this->posInText, $this->arrTermTagsInText);
            $foundTrackChangeMarkup = array_key_exists($this->posInText, $this->arrTrackChangeNodesInText);
            switch (true) {
                case ($foundTermTag && $foundTrackChangeMarkup && $this->trackChangeNodeStatus == 'open'):
                    // If a TrackChange-Node ist still open and both a TrackChange-Node AND a TermTag are found,
                    // we will close the TrackChange-Node first. The next loop then will recognize the other item from the TermTags.
                    $this->logText .= "\n" . $this->posInText .": foundTermTag && foundTrackChangeMarkup";
                    $posEnd += $this->handleTrackChangeNodeInText();
                    $this->logText .= "\n- weiter bis: " . $posEnd . "\n\n";
                    break;
                case $foundTermTag:
                    $this->logText .= "\n" . $this->posInText .": foundTermTag";
                    $posEnd += $this->handleTermTagInText();
                    $this->logText .= "\n- weiter bis: " . $posEnd . "\n\n";
                    break;
                case $foundTrackChangeMarkup:
                    $this->logText .= "\n" . $this->posInText .": foundTrackChangeMarkup";
                    $posEnd += $this->handleTrackChangeNodeInText();
                    $this->logText .= "\n- weiter bis: " . $posEnd . "\n\n";
                    break;
            }
            if ($this->posInText == $posAtTheBeginningOfThisStep) { // if we increase $pos after it has already been increased in the current step we will skip the current $pos
                $this->posInText++;
            }
        }
        
        $this->logText .= "\nERGEBNIS:\n" . $this->text;
        if (true) {
            error_log($this->logText); // TODO: remove logText after development
        }
        
        $this->text = $this->internalTagHelper->unprotect($this->text);
        
        
        // For safety reasons: delete the decoded item from the stored items so it cannot be used for another text that might per accident have the same textId.
        // (Maybe the FIRST text using it was the wrong one, but at least we know NOW that something went wrong.)
        unset($this->arrTrackChangeNodes[$textId]);
        
        return $this->text;
    }
    
    /**
     * Fetch the TrackChanges in the given text and store them in an array.
     * (textId needed for later matching these TrackChanges with their original text.)
     * @param string $text
     * @param string $textId
     */
    private function fetchTrackChangeNodes($text, $textId) {
        $this->arrTrackChangeNodes[$textId] = array();
        // - DEL
        preg_match_all(self::REGEX_DEL, $text, $tempMatchesTrackChangesDEL, PREG_OFFSET_CAPTURE);
        foreach ($tempMatchesTrackChangesDEL[0] as $match) {
            $this->arrTrackChangeNodes[$textId][$match[1]] = $match[0];
        }
        //- INS
        preg_match_all(self::REGEX_INS, $text, $tempMatchesTrackChangesINS, PREG_OFFSET_CAPTURE);
        foreach ($tempMatchesTrackChangesINS[0] as $match) {
            $this->arrTrackChangeNodes[$textId][$match[1]] = $match[0];
        }
        ksort($this->arrTrackChangeNodes[$textId]);
    }
    
    /**
     * Fetch the TermTags in the given text and return them in an array.
     * @param string $text
     * @return array
     */
    private function fetchTermTags($text) {
        $allTermTagsInText = array();
        preg_match_all(self::REGEX_TERMTAG, $text, $tempMatchesTermTags, PREG_OFFSET_CAPTURE);
        foreach ($tempMatchesTermTags[0] as $match) {
            $allTermTagsInText[$match[1]] = $match[0];
        }
        ksort($allTermTagsInText);
        return $allTermTagsInText;
    }
    
    /**
     * If there is a termTag in the text at this position, we need to:
     * - get all needed items related to the current $pos before positions in the text/arrays change
     * - close the current TrackChange-Node in case we are in the midst of one
     * - increase the following positions of the found TrackChange-Nodes by the length of the found termTag
     * - re-open the current TrackChange-Node in case we are in the midst of one
     * Returns the length of text that has been added.
     * @return number
     */
    private function handleTermTagInText() {
        $textLengthIncreased = 0;
        $openingTrackChangeNode = null;
        $closingTrackChangeNode = null;
        $textLengthIncreased = 0;
        $termTagInText = $this->arrTermTagsInText[$this->posInText];
        if ($this->trackChangeNodeStatus == 'open') {
            $openingTrackChangeNode = $this->getThresholdItemInArray($this->arrTrackChangeNodesInText, $this->posInText, 'before');
            $closingTrackChangeNode = $this->getThresholdItemInArray($this->arrTrackChangeNodesInText, $this->posInText, 'next');
        }
        if ($closingTrackChangeNode != null) {
            $length = strlen($closingTrackChangeNode);
            $this->arrTrackChangeNodesInText = $this->increaseKeysInArray($this->arrTrackChangeNodesInText, $length, $this->posInText);
            $this->arrTermTagsInText = $this->increaseKeysInArray($this->arrTermTagsInText, $length, $this->posInText);
            $this->logText .= "\n" . $this->posInText .": closingTrackChangeNode";
            $textLengthIncreased += $this->insertTextAtCurrentPos($closingTrackChangeNode);
        }
        $length = strlen($termTagInText);
        $this->arrTrackChangeNodesInText = $this->increaseKeysInArray($this->arrTrackChangeNodesInText, $length, $this->posInText);
        $this->posInText += $length;
        $this->logText .= "\n" . $this->posInText .": vorgefunden: " . $termTagInText.  "\n- length:" . $length . "\n- weiter bei: " . $this->posInText;
        if ($openingTrackChangeNode != null) {
            $length = strlen($openingTrackChangeNode);
            $this->arrTrackChangeNodesInText = $this->increaseKeysInArray($this->arrTrackChangeNodesInText, $length, $this->posInText);
            $this->arrTermTagsInText = $this->increaseKeysInArray($this->arrTermTagsInText, $length, $this->posInText);
            $this->logText .= "\n" . $this->posInText .": openingTrackChangeNode";
            $textLengthIncreased += $this->insertTextAtCurrentPos($openingTrackChangeNode);
        }
        return $textLengthIncreased;
    }
    
    /**
     * If there is a TrackChange-Node in the text at this position, we need to:
     * - get all needed items related to the current $pos before positions in the text/arrays change
     * - increase the following positions of the found TermTags by the length of the found TrackChange-Node
     * - re-enter the TrackChange-Node here
     * - set the status of the current TrackChange-Node (open/close) (but only when opening and closing tags are handled extra, thus not for "<del>...</del>")
     * Returns the length of text that has been added.
     * @return number
     */
    private function handleTrackChangeNodeInText() {
        $textLengthIncreased = 0;
        $trackChangeNodeInText = $this->arrTrackChangeNodesInText[$this->posInText];
        $length = strlen($trackChangeNodeInText);
        $this->arrTermTagsInText = $this->increaseKeysInArray($this->arrTermTagsInText, $length, $this->posInText);
        $textLengthIncreased += $this->insertTextAtCurrentPos($trackChangeNodeInText);
        if (!preg_match_all(self::REGEX_DEL, $trackChangeNodeInText)) {
            $this->trackChangeNodeStatus = ($this->trackChangeNodeStatus == 'open') ? 'close' : 'open'; // start was null and the first step must go to 'open'
        }
        return $textLengthIncreased;
    }
    
    /**
     * Insert given string at the current position and forward the position by length of given $textToInsert.
     * Returns the length of text that has been added.
     * @param string $textToInsert
     * @return number
     */
    private function insertTextAtCurrentPos($textToInsert) {
        $length = strlen($textToInsert);
        $this->text = substr($this->text, 0, $this->posInText) . $textToInsert . substr($this->text, $this->posInText);
        $this->posInText += $length;
        $this->logText .= "\n- eingefuegt: " . $textToInsert .  "\n- length:" . $length . "\n- weiter bei: " . $this->posInText;
        return $length;
    }
    
    /**
     * Returns the array-item of the key that is after or before/at the given threshold.
     * @param array $arr
     * @param number $threshold
     * @param number $direction
     * @return array
     */
    private static function getThresholdItemInArray ($arr, $threshold, $direction) {
        if ($direction == 'next') {
            end($arr);
            while(key($arr) > $threshold) prev($arr);   // set internal pointer to position before $threshold
            return next($arr);                          // return the item after that position.
        }
        if(array_key_exists($threshold, $arr)) {        // If there IS an item at the threshold's position
            return $arr[$threshold];                    // return that one.
        }
        while(key($arr) < $threshold) next($arr);       // set internal pointer to position after $threshold
        return prev($arr);                              // return the item before that position.
    }
    
    /**
     * Returns a "new version" of the given array with keys increased by the given number.
     * Increases only those keys that are higher than the given threshold.
     * @param array $arr
     * @param number $number
     * @param number $threshold
     * @return array
     */
    private static function increaseKeysInArray ($arr, $number, $threshold) {
        $arrOldValues = array_values($arr);
        $arrOldKeys = array_keys($arr);
        $arrNewKeys = array_map(function($oldKey) use ($number, $threshold) {
            if ($oldKey < $threshold) {
                return $oldKey;
            } else {
                return $oldKey + $number;
            }
        }, $arrOldKeys);
        return array_combine($arrNewKeys, $arrOldValues);
    }
}