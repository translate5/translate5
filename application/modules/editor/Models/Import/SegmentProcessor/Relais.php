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
        $this->segment->setTaskGuid($task->getTaskGuid());
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
        $data = $parser->getFieldContents();
        $source = $this->sfm->getFirstSourceName();
        $target = $this->sfm->getFirstTargetName();
        $mid = $parser->getMid();
        
        try {
            $this->segment->loadByFileidMid($this->fileId, $mid);
        } catch(ZfExtended_Models_Entity_NotFoundException $e) {
            $this->errors[] = 'Source segment to MID of relais file not found. Relais segment ignored. FileName: '.$this->fileName.' / mid: '.$parser->getMid();
            return false;
        }
        $sourceContent = $this->normalizeSegmentData($this->segment->getFieldOriginal($source));
        $relaisContent = $this->normalizeSegmentData($data[$source]["original"]);
        
        //equal means here, that also the tags must be equal in content and position
        if($sourceContent !== $relaisContent){
            $this->errors[] = 'Source of relais file is not identical with source of translated file. Relais target is left empty. FileName: '.$this->fileName.' / mid: '.$parser->getMid().' / Source content of translated file: '.$sourceContent.' / Source content of relais file: '.$data[$source]["original"];
            return false;
        }
        
        try {
            $this->segment->addFieldContent($this->relaisField, $this->fileId, $mid, $data[$target]);
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            $this->errors[] = 'Errors in adding relais segment: Source of original segment and source of relais segment are identical, but still original Segment not found in the database! Segment Info:'.$e->getMessage();
        }
        return false;
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
        
        if(!empty($this->errors)) {
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $log->logError('Errors in processing relais files', join("\n", $this->errors));
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