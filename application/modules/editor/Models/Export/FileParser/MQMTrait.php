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
 * this trait encapsulates all methods of the export fileparser, that deal with MQM-conversion during export
 * 
 * FIXME should be an own "converter" class, and not just a trait for the export fileparsers. 
 */
trait editor_Models_Export_FileParser_MQMTrait {
       /**
     * Set in convertQmTags2XliffFormat() and used by _addXlifQmTag()
     * 
     * @var string
     */
    private  $_user;
    /**
     * Set in convertQmTags2XliffFormat() and used by it and id's calees
     *
     * @var array
     */
    private  $_stack = array();
    
    
    /**
     * used by _handleOverlap and subroutines
     * @var array 
     */
    protected $_stackId2ArrayIndex = array();
    
    /**
     * used by _handleOverlap and subroutines
     * @var array 
     */
    protected $_closingButNotOpenedTags = array();
    
    /**
     * used by _handleOverlap and subroutines
     * @var array 
     */
    protected $_openTagsInLoop = array();
    /**
     * used by _handleOverlap and subroutines
     * @var array
     */
    protected $_newTagCounter = array();
    /**
     * used by _handleOverlap and subroutines
     * @var array 
     */
    protected $_newOpenTagData = array();
    /**
     * used by _handleOverlap and subroutines
     * @var array 
     */
    protected $_newCloseTagData = array();

        
    /**
     * converts the QM-Subsegment-Tags to xliff-format
     * 
     * @param string $segment
     * @return string
     */
    protected function convertQmTags2XliffFormat($segment){
        if(editor_Segment_Mqm_Configuration::instance($this->_task)->isEmpty()){
            return $segment;
        }
        $split = preg_split('"(<img[^>]+class=\"[^\"]*qmflag[^\"]*\"[^>]*>)"', $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        $count = count($split);
        if($count==1) {
            return $segment;
        }
        
        //if disabled we return the segment content without mqm
        if($this->disableMqmExport) {
            for ($i = 1; $i < $count; $i=$i+2) {//the uneven numbers are the tags
                $split[$i] = ''; //remove mqm tag
            }
            return implode('', $split);
        }
        $this->_user = $this->_segmentEntity->getUserName();
        $this->_stack = array();
        
        while ($count > 0) {
            $item = array_shift($split);
            if ($this->_isImageTag($item)) {
                $xliffData = $this->_getXliffDataFromImg($item);
                $this->_stack[]= $this->_getXliffTagFromData($xliffData);
            } 
            else {
                $this->_stack[]= $item;
            }
            $count--;
        }
        
        $this->_handleOverlap();

        return $this->_implodeStack();
    }
    
    protected function _implodeStack() {
        $segment = '';
        foreach ($this->_stack as $item) {
            if(is_string($item)){
                $segment.=$item;
            }
            else{
                $segment.=$item['tag'];
            }
        }
        return $segment;
    }
    
    /**
     * Resolves overlapped image tags to match XML tree model
     *  - record all currently opened tags and delete those close 
     *  - if a closing one occurs for a recorded open one, but others opened afterwards are still open, resolve the situation and insert referencing opening and closing tags
     *  - if a closing one occurs, that is not openend so far, record it, but do nothing (because this is willently done by the reviewer - he got a notive about the wrong tag-order)
     *  - if a opening one occurs, for a closing one, that had been recorded according the point 3, do nothing for the same reason
     * 
     * @return void
     */
    protected function _handleOverlap() {
        $this->handleOverlapInitLoopVar();
        while(current($this->_stack)!==false){ //get first tag due to initial preg_split (the first array-element is a text node or empty)
            $current = current($this->_stack);
            if(is_string($current)){//we have a text node
                next($this->_stack);
                continue;
            }
            $this->registerStackId2ArrayIndex(key($this->_stack));
            
            if($this->_handleOverlapIfTypeOpen()){
                next($this->_stack);
                continue;
            }
            if($this->_handleOverlapIfDirectTagPair()){
                next($this->_stack);
                continue;
            }
            if($this->_handleOverlapIfTypeClose()){
                next($this->_stack);
                continue;
            }
            throw new ZfExtended_Exception('MQM img2xml-conversion: This point should never be reached');
        }
    }

    /**
     * Checks image tag for errors 
     * 
     * @param string $type
     * @param string $content
     * @param string $input
     * @param bool $valueMustExist
     */
    protected function _checkImageTag($type, $content, $tag, $valueMustExist = true) {
    	if($valueMustExist && $content == ''){
            throw new ZfExtended_Exception($type.' had been emtpy when extracting from qm-subsegment-tag.');
    	}
    	if($content == $tag){
            throw new ZfExtended_Exception($type.' could not be extracted from qm-subsegment-tag.');
    	}
    }
    
    /**
     * Extracts image tag's attribute value and checks it
     * 
     * @param string $tag
     * @param string $type
     * @param bool $numeric
     * @param bool $valueMustExist
     */
    protected function _getImgTagAttr($tag, $type, $numeric = false, $valueMustExist = true) {
        $a = ($numeric) ? '\d+' : '[^\"]*';
        // combatibility with old data: formely the quality-id was encoded as data-seq
        if($type == 'data-t5qid' && strpos($tag, $type) === false && strpos($tag, 'data-seq') !== false){
            $type = 'data-seq';
        }
    	$content = preg_replace('".*'.$type.'=\"('.$a.')\".*"', '\\1', $tag);
    	$this->_checkImageTag($type, $content, $tag, $valueMustExist);
        if($numeric){
            $content = (int)$content;
        }
    	return $content;
    }
    
    /**
     * If $type is not set, checks while the $tag is image tag or no.
     * If $type is set, it assumes that $tag is image tag, but checks while is 'open' or 'close'
     * 
     * @param string $tag
     * @param string $type
     * @return boolean
     */
    protected function _isImageTag($tag, $type = '') {
    	if (substr($tag, 0, 5) != '<img ') {
            return false;
    	}
    	if (in_array($type, ['open', 'close'])) {
            $class = $this->_getImgTagAttr($tag, 'class');
            return (boolean)preg_match('"^('.$type.' .*)|(.* '.$type.')|(.* '.$type.' .*)$"', $class);
    	}
    	return true;
    }
    
    /**
     * Extracts from an img tag data needed for generation of XlifQmTag
     * 
     * @param string $img
     * @return string[]
     */
    protected function _getXliffDataFromImg($img) {
    	$data = array();
    	$data['type'] = ($this->_isImageTag($img, 'open')) ? 'open' : 'close';
    	$data['id'] = $this->_getImgTagAttr($img, 'data-t5qid', true);
    	if ($data['type'] == 'open') {
    	    $classes = array_map('trim', explode(' ', $this->_getImgTagAttr($img, 'class')));
    	    $data['class'] = implode(' ', $classes);
            $data['comment'] = $this->_getImgTagAttr($img, 'data-comment', false, false);
            $data['severity'] = preg_replace('"^\s*([^ ]+) .*$"', '\\1', $data['class']);
            $data['severity'] = editor_Segment_Mqm_Configuration::instance($this->_task)->findMqmSeverity($classes);
            $this->_checkImageTag('severity', $data['severity'], $data['class']);
            $data['issueId'] = preg_replace('"^.*qmflag-(\d+).*$"', '\\1', $data['class']);
            $this->_checkImageTag('issueId', $data['issueId'], $data['class']);
    	}
    	return $data;
    }
    
    /**
     * 
     * @param array $data
     * @param number $idref
     * @return array
     */
    protected function _getXliffTagFromData($data, $idref = false) {
    	$out = [
            'tag' => '</mqm:issue>',
            'data' => $data
    	];
    	if($data['type'] == 'open') {
            $mqmType = editor_Segment_Mqm_Configuration::instance($this->_task)->getMqmTypeForId($data['issueId']);
            $out['tag'] = '<mqm:issue xml:id="x'.$data['id'].'"';
            if($idref) {
                $out['tag'] .= ' idref="'.$idref.'"';
            }
            $out['tag'] .= ' type="'.$mqmType.'" severity="'.$data['severity'].
            '" note="'.$data['comment'].'" agent="'.$this->_user.'">';
    	}
    	return $out;
    }

    /**
     * 
     * @param array $array
     * @param mixed $indexToPointTo
     * @return boolean if successful or not
     */
    protected function setArrayPointer(array &$array, $indexToPointTo) {
        reset($array);

        while ($indexToPointTo !== key($array) && current($array) !== false) {
            next($array);
        }

        if (current($array) !== false) {
            return true;
        }
        return false;
    }
    /**
     * if pointer points to open tag, does nothing. Otherwise advances the pointer to the next open tag
     * @return array $current | false
     */
    protected function pointStackToNextOpenTagInLoop() {
        $current = true;
        while ($current!==false) {
            $current = next($this->_stack);
            if(is_string($current) || !isset($current['data']['type'])) {
                continue;
            }
            if($current['data']['type'] ==='open' && isset($this->_openTagsInLoop[(int)$current['data']['id']])){
                return $current;
            }
        }
        return false;
    }
    
    protected function registerStackId2ArrayIndex($curIndex) {
        $current = current($this->_stack);
        $this->_stackId2ArrayIndex[$current['data']['type']][$current['data']['id']] = $curIndex;
    }

    /**
     * 
     * @param array $current
     * @return boolean
     */
    protected function _handleOverlapIfTypeOpen() {
        $current = current($this->_stack);
        $curId = $current['data']['id'];
        if($current['data']['type'] === 'open'){
            if(array_key_exists($curId, $this->_closingButNotOpenedTags)){//if this tag has been closed, before opened, leave it alone
                unset($this->_closingButNotOpenedTags[$curId]);
                return true;
            }
            $this->_openTagsInLoop[$curId] = $curId;
            return true;
        }
        return false;
    }
    
    /**
     * 
     * @return boolean
     */
    protected function _handleOverlapIfTypeClose() {
        $current = current($this->_stack);
        $curId = $current['data']['id'];
        if($current['data']['type'] === 'close'){
            if($this->_handleOverlapResolve()){
                return true;
            }
            $this->_closingButNotOpenedTags[$curId] = $curId;
            return true;
        }
        return false;
    }
    
    /**
     * tag pair with no nested tags
     * @return boolean
     */
    protected function _handleOverlapIfDirectTagPair() {
        $current = current($this->_stack);
        $curId = $current['data']['id'];
        if($current['data']['type'] === 'close'){
            if(end($this->_openTagsInLoop) === $curId){//the last recorded opening tag combines to a matching tag-pair with this closing one
                unset($this->_openTagsInLoop[$curId]);
                return true;
            }
        }
        return false;
    }
    /**
     * must be called after _handleOverlapIfDirectTagPair in the loop
     * @return boolean
     */
    protected function _handleOverlapResolve() {
        $current = current($this->_stack);
        $correspondingId = $current['data']['id'];
        if(!array_key_exists($correspondingId, $this->_openTagsInLoop)){
            return false;
        }
        //key exists, but is not a direct open/close pair 
        //(this conclusion is done, because handleOverlaps must be called after _handleOverlapIfDirectTagPair in the loop
        
        $this->setArrayPointer($this->_stack, $this->_stackId2ArrayIndex['open'][$correspondingId]);//set the array pointer to be able to walk over the array and insert the tags to resolve the overlap
        $this->_handleOverlapResolveInitNewTagData();
        
        while(current($this->_stack)!==false){
            $current = $this->pointStackToNextOpenTagInLoop();
            if(!$current){
                break;
            }
            $this->_handleOverlapResolveSetNewTagData();
            $this->_handleOverlapResolveInsert($correspondingId);
        }
        //just start of from the beginning to find the next overlap
        $this->handleOverlapInitLoopVar();
        return true;
    }

    /**
     * 
     */
    protected function _handleOverlapResolveInitNewTagData() {
        $current = current($this->_stack);
        $this->_newCloseTagData = $current['data'];
        $this->_newOpenTagData = $current['data'];
        $this->_newCloseTagData['type'] = 'close';
        $this->_newOpenTagData['type'] = 'open';
    }
    
    protected function _handleOverlapResolveSetNewTagData() {
        $current = current($this->_stack);
        $curId = $current['data']['id'];
        (isset($this->_newTagCounter[$curId]))?$this->_newTagCounter[$curId]++:$this->_newTagCounter[$curId]=1;
        $this->_newOpenTagData['id'] = 'overlappingTagId-'.$curId.'_'.$this->_newTagCounter[$curId];
    }
    /**
     * 
     * @param string $correspondingTagId
     * @return void
     */
    protected function _handleOverlapResolveInsert($correspondingTagId) {
        $current = current($this->_stack);
        $curId = $current['data']['id'];
        $curPointerIndex = $this->_stackId2ArrayIndex['open'][$curId];

        //insert opening tag
        $insertOpening = array('',$this->_getXliffTagFromData($this->_newOpenTagData,$correspondingTagId));//we must also insert an empty text node to keep the rythm of alternating text nodes and tags
        $posToEnter = $curPointerIndex+1;//+1, this inserts it directly behind the current opening tag
        array_splice( $this->_stack, $posToEnter, 0, $insertOpening);
        //insert closing tag
        $insertClosing = array($this->_getXliffTagFromData($this->_newCloseTagData,$correspondingTagId),'');//we must also insert an empty text node to keep the rythm of alternating text nodes and tags 
        array_splice( $this->_stack, $curPointerIndex, 0, $insertClosing);
        $this->_newCloseTagData['id'] = $this->_newOpenTagData['id'];
        
        //adjust the id of the corresponding closing tag to match the newly inserted one - but only the currently surrounded opening tag is the last one, that is not closed again before the corresponding closing tag
        $this->setArrayPointer($this->_stack, $curPointerIndex+2);//array_splice resets the array-pointer. Set it at the tag, which just had been surronded
        if(end($this->_openTagsInLoop) === $curId){
            while (current($this->_stack) !== false) {
                $current = next($this->_stack);
                if(is_array($current) && $current['data']['id'] === $correspondingTagId 
                        && $current['data']['type'] === 'close'){
                    $this->_stack[key($this->_stack)]['data']['id'] = $insertOpening[1]['data']['id'];
                    break;
                }
            }
        }
        $this->_handleOverlapResolveInsertAdjustStackId2ArrayIndex($curId);
        //advance the pointer for the number of fields we inserted into the array
        $this->setArrayPointer($this->_stack, $curPointerIndex+4);
    }
    
    protected function _handleOverlapResolveInsertAdjustStackId2ArrayIndex($curId) {
        $a = &$this->_stackId2ArrayIndex['open'];
        $this->setArrayPointer($a, $curId);
        $a[key($a)] = $a[key($a)]+2;
        next($a);
        while (current($a) !== false) {
            $a[key($a)] = $a[key($a)]+4;
            next($a);
        }
    }
    
    protected function handleOverlapInitLoopVar() {
        $this->_openTagsInLoop = array();
        $this->_closingButNotOpenedTags = array();
        $this->_stackId2ArrayIndex = array();
        $this->_newOpenTagData = array();
        $this->_newCloseTagData = array();
        reset($this->_stack);
    }
}