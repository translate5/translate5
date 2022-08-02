<?php
/*
 START LICENSE AND COPYRIGHT

  This file is part of translate5

  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Segment\CharacterCount;

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Stellt Methoden zur Verarbeitung der vom Parser ermittelteten Segment Daten bereit
 * - speichert die ermittelten Segment Daten als Segmente in die DB
 */
class editor_Models_Import_SegmentProcessor_Review extends editor_Models_Import_SegmentProcessor {
    /**
     * @var Zend_Db_Adapter_Mysqli
     */
    protected $db = NULL;
    
    /**
     * @var Zend_Config
     */
    protected $taskConf;
    
    /**
     * @var editor_Models_Import_Configuration
     */
    protected $importConfig;

    /**
     * @var editor_Models_Segment_WordCount
     */
    protected $wordCount;

    /***
     * @var CharacterCount
     */
    protected $characterCount;

    /**
     * @var int
     */
    protected $segmentNrInTask = 0;
    
    /**
     * @param editor_Models_Task $task
     * @param editor_Models_Import_Configuration $config
     */
    public function __construct(editor_Models_Task $task, editor_Models_Import_Configuration $config){
        parent::__construct($task);
        $this->importConfig = $config;
        $this->db = Zend_Registry::get('db');
        $this->taskConf = $this->task->getConfig();

        //init word counter
        $langModel = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        $langModel->load($task->getSourceLang());

        $this->wordCount = ZfExtended_Factory::get('editor_Models_Segment_WordCount',[
            $langModel->getRfc5646()
        ]);
        $this->characterCount = ZfExtended_Factory::get('\MittagQI\Translate5\Segment\CharacterCount');
    }

    public function process(editor_Models_Import_FileParser $parser){
        $seg = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $seg editor_Models_Segment */
        //statische, Task spezifische Daten zum Segment
        $seg->setUserGuid($this->importConfig->userGuid);
        $seg->setUserName($this->importConfig->userName);

        $seg->setTaskGuid($this->taskGuid);
        
        //Segment Spezifische Daten
        $mid = $parser->getMid();
        $seg->setMid($mid);
        $seg->setFileId($this->fileId);
        
        $attributes = $parser->getSegmentAttributes($mid);
        $seg->setMatchRate($attributes->matchRate);
        $seg->setMatchRateType($attributes->matchRateType);
        $seg->setEditable($attributes->editable);
        $seg->setAutoStateId($attributes->autoStateId);
        $seg->setPretrans($attributes->isPreTranslated ? $seg::PRETRANS_INITIAL : $seg::PRETRANS_NOTDONE);
        
        $this->segmentNrInTask++;
        $seg->setSegmentNrInTask($this->segmentNrInTask);
        $sfm = $parser->getSegmentFieldManager();
        $seg->setFieldContents($sfm, $parser->getFieldContents());
        
        $this->events->trigger("process", $this, [
            'config' => $this->taskConf,
            'segment' => $seg, //editor_Models_Segment
            'segmentAttributes' => $attributes, //editor_Models_Import_FileParser_SegmentAttributes
            'importConfig' => $this->importConfig //editor_Models_Import_Configuration
        ]);
        
        $segmentId = $seg->save();
        $this->processSegmentMeta($seg, $attributes);
        return $segmentId;
    }
    
    /**
     * Processes additional segment attributes which are stored in the segment meta table
     * @param editor_Models_Segment $seg
     * @param editor_Models_Import_FileParser_SegmentAttributes $attributes
     */
    protected function processSegmentMeta(editor_Models_Segment $seg, editor_Models_Import_FileParser_SegmentAttributes $attributes) {
        $meta = $seg->meta();
        if(!empty($attributes->maxNumberOfLines) && !is_null($attributes->maxNumberOfLines)) {
            $meta->setMaxNumberOfLines($attributes->maxNumberOfLines);
        }
        if(!empty($attributes->maxWidth) && !is_null($attributes->maxWidth)) {
            $meta->setMaxWidth($attributes->maxWidth);
        }
        if(!empty($attributes->minWidth) && !is_null($attributes->minWidth)) {
            $meta->setMinWidth($attributes->minWidth);
        }
        if(!empty($attributes->sizeUnit) && !is_null($attributes->sizeUnit)) {
            $meta->setSizeUnit($attributes->sizeUnit);
        }
        if(!empty($attributes->font) && !is_null($attributes->font)) {
            $meta->setFont($attributes->font);
        }
        if(!empty($attributes->fontSize) && !is_null($attributes->fontSize)) {
            $meta->setFontSize($attributes->fontSize);
        }
        if(!empty($attributes->additionalMrkLength)) {
            $meta->setAdditionalMrkLength($attributes->additionalMrkLength);
        }
        if(!empty($attributes->additionalUnitLength)) {
            $meta->setAdditionalUnitLength($attributes->additionalUnitLength);
        }
        if(!empty($attributes->transunitId) && !is_null($attributes->transunitId)) {
            $meta->setTransunitId($attributes->transunitId);
        }
        else {
            //transunitId must not be null, so if no info given we use segmentNr to assume that just the single segment is in a transunit
            $meta->setTransunitId($seg->getSegmentNrInTask());
        }
        
        if(!empty($attributes->autopropagated)) {
            $meta->setAutopropagated($attributes->autopropagated);
        }
        
        if(!empty($attributes->locked)) {
            $meta->setLocked($attributes->locked);
        }

        //add custom meta fields
        if(!empty($attributes->customMetaAttributes)) {
            foreach($attributes->customMetaAttributes as $key => $value) {
                $meta->__call('set'.ucfirst($key), [$value]);
            }
        }

        $this->characterCount->setSegment($seg);
        $meta->setSourceCharacterCount($this->characterCount->getCharacterCount());
        
        $this->wordCount->setSegment($seg);
        $meta->setSourceWordCount($this->wordCount->getSourceCount());

        $meta->setSiblingData($seg);
        $meta->save();
    }
    
    /**
     * Überschriebener Post Parse Handler, erstellt in diesem Fall das Skeleton File
     * @override
     * @param editor_Models_Import_FileParser $parser
     */
    public function postParseHandler(editor_Models_Import_FileParser $parser) {
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $file->load($this->fileId);
        $file->saveSkeletonToDisk($parser->getSkeletonFile(), $this->task);
        
        $this->saveFieldWidth($parser);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_SegmentProcessor::postProcessHandler()
     */
    public function postProcessHandler(editor_Models_Import_FileParser $parser, $segmentId) {
        $this->calculateFieldWidth($parser);
    }
}