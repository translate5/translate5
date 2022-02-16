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
     * editor_Models_Segment_RepetitionUpdater
     * @var Zend_Config
     */
    protected $config = null;
    
    /**
     * 
     * @param editor_Models_Segment $originalSegment
     * @param Zend_Config $config
     */
    public function __construct(editor_Models_Segment $originalSegment, Zend_Config $config){
        $this->config = $config;
        $this->originalSegment = $originalSegment;
        $this->tagHelper = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->trackChangesTagHelper = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
    }
    
    /**
     * updates the given repetition if possible (due tags)
     * @param editor_Models_Segment $master the segment to copy from
     * @param editor_Models_Segment $repetition the segment to write to
     * @return boolean true if the repetition could be processed, false otherwise
     */
    public function updateTargetOfRepetition(editor_Models_Segment $master, editor_Models_Segment $repetition): bool {
        $this->originalSegment = $master;
        $this->setRepetition($repetition);
        return $this->updateTarget();
    }
    
    /**
     * Set the segment instance which is the repetition, needed for updateSegmentContent
     * @param editor_Models_Segment $entity
     */
    public function setRepetition(editor_Models_Segment $entity) {
        $this->repeatedSegment = $entity;
    }

    /**
     * Takes the tags from the first content, and applies them to the second content. After that pass both contents to the update field callback to set the content to the desired place
     * @param string $originalContent the content where the tags should be used from (normally the original field of the repetition)
     * @param string $segmentContent the content which is repeated and should be used, and where the tags should be applied to (normally the edit field of the master segment)
     * @param callable $updateField callback to deal with the updated segment content
     * @param bool $ignoreWhitespace default true, ignores whitespace tags
     * @return bool
     */
    protected function updateSegmentContent(string $originalContent, string $segmentContent, Callable $updateField, bool $ignoreWhitespace = true) : bool {
        // TODO: we could make much more use of the segment-tags code if only it would be more clear what this code does ...

        //replace the repeatedSegment tags with the original repetition ones,
        // if the original (mostly target) is empty use the tags from source
        // also do that if the repeated segment was pretranslated, since
        $useSourceTags = ZfExtended_Utils::emptyString($originalContent);
        
        //get only the real tags, we do not consider whitespace tags in repetitions,
        // this is because whitespace belongs to the content and not to the segment (tags instead belong to the segment)
        // if the original had no content (mostly translation context), we have to load the source tags.
        $loadTagsFrom = $useSourceTags ? $this->repeatedSegment->getSource() : $originalContent;
        if($ignoreWhitespace) {
            $tagsForRepetition = $this->tagHelper->getRealTags($loadTagsFrom);
        }
        else {
            $tagsForRepetition = $this->tagHelper->get($loadTagsFrom);
        }
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
            $segmentContent = $this->tagHelper->replace($segmentContent, function($match) use (&$i, $tagsForRepetition, $shortTagNumbers, &$newShortTagNumber, $ignoreWhitespace){
                $id = $match[3];
                //if it is a whitespace tag, we do not replace it:
                if($ignoreWhitespace && in_array($id, editor_Models_Segment_Whitespace::WHITESPACE_TAGS)) {
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
        $updateField($originalContent, $segmentContent);
        return true;
    }

    /**
     * Updates the target fields in the repeated segment with the content of the original segment with the tags replaced with the previous tags (in the repeated)
     * @param bool $useSourceTags if true, force usage tags from source instead from target
     * @return bool
     */
    public function updateTarget($useSourceTags = false): bool
    {
        $originalContent = $useSourceTags ? '' : $this->repeatedSegment->getTarget();
        $segmentContent = $this->originalSegment->getTargetEdit();
        return $this->updateSegmentContent($originalContent, $segmentContent, function($originalContent, $segmentContent){
            $this->repeatedSegment->setTargetEdit($segmentContent);
            // when copying targets originating from a language-resource, we copy the original target as well ...
            if($this->originalSegment->isFromLanguageResource()){
                $this->updateSegmentContent($originalContent, $this->originalSegment->getTarget(), function($originalContent, $segmentContent){
                    $this->repeatedSegment->setTarget($segmentContent);
                    $this->repeatedSegment->updateToSort('target');
                });
            }
            $this->repeatedSegment->updateToSort('target'.editor_Models_SegmentFieldManager::_EDIT_PREFIX);
        });
    }

    /**
     * Updates the non editable source field to take over the term markup into the repetition
     * @param bool $editable
     * @return bool
     */
    public function updateSource(bool $editable): bool
    {
        $originalContent = $this->repeatedSegment->getSource();
        $segmentContent = $editable ? $this->originalSegment->getSourceEdit() : $this->originalSegment->getSource();

        return $this->updateSegmentContent($originalContent, $segmentContent, function($originalContent, $segmentContent) use ($editable){
            $toSort = 'source';
            if($editable) {
                $this->repeatedSegment->setSourceEdit($segmentContent);
                $toSort .= editor_Models_SegmentFieldManager::_EDIT_PREFIX;
            }
            else {
                $this->repeatedSegment->setSource($segmentContent);
            }
            $this->repeatedSegment->updateToSort($toSort);
        }, $editable); //if modifying the editable source, whitespace should be ignored, if repeating the source all tags must be taken over
    }
}
