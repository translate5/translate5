<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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