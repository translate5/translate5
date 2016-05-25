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
class editor_Plugins_TmMtIntegration_Models_Worker extends ZfExtended_Worker_Abstract {

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        error_log(print_r($parameters,1));
        if(empty($parameters['query'])) {
            error_log('Missing Parameter "query" in '.__CLASS__);
            return false;
        }
        if(empty($parameters['tmmtId'])) {
            error_log('Missing Parameter "tmmtId" in '.__CLASS__);
            return false;
        }
        return true;
    }
    
    /**
     * (non-PHPdoc)
     *
     * @see ZfExtended_Worker_Abstract::run()
     */
    public function run() {
        return parent::run();
    }

    /**
     * (non-PHPdoc)
     *
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->taskGuid);
        
        $manager = ZfExtended_Factory::get('editor_Plugins_TmMtIntegration_Services_Manager');
        /* @var $manager editor_Plugins_TmMtIntegration_Services_Manager */
        $manager->getResourceById($serviceType, $id);
        
        error_log("WORK");
        return true;
        
        if (empty($this->serverCommunication)) {
            return false;
        }
        
        $termTagger = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service');
        /* @var $termTagger editor_Plugins_TermTagger_Service */
        
        try {
            $this->checkTermTaggerTbx($this->workerModel->getSlot(), $this->serverCommunication->tbxFile);
            $result = $termTagger->tagterms($this->workerModel->getSlot(), $this->serverCommunication);
        }
        catch(editor_Plugins_TermTagger_Exception_Abstract $exception) {
            $result = '';
            $url = $this->workerModel->getSlot();
            $exception->setMessage('TermTagger '.$url.' (task '.$this->taskGuid.') could not tag segments! Reason: '."\n".$exception->getMessage(), false);
            $this->log->logException($exception);
        }
        
        // on error return false and store original untagged data
        if (empty($result)) {
            return false;
        }
        $this->result = $result->segments;
        $this->result = $this->markTransFound($this->result);
        return true;
    }
    
}