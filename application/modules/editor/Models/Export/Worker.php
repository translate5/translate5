<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Contains the Export Worker (the scheduling parts)
 * The export process itself is encapsulated in editor_Models_Export
 * The worker nows three parameters:
 *  "diff" boolean en- or disables the export differ
 *  "method" string is either "exportToZip" [default] or "exportToFolder"
 *  "exportToFolder" string valid writable path to the export folder, only needed for method "exportToFolder"
 */
class editor_Models_Export_Worker extends ZfExtended_Worker_Abstract {
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        error_log(print_r($parameters,1));
        if(!isset($parameters['diff']) || !is_bool($parameters['diff'])) {
            return false;
        }
        // $parameters['zipFile'] has not to be checked since set internally in this class
        if(isset($parameters['exportToFolder']) && (!is_dir($parameters['exportToFolder']) || !is_writable($parameters['exportToFolder']))){
            $this->log->logError('Export folder not found or not write able: '.$parameters['exportToFolder']);
            return false;
        }
        return true;
    }
    
    /**
     * inits a export to a ZIP file (path to destination export.zip is returned by this method)
     * @param string $taskGuid
     * @param bool $diff
     * @return string
     */
    public function initZipExport(editor_Models_Task $task, bool $diff) {
        
        $parameter = [
                'diff' => $diff,
                'zipFile' => tempnam($task->getAbsoluteTaskDataPath(), 'taskExport_')
        ];
        $this->init($task->getTaskGuid(), $parameter);
        return $parameter['zipFile'];
    }
    
    /**
     * inits a export to a given directory
     * @param string $taskGuid
     * @param bool $diff
     * @param string $exportFolder
     * @return string
     */
    public function initFolderExport(editor_Models_Task $task, bool $diff, string $exportFolder) {
        $parameter = ['diff' => $diff, 'exportToFolder' => $exportFolder];
        $this->init($task->getTaskGuid(), $parameter);
        return $exportFolder; //just to be compatible with initZipExport
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
        
        $export = ZfExtended_Factory::get('editor_Models_Export');
        /* @var $export editor_Models_Export */
        $export->setTaskToExport($task, $parameters['diff']);
        if(isset($parameters['exportToFolder'])) {
            $export->exportToFolder($parameters['exportToFolder']);
        }
        else {
            $export->exportToZip($parameters['zipFile']);
        }
        return true;
    }
}