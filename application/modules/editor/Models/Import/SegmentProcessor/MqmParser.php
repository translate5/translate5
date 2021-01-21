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
class editor_Models_Import_SegmentProcessor_MqmParser extends editor_Models_Import_SegmentProcessor {
    const OPEN_TAG = true;
    const CLOSE_TAG = false;
    
    const ERR_TAG_INVALID = 1;
    const ERR_TAG_CLOSE = 2;
    const ERR_TAG_SAVE = 3;
    
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $sfm;
    
    /**
     * @var editor_Models_Qmsubsegments
     */
    protected $mqm;
    
    /**
     * issue type to ID map
     * @var array
     */
    protected $issues;
    
    protected $segmentMqmIds = array();
    
    protected $mqmEnabled = true;
    
    /**
     * list of occured MQM Import errors
     * @var array
     */
    protected $errors = array();
    
    /**
     * @param editor_Models_Task $task
     * @param editor_Models_SegmentFieldManager $sfm receive the already inited sfm
     */
    public function __construct(editor_Models_Task $task, editor_Models_SegmentFieldManager $sfm) {
        parent::__construct($task);
        $this->sfm = $sfm;
        $this->segment = ZfExtended_Factory::get('editor_Models_Segment');
        $this->segment->setTaskGuid($task->getTaskGuid());
        $config = $task->getConfig();
        $this->mqmEnabled = $config->runtimeOptions->editor->enableQmSubSegments;
        if(! $this->mqmEnabled) {
            return;
        }
        $this->issues = array_flip($this->task->getQmSubsegmentIssuesFlat());
        $this->mqm = ZfExtended_Factory::get('editor_Models_Qmsubsegments');
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_SegmentProcessor::process()
     */
    public function process(editor_Models_Import_FileParser $parser){
        $this->closeTags = array();
        $this->segmentMqmIds = array();
        $allFields = &$parser->getFieldContents();
        foreach($allFields as $field => $data) {
            if($this->mqmEnabled) {
                $allFields[$field] = $this->restoreMqmTags($data, $field);
            }
            else {
                $allFields[$field] = $this->removeMqmTags($data, $field);
            }
        }
        return false;
    }
    
    /**
     * restores the MQM Tags for the given data array (original => "FOO", originalMd5 => "123")
     * @param array $data
     * @param string $data
     */
    protected function restoreMqmTags(array $data, $field){
        $seg = $data['original'];
        
        //start tags
        $split = preg_split('#<mqm:startIssue([^>]+)/>#', $seg, null, PREG_SPLIT_DELIM_CAPTURE);
        $splitCnt = count($split);
        
        //no mqms found
        if(count($split) == 1) {
            return $data;
        }
        
        //operating on plain strings, the final Review processor makes finally the md5 hash
        $data['originalMd5'] = preg_replace('#<mqm:(startIssue|)([^>]+)/>#', '', $seg);
        
        $closeTags = array();
        for($i = 1; $i < $splitCnt; $i++) {
            $current = $i++; // save current index and jump over content to next attributes
            //we ignore agent here
            preg_match_all('/(type|severity|note|id)="([^"]*)"/i',$split[$current], $attributes);
            
            if(count($attributes) != 3) {
                $this->logError(self::ERR_TAG_INVALID, '<mqm:startIssue '.$split[$current].'/>');
                $split[$current] = ''; //delete given mqm tag
                continue;
            }
            $attributes = array_combine($attributes[1], $attributes[2]);
            $attributes['segmentfield'] = $field;
            if($this->createAndSaveInternalMqm($attributes) === false) {
                $this->logError(self::ERR_TAG_SAVE, '<mqm:startIssue '.$split[$current].'/>');
                $split[$current] = ''; //delete given mqm tag
                continue;
            }
            $this->segmentMqmIds[] = $this->mqm->getId();
            $closeTags[$attributes['id']] = $this->mqm->createTag(self::CLOSE_TAG);
            $split[$current] = $this->mqm->createTag(self::OPEN_TAG);
        }
        
        $seg = join('', $split);
        
        //end tags:
        $split = preg_split('#<mqm:endIssue([^>]+)/>#', $seg, null, PREG_SPLIT_DELIM_CAPTURE);
        $splitCnt = count($split);
        for($i = 1; $i < $splitCnt; $i++) {
            $current = $i++; // save current index and jump over content to next attributes
            //we ignore agent here
            preg_match('/id="([^"]*)"/i',$split[$current], $match);
            settype($match[1], 'integer');
            if(empty($match[1]) || empty($closeTags[$match[1]])) {
                $this->logError(self::ERR_TAG_CLOSE, '<mqm:endIssue '.$split[$current].'/>');
                $split[$current] = ''; //delete given mqm tag
                continue;
            }
            $split[$current] = $closeTags[$match[1]];
        }
        
        $data['original'] = join('', $split);
        return $data;
    }
    
    /**
     * removes the MQM Tags for the given data array (original => "FOO", originalMd5 => "123")
     * @param array $data
     * @param string $data
     */
    protected function removeMqmTags(array $data, $field){
        $seg = $data['original'];

        $data['original'] = preg_replace('#<mqm:(startIssue|endIssue)([^>]+)/>#', '', $seg);
        //operating on plain strings, the final Review processor makes finally the md5 hash
        $data['originalMd5'] = $data['original'];

        return $data;
    }

    /**
     * fills the internal mqm object with the parsed data and saves it to db
     * @param array $attributes
     * @return integer the db id of the saved mqm entry
     */
    protected function createAndSaveInternalMqm(array $attributes) {
        settype($attributes['type'], 'string');
        settype($attributes['severity'], 'string');
        settype($attributes['note'], 'string');
        settype($attributes['id'], 'integer');
        
        if(empty($this->issues[$attributes['type']]) || empty($attributes['severity']) || empty($attributes['id'])) {
            return false;
        }
        
        $data = array(
            'fieldedited' => $attributes['segmentfield'],
            'taskGuid' => $this->taskGuid,
            //'segmentId' => '', //can only be set by postprocesshandler
            'qmtype' => $this->issues[$attributes['type']],
            'severity' => $attributes['severity'],
            'comment' => $attributes['note'],
        );
        $this->mqm->init($data);
        return $this->mqm->save();
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_SegmentProcessor::postProcessHandler()
     */
    public function postProcessHandler(editor_Models_Import_FileParser $parser, $segmentId) {
        if(!empty($this->segmentMqmIds)) {
            $this->mqm->updateSegmentId($segmentId, $this->segmentMqmIds);
        }
    }
    
    /**
     * logs one error
     * @param string $error
     * @param string $context MQM segment where the error occured
     */
    protected function logError($error, $context) {
        switch ($error) {
            case self::ERR_TAG_INVALID:
                $this->errors[] = 'Invalid MQM Tag ignored on import: '.$context;
                return;
            case self::ERR_TAG_SAVE:
                $this->errors[] = 'MQM Tag cant be saved (wrong type or empty severity / id) and was ignored on import: '.$context;
                return;
            case self::ERR_TAG_CLOSE:
                $msg = 'Invalid closing MQM Tag ignored on import: '.$context;
                $msg .= ' either the tag was invalid or no open tag was found.';
                $this->errors[] = $msg;
                return;
            default:
                $this->errors[] = 'Unknown error while importing MQM Tag: '.$context;
                return;
        }
    }
    
    /**
     * sends a mail with all occured MQM Import errors
     */
    public function handleErrors() {
        if(empty($this->errors)) {
            return;
        }
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        /* @var $log ZfExtended_Log */
        array_unshift($this->errors, '');
        array_unshift($this->errors, 'Errors on importing MQM in task '.$this->taskGuid);
        $this->errors[] = '';
        $log->logError('Errors on importing MQM!', join("\n", $this->errors));
    }
}