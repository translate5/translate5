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
     * array containing calculated field width for the GUI for each field
     * @var [array] 1D array, keys map to the segment field names. Values contain the width in pixel
     */
    protected $fieldWidth = array();
    
    /**
     * Konstruktor
     * @param editor_Models_Task $task
     */
     public function __construct(editor_Models_Task $task){
         $this->task = $task;
         $this->taskGuid = $task->getTaskGuid();
    }
    
    /**
     * Verarbeitet ein einzelnes Segment und gibt die ermittelte SegmentId zurück
     * @return integer|false MUST return the segmentId or false
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
     * @param editor_Models_Import_FileParser $parser
     */
    public function preParseHandler(editor_Models_Import_FileParser $parser){}
    
    /**
     * Template Methode welche nach dem Parsen der Datei aufgerufen wird
     * @param editor_Models_Import_FileParser $parser
     */
    public function postParseHandler(editor_Models_Import_FileParser $parser){}
    
    /**
     * Template Method which is called after all segment processors were processed
     * @param editor_Models_Import_FileParser $parser
     * @param integer $segmentId
     */
    public function postProcessHandler(editor_Models_Import_FileParser $parser, $segmentId) {}
    
    /**
     * 
     * @param editor_Models_Import_FileParser $parser
     * @param array $fields2Calculate 1-D array, key is name of field in file and value is mapped field name for db (may be same as key, or e. g. "relais" | default null; if null, all fields are calculated
     * @throws ZfExtended_Exception
     */
    protected function calculateFieldWidth(editor_Models_Import_FileParser $parser, $fields2Calculate = null) {
        $fieldContents = $parser->getFieldContents();
        $config = Zend_Registry::get('config');
        $widthFactor = (float)$config->runtimeOptions->editor->columns->widthFactor;
        foreach ($fieldContents as $field => $contents) {
            if(!is_null($fields2Calculate)){
                if(!array_key_exists($field, $fields2Calculate)){
                    continue;
                }
                $field = $fields2Calculate[$field];
            }
            $strlen = mb_strlen(strip_tags($contents['original']));
            if($strlen === false){
                throw new ZfExtended_Exception('strlen could not be detected. Something with the internal encoding must be wrong.');
            }
            if(!isset($this->fieldWidth[$field])){
                $this->fieldWidth[$field] = 0;
            }
            $calculatedWidth = $strlen*$widthFactor;
            $this->fieldWidth[$field] = max($this->fieldWidth[$field],$calculatedWidth);
        }
    }
    
    /**
     * 
     * @param editor_Models_Import_FileParser $parser
     */
    protected function saveFieldWidth(editor_Models_Import_FileParser $parser) {
        $config = Zend_Registry::get('config');
        $maxWidth = (integer)$config->runtimeOptions->editor->columns->maxWidth;
        $sfm = $parser->getSegmentFieldManager();
        $fieldList = $sfm->getFieldList();
        foreach ($fieldList as $fieldName => $fieldEntity) {
            if(!isset($this->fieldWidth[$fieldName])){
                continue;
            }
            $width2Save = false;
            $currentWidth = $fieldEntity->width;
            $newWidth = $this->fieldWidth[$fieldName];
            if($newWidth > $currentWidth){
                $width2Save = $newWidth;
            }
            if($width2Save && $width2Save > $maxWidth){
                $width2Save = $maxWidth;
            }
            if($width2Save){
                $fieldEntity->width = $width2Save;
                $fieldEntity->save();
            }
        }
    }
}