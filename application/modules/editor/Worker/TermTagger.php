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
 * editor_Worker_TermTagger Class
 */
class editor_Worker_Termtagger extends ZfExtended_Worker_Abstract {
    
    protected $maxLifetime = '2 HOUR';
    
    protected $TESTDATA = false;
    
    public function init($taskGuid, $data = array()) {
        
        // seperate data from datalist which are needed while working queued-worker
        // all informations which are only relevant in 'normal processing (not queued)'
        // must not be saved in DB worker-table
        // aka not send to parent::init as second parameter.
        $dataToSave = array();
        foreach ($data['segmentData'] as $item) {
            $dataToSave[] = $item['id'];
        }
         
        parent::init($taskGuid, array('segmentIds' => $dataToSave));
        parent::init($taskGuid, $data);
        
        $this->TESTDATA = $data['segmentData'];
    }
    
    public function queue() {
        throw new BadMethodCallException('Du kommst hier nicht rein '.__CLASS__.'->'.__FUNCTION__);
    }
    
    public function getResult() {
        $tempReturn = $this->TESTDATA;
        $tempReturn[0]['targetEdit'] = 'PSEUDO-TERMTAGGED: '.$tempReturn[0]['targetEdit'];
        return $tempReturn;
    }
    
    public function run($taskGuid) {
        //$this->setTaskGuid($taskGuid);
        parent::run($taskGuid);
        //$this->cleanGarbage(); // Aufruf nur für Testzwecke !!!
    }
}