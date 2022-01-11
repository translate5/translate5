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
 * This class is meant as the blueprint for any worker to process quality tags for segments on import or when editing
 * It is expected, that the property 
 */
abstract class editor_Segment_Quality_SegmentWorker extends editor_Models_Import_Worker_ResourceAbstract {
    
    /**
     * Holds the segment to process in case of a non-threaded single segment edit
     * @var editor_Segment_Tags
     */
    private $currentSegmentTags;
    /**
     * This defines the processing mode for the segments we process
     * This worker is used in various situations
     * @var string
     */
    protected $processingMode;
    
    /**
     * Needed Implementation for editor_Models_Import_Worker_ResourceAbstract
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        // required param steers the way the segments are processed: either directly or via the LEK_segment_tags
        if(array_key_exists('processingMode', $parameters)){
            $this->processingMode = $parameters['processingMode'];
            return true;
        }
        return false;
    }
    /**
     * Processes a single segment, just a plugin-method for workers that may need to process the segments with additional logic
     * @param editor_Models_Segment $segment
     * @return bool
     */
    protected function processSegment(editor_Models_Segment $segment, string $slot){
        return $this->processSegmentTags(editor_Segment_Tags::fromSegment($this->task, $this->processingMode, $segment, editor_Segment_Processing::isOperation($this->processingMode)), $slot);
    }
    /**
     * Process multiple segments as a batch
     * This is just a plugin function in case additional logic is needed creating the segment-tags out of the segments
     * @param editor_Models_Segment[] $segments
     * @param string $slot
     * @return bool
     */
    protected function processSegments(array $segments, string $slot) : bool {
        return $this->processSegmentsTags(editor_Segment_Tags::fromSegments($this->task, $this->processingMode, $segments, editor_Segment_Processing::isOperation($this->processingMode)), $slot);
    }
    /**
     * Processes the segment-tags and write them back to the segment-tags-model
     * @param editor_Segment_Tags[] $tags
     * @param string $slot
     * @return bool
     */
    protected function processSegmentsTags(array $segmentsTags, string $slot) : bool {
        foreach($segmentsTags as $tags){
            if(!$this->processSegmentTags($tags, $slot)){
                return false;
            }
        }
        return true;
    }
    /**
     * Processes a single segment-tags
     * @param editor_Segment_Tags $tags
     * @param string $slot
     * @return bool
     */
    abstract protected function processSegmentTags(editor_Segment_Tags $tags, string $slot) : bool;
    /**
     * To be implemented in inheriting classes to load the segments for a threaded run
     * @param string $slot
     * @return editor_Models_Segment[]
     */
    abstract protected function loadNextSegments(string $slot) : array;
    /**
     * Implements the process function to distribute to the processSegment & processSegments API 
     * {@inheritDoc}
     * @see editor_Models_Import_Worker_ResourceAbstract::process()
     */
    protected function process(string $slot) : bool {
        if($this->isWorkerThread){
            $segments = $this->loadNextSegments($slot);
            if(count($segments) == 0){
                return false;
            }
            return $this->processSegments($segments, $slot);
        } else {
            return $this->processSegmentTags($this->currentSegmentTags, $slot);
        }
    }
    /**
     * API that processes an segment after it was edited with the segment editor
     * @param editor_Models_Segment $segment
     * @return boolean
     */
    public function runSegmentTagsProcessing(editor_Segment_Tags $tags){
        $this->currentSegmentTags = $tags;
        return $this->run();
    }
}
