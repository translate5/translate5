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
 * Segment TermTagTrackChange Helper Class
 *
 * Helper for removing and re-inserting TrackChange-Nodes in Term-tagged texts.
 * Needed before and after texts are sent to the TermTag-Server that finds the terms.
 * - First step (before): Store all TrackChange-Nodes and their positions; then remove them from the text.
 * - Second step (after): Re-insert the stored TrackChange-Nodes at the stored positions in the text that now includes the TermTags, too.
 *
 * Nothing in here really relates to TrackChange-Stuff itself, only the regular expressions for finding the nodes.
 * Feel free to rename it for general use and extend it to other nodes.
 *
 * The Service Class of Plugin "TermTagger" (editor_Plugins_TermTagger_Service) uses these methods
 * no matter if the TrackChange-Plugin is activated or not.
 * That's why we need this in the core-Code, not in the Plugin-Code.
 *
 * ********************* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! *********************
 * NO MULTIBYTE_PROBLEMS occur at the moment - although we use strlen and substr. This is because we just 
 * walk through the string step by step, and it does not matter that these steps are bytes, not characters. 
 * USING mb_strlen and mb_substr however WILL PRODUCE WRONG RESULTS because we loose the offsets from PREG_OFFSET_CAPTURE 
 * in preg_match_all.
 * ********************* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! *********************
 * 
 */
class editor_Models_Segment_TermTagTrackChange {
    
    /**
     * Example for an ins-Node at position 4:
     * $arrTrackChangeNodes[$textId][4] = '<ins...>'
     * ($textId: We will need to assign the found TrackChange-Nodes to the original text later.
     * So we have to remember which text the found TrackChange-Nodes belong to!)
     */
    private $arrTrackChangeNodes = array();
    
    /**
     * For fetching/searching the TrackChanges and TermTags:
     */
    const REGEX_TERMTAG = '/<\/?div[^>]*>/i';           // term-Tag: only the tags without their content (all other divs have been masked already) // TODO: get regex from from editor_Models_Segment_TermTag
    
    /**
     * For the process of decoding a given text:
     */
    private $text;
    private $arrTrackChangeNodesInText = array();
    private $arrTermTagsInText = array();
    private $posInText;
    private $trackChangeNodeStatus;
    private $debugText;
    
    /**
     * @var editor_Models_Segment_InternalTag
     */
    private $internalTagHelper;
    
    /**
     * @var editor_Models_Segment_TermTag
     */
    protected $termTagHelper;
    
    /**
     * enables / disables debugging (logging), can be enabled by setting runtimeOptions.debug.core.termTagTrackChange = 1 in installation.ini
     * 0 => disabled
     * 1 => log called handler methods (logging must be manually implemented in the handler methods by usage of $this->doDebug)
     * @var integer
     */
    private $debug = 0;
    
    public function __construct() {
        $this->internalTagHelper = ZfExtended_Factory::get('editor_Models_Segment_InternalTag'); // TODO: lazy load?
        $this->termTagHelper = ZfExtended_Factory::get('editor_Models_Segment_TermTag');
        $this->debug = ZfExtended_Debug::getLevel('core', 'termTagTrackChange');
    }
    
    /**
     * Store all TrackChange-Nodes and their positions; then remove them from the text.
     * This arry must be stored even if it includes no Nodes; that there are NO nodes is exactly
     * an information we will need later on when it comes to (not) re-inserting the (not) found nodes.
     * @param string $text
     * @param string $textId
     * @return string
     */
    public function storeNodes($text, $textId) {
        $this->doDebug('Store TrackChangeNodes for (textId: ' . $textId . '): ' . $text);
        $text = $this->internalTagHelper->protect($text);
        $this->fetchTrackChangeNodes($text, $textId);
    }
    
