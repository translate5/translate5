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
 * je nach Ableitung dieser Klasse werden die Daten entsprechend weiterverarbeitet 
 */
abstract class editor_Models_Import_SegmentProcessor {
    /**
     * @var string GUID
     */
    protected $taskGuid = null;
    /**
     * @var editor_Models_Task
     */
    protected $task;
    /**
     * @var integer
     */
    protected $fileId;
    /**
     * @var string
     */
    protected $fileName;
    
    /**
     * @var editor_Models_Languages Entity Instanz der Sprache
     */
    protected $sourceLang = null;
    
    /**
     * @var editor_Models_Languages Entity Instanz der Sprache
     */
    protected $targetLang = null;
    
    /**
     * @var integer Länge, auf die die Spalten mit Text gekürzt werden, bevor
     *      sie in die DB-Spalten die für die Sortierung der Textspalten zuständig
     *      sind eingefügt werden (nötig für (krank) MSSQL, da MSSQL das auf
     *      DB-Ebene nicht vernünftig kann.
     */
    protected $lengthToTruncateSegmentsToSort = null;
        
    /**
     * Konstruktor
     * @param editor_Models_Languages $sourceLang
     * @param editor_Models_Languages $targetLang
     * @param editor_Models_Task $task
     */
     public function __construct(editor_Models_Languages $sourceLang, editor_Models_Languages $targetLang, editor_Models_Task $task){
         $session = new Zend_Session_Namespace();
         $this->lengthToTruncateSegmentsToSort = $session->runtimeOptions->lengthToTruncateSegmentsToSort;
         $this->sourceLang = $sourceLang;
         $this->targetLang = $targetLang;

         $this->task = $task;
         $this->taskGuid = $task->getTaskGuid();
    }
    
    /**
     * Verarbeitet ein einzelnes Segment und gibt die ermittelte SegmentId zurück
     * @return integer MUST return the segmentId
     */
    abstract public function process(editor_Models_Import_FileParser $parser);
    
    /**
     * Setzt die Dateispezifischen Informationen zum Segment
     * @param integer $fileId
     * @param string $filename
     */
    public function setSegmentFile($fileId, $filename){
        $this->fileId = $fileId;
        $this->fileName = $filename;
    }
    
    /**
     * Template Methode welche vor dem Parsen der Datei aufgerufen wird
     */
    public function preParseHandler(editor_Models_Import_FileParser $parser){}
    
    /**
     * Template Methode welche nach dem Parsen der Datei aufgerufen wird
     */
    public function postParseHandler(editor_Models_Import_FileParser $parser){}
    
    /**
     * Segment Source / Target String gekürzt auf $this->lengthToTruncateSegmentsToSort
     * @param string|NULL segment
     * @return string
     */
    protected function truncateSegmentsToSort($segment){
    	if(!is_string($segment)){
    		return $segment;
    	}
    	return mb_substr($segment,0,$this->lengthToTruncateSegmentsToSort,'utf-8');
    }
}