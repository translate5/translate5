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
     * Updates the target fields in the repeated segment with the content
     * of the original segment with the tags replaced with the previous tags (in the repeated)
     * @param bool $useSourceTags if true, force usage tags from source instead from target
     * @return bool
     */
    public function updateTarget($useSourceTags = false): bool
    {
        $originalContent = $useSourceTags ? '' : $this->repeatedSegment->getTarget();
        $segmentContent = $this->originalSegment->getTargetEdit();

        $originalContent = $this->checkAndGetSegmentContent($originalContent);

        return $this->tagHelper->updateSegmentContent(
                    $originalContent,
                    $segmentContent,
                    function ($originalContent, $segmentContent) {
                        $this->repeatedSegment->setTargetEdit($segmentContent);
                        // when copying targets originating from a
                        // language-resource, we copy the original target as well ...
                        if ($this->originalSegment->isFromLanguageResource()) {
                            $originalContent = $this->checkAndGetSegmentContent($originalContent);
                            $this->tagHelper->updateSegmentContent(
                                        $originalContent,
                                        $this->originalSegment->getTarget(),
                                        function ($originalContent, $segmentContent){
                                            $this->repeatedSegment->setTarget($segmentContent);
                                            $this->repeatedSegment->updateToSort('target');
                                        }
                            );
                        }
                        $this->repeatedSegment->updateToSort('target'.editor_Models_SegmentFieldManager::_EDIT_SUFFIX);
                    }
        );
    }

    /**
     * Updates the non editable source field to take over the term markup into the repetition
     * @param bool $editable
     * @return bool
     */
    public function updateSource(bool $editable): bool
    {
        $segmentContent = $editable ? $this->originalSegment->getSourceEdit() : $this->originalSegment->getSource();

        $originalContent = $this->checkAndGetSegmentContent($this->repeatedSegment->getSource());

        return $this->tagHelper->updateSegmentContent(
                $originalContent,
                $segmentContent,
                function ($originalContent, $segmentContent) use ($editable) {
                    $toSort = 'source';
                    if ($editable) {
                        $this->repeatedSegment->setSourceEdit($segmentContent);
                        $toSort .= editor_Models_SegmentFieldManager::_EDIT_SUFFIX;
                    }else {
                        $this->repeatedSegment->setSource($segmentContent);
                    }
            $this->repeatedSegment->updateToSort($toSort);
        }, $editable); //if modifying the editable source, whitespace
        // should be ignored, if repeating the source all tags must be taken over
    }

    /**
     * Check and validate the given segment original content. In case the given content is empty, the repeated segment
     * source will be returned.
     * @param string $originalContent
     * @return string
     */
    public function checkAndGetSegmentContent(string $originalContent): string
    {
        if(ZfExtended_Utils::emptyString($originalContent)){
            return $this->repeatedSegment->getSource();
        }
        return $originalContent;
    }
}
