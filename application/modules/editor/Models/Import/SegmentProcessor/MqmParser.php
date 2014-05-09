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
class editor_Models_Import_SegmentProcessor_MqmParser extends editor_Models_Import_SegmentProcessor {
    const OPEN_TAG = true;
    const CLOSE_TAG = false;
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
     * @param editor_Models_Task $task
     * @param editor_Models_SegmentFieldManager $sfm receive the already inited sfm
     */
    public function __construct(editor_Models_Task $task, editor_Models_SegmentFieldManager $sfm) {
        parent::__construct($task);
        $this->sfm = $sfm;
        $this->segment = ZfExtended_Factory::get('editor_Models_Segment');
        $this->segment->setTaskGuid($task->getTaskGuid());
        $config = Zend_Registry::get('config');
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
        
        $data['originalMd5'] = md5(preg_replace('#<mqm:(startIssue|)([^>]+)/>#', '', $seg));
        
        $closeTags = array();
        for($i = 1; $i < $splitCnt; $i++) {
            $current = $i++; // save current index and jump over content to next attributes
            //we ignore agent here
            preg_match_all('/(type|severity|note|id)="([^"]*)"/i',$split[$current], $attributes);
            
            if(count($attributes) != 3) {
                error_log('Invalid MQM Tag ignored on import of task '.$this->taskGuid.': '.'<mqm:startIssue '.$split[$current].'/>');
                $split[$current] = ''; //delete given mqm tag
                continue;
            }
            $attributes = array_combine($attributes[1], $attributes[2]);
            $attributes['segmentfield'] = $field;
            if($this->createAndSaveInternalMqm($attributes) === false) {
                error_log('MQM Tag cant be saved and was ignored on import of task '.$this->taskGuid.': '.'<mqm:startIssue '.$split[$current].'/>');
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
                error_log('Invalid closing MQM Tag ignored on import of task '.$this->taskGuid.': '.'<mqm:endIssue '.$split[$current].'/> either the tag was invalid or no open tag was found.');
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
        $data['originalMd5'] = md5($data['original']);

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
}