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
 * Encapsulates the part of the import which looks for the files to be imported
 */
class editor_Models_Import_Worker_FileTree extends editor_Models_Import_Worker_Abstract {
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
        
        $this->registerShutdown();
        
        //also containing an instance of the initial dataprovider.
        // The Dataprovider can itself hook on to several import events 
        $parameters = $this->workerModel->getParameters();
        $importConfig = $parameters['config'];
        /* @var $importConfig editor_Models_Import_Configuration */
        
        //we should use __CLASS__ here, if not we loose bound handlers to base class in using subclasses
        $events = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        
        try {
            $metaDataImporter = ZfExtended_Factory::get('editor_Models_Import_MetaData', array($importConfig));
            /* @var $metaDataImporter editor_Models_Import_MetaData */
            $metaDataImporter->import($this->task);
//FIXME die bindings zu diesem Event!
            $events->trigger("beforeDirectoryParsing", $this,[
                    'importFolder'=>$importConfig->importFolder,
                    'task' => $this->task,
            ]);
            
            $filelistInstance = ZfExtended_Factory::get('editor_Models_Import_FileList', [
                    $importConfig,
                    $this->task
            ]);
            /* @var $filelistInstance editor_Models_Import_FileList */
            $filelist = $filelistInstance->processProofreadAndReferenceFiles($importConfig->getProofReadDir());
//FIXME die bindings zu diesem Event!
            $events->trigger("afterDirectoryParsing", $this,[
                    'task'=>$this->task,
                    'importFolder'=>$importConfig->importFolder,
                    'filelist'=>$filelist
            ]);
            
            $fileFilter = ZfExtended_Factory::get('editor_Models_File_FilterManager');
            /* @var $fileFilter editor_Models_File_FilterManager */
            $fileFilter->initImport($this->task, $importConfig);
            
            foreach ($filelist as $fileId => $path) {
//FIXME parameter anpassen!
                //$fileFilter->applyImportFilters($params[0], $params[2], $filelist);
            }
            
            $this->task->save();
            return true;
        } catch (Exception $e) {
            $task->setErroneous();
            throw $e; 
        }
    }
}