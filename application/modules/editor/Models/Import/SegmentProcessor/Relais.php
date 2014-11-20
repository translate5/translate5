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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Stellt Methoden zur Verarbeitung der vom Parser ermittelteten Segment Daten bereit
 * speichert die ermittelten Segment Daten in die Relais Spalte des entsprechenden Segments 
 */
class editor_Models_Import_SegmentProcessor_Relais extends editor_Models_Import_SegmentProcessor {
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $sfm;
    
    /**
     * @var editor_Models_Segment
     */
    protected $segment;
    
    /**
     * Relais Field
     * @var Zend_Db_Table_Row_Abstract
     */
    protected $relaisField;
    
    /**
     * @param editor_Models_Task $task
     * @param editor_Models_SegmentFieldManager $sfm receive the already inited sfm
     */
    public function __construct(editor_Models_Task $task, editor_Models_SegmentFieldManager $sfm) {
        parent::__construct($task);
        //relais is forced non editable (last parameter)
        $relais = $sfm->addField($sfm::LABEL_RELAIS, editor_Models_SegmentField::TYPE_RELAIS, false);
        $this->relaisField = $sfm->getByName($relais);
        $this->sfm = $sfm;
        $this->segment = ZfExtended_Factory::get('editor_Models_Segment');
        $this->segment->setTaskGuid($task->getTaskGuid());
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_SegmentProcessor::process()
     */
    public function process(editor_Models_Import_FileParser $parser){
        $data = $parser->getFieldContents();
        $target = $this->sfm->getFirstTargetName();
        
        try {
            $this->segment->addFieldContent($this->relaisField, $this->fileId, $parser->getMid(), $data[$target]);
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            $msg = 'Errors in adding relais segment: Original Segment not Found! Segment Info:'.$e->getMessage();
            trigger_error($msg, E_USER_ERROR);
        }
        return false;
    }
      
    /**
     * Überschriebener Post Parse Handler, erstellt in diesem Fall das Skeleton File
     * @override
     * @param editor_Models_Import_FileParser $parser
     */
    public function postParseHandler(editor_Models_Import_FileParser $parser) {
        $this->saveFieldWidth($parser);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_SegmentProcessor::postProcessHandler()
     */
    public function postProcessHandler(editor_Models_Import_FileParser $parser, $segmentId) {
        $this->calculateFieldWidth($parser,array($this->sfm->getFirstTargetName() => 'relais'));
    }
}