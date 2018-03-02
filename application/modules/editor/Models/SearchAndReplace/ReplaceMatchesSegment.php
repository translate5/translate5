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

class editor_Models_SearchAndReplace_ReplaceMatchesSegment{

    // del-Tag:  including their content!
    const REGEX_DEL     = '/<del[^>]*>.*?<\/del>/i';
    
    // ins-Tag:  only the tags without their content
    const REGEX_INS     = '/<\/?ins[^>]*>/i';
    
    //del protected tag regex
    const REGEX_PROTECTED_DEL='/<segment:del[^>]+((id="([^"]*)"[^>]))[^>]*>/';
    
    //internal protected tag regex
    const REGEX_PROTECTED_INTERNAL='/<translate5:escaped[^>]+((id="([^"]*)"[^>]))[^>]*>/';
    
    //delete node name
    const NODE_NAME_DEL='del';
    
    //insert node name
    const NODE_NAME_INS='ins';
    
    //insert tag css class
    const CSS_CLASSNAME_INS='trackchanges ownttip';
    
    //delete tag css class
    const CSS_CLASSNAME_DEL='trackchanges ownttip deleted';
    
    // Attributes for the trackchange-Node
    const ATTRIBUTE_USERGUID='data-userguid';
    const ATTRIBUTE_USERNAME='data-username';
    const ATTRIBUTE_USERCSSNR='data-usercssnr';
    const ATTRIBUTE_WORKFLOWSTEP='data-workflowstep';
    const ATTRIBUTE_TIMESTAMP='data-timestamp';
    const ATTRIBUTE_HISTORYLIST='data-historylist';
    const ATTRIBUTE_HISTORY_SUFFIX='_history_';
    const ATTRIBUTE_ACTION='data-action';
    const ATTRIBUTE_USERCSSNR_VALUE_PREFIX='usernr';
    
    
    /**
     * Array of JSON with the userColorMap from DB's task_meta-Info
     */
    public $userColorNr;
    
    /***
     * Trackchanges workflow step attribute
     * 
     * @var unknown
     */
    public $attributeWorkflowstep;
    
    /***
     * Is trach changes plugin active
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
    
    
    public function __construct($text, $target, $id){
        $this->segmentText=$text;
        $this->replaceTarget=$target;
        $this->segmentId=$id;
        
        $this->internalTagHelper = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->rangesOffset=0;
    }
    
    
    /***
     * Find and replace the matches in the segment text
     *   
     * @param string $queryString
     * @param string $replaceText
     * @param string $searchType
     * @param boolean $matchCase
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
            preg_match_all(self::REGEX_PROTECTED_DEL, $range['text'], $tempMatchesProtectedDel, PREG_OFFSET_CAPTURE);
            foreach ($tempMatchesProtectedDel[0] as $match) {
                //remove the protected del tag conteng from the array if matches
                $this->internalTagHelper->getOriginalTags()[$match[0]]="";
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
            $rangePiece=preg_replace_callback(self::REGEX_INS, function($match) use (&$insOpen,&$stackStart,&$insAtEnd){
                
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
            $deletePart=implode('',$stackStart).$this->createTrackChangesNode('del',$rangePiece);
            //create the replace ins part
            $insertPart=$this->createTrackChangesNode('ins',$replaceText.implode('', $stackEnd)).$insAtEnd;
            
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
        
        //set new placeholder template
        $this->internalTagHelper->setPlaceholderTemplate('<segment:del id="%s" />');

        //set new replacer regex
        $this->internalTagHelper->setReplacerRegex(self::REGEX_DEL);
        
        //protect the del tags
        $this->segmentText=$this->internalTagHelper->protect($this->segmentText,true);
    }
    
    /***
     * Unprotect the tags
     */
    private function unprotectTags(){
        $this->segmentText= $this->internalTagHelper->unprotect($this->segmentText);
    }
    
    /***
     * Insert the text at range and return the result
     * 
     * @param string $text        - text to be inserted
     * @param integer $startIndex - start index 
     * @param integer $endIndex   - end index
     * @return string
     */
    private function insertTextAtRange($text, $startIndex, $endIndex){
        $partOne=substr($this->segmentText, 0, $startIndex);
        $partTwo=substr($this->segmentText, $endIndex);
        return $partOne.$text.$partTwo;
    }
    
    /**
     * replaces delete tags with either the callback or the given scalar
     * @see preg_replace
     * @see preg_replace_callback
     * @param string $segment
     * @param Closure|string $replacer
     * @param int $limit optional
     * @param int $count optional, returns the replace count
     * @return mixed
     */
    public function replace($segment, $replacer, $limit = -1, &$count = null) {
        if(!is_string($replacer) && is_callable($replacer)) {
            return preg_replace_callback(self::REGEX_DEL, $replacer, $segment, $limit, $count);
        }
        return preg_replace(self::REGEX_DEL, $replacer, $segment, $limit, $count);
    }
    
    /***
     * Create trackchanges node
     * 
     * @param string $nodeName
     * @param string $nodeText
     * @return string|string
     */
    public function createTrackChangesNode($nodeName,$nodeText){
        
        if(!$this->isActiveTrackChanges || $nodeText===""){
            return $nodeName===self::NODE_NAME_DEL ? "" : $nodeText;
        }
        $sessionUser = new Zend_Session_Namespace('user');
        
        $node=[];
        $node[]='<'.$nodeName;
        $node[]='class="'.$this->getTrachChangesCss($nodeName).'"';
        
        // id to identify the user who did the editing (also used for verifying checks)
        $node[]=self::ATTRIBUTE_USERGUID.'="'.$sessionUser->data->userGuid.'"';
        
        // name of the user who did the editing
        $node[]=self::ATTRIBUTE_USERNAME.'="'.$sessionUser->data->userName.'"';
        
        // css-selector with specific number for this user
        $node[]=self::ATTRIBUTE_USERCSSNR.'="'.self::ATTRIBUTE_USERCSSNR_VALUE_PREFIX.$this->userColorNr.'"';
        
        //workflow-step:
        $node[]=self::ATTRIBUTE_WORKFLOWSTEP.'="'.$this->attributeWorkflowstep.'"';
        
        $date = new DateTime();
        // timestamp af the change:
        $node[]=self::ATTRIBUTE_TIMESTAMP.'="'.$date->getTimestamp().'"';
        
        $node[]='>'.$nodeText.'</'.$nodeName.'>';
        
        return implode(' ', $node);
        
    }
    
    /***
     * Get trachckanges css class based on the node type
     * 
     * @param string $nodeName
     * @return string
     */
    private function getTrachChangesCss($nodeName){
        switch(strtolower($nodeName)) {
            case self::NODE_NAME_DEL:
                return self::CSS_CLASSNAME_DEL;
            case self::NODE_NAME_INS:
                return self::CSS_CLASSNAME_INS;
        }
    }
}