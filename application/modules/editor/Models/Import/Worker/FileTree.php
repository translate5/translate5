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
 * Encapsulates the part of the import which looks for the files to be imported
 */
class editor_Models_Import_Worker_FileTree extends editor_Models_Task_AbstractWorker {
    
    /**
     * @var ZfExtended_EventManager
     */
    protected $events;
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        if(empty($parameters['config']) || !$parameters['config'] instanceof editor_Models_Import_Configuration) {
            throw new ZfExtended_Exception('missing or wrong parameter config, must be if instance editor_Models_Import_Configuration');
        }
        return true;
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $task = $this->task;
        if ($task->getState() != $task::STATE_IMPORT) {
            return false;
        }
        
        $this->behaviour->registerShutdown();
        
        //also containing an instance of the initial dataprovider.
        // The Dataprovider can itself hook on to several import events
        $parameters = $this->workerModel->getParameters();
        $importConfig = $parameters['config'];
        /* @var $importConfig editor_Models_Import_Configuration */
        
        //we should use __CLASS__ here, if not we loose bound handlers to base class in using subclasses
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        
        try {
            $metaDataImporter = ZfExtended_Factory::get('editor_Models_Import_MetaData', array($importConfig));
            /* @var $metaDataImporter editor_Models_Import_MetaData */
            $metaDataImporter->import($this->task);
            
            $this->triggerBefore($importConfig);
            
            $filelistInstance = ZfExtended_Factory::get('editor_Models_Import_FileList', [
                $importConfig,
                $this->task
            ]);
            /* @var $filelistInstance editor_Models_Import_FileList */
            $filelist = $filelistInstance->processReviewedFiles();
            $filelistInstance->processReferenceFiles();
            
            $this->triggerAfter($importConfig, $filelist);
            $this->task->save(); //needed to save modifications done via the meta data importers
            return true;
        } catch (Exception $e) {
            $task->setErroneous();
            $this->behaviour->defuncRemainingOfGroup();
            throw $e;
        }
    }
    
    /**
     * trigger beforeDirectoryParsing event
     * @param editor_Models_Import_Configuration $importConfig
     */
    protected function triggerBefore(editor_Models_Import_Configuration $importConfig) {
        $this->events->trigger("beforeDirectoryParsing", $this, [
                'importConfig' => $importConfig,
                'importFolder' => $importConfig->importFolder,
                'task' => $this->task,
                'workerParentId' => $this->workerModel->getParentId(),
        ]);
    }
    
    /**
     * trigger beforeDirectoryParsing event
     * @param editor_Models_Import_Configuration $importConfig
     * @param array $filelist
     */
    protected function triggerAfter(editor_Models_Import_Configuration $importConfig, array $filelist) {
        $this->events->trigger("afterDirectoryParsing", $this, [
                'importConfig' => $importConfig,
                'task' => $this->task,
                'importFolder' => $importConfig->importFolder,
                'filelist' => $filelist,
                'workerParentId' => $this->workerModel->getParentId(),
        ]);
    }
}