    /**
     * Re-insert the stored TrackChange-Nodes at the stored positions in the text (that now includes - only! - the TermTags).
     * @param string $text
     * @param string $textId
     * @return string
     */
    public function restoreNodes($text, $textId) {
        $this->doDebug('Restore TrackChangeNodes for (textId: ' . $textId . '): ' . $text);
        
        if (!array_key_exists($textId, $this->arrTrackChangeNodes) || $this->arrTrackChangeNodes[$textId] == null) {
            //throw new ZfExtended_Exception('Decoding TrackChanges failed because there is no information about the original version (textId: ' . $textId . '): ' . $text);
            $this->doDebug('Decoding TrackChanges failed because there is no information about the original version (textId: ' . $textId . '): ' . $text);
            return $text;
        }
        
        // At this point, we cannot check for ins- and del-Nodes in the text
        // because the text that the TermTagger has returned does not include them anyway!
        // (It is OUR task HERE to re-include them if there have been any.)
        if (count($this->arrTrackChangeNodes[$textId]) == 0) {
            // no TrackChange-Markup found; return the termtagged text as it is.
            return $text;
        }
        
        $this->text = $text;
        $this->text = $this->internalTagHelper->protect($this->text);
        
        $this->debugText = "\n-----------\n" . $text . "\n";
        
        $this->arrTrackChangeNodesInText = $this->arrTrackChangeNodes[$textId]; // TrackChange-Markup: stored before cleaning the text BEFORE using the TermTagger-Server
        $this->arrTermTagsInText = $this->fetchTermTags($this->text, $textId);  // Term-Tags: fetched now from the text AFTER using the TermTagger-Server
        
        // Re-insert the stored TrackChange-Nodes:
        $this->trackChangeNodeStatus = null;
        $this->posInText = 0;
        $posEnd = strlen($this->text);
        while ($this->posInText <= $posEnd) {
            $posAtTheBeginningOfThisStep = $this->posInText;
            $foundTermTag = array_key_exists($this->posInText, $this->arrTermTagsInText);
            $foundTrackChangeMarkup = array_key_exists($this->posInText, $this->arrTrackChangeNodesInText);
            switch (true) {
                case ($foundTermTag && $foundTrackChangeMarkup && $this->trackChangeNodeStatus == 'open'):
                    // If a TrackChange-Node ist still open and both a TrackChange-Node AND a TermTag are found,
                    // we will close the TrackChange-Node first. The next loop then will recognize the other item from the TermTags.
                    $this->debugText .= "\n" . $this->posInText .": foundTermTag && foundTrackChangeMarkup";
                    $posEnd += $this->handleTrackChangeNodeInText();
                    $this->debugText .= "\n- weiter bis: " . $posEnd . "\n\n";
                    break;
                case $foundTermTag:
                    $this->debugText .= "\n" . $this->posInText .": foundTermTag";
                    $posEnd += $this->handleTermTagInText();
                    $this->debugText .= "\n- weiter bis: " . $posEnd . "\n\n";
                    break;
                case $foundTrackChangeMarkup:
                    $this->debugText .= "\n" . $this->posInText .": foundTrackChangeMarkup";
                    $posEnd += $this->handleTrackChangeNodeInText();
                    $this->debugText .= "\n- weiter bis: " . $posEnd . "\n\n";
                    break;
            }
            if ($this->posInText == $posAtTheBeginningOfThisStep) { // if we increase $pos after it has already been increased in the current step we will skip the current $pos
                $this->posInText++;
            }
        }
        
        $this->debugText .= "\nERGEBNIS:\n" . $this->text;
        $this->doDebug($this->debugText);
        
        $this->text = $this->internalTagHelper->unprotect($this->text);
        
        // For safety reasons: delete the decoded item from the stored items so it cannot be used for another text that might per accident have the same textId.
        // (Maybe this FIRST text using it was the wrong one, but at least on the following SECOND try we will know that something DID go wrong.)
        // If this happens, the given $textId was not unique enough.
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
        preg_match_all(editor_Models_Segment_TrackChangeTag::REGEX_DEL, $text, $tempMatchesTrackChangesDEL, PREG_OFFSET_CAPTURE);
        foreach ($tempMatchesTrackChangesDEL[0] as $match) {
            $this->arrTrackChangeNodes[$textId][$match[1]] = $match[0];
        }
        //- INS
        preg_match_all(editor_Models_Segment_TrackChangeTag::REGEX_INS, $text, $tempMatchesTrackChangesINS, PREG_OFFSET_CAPTURE);
        foreach ($tempMatchesTrackChangesINS[0] as $match) {
            $this->arrTrackChangeNodes[$textId][$match[1]] = $match[0];
        }
        ksort($this->arrTrackChangeNodes[$textId]);
    }
    
    /**
     * Are there no nodes to stored for the given textId?
     * @param string $textId
     * @return boolean
     */
    public function hasNoTrackChangeNodes($textId) {
        return empty($this->arrTrackChangeNodes[$textId]);
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
            $this->debugText .= "\n" . $this->posInText .": closingTrackChangeNode";
            $textLengthIncreased += $this->insertTextAtCurrentPos($closingTrackChangeNode);
        }
        $length = strlen($termTagInText);
        $this->arrTrackChangeNodesInText = $this->increaseKeysInArray($this->arrTrackChangeNodesInText, $length, $this->posInText);
        $this->posInText += $length;
        $this->debugText .= "\n- vorgefunden: " . $termTagInText.  "\n- length:" . $length . "\n- weiter bei: " . $this->posInText;
        if ($openingTrackChangeNode != null) {
            $length = strlen($openingTrackChangeNode);
            $this->arrTrackChangeNodesInText = $this->increaseKeysInArray($this->arrTrackChangeNodesInText, $length, $this->posInText);
            $this->arrTermTagsInText = $this->increaseKeysInArray($this->arrTermTagsInText, $length, $this->posInText);
            $this->debugText .= "\n" . $this->posInText .": openingTrackChangeNode";
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
        if (!preg_match_all(editor_Models_Segment_TrackChangeTag::REGEX_DEL, $trackChangeNodeInText)) {
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
        $this->debugText .= "\n- eingefuegt: " . $textToInsert .  "\n- length: " . $length . "\n- weiter bei: " . $this->posInText;
        return $length;
    }
    
    /**
     * Returns the array-item of the key that is after or before/at the given threshold.
     * @param array $arr
     * @param number $threshold
     * @param number $direction
     * @return array||string
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
    
    /**
     * simple debugging
     * @param string $name
     */
    private function doDebug($name) {
        if($this->debug === 1) {
            error_log($name);
        }
    }
}