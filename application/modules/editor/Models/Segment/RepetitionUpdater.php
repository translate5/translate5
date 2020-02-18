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
    public function __construct(editor_Models_Segment $segment, array $qmSubsegmentAlikes = null){
        $this->config = Zend_Registry::get('config');
        $this->originalSegment = $segment;
        $this->tagHelper = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->trackChangesTagHelper = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        $this->qmSubsegmentAlikes = $qmSubsegmentAlikes;
    }
    
    /**
     * updates the given repetition if possible (due tags)
     * @param editor_Models_Segment $master the segment to copy from
     * @param editor_Models_Segment $repetition the segment to write to
     * @return boolean true if the repetition could be processed, false otherwise
     */
    public function updateRepetition(editor_Models_Segment $master, editor_Models_Segment $repetition): bool {
        $this->originalSegment = $master;
        $this->setRepetition($repetition);
        return $this->updateSegmentContent('target', 'targetEdit', 'getTargetEdit', 'setTargetEdit');
    }
    
    /**
     * Set the segment instance which is the repetition, needed for updateSegmentContent
     * @param editor_Models_Segment $entity
     */
    public function setRepetition(editor_Models_Segment $entity) {
        $this->repeatedSegment = $entity;
    }
    
    /**
     * call back for the fieldloop over all editable segments, 
     *   replaces the tags in the given content with the tags which were before in the segemnt
     * @param string $field
     * @param string $editField
     * @param string $getter
     * @param string $setter
     * @return bool
     */
    public function updateSegmentContent(string $field, string $editField, string $getter, string $setter) : bool {
        $id = $this->repeatedSegment->getId();
        $getOriginal = 'get'.ucfirst($field);
        //get content, dependent on using MQM or not:
        if($this->config->runtimeOptions->editor->enableQmSubSegments && is_array($this->qmSubsegmentAlikes)) {
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
        $shortTagNumbers = $this->tagHelper->getTagNumbers($tagsForRepetition);
        if(empty($shortTagNumbers)) {
            $newShortTagNumber = 1;
        }
        else {
            $newShortTagNumber = max($shortTagNumbers) + 1;
        }
        if(empty($tagsForRepetition)) {
            //if there are no original tags we have to init $i with the realTagCount in the targetEdit for below check
            $stat = $this->tagHelper->statistic($this->trackChangesTagHelper->protect($segmentContent));
            $i = $stat['tag']; //if $i is > 0 here, this should not be a repetition at all.
        }
        else {
            $i = 0;
            $segmentContent = $this->trackChangesTagHelper->protect($segmentContent);
            $segmentContent = $this->tagHelper->replace($segmentContent, function($match) use (&$i, $tagsForRepetition, $shortTagNumbers, &$newShortTagNumber){
                $id = $match[3];
                //if it is a whitespace tag, we do not replace it:
                if(in_array($id, editor_Models_Segment_Whitespace::WHITESPACE_TAGS)) {
                    if(in_array($this->tagHelper->getTagNumber($match[0]), $shortTagNumbers)) {
                        return $this->tagHelper->replaceTagNumber($match[0], $newShortTagNumber++);
                    }
                    //Problem here: this may return a tag with number <2/> which does already exist as real tag <2/> in tags for repetition 
                    //test if numbr of returned tag exists in $tagsForRepetition, then get highest number in $tagsForRepetition and increase number from there
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
