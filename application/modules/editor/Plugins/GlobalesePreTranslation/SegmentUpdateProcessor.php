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
class editor_Plugins_GlobalesePreTranslation_SegmentUpdateProcessor extends editor_Models_Import_SegmentProcessor {
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $sfm;
    
    /**
     * @var editor_Models_Segment
     */
    protected $segment;
    
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTag;
    
    /**
     * @param editor_Models_Task $task
     * @param editor_Models_SegmentFieldManager $sfm receive the already inited sfm
     */
    public function __construct(editor_Models_Task $task, editor_Models_SegmentFieldManager $sfm) {
        parent::__construct($task);
        //relais is forced non editable (last parameter)
        $this->sfm = $sfm;
        $this->segment = ZfExtended_Factory::get('editor_Models_Segment');
        $this->segment->setTaskGuid($task->getTaskGuid());
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_SegmentProcessor::process()
     */
    public function process(editor_Models_Import_FileParser $parser){
        $data = $parser->getFieldContents();
        $source = $this->sfm->getFirstSourceName();
        $target = $this->sfm->getFirstTargetName();
        $mid = $parser->getMid(); //this is our segmentNrInTask prefixed with the fileid
        $attributes = $parser->getSegmentAttributes($mid);
        
        //the XLF import adds the segmentNrInTask to the real mid, since the real mid could not be unique in the XLF
        $mid = explode('_', $mid);
        $mid = reset($mid);
        
        if($attributes->targetState !== 'needs-review-translation') {
            return;
        }
        
        try {
            $this->segment->loadBySegmentNrInTask($mid, $this->segment->getTaskGuid());
        } catch(ZfExtended_Models_Entity_NotFoundException $e) {
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            $log->logError('Source segment to segmentNrInTask of reimported file not found.',  'Source segment to segmentNrInTask of reimported file not found. Reimported segment ignored. FileName: '.$this->fileName.' / segmentNrInTask: '.$parser->getMid());
            return false;
        }
        $ourSource = $this->normalizeSegmentData($this->segment->getFieldOriginal($source));
        $theirSource = $this->normalizeSegmentData($data[$source]["original"]);
        $updateContent = $data[$target]["original"];
        
        //FIXME target md5 hash an vorübersetzte Segmente anpassen wenn der task ein translation task TRANSLATE-885 ist
        // → diese Info auch in den Issue mitaufnehmen!
        
        //equal means here, that also the tags must be equal in content and position
        if($ourSource !== $theirSource){
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            $log->logError('Source of reimported file not identical with source of original file.',  'Source of reimported file is not identical with source of original file. Original target is left empty. FileName: '.$this->fileName.' / segmentNrInTask: '.$parser->getMid().' / Source content of original file (all tags stripped!): #'.$ourSource.'# / Source content of reimported file: #'.$theirSource.'#');
            return false;
        }
        
        try {
            $this->segment->setTarget($updateContent);
            $this->segment->setTargetMd5($data[$target]["originalMd5"]);
            $this->segment->setTargetEdit($updateContent);
            // set the AutoStatus to translated
            $this->segment->setPretrans(true);
            $this->segment->setAutoStateId(editor_Models_Segment_AutoStates::PRETRANSLATED);
            $this->segment->setMatchRateType('import;mt;globalese');
            $this->segment->save();
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $log->logError('Errors in adding relais segment: Source of original segment and source of relais segment are identical, but still original Segment not found in the database!',  'Segment Info:'.$e->getMessage());
        }
        return false;
    }

    /**
     * The given segment content is normalized for source comparsion
     * Currently all tags are removed (means ignored). To keep word boundaries the tags
     * are replaced with whitespace, multiple whitespaces are replaced to a single one
     * HTML Entities are decoded to enable comparsion of " and &quot;
     *
     * @param string $segmentContent
     * @return string
     */
    protected function normalizeSegmentData($segmentContent) {
        $segmentContent = $this->internalTag->replace($segmentContent, ' ');
        //trim removes leading / trailing whitespaces added by tag removing
        $segmentContent = trim(preg_replace('/\s{2,}/', ' ', $segmentContent));
        return html_entity_decode(strip_tags($segmentContent));
    }
}
