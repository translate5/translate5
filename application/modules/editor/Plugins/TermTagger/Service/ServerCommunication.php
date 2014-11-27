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
    
    // OPTIONAL FIELDS:
    // *****************************************************
    public $debug = 1;
    /*
    public $debug = 0;
    public $fuzzy = 1;
    public $stemmed = 1;
    public $targetStringMatch = 1;
    public $fuzzyPercent = 70;
    public $maxWordLengthSearch = 2;
    public $minFuzzyStartLength = 2;
    public $minFuzzyStringLength = 5;
    */
    
    public function addSegment ($id, $field = 'target', string $source , string $target) {
        $segment = new stdClass();
        $segment->id = $id;
        $segment->field = $field;
        $segment->source = $source;
        $segment->target = $target;
        
        $this->segments[] = $segment;
    }

}