<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
			 http://www.gnu.org/licenses/agpl.html

END LICENSE AND COPYRIGHT
*/

/**
 * Implements an Iterator for Task Segments. 
 * This Iterator loads always one Segment instead storing all segments in Memory.
 * @author tlauria
 *
 */
class editor_Models_Segment_Iterator implements Iterator {

    /**
     * @var string
     */
    protected $taskGuid;
    /**
     * @var editor_Models_Segment
     */
    protected $segment;
    
    /**
     * @var boolean
     */
    protected $isEmpty = false;
    
    /**
     * @param string $taskGuid
     */
    public function __construct($taskGuid) {
        $this->taskGuid = $taskGuid;
        $this->rewind();
    }
    
    /**
     * (non-PHPdoc)
     * @see Iterator::current()
     * @return editor_Models_Segment | null
     */
    public function current() {
        return $this->segment;
    }
    
    /**
     * (non-PHPdoc)
     * @see Iterator::next()
     * @return editor_Models_Segment | null
     */
    public function next() {
        $this->segment = $this->segment->loadNext($this->taskGuid, $this->key());
        return $this->segment;
    }
    
    /**
     * (non-PHPdoc)
     * @see Iterator::key()
     * @return integer the segment id
     */
    public function key() {
        return $this->segment->getId();
    }
    
    /**
     * Reloads the first segment of task, we assume we have always a first segment. 
     * If not a notfound exceptions is thrown.
     * (non-PHPdoc)
     * @see Iterator::rewind()
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function rewind() {
        $this->segment = ZfExtended_Factory::get('editor_Models_Segment');
        try {
            $this->segment->loadFirst($this->taskGuid);
            $this->isEmpty = false;
        }
        catch(ZfExtended_Models_Entity_NotFoundException $noSegments) {
            $this->isEmpty = true;
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see Iterator::valid()
     */
    public function valid() {
        return !!$this->segment && ($this->key() > 0);
    }
    
    public function isEmpty() {
        return $this->isEmpty;
    }
}