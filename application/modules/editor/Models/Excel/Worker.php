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

/**
 * Contains the Excel Reimport Worker
 */
class editor_Models_Excel_Worker extends ZfExtended_Worker_Abstract {
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        // @TODO: what needs to be check here?
        return true;
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $ class */
        $task->loadByTaskGuid($this->taskGuid);
        
        // do nothing if task is not in state "is Excel exported"
        if ($task->getState() != editor_Models_Excel_ExImport::TASK_STATE_ISEXCELEXPORTED) {
            return false;
        }
        
        // detect the filename from the workers parameter
        $tempParameter = $this->getModel()->getParameters();
        $filename = $tempParameter['filename'];
        
        $reimportExcel = ZfExtended_Factory::get('editor_Models_Import_Excel');
        /* @var $reimportExcel editor_Models_Import_Excel */
        if ($reimportExcel::run($task, $filename)) {
            // if everything is OK
            // unlock task and set state to 'open'
            $excelExImport = ZfExtended_Factory::get('editor_Models_Excel_ExImport');
            /* @var $excelExImport editor_Models_Excel_ExImport */
            $excelExImport::taskUnlock($task);
            
            // @TODO: if there where error in segments, show them as hint in frontend.
            if ($segmentError = $reimportExcel::getSegmentError()) {
                error_log(__FILE__.'::'.__LINE__.'; '.__CLASS__.' -> '.__FUNCTION__.'; Error on reimport in the following segments. Please check the following segment(s):.'."\n".$segmentError);
            }
        }
        return TRUE;
   }
}