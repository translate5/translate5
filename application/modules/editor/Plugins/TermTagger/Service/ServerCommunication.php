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
/**
 * Service-ServerCommunication Class of Plugin "TermTagger"
 */
class editor_Plugins_TermTagger_Service_ServerCommunication {
    
    // REQUIRED FIELDS:
    // *****************************************************
    public $tbxFile = NULL;
    public $sourceLang = NULL;
    public $targetLang = NULL;
    public $segments = NULL;
    /*
    {
        "id": "123",
        "field": "target",
        "source": "SOURCE TEXT",
        "target": "TARGET TEXT"
    },
    { ... MORE SEGMENTS ... }
    ],
    */
    
    /**
     * If $task is sumbitted, ServerCommunication is initialized with all required fields,
     * so after that all there has to be done is addSegment()
     * 
     * @param editor_Models_Task $task
     */
    public function __construct(editor_Models_Task $task = NULL) {
        $config = Zend_Registry::get('config');
        $taggerConfig = $config->runtimeOptions->termTagger;
        $this->debug = (integer)$taggerConfig->debug;
        $this->fuzzy = (integer)$taggerConfig->fuzzy;
        $this->stemmed = (integer)$taggerConfig->stemmed;
        $this->fuzzyPercent = (integer)$taggerConfig->fuzzyPercent;
        $this->maxWordLengthSearch = (integer)$taggerConfig->maxWordLengthSearch;
        $this->minFuzzyStartLength = (integer)$taggerConfig->minFuzzyStartLength;
        $this->minFuzzyStringLength = (integer)$taggerConfig->minFuzzyStringLength;
        
        $this->targetStringMatch = 0;
        foreach ($taggerConfig->targetStringMatch as  $targetLangOnlyStringMatch) {
            if($this->targetLang === $targetLangOnlyStringMatch){
                $this->targetStringMatch = 1;
            }
        }
        
        if (!$task) {
            return;
        }
        
        $this->tbxFile = $task->meta()->getTbxHash();
        
        $langModel = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        $langModel->load($task->getSourceLang());
        $this->sourceLang = $langModel->getRfc5646();
        $langModel->load($task->getTargetLang());
        $this->targetLang = $langModel->getRfc5646();
    } 
    
    /**
     * Adds a segment to the server-communication.
     * 
     * @param string $id
     * @param string $field
     * @param string $source
     * @param string $target
     */
    public function addSegment ($id, $field = 'target',  $source, $target) {
        $segment = new stdClass();
        $segment->id = (string) $id;
        $segment->field = $field;
        $segment->source = $source;
        $segment->target = $target;
        
        $this->segments[] = $segment;
    }
}