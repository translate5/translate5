<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Contains the Xliff2Export Worker which exports the whole task as native Xliff2 regardless of the import format
 */
class editor_Models_Export_Xliff2Worker extends ZfExtended_Worker_Abstract {
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        if(isset($parameters['exportToFolder']) && (!is_dir($parameters['exportToFolder']) || !is_writable($parameters['exportToFolder']))){
            $this->log->error('E1398', 'Export folder not found or not write able: {folder}', ['folder' => $parameters['exportToFolder']]);
            return false;
        }
        return true;
    }
    
    /**
     * inits a export to the default directory
     * @param string $taskGuid
     * @return string the folder which receives the exported data
     */
    public function initExport(editor_Models_Task $task) {
        //if no explicit exportToFolder is given, we use the default (taskGuid)
        $exportFolder = $task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR.trim($task->getTaskGuid(),'{}').'-xlf2';
        $this->prepareDirectory($exportFolder);
        
        $this->init($task->getTaskGuid(), [
            'exportToFolder' => $exportFolder
        ]);
        return $exportFolder;
    }
    
    protected function prepareDirectory($directory) {
        //we create it if it does not exist
        if(is_dir($directory)) {
            $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
                'Recursivedircleaner'
                );
            $recursivedircleaner->delete($directory);
        }
        
        if(!file_exists($directory) && !@mkdir($directory, 0777, true)){
            throw new Zend_Exception(sprintf('Temporary Export Folder could not be created! Task: %s Path: %s', $this->taskGuid, $directory));
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        //also containing an instance of the initial dataprovider.
        // The Dataprovider can itself hook on to several import events
        $parameters = $this->workerModel->getParameters();
        
        if(!$this->validateParameters($parameters)) {
            //no separate logging here, missing diff is not possible,
            // directory problems are loggeed above
            return false;
        }
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->taskGuid);
        
        $xliffConf = [
            editor_Models_Converter_SegmentsToXliff2::CONFIG_ADD_TERMINOLOGY=>true,
            editor_Models_Converter_SegmentsToXliff2::CONFIG_INCLUDE_DIFF=>true,
            editor_Models_Converter_SegmentsToXliff2::CONFIG_ADD_QM=>true,
        ];
        $xliffConverter = ZfExtended_Factory::get('editor_Models_Converter_SegmentsToXliff2', [$xliffConf, $task->getWorkflowStepName()]);
        /* @var $xliffConverter editor_Models_Converter_SegmentsToXliff2 */
        
        $this->prepareDirectory($parameters['exportToFolder']);
        $filename = $parameters['exportToFolder'].'/export-'.date('Y-m-d-H-i-s').'.xliff';
        file_put_contents($filename, $xliffConverter->export($task));
        
        //run xliff2 validator directly on export and save output to error_log:
        //$out = [];
        //exec('/home/tlauria/bin/okapi-xliff-toolkit/lynx.sh '.$filename, $out);
        //error_log('Validate XLIFF2 Export of Task '.$this->taskGuid.' '.join("\n", $out));
        
        return true;
    }
}
