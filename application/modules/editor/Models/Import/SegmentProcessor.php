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
     * @var ZfExtended_EventManager
     */
    protected $events = false;
    
    /**
     * Konstruktor
     * @param editor_Models_Task $task
     */
     public function __construct(editor_Models_Task $task){
         $this->task = $task;
         $this->taskGuid = $task->getTaskGuid();
         $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(get_class($this)));
    }
    
    /**
     * Verarbeitet ein einzelnes Segment und gibt die ermittelte SegmentId zurück
     * @return integer|false MUST return the segmentId or false
     */
    abstract public function process(editor_Models_Import_FileParser $parser);
    
    /**
     * Setzt die Dateispezifischen Informationen zum Segment
     * @param int $fileId
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
     * @param int $segmentId
     */
    public function postProcessHandler(editor_Models_Import_FileParser $parser, $segmentId) {}
    
    /**
     *
     * @param editor_Models_Import_FileParser $parser
     * @param array $fields2Calculate 1-D array, key is name of field in file and value is mapped field name for db (may be same as key, or e. g. "relais" | default null; if null, all fields are calculated
     * @throws ZfExtended_Exception
     */
    protected function calculateFieldWidth(editor_Models_Import_FileParser $parser, array $fields2Calculate = null) {
        $fieldContents = $parser->getFieldContents();
        $config = Zend_Registry::get('config');
        $widthFactor = (float)$config->runtimeOptions->editor->columns->widthFactor;
        foreach ($fieldContents as $field => $contents) {
            //first source and first target are always on the default width!
            if($this->fieldHasDefaultWidth($field)) {
                continue;
            }
            
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
            $this->fieldWidth[$field] = max($this->fieldWidth[$field], $calculatedWidth);
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
            if($this->fieldHasDefaultWidth($fieldName)) {
                $this->fieldWidth[$fieldName] = $maxWidth;
            }
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
    
    /**
     * returns if the given field should use the max default width or not
     * @param string $field
     * @return boolean
     */
    protected function fieldHasDefaultWidth(string $field): bool
    {
        return match ($field) {
            editor_Models_SegmentField::TYPE_SOURCE, editor_Models_SegmentField::TYPE_TARGET, editor_Models_SegmentField::TYPE_RELAIS => true,
            default => false,
        };
    }
}
