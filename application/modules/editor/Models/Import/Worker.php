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
 * Contains the Import Worker (the scheduling parts)
 * The import process itself is encapsulated in editor_Models_Import_Worker_Import
 */
class editor_Models_Import_Worker extends editor_Models_Import_Abstract {
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        if(empty($parameters['config']) || !$parameters['config'] instanceof editor_Models_Import_Configuration) {
            throw new ZfExtended_Exception('missing or wrong parameter config, must be if instance editor_Models_Import_Configuration');
        }
        
        if(empty($parameters['dataProvider']) || !$parameters['dataProvider'] instanceof editor_Models_Import_DataProvider_Abstract) {
            throw new ZfExtended_Exception('missing or wrong parameter dataProvider, must be if instance editor_Models_Import_DataProvider_Abstract');
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
        
        //also containing an instance of the initial dataprovider.
        // The Dataprovider can itself hook on to several import events 
        $parameters = $this->workerModel->getParameters();
        
        try {
            $import = ZfExtended_Factory::get('editor_Models_Import_Worker_Import');
            /* @var $import editor_Models_Import_Worker_Import */
            $import->import($task, $parameters['config']);
            return true;
        } catch (Exception $e) {
            $task->setErroneous();
            //what can happen internally: 
                //a exception thrown by us or the framework
                //a notice / warning / error (which should be all converted to a exception)
                //a error for check run
                //a fatal
            //logging of import errors → error message refactoring
            //visually we have 3 types of error
            //  task add window remains open
            //  task remains as import → WHEN THIS?
            //  task is getting deleted after an error  → WHEN THIS?
            throw $e; //FIXME throw the original exception or a new import exception with the original referenced? 
        }
    }
}