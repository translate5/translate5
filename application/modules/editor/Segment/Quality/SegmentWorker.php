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
 * This class is meant as the blueprint for any worker to process quality tags for segments on import or when editing
 *
 */
abstract class editor_Segment_Quality_SegmentWorker extends editor_Models_Import_Worker_ResourceAbstract {
    
    /**
     *
     * @var editor_Models_Segment
     */
    private $currentSegment;
    /**
     * To be implemented in inheriting classes to process a single segment
     * @param editor_Models_Segment $segment
     * @return bool
     */
    abstract protected function processSegment(editor_Models_Segment $segment, string $slot) : bool;
    /**
     * Process multiple segments as a batch
     * This is just a default-implementation that nees to be overwritten in implementing classes e.g. because the segments may be requested via Request from a serveice
     * @param editor_Models_Segment[] $segments
     * @return bool
     */
    protected function processSegments(array $segments, string $slot) : bool {
        foreach($segments as $segment){
            if(!$this->processSegment($segment, $slot)){
                return false;
            }
        }
        return true;
    }
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
            return $this->processSegment($this->currentSegment, $slot);
        }
    }
    /**
     * API that processes an segment after it was edited with the segment editor
     * @param editor_Models_Segment $segment
     * @return boolean
     */
    public function segmentEdited(editor_Models_Segment $segment){
        $this->currentSegment = $segment;
        return $this->run();
    }
}
