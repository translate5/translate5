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

/***
 * This class is used to replace the content between two ranges(the ranges are provided by editor_Models_SearchAndReplace_FindMatchesHtml)/indexes (start and end index) 
 * in string/segment with or without html tags in it.
 * Unneded content betwen those ranges will be removed or moved after the replace string(see assembleReplaceContent function)
 * 
 * @author aleksandar
 *
 */
class editor_Models_SearchAndReplace_ReplaceMatchesSegment{

    //internal protected tag regex
    const REGEX_PROTECTED_INTERNAL='/<translate5:escaped[^>]+((id="([^"]*)"[^>]))[^>]*>/';
    
    /***
     * Is track changes plugin active
     * 
     * @var boolean
     */
    public $isActiveTrackChanges=false;
    
    /***
     * The initial segment text
     * @var string
     */
    public $segmentText;
    
    
    /***
     * Segment id
     * @var int
     */
    public $segmentId;
    
    
    /***
     * Replace field
     * @var string
     */
    public $replaceTarget;
    
    
    /***
     * Temporary range offset
     * @var integer
     */
    private $rangesOffset=0;
    
    
    /***
     * 
     * @var editor_Models_Segment_InternalTag
     */
    private $internalTagHelper;
    
    /***
     * 
     * @var editor_Models_Segment_TrackChangeTag
     */
    public $trackChangeTag;
    
    public function __construct($text, $target, $id){
        $this->segmentText=$text;
        $this->replaceTarget=$target;
        $this->segmentId=$id;
        
        $this->internalTagHelper = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->trackChangeTag= ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        
        $this->rangesOffset=0;
    }
    
    
    /***
     * Find and replace the matches in the segment text
     *   
     * @param string $queryString
     * @param string $replaceText
     * @param string $searchType
     * @param bool $matchCase
     */
    public function replaceText($queryString, $replaceText,$searchType,$matchCase=false){
        
        //protect the tags and remove the terms
        $this->protectTags();
        
        //find matches in the segment
        $html = new editor_Models_SearchAndReplace_FindMatchesHtml($this->segmentText);
        $html->matchCase=$matchCase;
        
        //find match ranges in the original segment text
        $replaceRanges = $html->findContent($queryString,$searchType);
        
        //merge the replace string in the segment
        $this->assembleReplaceContent($replaceRanges, $replaceText);
        
        //unprotect the tags
        $this->unprotectTags();
        
    }
    
    
    /***
     * Insert the replace text in the segment text based on the given range.
     *  
     * @param array $replaceRanges
     * @param string $replaceText
     */
    private function assembleReplaceContent($replaceRanges,$replaceText){
        
        
        foreach ($replaceRanges as $range){

            //update the ranges with given offset
            $range['start']=$range['start']+$this->rangesOffset;
            $range['end']=$range['end']+$this->rangesOffset;
            
            //handle delete tags in range
            preg_match_all(editor_Models_Segment_TrackChangeTag::REGEX_PROTECTED_DEL, $range['text'], $tempMatchesProtectedDel, PREG_OFFSET_CAPTURE);
            foreach ($tempMatchesProtectedDel[0] as $match) {
                //remove the protected del tag conteng from the array if matches
                $this->trackChangeTag->updateOriginalTagValue($match[0],"");
            }
            
            $stackEnd=[];
            //handle internal tags in range
            preg_match_all(self::REGEX_PROTECTED_INTERNAL, $range['text'], $tempMatchesProtectedInternal, PREG_OFFSET_CAPTURE);
            foreach ($tempMatchesProtectedInternal[0] as $match) {
                $stackEnd[]=$match[0];
            }
            
            //open insert tag is found
            $insOpen=false;
            //insert tag at end of the text
            $insAtEnd="";
            //all tags to be placed at the beginning of the string
            $stackStart=[];
            
            //merge the insert tags in the replace range
            //remove pair tags (start and end ins in the range)
            //move unpaired end tags at the beggining of the replace string
            //move unpaired start tags at the end of the replace string
            $rangePiece=preg_replace_callback(editor_Models_Segment_TrackChangeTag::REGEX_INS, function($match) use (&$insOpen,&$stackStart,&$insAtEnd){
                
                //if the replace range is in the midle of an ins
                //ex. Aleksandar </ins> mitrev    (move the tag at the begining of the replace text) ->  </ins>Aleksandar Mitrev
                if(!$insOpen && strtolower($match[0])==="</ins>"){
                    $stackStart[]=$match[0];
                    return "";
                }
                
                //if the match is an open ins tag, collect the tag
                if(substr(strtolower($match[0]),0,5)==="<ins "){
                    $insOpen=true;
                    $insAtEnd=$match[0];
                    return "";
                }
                //if it is an end ins tag, the paired tag is reached
                if(strtolower($match[0])==="</ins>"){
                    $insAtEnd="";
                    $insOpen=false;
                    return "";
                }
            },$range['text']);
            
            
            //create the replace delete part
            $deletePart=implode('',$stackStart).$this->getDeletePart($rangePiece);
            //create the replace ins part
            $insertPart=$this->getInsertPart($replaceText.implode('', $stackEnd)).$insAtEnd;
            
            $str=$deletePart.$insertPart;
            
            //calculate the offset
            $this->rangesOffset+=strlen($str)-$range['length'];
            
            //insert the text at the position
            $this->segmentText=$this->insertTextAtRange($str, $range['start'], $range['end']);
            
        }
        
    }
    
    /***
     * Protect the internal tags and the del tags from the segment text.
     * Remove the terms from the segment text.
     */
    private function protectTags(){
        $termTag=ZfExtended_Factory::get('editor_Models_Segment_TermTag');
        /* @var $termTag editor_Models_Segment_TermTag */
        
        //remove the terms from the string. The term tagger should be started before the segment is saved
        $this->segmentText=$termTag->remove($this->segmentText,true);
        
        //protect the internal tags
        $this->segmentText= $this->internalTagHelper->protect($this->segmentText);
        
        //protect the del tags
        $this->segmentText=$this->trackChangeTag->protect($this->segmentText);
    }
    
    /***
     * Unprotect the tags
     */
    private function unprotectTags(){
        $this->segmentText= $this->internalTagHelper->unprotect($this->segmentText);
        $this->segmentText= $this->trackChangeTag->unprotect($this->segmentText);
    }
    
    /***
     * Insert the text at range and return the result
     * 
     * @param string $text        - text to be inserted
     * @param int $startIndex - start index 
     * @param int $endIndex   - end index
     * @return string
     */
    private function insertTextAtRange($text, $startIndex, $endIndex){
        $partOne=substr($this->segmentText, 0, $startIndex);
        $partTwo=substr($this->segmentText, $endIndex);
        return $partOne.$text.$partTwo;
    }
    
    /***
     * Handle the delete part of the replacement
     * 
     * @param string $deleteText
     * @return string
     */
    private function getDeletePart($deleteText){
        if(!$this->isActiveTrackChanges || $deleteText===""){
            return "";
        }
        return $this->trackChangeTag->createTrackChangesNode(editor_Models_Segment_TrackChangeTag::NODE_NAME_DEL,$deleteText);
    }
    
    /***
     * Handle the insert part of the replacement
     * 
     * @param string $insertText
     * @return string
     */
    private function getInsertPart($insertText){
        if(!$this->isActiveTrackChanges || $insertText===""){
            return $insertText;
        }
        return $this->trackChangeTag->createTrackChangesNode(editor_Models_Segment_TrackChangeTag::NODE_NAME_INS,$insertText);
    }
    
}