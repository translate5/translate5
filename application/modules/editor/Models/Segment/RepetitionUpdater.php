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
 * Segment Repetition Tag Replacer
 * when processing repetitions (change alikes) the contained tags in the content must be replaced by the tags which were before in the segment.
 */
class editor_Models_Segment_RepetitionUpdater {
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $tagHelper = null;
    
    /**
     * @var editor_Models_Segment_TrackChangeTag
     */
    protected $trackChangesTagHelper = null;
    
    /**
     * @var editor_Models_Segment
     */
    protected $repeatedSegment = null;
    
    /**
     * @var editor_Models_Segment
     */
    protected $originalSegment = null;
    
    /**
     * @var Zend_Config
     */
    protected $config = null;
    
    /**
     * @var array
     */
    protected $qmSubsegmentAlikes = null;
    
    /**
     * @param editor_Models_Segment $segment
     * @param array $qmSubsegmentAlikes
     */
    public function __construct(editor_Models_Segment $segment, array $qmSubsegmentAlikes){
        $this->config = Zend_Registry::get('config');
        $this->originalSegment = $segment;
        $this->tagHelper = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->trackChangesTagHelper = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        $this->qmSubsegmentAlikes = $qmSubsegmentAlikes;
    }
    
    /**
     * Set the segment instance which is the repetition
     * @param editor_Models_Segment $entity
     */
    public function setRepetition($entity) {
        $this->repeatedSegment = $entity;
    }
    
    /**
     * replaces the tags in the given content with the tags which were before in the segemnt
     * @param string $segmentContent
     * @return string
     */
    public function updateSegmentContent($field, $editField, $getter, $setter) : bool {
        $id = $this->repeatedSegment->getId();
        $getOriginal = 'get'.ucfirst($field);
        //get content, dependent on using MQM or not:
        if($this->config->runtimeOptions->editor->enableQmSubSegments) {
            $segmentContent = $this->qmSubsegmentAlikes[$field]->cloneAndUpdate($id, $field);
        }
        else {
            $segmentContent = $this->originalSegment->{$getter}();
        }
        
        //replace the repeatedSegment tags with the original repetition ones
        $originalContent = $this->repeatedSegment->{$getOriginal}();
        $useSourceTags = empty($originalContent);
        if($useSourceTags) {
            //if the original had no content (mostly translation context), we have to load the source tags.
            $originalContent = $this->repeatedSegment->getSource();
        }
        //get only the real tags, we do not consider white tags in repetitions, 
        // this is because whitespace belongs to the content and not to the segment (tags instead belong to the segment instead)
        $tagsForRepetition = $this->tagHelper->getRealTags($originalContent);
        if(empty($tagsForRepetition)) {
            //if there are no original tags we have to init $i with the realTagCount in the targetEdit for below check
            $stat = $this->tagHelper->statistic($this->trackChangesTagHelper->protect($segmentContent));
            $i = $stat['tag']; //if $i is > 0 here, this should not be a repetition at all.
        }
        else {
            $i = 0;
            $segmentContent = $this->trackChangesTagHelper->protect($segmentContent);
            $segmentContent = $this->tagHelper->replace($segmentContent, function($match) use (&$i, $tagsForRepetition){
                $id = $match[3];
                //if it is a whitespace tag, we do not replace it:
                if(in_array($id, editor_Models_Segment_Whitespace::WHITESPACE_TAGS)) {
                    return $match[0];
                }
                return $tagsForRepetition[$i++] ?? '';
            });
            $segmentContent = $this->trackChangesTagHelper->unprotect($segmentContent);
        }
        //the count of tags available for the repetition and the used tags ($i) must be equal!
        // If this is not the case, the segment can not be processed as repetition (return false)!
        if(count($tagsForRepetition) !== $i) {
            return false;
        }
        
        $this->repeatedSegment->{$setter}($segmentContent);
        $this->repeatedSegment->updateToSort($editField);
        return true;
    }
}
