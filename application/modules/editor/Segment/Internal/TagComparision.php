<?php
/*
 START LICENSE AND COPYRIGHT
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
 
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/gpl.html
 http://www.translate5.net/plugin-exception.txt
 
 END LICENSE AND COPYRIGHT
 */

/**
 * Compares two field-tags if they have the same amount of internal tags in the same order
 */
class editor_Segment_Internal_TagComparision {
    /**
     * @var string
     */
    const TAGS_MISSING = 'internal_tags_missing';
    /**
     * @var string
     */    
    const TAGS_ADDED = 'internal_tags_added';
    /**
     * @var string
     */
    const TAG_STRUCTURE_FAULTY = 'internal_tag_structure_faulty';
    /**
     * 
     * @var string;
     */
    private $stati = [];
    /**
     * 
     * @var editor_Segment_Internal_Tag[]
     */    
    private $checkTags = [];
    /**
     * 
     * @var int
     */
    private $numCheckTags = 0;
    /**
     *
     * @var editor_Segment_Internal_Tag[]
     */ 
    private $againstTags = [];
    /**
     *
     * @var int
     */
    private $numAgainstTags = 0;
    /**
     * 
     * @param editor_Segment_FieldTags $toCheck
     * @param editor_Segment_FieldTags $against
     */
    public function __construct(editor_Segment_FieldTags $toCheck, ?editor_Segment_FieldTags $against){
        $this->status = array();
        $toCheck->sort();
        $this->checkTags = $toCheck->getByType(editor_Segment_Tag::TYPE_INTERNAL);
        $this->numCheckTags = count($this->checkTags);
        // the structural check can be done without against tags
        $this->checkStructure();
        // there is a against
        if($against != NULL){
            $against->sort();
            $this->againstTags = $against->getByType(editor_Segment_Tag::TYPE_INTERNAL);
            $this->numAgainstTags = count($this->againstTags);
            // for the completeness check we need something to check against
            $this->checkCompleteness();
        }
    }
    /**
     * Here we check if all tags from checkAgainst are present in the check tags
     */
    private function checkCompleteness(){
        if($this->numCheckTags == $this->numAgainstTags){
            $checkHashes = [];
            $againstHashes = [];
            foreach($this->checkTags as $tag){
                $checkHashes[] = $tag->getHash();
            }
            foreach($this->againstTags as $tag){
                $againstHashes[] = $tag->getHash();
            }
            sort($checkHashes);
            sort($againstHashes);
            for($i=0; $i < $this->numCheckTags; $i++){
                if($checkHashes[$i] != $againstHashes[$i]){
                    $this->stati[] = self::TAGS_MISSING;
                    $this->stati[] = self::TAGS_ADDED;
                    return;
                }
            }
        } else {
            $this->stati[] = ($this->numCheckTags < $this->numAgainstTags) ? self::TAGS_MISSING : self::TAGS_ADDED;
        }
    }
    /**
     * Checks if the structure of internal tags is correct, every opener has his corresponding closer without overlaps
     */
    private function checkStructure(){
        if($this->numCheckTags == 0){
            return;
        }
        $numOpeners = 0;
        // check every opener if it has a corresponding closer with no overlaps inbetween
        for($i=0; $i < $this->numCheckTags; $i++){
            if($this->checkTags[$i]->isOpening()){
                $numOpeners++;
                if(!$this->nextClosingMatches($this->checkTags[$i]->getTagIndex(), $i + 1)){
                    $this->stati[] = self::TAG_STRUCTURE_FAULTY;
                    return;
                }
            }
        }
        // if we come that close we just check if we have the same amount of openers/closers t make sure there is no orphan closer ...
        $numClosers = 0;
        for($i=0; $i < $this->numCheckTags; $i++){
            if($this->checkTags[$i]->isClosing()){
                $numClosers++;
            }
        }
        if($numOpeners != $numClosers){
            $this->stati[] = self::TAG_STRUCTURE_FAULTY;
        }
    }
    /**
     * Finds for an opener the corresponding closer, accepts openers/closers inbetween as long as they the number of openers & closers equals
     * @param int $tagIndex
     * @param int $start
     * @return bool
     */
    private function nextClosingMatches(int $tagIndex, int $start) : bool {
        if($start >= $this->numCheckTags){
            return false;
        }
        $numOpen = 0;
        for($i=$start; $i < $this->numCheckTags; $i++){            
            if($this->checkTags[$i]->isClosing() && $this->checkTags[$i]->getTagIndex() == $tagIndex){
                // we only are the "correct" closer if no other tags are open or closed (the value will be negative then)
                // Note that we do not check the opened/closed tags for validity, this will be handled by the checks for those openers ...
                return ($numOpen === 0);
            } else if($this->checkTags[$i]->isOpening()) {
                $numOpen++;
            } else if($this->checkTags[$i]->isClosing()){
                $numOpen--;
                if($numOpen < 0){
                    // as soon as we have an closing tag (no the one we are searching for) and no opening tag before this is a structural fault
                    return false;
                }
            }
        }
        // closer not found
        return false;
    }
    /**
     * 
     * @return string[]
     */
    public function getStati(){
        return $this->stati;
    }
}
