<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTag;
    
    /**
     * @var Integer
     */
    protected $configuredCompareMode = 0;
    
    /**
     * Error Container
     * @var array
     */
    protected $errors = [];
    
    /**
     * @var int
     */
    protected $segmentNrInTask = 0;
    
    /**
     * Definitions of the different relais compare mode flags
     * @var integer
     */
    const MODE_IGNORE_TAGS = 1;
    const MODE_NORMALIZE_ENTITIES = 2;
    
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
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        
        // preset configured
        $config = Zend_Registry::get('config');
        $modes = $config->runtimeOptions->import->relaisCompareMode;
        foreach($modes as $mode) {
            $this->configuredCompareMode += constant('self::MODE_'.$mode);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_SegmentProcessor::process()
     */
    public function process(editor_Models_Import_FileParser $parser){
        //the segment internal taskGuid is reset to null if loadByFileidMid fails,
        // so we just set it on each process call
        $this->segment->init(['taskGuid' => $this->taskGuid]);
        $data = $parser->getFieldContents();
        $source = $this->sfm->getFirstSourceName();
        $target = $this->sfm->getFirstTargetName();
        $mid = $parser->getMid();
        $loadBySegmentNr = false;
        
        $this->segmentNrInTask++;
        
        try {
            //try loading via fileId and Mid
            $this->segment->loadByFileidMid($this->fileId, $mid);
        //} catch(Zend_Db_Statement_Exception $e) {
            //xdebug_break();
        } catch(ZfExtended_Models_Entity_NotFoundException $e) {
            //if above was not successful, load via segmentNrInTask
            $loadBySegmentNr = $this->loadSegmentByNrInTask($parser->getMid());
            if(!$loadBySegmentNr) {
                //if no segment was found via segmentNr, we ignore it
                return false;
            }
        }
        $contentIsEqual = $this->isContentEqual($this->segment->getFieldOriginal($source), $data[$source]["original"]);
        
        //if content is not equal, but was loaded with mid, try to load with segment nr and compare again
        if(!$contentIsEqual && !$loadBySegmentNr){
            if(!$this->loadSegmentByNrInTask($parser->getMid())) {
                return false;
            }
            $contentIsEqual = $this->isContentEqual($this->segment->getFieldOriginal($source), $data[$source]["original"]);
        }
        
        //if source and relais content is finally not equal, we log that and ignore the segment
        if(!$contentIsEqual){
            $this->errors['source-different'][] = 'mid: '.$parser->getMid().' / Source content of translated file: '.$this->segment->getFieldOriginal($source).' / Source content of relais file: '.$data[$source]["original"];
            return false;
        }
        
        try {
            $this->segment->addFieldContent($this->relaisField, $this->fileId, $mid, $data[$target]);
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            $this->errors['source-missing'][] = $e->getMessage();
        }
        return false;
    }
    
    /**
     * returns true if content is equal
     * equal means here, that also the tags must be equal in content and position
     * @param string $source
     * @param string $relais
     * @return boolean
     */
    protected function isContentEqual(string $source, string $relais) : bool {
        $source = $this->normalizeSegmentData($source);
        $relais = $this->normalizeSegmentData($relais);
        return $source === $relais;
    }
    
    /**
     * Tries to load the segment to current relais content via segmentNrInTask
     * returns true if found a segment, false if not. If false this is logged.
     * @param string $mid
     * @return boolean
     */
    protected function loadSegmentByNrInTask(string $mid): bool {
        try {
            $this->segment->loadBySegmentNrInTask($this->segmentNrInTask, $this->taskGuid);
            return true;
        } catch(ZfExtended_Models_Entity_NotFoundException $e) {
            $this->errors['source-not-found'][] = $mid;
            return false;
        }
    }

    /**
     * The given segment content is normalized for source / relais source comparsion
     * Currently all tags are removed (means ignored). To keep word boundaries the tags
     * are replaced with whitespace, multiple whitespaces are replaced to a single one
     * HTML Entities are decoded to enable comparsion of " and &quot;
     *
     * @param string $segmentContent
     * @return string
     */
    protected function normalizeSegmentData($segmentContent) {
        if($this->configuredCompareMode & self::MODE_IGNORE_TAGS) {
            $segmentContent = $this->internalTag->replace($segmentContent, ' ');
            //trim removes leading / trailing whitespaces added by tag removing
            $segmentContent = trim(preg_replace('/\s{2,}/', ' ', $segmentContent));
        }
        if($this->configuredCompareMode & self::MODE_NORMALIZE_ENTITIES){
            return html_entity_decode($segmentContent);
        }
        return $segmentContent;
    }
      
    /**
     * Überschriebener Post Parse Handler, erstellt in diesem Fall das Skeleton File
     * @override
     * @param editor_Models_Import_FileParser $parser
     */
    public function postParseHandler(editor_Models_Import_FileParser $parser) {
        $this->saveFieldWidth($parser);
        
        if(empty($this->errors)) {
            return;
        }
        $logger = Zend_Registry::get('logger');
        /* @var $logger ZfExtended_Logger */
        $logData = [
            'task' => $this->task,
            'fileName' => $this->fileName,
        ];
        if(!empty($this->errors['source-not-found'])){
            $msg = 'Errors in processing relais files: '."\n";
            $msg .= 'The following MIDs are present in the relais file "{fileName}" but could not be found in the source file, the relais segment(s) was/were ignored.﻿ See Details.';
            $logger->warn('E1020', $msg, array_merge($logData, ['midList' => join(', ', $this->errors['source-not-found'])]));
        }
        if(!empty($this->errors['source-different'])){
            $msg = 'Errors in processing relais files: '."\n";
            $msg .= 'Source-content of relais file "{fileName}" is not identical with source of translated file. Relais target is left empty. See Details.';
            $logger->warn('E1021', $msg, array_merge($logData, ['segments' => join(",\n ", $this->errors['source-different'])]));
        }
        if(!empty($this->errors['source-missing'])){
            $msg = 'Errors in adding relais segment: '."\n";
            $msg .= 'Source-content of relais file "{fileName}" is identical with source of translated file, but still original segment not found in the database.﻿ See Details.';
            $logger->warn('E1022', $msg, array_merge($logData, ['segments' => join(",\n ", $this->errors['source-missing'])]));
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_SegmentProcessor::postProcessHandler()
     */
    public function postProcessHandler(editor_Models_Import_FileParser $parser, $segmentId) {
        $this->calculateFieldWidth($parser,array($this->sfm->getFirstTargetName() => 'relais'));
    }
}