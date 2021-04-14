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
 * Segment Helper Class
 */
class editor_Models_Segment_Utility {
    /**
     * @var array
     */
    protected $stateFlags;
    
    /**
     * @var array
     */
    protected $qualityFlags;
    
    /**
     * Does the entity encoding when importing segment content, see inline comments
     * @param string $textNode
     * @param bool $xmlBased
     * @return string
     */
    public static function entityCleanup($textNode, $xmlBased = true) {
        // It is important that we have no entities in our DB but their UTF8 characters instead,
        // since a XLF export of our segments would not be valid XML with the entities.
        // And the browsers are converting the entities anyway to UTF8 characters.
        // Refactor to a better place with TRANSLATE-296
        if($xmlBased) {
            // in a XML based format only the defined entities may exist
            // - for our major XML formats these are: &amp; &lt; &gt; only
            // - all other entities must be encoded back into their utf8 character: &zslig; into ß
            //   → otherwise our XLF export will fail with invalid XML
            //   → also the browser will convert the &zslig; into ß anyway, so we do this directly on the import
            // why using this encode(decode) see
            //  https://stackoverflow.com/questions/18039765/php-not-have-a-function-for-xml-safe-entity-decode-not-have-some-xml-entity-dec
            return htmlentities(html_entity_decode($textNode, ENT_HTML5|ENT_QUOTES), ENT_XML1);
        }
        // for non XML based formats (for example CSV) all content and its contained entities are displayed to the user as they were in the import file
        // therefore we have just to encode the < > & characters.
        // so if the CSV contains &amp; &szlig; ß < this would be converted to &amp;amp; &amp;szlig; ß &gt; to be displayed correctly in the browser
        return htmlentities($textNode, ENT_XML1);
    }
    
    /**
     * protects whitespace inside a segment with a tag
     *
     * @param string $segment
     * @param callable $textNodeCallback callback which is applied to the text node
     * @return string $segment
     */
    public static function foreachSegmentTextNode($segment, callable $textNodeCallback) {
        $split = preg_split('#(<[^\s][^>]*>)#', $segment, null, PREG_SPLIT_DELIM_CAPTURE);
        
        $i = 0;
        foreach($split as $idx => $chunk) {
            if($i++ % 2 === 1 || strlen($chunk) == 0) {
                //ignore found tags in the content or empty chunks
                continue;
            }
            
            $split[$idx] = $textNodeCallback($chunk);
        }
        return join($split);
    }
    
    public function __construct() {
        $config = Zend_Registry::get('config');
        $this->stateFlags = $config->runtimeOptions->segments->stateFlags->toArray();
        $this->qualityFlags = $config->runtimeOptions->segments->qualityFlags->toArray();
    }
    
    /**
     * returns the configured value to the given state id
     * @param string $stateId
     * @return string
     */
    public function convertStateId($stateId) {
        if(empty($stateId)) {
            return '';
        }
        if(isset($this->stateFlags[$stateId])){
            return $this->stateFlags[$stateId];
        }
        return 'Unknown State '.$stateId;
    }
    
    /**
     * converts the semicolon separated qmId string into an associative array
     * key => qmId
     * value => configured String in the config for this id
     * @param string $qmIds
     * @return array
     */
    public function convertQmIds($qmIds) {
        if(empty($qmIds)) {
            return array();
        }
        $qmIds = trim($qmIds, ';');
        $qmIds = explode(';', $qmIds);
        $result = array();
        foreach($qmIds as $qmId) {
            if(isset($this->qualityFlags[$qmId])){
                $result[$qmId] = $this->qualityFlags[$qmId];
                continue;
            }
            $result[$qmId] = 'Unknown Qm Id '.$qmId;
        }
        return $result;
    }
    
    /***
     * Update the $edit100PercentMatch flag for all segments in the task.
     * @param editor_Models_Task $task
     * @param bool $edit100PercentMatch
     */
    public function updateSegmentsEdit100PercentMatch(editor_Models_Task $task, bool $edit100PercentMatch){
        // create a segment-iterator to get all segments of this task as a list of editor_Models_Segment objects
        $segments = ZfExtended_Factory::get('editor_Models_Segment_Iterator', [$task->getTaskGuid()]);
        /* @var $segments editor_Models_Segment_Iterator */
        $segmentHistory=ZfExtended_Factory::get('editor_Models_SegmentHistory');
        /* @var $segmentHistory editor_Models_SegmentHistory */
        $autoState=ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        /* @var $autoState editor_Models_Segment_AutoStates */
        foreach ($segments as $segment){
            if($segment->getEditable() == $edit100PercentMatch || $segment->getMatchRate()<100){
                continue;
            }
            
            $actualHistory=$segmentHistory->loadBySegmentId($segment->getId(),1);
            $actualHistory=$actualHistory[0] ?? [];
            
            $history=$segment->getNewHistoryEntity();
            
            //it is full match always
            $isFullMatch=true;
            $isLocked = $segment->meta()->getLocked() && (bool) $task->getLockLocked();
            
            $isEditable  = (!$isFullMatch || (bool) $edit100PercentMatch || $segment->meta()->getAutopropagated()) && !$isLocked;
            
            $segment->setEditable($isEditable);
            
            $autoStateId=$actualHistory['autoStateId'] ?? null;
            
            //if the autostate does not exist in the history or it is blocked, calculate same as import
            if(!$autoStateId || $autoStateId==$autoState::BLOCKED){
                $autoStateId=$autoState->restoreImportState($isEditable, $segment->isTargetTranslated());
            }
            $segment->setAutoStateId($autoStateId);
            $history->save();
            $segment->save();
        }
        
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta */
        //update task word count when 100% matches editable is changed
        $task->setWordCount($meta->getWordCountSum($task));
    }
}