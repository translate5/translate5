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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Starts an import by gathering all needed data, check and store it, and start an Import Worker
 */
trait editor_Models_Import_HandleExceptionTrait {
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * 
     * @var editor_Models_Import_Configuration
     */
    protected $importConfig;
    
    /**
     * Handler of Import Exceptions
     * We delete the task from database, the import directory remains on the disk,
     * if runtimeOptions.import.keepFilesOnError is set to true (for developing mainly)
     * @param Exception $e
     * @param editor_Models_Import_DataProvider_Abstract $dataProvider
     */
    public function handleImportException(Exception $e, editor_Models_Import_DataProvider_Abstract $dataProvider) {
        $config = Zend_Registry::get('config');
        //delete task but keep taskfolder if configured, on checkRun never keep files
        $deleteFiles = $this->importConfig->isCheckRun || !$config->runtimeOptions->import->keepFilesOnError;
        
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        /* @var $log ZfExtended_Log */
        $msg = "\nImport Exception: ".$e."\n";
        if(!$deleteFiles) {
            $msg .= "\n".'The imported data is kept in '.$config->runtimeOptions->dir->taskData;
        }
        $log->logError('Exception while importing task '.$this->task->getTaskGuid(), $msg);
        
        $remover = ZfExtended_Factory::get('editor_Models_Task_Remover', array($this->task));
        /* @var $remover editor_Models_Task_Remover */
        $remover->removeForced($deleteFiles);
        if($deleteFiles) {
            $dataProvider->handleImportException($e);
        }
    }
}
