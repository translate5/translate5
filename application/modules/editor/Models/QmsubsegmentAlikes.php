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

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

/**
 * Methods for Management of QM-Subsegments Alike Segments
 */
class editor_Models_QmsubsegmentAlikes {
    
    /**
     * The parsed (splitted) segment string
     * @var array
     */
    protected $parsedSegment;

    /**
     * found QM Ids in the parsed Segment
     * @var array
     */
    protected $foundQmIds = array();
    
    /**
     * ID of the intial parsed Segment 
     * @var integer
     */
    protected $segmentId;
    
    /**
     * @var string
     */
    protected $segment;
    
    /**
     * The subsegments of the loaded and parsed segment
     * @var array
     */ 
    protected $subSegments; 
    
    /**
     * @var editor_Models_Db_Qmsubsegments
     */
    protected $qmSubDb;
    
    /**
     * @var editor_Models_Qmsubsegments
     */
    protected $qmSub;
    
    /**
     * @var string
     */
    protected $taskGuid;
    
    public function __construct() {
        $this->qmSub = ZfExtended_Factory::get('editor_Models_Qmsubsegments');
        $this->qmSubDb = ZfExtended_Factory::get('editor_Models_Db_Qmsubsegments');
        $session = new Zend_Session_Namespace();
        $this->taskGuid = $session->taskGuid; 
    }
    
    /**
     * parse / split the given segment string, load the qm SubSegment Data to the found 
     * @param string $segment
     * @param integer $segmentid
     * @throws Zend_Exception
     */
    public function parseSegment(string $segment, integer $segmentid) {
        $this->segmentId = $segmentid;
        $this->segment = $segment;
        $this->foundQmIds = array();
        $this->parsedSegment = preg_split('"(<img [^>]+>)"s', $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        $count = count($this->parsedSegment);
        for ($i=1; $i < $count;$i=$i+2) {//the odd entries contain the img-tags
            $part = $this->parsedSegment[$i];
        	$id = $this->qmSub->getIdFromImg($part);
        	$cls = $this->qmSub->getClsFromImg($part);
        	if(strpos($cls, ' open ') !== false) {
            	$this->foundQmIds[] = $id; // catch only open tags, ignore close tags to prevent duplications
        	}
        }
    }
    
    /**
     * duplicates the DB QM Sub Segment entries from the parsed segment to another
     * returnes the segment content with the new IDs
     * @param integer $alikeSegmentId
     * @param string $fieldedited
     * @return string
     */
    public function cloneAndUpdate(integer $alikeSegmentId, $fieldedited) {
        $s = $this->segment;
        $ids = array();
        foreach($this->foundQmIds as $id) {
            $newId = $this->qmSubDb->cloneSubsegment($this->taskGuid, $this->segmentId, (int)$id, $alikeSegmentId, $fieldedited);
            $ids[] = $newId;
            $s = str_replace('data-seq="'.$id.'"', 'data-seq="'.$newId.'"', $s);
        } 
        $this->qmSub->deleteUnused($alikeSegmentId, $ids, $fieldedited);
        return $s;
    }
}